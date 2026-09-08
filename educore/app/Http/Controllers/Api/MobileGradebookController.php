<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileGradebookController extends Controller
{
    public function __construct(private readonly LifecycleAuditLogger $auditLogger)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'class_arm_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer'],
        ]);

        $classArms = $this->accessibleClassArms($user)
            ->with(['classLevel:id,name', 'formTutor:id,name'])
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();
        $terms = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $classId = isset($data['class_arm_id']) ? (int) $data['class_arm_id'] : null;
        $termId = isset($data['term_id']) ? (int) $data['term_id'] : null;
        $classArm = null;
        $term = null;
        $rows = [];
        $assessmentPayload = [];
        $subjectPayload = [];

        if ($classId !== null) {
            $classArm = $this->accessibleClassArms($user)
                ->with(['classLevel:id,name', 'formTutor:id,name'])
                ->whereKey($classId)
                ->firstOrFail();
        }
        if ($termId !== null) {
            $term = Term::where('tenant_id', $tenantId)
                ->with('session:id,name')
                ->whereKey($termId)
                ->firstOrFail();
        }

        if ($classArm && $term) {
            [$rows, $assessmentPayload, $subjectPayload] = $this->gradebookPayload(
                $tenantId,
                $classArm,
                $term,
            );
        }

        return response()->json([
            'contract_version' => 1,
            'module' => [
                'key' => 'gradebook',
                'title' => 'Gradebook & Remarks',
            ],
            'capabilities' => [
                'view' => true,
                'edit_form_tutor_remark' => true,
                'edit_principal_remark' => $this->hasFullReportsAccess($user),
                'compute_reports' => false,
                'publish_reports' => false,
            ],
            'options' => [
                'class_arms' => $classArms->map(fn (ClassArm $arm): array => $this->classPayload($arm))->values(),
                'terms' => $terms->map(fn (Term $option): array => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'session' => $option->session?->name,
                    'is_current' => (bool) $option->is_current,
                ])->values(),
            ],
            'selected' => [
                'class_arm_id' => $classArm?->id,
                'term_id' => $term?->id,
            ],
            'class' => $classArm ? $this->classPayload($classArm) : null,
            'term' => $term ? [
                'id' => $term->id,
                'name' => $term->name,
                'session' => $term->session?->name,
            ] : null,
            'assessment_types' => $assessmentPayload,
            'subjects' => $subjectPayload,
            'students' => $rows,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function updateRemark(Request $request, int $summary): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'field' => ['required', Rule::in(['form_tutor_remark', 'principal_remark'])],
            'remark' => ['nullable', 'string', 'max:2000'],
        ]);

        $termlySummary = TermlySummary::where('tenant_id', $tenantId)
            ->whereKey($summary)
            ->firstOrFail();
        $classArm = $this->accessibleClassArms($user)
            ->whereKey($termlySummary->class_arm_id)
            ->firstOrFail();

        $field = $data['field'];
        if ($field === 'principal_remark') {
            abort_unless(
                $this->hasFullReportsAccess($user),
                403,
                'Only report-card administrators can edit principal remarks.'
            );
        } else {
            $this->assertCanEditFormTutorRemark($user, $classArm);
        }

        $remark = isset($data['remark']) ? trim((string) $data['remark']) : null;
        if ($remark === '') {
            $remark = null;
        }

        DB::transaction(function () use ($termlySummary, $field, $remark, $tenantId, $user, $request): void {
            $oldValue = $termlySummary->{$field};
            $termlySummary->forceFill([$field => $remark])->save();

            $this->auditLogger->record(
                $tenantId,
                $user,
                $termlySummary,
                'report.remark.updated',
                [$field => $oldValue],
                [$field => $remark],
                null,
                $request,
            );
        });

        return response()->json([
            'message' => $field === 'principal_remark'
                ? 'Principal remark saved.'
                : 'Form tutor remark saved.',
            'summary_id' => $termlySummary->id,
            'field' => $field,
            'remark' => $remark,
            'updated_at' => $termlySummary->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    private function gradebookPayload(int $tenantId, ClassArm $classArm, Term $term): array
    {
        $summaries = TermlySummary::where('tenant_id', $tenantId)
            ->where('class_arm_id', $classArm->id)
            ->where('term_id', $term->id)
            ->with('student:id,tenant_id,admission_number,first_name,middle_name,last_name')
            ->get()
            ->keyBy('student_id');

        $students = Student::where('tenant_id', $tenantId)
            ->where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'tenant_id', 'admission_number', 'first_name', 'middle_name', 'last_name']);

        if ($summaries->isNotEmpty()) {
            $summaryStudentIds = $summaries->keys()->map(fn ($id) => (int) $id);
            $missingIds = $summaryStudentIds->diff($students->pluck('id'));
            if ($missingIds->isNotEmpty()) {
                $historicalStudents = Student::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('id', $missingIds)
                    ->get(['id', 'tenant_id', 'admission_number', 'first_name', 'middle_name', 'last_name']);
                $students = $students->concat($historicalStudents)->unique('id')->values();
            }
        }

        $studentIds = $students->pluck('id');
        $assessmentTypes = AssessmentType::where('tenant_id', $tenantId)
            ->where('term_id', $term->id)
            ->orderBy('is_exam')
            ->orderBy('name')
            ->get();

        $scores = Score::where('tenant_id', $tenantId)
            ->where('class_arm_id', $classArm->id)
            ->where('term_id', $term->id)
            ->whereIn('student_id', $studentIds)
            ->get();
        $subjectIds = $scores->pluck('subject_id')->filter()->unique()->values();
        $subjects = Subject::where('tenant_id', $tenantId)
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $scoreGroups = $scores->groupBy(fn (Score $score): string => $score->student_id.'-'.$score->subject_id);

        $rows = $students->map(function (Student $student) use ($summaries, $subjects, $scoreGroups): array {
            $summary = $summaries->get($student->id);
            $subjectRows = $subjects->map(function (Subject $subject) use ($student, $scoreGroups): array {
                $subjectScores = $scoreGroups->get($student->id.'-'.$subject->id, collect());

                return [
                    'subject_id' => $subject->id,
                    'subject' => $subject->name,
                    'code' => $subject->code,
                    'total' => round((float) $subjectScores->sum('score'), 2),
                    'assessments' => $subjectScores
                        ->mapWithKeys(fn (Score $score): array => [(string) $score->assessment_type_id => $score->score === null ? null : (float) $score->score]),
                ];
            })->values();

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => trim(implode(' ', array_filter([$student->first_name, $student->middle_name, $student->last_name]))),
                    'admission_number' => $student->admission_number,
                ],
                'summary' => $summary ? [
                    'id' => $summary->id,
                    'average' => (float) $summary->final_average,
                    'position' => $summary->position_in_class,
                    'class_size' => $summary->total_students_in_class,
                    'subjects_offered' => (int) $summary->subjects_offered,
                    'subjects_failed' => (int) $summary->subjects_failed,
                    'promotion_status' => $summary->promotion_status,
                    'form_tutor_remark' => $summary->form_tutor_remark,
                    'principal_remark' => $summary->principal_remark,
                ] : null,
                'subjects' => $subjectRows,
            ];
        })->values()->all();

        return [
            $rows,
            $assessmentTypes->map(fn (AssessmentType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'max_score' => (float) $type->max_score,
                'is_exam' => (bool) $type->is_exam,
            ])->values()->all(),
            $subjects->map(fn (Subject $subject): array => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
            ])->values()->all(),
        ];
    }

    private function accessibleClassArms(User $user): Builder
    {
        $query = ClassArm::query()->where('tenant_id', $user->tenant_id);
        if ($this->canManageAllClasses($user)) {
            return $query;
        }

        return $query->where('form_tutor_id', $user->id);
    }

    private function classPayload(ClassArm $arm): array
    {
        $arm->loadMissing(['classLevel:id,name', 'formTutor:id,name']);

        return [
            'id' => $arm->id,
            'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
            'class_level_id' => $arm->class_level_id,
            'class_level' => $arm->classLevel?->name,
            'form_tutor_id' => $arm->form_tutor_id,
            'form_tutor' => $arm->formTutor?->name,
        ];
    }

    private function canManageAllClasses(User $user): bool
    {
        return $user->canAccessExactModule('students') || $user->canAccessExactModule('reports');
    }

    private function hasFullReportsAccess(User $user): bool
    {
        return $user->canAccessExactModule('reports');
    }

    private function assertCanEditFormTutorRemark(User $user, ClassArm $classArm): void
    {
        if ($this->canManageAllClasses($user)) {
            return;
        }

        abort_unless(
            (int) $classArm->form_tutor_id === (int) $user->id,
            403,
            'You can only enter remarks for your assigned form class.'
        );
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403);
        abort_unless($user->tenant_id, 403);

        $allowed = $user->canAccessExactModule('gradebook')
            || $user->canAccessExactModule('reports')
            || $user->canAccessModule('reports.remarks');
        abort_unless($allowed, 403, 'Gradebook or form-tutor remark access is required.');

        return $user;
    }
}
