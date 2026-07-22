<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScoreController extends Controller
{
    /** Subject-teaching assignments for the current session (what a teacher can score). */
    public function teaching(Request $request)
    {
        $user = $request->user();
        $term = Term::current()->first();

        $assignments = ClassArmSubject::with(['classArm.classLevel', 'subject'])
            ->when(!$this->canEnterAll($user), fn ($q) => $q->where('teacher_id', $user->id))
            ->get()
            ->filter(fn ($cas) => $cas->classArm && $cas->subject)
            ->map(fn ($cas) => [
                'class_arm_id' => $cas->class_arm_id,
                'class_name'   => trim(optional($cas->classArm->classLevel)->name . ' ' . $cas->classArm->name),
                'subject_id'   => $cas->subject_id,
                'subject_name' => $cas->subject->name,
            ])->values();

        return response()->json([
            'term'        => $term?->only(['id', 'name']),
            'assignments' => $assignments,
        ]);
    }

    /** Score sheet: students, assessment types, and existing scores for a class+subject. */
    public function sheet(Request $request)
    {
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'subject_id'   => ['required', 'integer'],
        ]);

        $user = $request->user();
        $this->assertTeaches($user, $data['class_arm_id'], $data['subject_id']);

        $term = Term::current()->first();
        if (!$term) {
            return response()->json(['message' => 'No current term is set.'], 422);
        }

        $classArm = ClassArm::with('classLevel')->findOrFail($data['class_arm_id']);
        $subject  = Subject::findOrFail($data['subject_id']);

        $students = Student::where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        $assessmentTypes = AssessmentType::where('term_id', $term->id)
            ->orderBy('is_exam')->orderBy('weight_percentage')->orderBy('name')
            ->get(['id', 'name', 'weight_percentage', 'is_exam']);

        $existing = [];
        if ($students->isNotEmpty() && $assessmentTypes->isNotEmpty()) {
            Score::whereIn('student_id', $students->pluck('id'))
                ->where('subject_id', $subject->id)
                ->where('term_id', $term->id)
                ->get()
                ->each(function ($s) use (&$existing) {
                    $existing[$s->student_id][$s->assessment_type_id] = $s->score;
                });
        }

        return response()->json([
            'class'   => ['id' => $classArm->id, 'name' => trim(optional($classArm->classLevel)->name . ' ' . $classArm->name)],
            'subject' => ['id' => $subject->id, 'name' => $subject->name],
            'term'    => ['id' => $term->id, 'name' => $term->name],
            'assessment_types' => $assessmentTypes->map(fn ($a) => [
                'id'     => $a->id,
                'name'   => $a->name,
                'max'    => (float) $a->weight_percentage,
                'is_exam' => (bool) $a->is_exam,
            ]),
            'students' => $students->map(fn ($st) => [
                'id'     => $st->id,
                'name'   => $st->full_name,
                'admission_number' => $st->admission_number,
                'scores' => (object) ($existing[$st->id] ?? []),
            ]),
        ]);
    }

    /** Save scores: { class_arm_id, subject_id, scores: { student_id: { assessment_type_id: value } } } */
    public function save(Request $request)
    {
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'subject_id'   => ['required', 'integer'],
            'scores'       => ['required', 'array'],
        ]);

        $user = $request->user();
        $this->assertTeaches($user, $data['class_arm_id'], $data['subject_id']);

        $term = Term::current()->first();
        if (!$term) {
            return response()->json(['message' => 'No current term is set.'], 422);
        }

        $allowedStudents = Student::where('current_class_arm_id', $data['class_arm_id'])
            ->where('status', Student::STATUS_ACTIVE)
            ->pluck('id')->flip();

        $assessmentTypes = AssessmentType::where('term_id', $term->id)->get()->keyBy('id');

        $saved = 0;
        DB::transaction(function () use ($data, $term, $user, $allowedStudents, $assessmentTypes, &$saved) {
            foreach ($data['scores'] as $studentId => $assessments) {
                if (!$allowedStudents->has((int) $studentId) || !is_array($assessments)) {
                    continue;
                }
                foreach ($assessments as $atId => $value) {
                    $at = $assessmentTypes->get((int) $atId);
                    if (!$at) continue;
                    if ($value === null || $value === '') continue;

                    $capped = min(max((float) $value, 0), (float) $at->weight_percentage);

                    Score::updateOrCreate(
                        [
                            'student_id'         => (int) $studentId,
                            'subject_id'         => $data['subject_id'],
                            'assessment_type_id' => (int) $atId,
                            'term_id'            => $term->id,
                        ],
                        [
                            'session_id' => $term->session_id,
                            'score'      => $capped,
                            'entered_by' => $user->id,
                            'entered_at' => now(),
                        ]
                    );
                    $saved++;
                }
            }
        });

        return response()->json(['message' => "Saved {$saved} scores.", 'saved' => $saved]);
    }

    private function assertTeaches($user, int $classArmId, int $subjectId): void
    {
        if ($this->canEnterAll($user)) return;
        $ok = ClassArmSubject::where('teacher_id', $user->id)
            ->where('class_arm_id', $classArmId)
            ->where('subject_id', $subjectId)
            ->exists();

        abort_unless($ok, 403, 'You are not assigned to teach this subject in this class.');
    }

    private function canEnterAll($user): bool
    {
        $access = \App\Models\User::ROLE_ACCESS[$user->roleKey()] ?? [];
        return in_array('*', $access, true) || in_array('scores', $access, true);
    }
}
