<?php
namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\Term;
use App\Models\AssessmentType;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Score;
use App\Models\ScoreImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScoreImportController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function index()
    {
        $classArms      = ClassArm::with('classLevel')->get();
        $terms          = Term::with('session')->latest()->get();
        $assessmentTypes= AssessmentType::orderBy('name')->get();
        $recentImports  = ScoreImport::latest()->limit(10)->get();
        return view('scores.import', compact('classArms', 'terms', 'assessmentTypes', 'recentImports'));
    }

    public function download(Request $request)
    {
        $request->validate([
            'class_arm_id'      => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'term_id'           => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'assessment_type_id'=> ['required', Rule::exists('assessment_types', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $classArm = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $term     = Term::findOrFail($request->term_id);
        $at       = AssessmentType::findOrFail($request->assessment_type_id);

        $students = Student::where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)->orderBy('last_name')->get();

        $subjects = Subject::whereHas('classArmSubjects', fn($q) =>
            $q->where('class_arm_id', $classArm->id)
        )->orderBy('name')->get();

        $filename = "score_template_{$classArm->classLevel->name}_{$classArm->name}_{$term->name}_{$at->name}.csv";
        $callback = function () use ($students, $subjects, $classArm, $term, $at) {
            $file = fopen('php://output', 'w');
            // Header metadata
            fputcsv($file, ['# class_arm_id', $classArm->id, '# term_id', $term->id, '# assessment_type_id', $at->id]);
            fputcsv($file, ['# DO NOT edit the header rows. Only fill in scores in the score columns.']);
            fputcsv($file, ['student_id', 'admission_number', 'student_name', ...$subjects->pluck('name')->toArray()]);

            // Existing scores
            $existing = Score::where('term_id', $term->id)
                ->where('assessment_type_id', $at->id)
                ->whereIn('student_id', $students->pluck('id'))
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->get()->groupBy('student_id');

            foreach ($students as $s) {
                $row = [$s->id, $s->admission_number, $s->full_name];
                foreach ($subjects as $sub) {
                    $sc = $existing[$s->id]?->firstWhere('subject_id', $sub->id);
                    $row[] = $sc ? $sc->score : '';
                }
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file     = $request->file('import_file');
        $handle   = fopen($file->path(), 'r');

        // Read metadata row
        $meta1    = fgetcsv($handle);
        fgetcsv($handle); // skip instruction row
        $header   = fgetcsv($handle); // student_id, admission_number, student_name, subject1, subject2...

        $classArmId      = $meta1[1] ?? null;
        $termId          = $meta1[3] ?? null;
        $assessmentTypeId= $meta1[5] ?? null;

        if (!$classArmId || !$termId || !$assessmentTypeId) {
            return back()->withErrors(['import_file' => 'Invalid template format. Please download a fresh template.']);
        }

        $classArm = ClassArm::find($classArmId);
        $term = Term::find($termId);
        $assessmentType = AssessmentType::where('term_id', $termId)->find($assessmentTypeId);

        if (!$classArm || !$term || !$assessmentType) {
            return back()->withErrors(['import_file' => 'The template belongs to another school or an unavailable term.']);
        }

        $subjectNames = array_slice($header, 3);
        $subjectMap   = Subject::whereIn('name', $subjectNames)
            ->whereHas('classArmSubjects', fn ($query) => $query
                ->where('class_arm_id', $classArm->id))
            ->pluck('id', 'name');
        $allowedStudentIds = Student::where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $imported = 0; $failed = 0; $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;
            $studentId = (int)$row[0];
            if (!in_array($studentId, $allowedStudentIds, true)) {
                $failed++;
                $errors[] = "Student does not belong to this class: $studentId";
                continue;
            }
            $scores    = array_slice($row, 3);

            foreach ($subjectNames as $idx => $subjectName) {
                $score = trim($scores[$idx] ?? '');
                if ($score === '' || !is_numeric($score)) continue;
                $subjectId = $subjectMap[$subjectName] ?? null;
                if (!$subjectId) { $failed++; $errors[] = "Unknown subject: $subjectName"; continue; }

                try {
                    Score::updateOrCreate(
                        ['student_id' => $studentId, 'subject_id' => $subjectId,
                         'term_id' => $termId, 'assessment_type_id' => $assessmentTypeId],
                        ['score' => (float)$score]
                    );
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row error: ".$e->getMessage();
                }
            }
        }
        fclose($handle);

        ScoreImport::create([
            'filename'           => $file->getClientOriginalName(),
            'class_arm_id'       => $classArmId,
            'term_id'            => $termId,
            'rows_imported'      => $imported,
            'rows_failed'        => $failed,
            'errors'             => implode("\n", array_slice($errors, 0, 20)),
            'status'             => $failed > 0 ? 'done' : 'done',
            'imported_by'        => auth()->id(),
        ]);

        $msg = "Import complete: {$imported} scores saved";
        if ($failed) $msg .= ", {$failed} skipped";
        return back()->with('success', $msg);
    }
}
