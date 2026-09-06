<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\ReportCardPublication;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Services\Mobile\MobileIdempotencyService;
use App\Services\Scores\ObjectiveScoreResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScoreController extends Controller
{
    public function __construct(private readonly MobileIdempotencyService $idempotency) {}

    public function teaching(Request $request)
    {
        $user = $request->user();
        $this->assertCanEnterScores($user);
        $session = AcademicSession::current()->first();
        $term = Term::current()->with('session')->first();

        $assignments = ClassArmSubject::with(['classArm.classLevel', 'subject'])
            ->when($session, fn ($query) => $query->where('session_id', $session->id))
            ->when(! $this->canEnterAll($user), fn ($query) => $query->where('teacher_id', $user->id))
            ->get()
            ->filter(fn (ClassArmSubject $assignment) => $assignment->classArm && $assignment->subject)
            ->unique(fn (ClassArmSubject $assignment) => $assignment->class_arm_id.':'.$assignment->subject_id)
            ->map(fn (ClassArmSubject $assignment) => [
                'class_arm_id' => $assignment->class_arm_id,
                'class_name' => $this->className($assignment->classArm),
                'subject_id' => $assignment->subject_id,
                'subject_name' => $assignment->subject->name,
            ])->values();

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'session' => $session?->only(['id', 'name']),
            'term' => $term ? ['id' => $term->id, 'name' => $term->name, 'session' => $term->session?->name] : null,
            'assignments' => $assignments,
        ]);
    }

    public function sheet(Request $request, ObjectiveScoreResolver $resolver)
    {
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'term_id' => ['nullable', 'integer'],
        ]);
        $user = $request->user();
        $this->assertCanEnterScores($user);
        $this->assertTeaches($user, (int) $data['class_arm_id'], (int) $data['subject_id']);

        $term = $this->resolveTerm($data['term_id'] ?? null);
        $classArm = ClassArm::with('classLevel')->findOrFail($data['class_arm_id']);
        $subject = Subject::findOrFail($data['subject_id']);
        $students = $this->studentsFor($classArm->id);
        $types = $this->assessmentTypesFor($term->id);
        $records = $this->scoresFor($students, $subject->id, $term->id);
        $published = $this->isPublished($classArm->id, $term->id);
        $recordsByCell = $records->keyBy(fn (Score $score) => $score->student_id.':'.$score->assessment_type_id);
        $objective = $this->objectiveContext($students, $types, $classArm, $subject, $term, $resolver);

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'version' => $this->sheetVersion($students, $types, $records, $published),
            'locked' => $published,
            'lock_reason' => $published ? 'These results are published. Unpublish the report cards before scores can change.' : null,
            'class' => ['id' => $classArm->id, 'name' => $this->className($classArm)],
            'subject' => ['id' => $subject->id, 'name' => $subject->name],
            'term' => ['id' => $term->id, 'name' => $term->name, 'session' => $term->session?->name],
            'assessment_types' => $types->map(fn (AssessmentType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'max' => (float) $type->weight_percentage,
                'is_exam' => (bool) $type->is_exam,
                'is_split' => $type->isSplit(),
                'objective_max' => $type->objective_max,
                'theory_max' => $type->theory_max,
                'input_mode' => $type->isSplit() ? 'theory' : 'total',
                'objective_source_available' => $type->isSplit() ? (bool) ($objective['exams'][$type->id] ?? false) : null,
            ])->values(),
            'students' => $students->map(function (Student $student) use ($types, $recordsByCell, $objective, $published) {
                $cells = $types->mapWithKeys(function (AssessmentType $type) use ($student, $recordsByCell, $objective, $published) {
                    $record = $recordsByCell->get($student->id.':'.$type->id);
                    $objectiveScore = $type->isSplit()
                        ? ($objective['scores'][$student->id][$type->id] ?? $record?->objective_score)
                        : null;

                    return [(string) $type->id => [
                        'total' => $record?->score,
                        'value' => $type->isSplit() ? $record?->theory_score : $record?->score,
                        'objective_score' => $objectiveScore,
                        'theory_score' => $record?->theory_score,
                        'locked' => $published || (bool) $record?->is_source_locked,
                        'source' => $record?->score_source,
                    ]];
                });

                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'admission_number' => $student->admission_number,
                    'scores' => (object) $cells->all(),
                ];
            })->values(),
        ]);
    }

    public function save(Request $request, ObjectiveScoreResolver $resolver)
    {
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
            'version' => ['required', 'string', 'max:128'],
            'request_id' => ['required', 'uuid'],
            'scores' => ['required', 'array', 'min:1'],
        ]);
        $user = $request->user();
        $this->assertCanEnterScores($user);
        $this->assertTeaches($user, (int) $data['class_arm_id'], (int) $data['subject_id']);
        $term = $this->resolveTerm((int) $data['term_id']);
        $classArm = ClassArm::findOrFail($data['class_arm_id']);
        Subject::findOrFail($data['subject_id']);

        $response = $this->idempotency->execute(
            $user,
            "scores.{$classArm->id}.{$data['subject_id']}.{$term->id}.save",
            $data['request_id'],
            $data,
            function () use ($data, $term, $classArm, $user, $resolver): array {
                $saved = DB::transaction(function () use ($data, $term, $classArm, $user, $resolver) {
            abort_if($this->isPublished($classArm->id, $term->id), 423, 'These results are published and locked. Unpublish the report cards before changing scores.');
            $students = $this->studentsFor($classArm->id);
            $types = $this->assessmentTypesFor($term->id);
            $records = $this->scoresFor($students, (int) $data['subject_id'], $term->id, true);
            $serverVersion = $this->sheetVersion($students, $types, $records, false);
            abort_unless(hash_equals($serverVersion, (string) $data['version']), 409, 'Scores changed on the server. Reload the sheet before saving your draft.');

            $studentsById = $students->keyBy('id');
            $typesById = $types->keyBy('id');
            $recordsByCell = $records->keyBy(fn (Score $score) => $score->student_id.':'.$score->assessment_type_id);
            $errors = [];
            foreach ($data['scores'] as $studentId => $values) {
                if (! $studentsById->has((int) $studentId) || ! is_array($values)) {
                    $errors["scores.{$studentId}"][] = 'The selected student is not active in this class.';

                    continue;
                }
                foreach ($values as $typeId => $value) {
                    $type = $typesById->get((int) $typeId);
                    $path = "scores.{$studentId}.{$typeId}";
                    if (! $type) {
                        $errors[$path][] = 'The selected assessment does not belong to this term.';

                        continue;
                    }
                    if ($value === null || $value === '') {
                        continue;
                    }
                    if (! is_numeric($value)) {
                        $errors[$path][] = 'Enter a numeric score.';

                        continue;
                    }
                    $maximum = (float) ($type->isSplit() ? $type->theory_max : $type->weight_percentage);
                    if ((float) $value < 0 || (float) $value > $maximum) {
                        $errors[$path][] = "Enter a score between 0 and {$maximum}.";
                    }
                    if ((bool) $recordsByCell->get($studentId.':'.$typeId)?->is_source_locked) {
                        $errors[$path][] = 'This score is controlled by its source and cannot be edited manually.';
                    }
                }
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $savedCount = 0;
            foreach ($data['scores'] as $studentId => $values) {
                $student = $studentsById->get((int) $studentId);
                foreach ($values as $typeId => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $type = $typesById->get((int) $typeId);
                    $attributes = ['session_id' => $term->session_id, 'entered_by' => $user->id, 'entered_at' => now()];
                    if ($type->isSplit()) {
                        $exam = $resolver->findExam($classArm->id, (int) $data['subject_id'], $term->id, $type);
                        $objective = $exam ? $resolver->resolve($student, $exam, $type) : null;
                        $theory = (float) $value;
                        $attributes += [
                            'score' => min(($objective ?? 0) + $theory, (float) $type->weight_percentage),
                            'objective_score' => $objective,
                            'theory_score' => $theory,
                            'cbt_exam_id' => $exam?->id,
                        ];
                    } else {
                        $attributes += ['score' => (float) $value, 'objective_score' => null, 'theory_score' => null, 'cbt_exam_id' => null];
                    }
                    Score::updateOrCreate([
                        'student_id' => $student->id,
                        'subject_id' => (int) $data['subject_id'],
                        'assessment_type_id' => $type->id,
                        'term_id' => $term->id,
                    ], $attributes);
                    $savedCount++;
                }
            }

            return $savedCount;
                });

                $students = $this->studentsFor($classArm->id);
                $types = $this->assessmentTypesFor($term->id);
                $records = $this->scoresFor($students, (int) $data['subject_id'], $term->id);

                return [
                    'message' => "Saved {$saved} scores.", 'saved' => $saved, 'request_id' => $data['request_id'],
                    'version' => $this->sheetVersion($students, $types, $records, false), 'saved_at' => now()->toIso8601String(),
                ];
            },
        );

        return response()->json($response);
    }

    private function assertCanEnterScores($user): void
    {
        abort_if($user->isAccountant(), 403, 'Accountants cannot enter academic scores.');
        abort_unless($this->canEnterAll($user) || $user->canAccessExactModule('scores.entry'), 403, 'You do not have permission to enter scores.');
    }

    private function assertTeaches($user, int $classArmId, int $subjectId): void
    {
        if ($this->canEnterAll($user)) {
            return;
        }
        $session = AcademicSession::current()->first();
        $allowed = ClassArmSubject::where('teacher_id', $user->id)->where('class_arm_id', $classArmId)
            ->where('subject_id', $subjectId)->when($session, fn ($query) => $query->where('session_id', $session->id))->exists();
        abort_unless($allowed, 403, 'You are not assigned to teach this subject in this class.');
    }

    private function canEnterAll($user): bool
    {
        return $user->isSuperAdmin() || $user->canAccessExactModule('scores');
    }

    private function resolveTerm(?int $termId): Term
    {
        $term = $termId ? Term::with('session')->find($termId) : Term::current()->with('session')->first();
        abort_unless($term, 422, $termId ? 'The selected term is not available for this school.' : 'No current term is set.');

        return $term;
    }

    private function studentsFor(int $classArmId): Collection
    {
        return Student::where('current_class_arm_id', $classArmId)->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')->orderBy('first_name')->get();
    }

    private function assessmentTypesFor(int $termId): Collection
    {
        return AssessmentType::where('term_id', $termId)->orderBy('is_exam')->orderBy('weight_percentage')->orderBy('name')->get();
    }

    private function scoresFor(Collection $students, int $subjectId, int $termId, bool $lock = false): Collection
    {
        if ($students->isEmpty()) {
            return collect();
        }
        $query = Score::whereIn('student_id', $students->pluck('id'))->where('subject_id', $subjectId)
            ->where('term_id', $termId)->orderBy('student_id')->orderBy('assessment_type_id');

        return $lock ? $query->lockForUpdate()->get() : $query->get();
    }

    private function isPublished(int $classArmId, int $termId): bool
    {
        return ReportCardPublication::where('class_arm_id', $classArmId)->where('term_id', $termId)->where('status', 'published')->exists();
    }

    private function objectiveContext(Collection $students, Collection $types, ClassArm $classArm, Subject $subject, Term $term, ObjectiveScoreResolver $resolver): array
    {
        $scores = [];
        $exams = [];
        foreach ($types->filter->isSplit() as $type) {
            $exam = $resolver->findExam($classArm->id, $subject->id, $term->id, $type);
            $exams[$type->id] = (bool) $exam;
            if (! $exam) {
                continue;
            }
            foreach ($students as $student) {
                $scores[$student->id][$type->id] = $resolver->resolve($student, $exam, $type);
            }
        }

        return compact('scores', 'exams');
    }

    private function sheetVersion(Collection $students, Collection $types, Collection $records, bool $published): string
    {
        return hash('sha256', json_encode([
            'published' => $published,
            'students' => $students->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'types' => $types->map(fn (AssessmentType $type) => [$type->id, $type->weight_percentage, $type->objective_max, $type->theory_max])->all(),
            'scores' => $records->map(fn (Score $score) => [
                $score->id, $score->student_id, $score->assessment_type_id, $score->score, $score->objective_score,
                $score->theory_score, (bool) $score->is_source_locked, $score->updated_at?->format('Y-m-d H:i:s.u'),
            ])->all(),
        ], JSON_PRESERVE_ZERO_FRACTION));
    }

    private function className(ClassArm $classArm): string
    {
        return trim(($classArm->classLevel?->name ?? '').' '.$classArm->name);
    }
}
