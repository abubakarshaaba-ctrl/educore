<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\ClassArm;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumArmSubjectTeacher;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassGrade;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumGrade;
use App\Models\ParallelCurriculumPromotion;
use App\Models\ParallelCurriculumPromotionRule;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumScore;
use App\Models\ParallelCurriculumSubject;
use App\Models\ParallelCurriculumTransfer;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\Student;
use App\Models\User;
use App\Services\ParallelCurriculumLifecycleService;
use App\Services\ParallelCurriculumOperationsService;
use App\Services\ParallelCurriculumService;
use App\Services\ParallelCurriculumStudentAssignmentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileParallelCurriculumLifecycleController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
        private readonly ParallelCurriculumLifecycleService $lifecycle,
        private readonly ParallelCurriculumStudentAssignmentImportService $assignmentImport,
        private readonly ParallelCurriculumOperationsService $operations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);

        $armTeacherOverridesReady = Schema::hasTable(
            'parallel_curriculum_arm_subject_teachers'
        );

        $classRelations = [
            'assessmentTemplate',
            'classGrades',
            'promotionRule.destinationClass',
            'subjectAssignments.subject',
            'subjectAssignments.teacher',
        ];

        $classRelations[] = $armTeacherOverridesReady
            ? 'arms.subjectTeachers.teacher'
            : 'arms';

        $curricula = ParallelCurriculum::with([
                'defaultAssessmentTemplate',
                'subjects',
                'classes' => fn ($query) => $query
                    ->with($classRelations)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
                'grades',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $assessmentTemplates = AssessmentTemplate::where(
                'status',
                AssessmentTemplate::STATUS_ACTIVE
            )
            ->orderBy('name')
            ->get();

        $staff = User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('role', User::staffRoleNames())
            ->orderBy('name')
            ->get()
            ->filter(fn (User $person) =>
                ! $person->isAccountant()
                && (
                    $person->canAccessExactModule('scores')
                    || $person->canAccessExactModule('scores.entry')
                )
            )
            ->values();

        $sessions = AcademicSession::orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $currentSession = $sessions->firstWhere('is_current', true) ?: $sessions->first();
        $curriculumId = (int) ($request->integer('parallel_curriculum_id')
            ?: ($curricula->first()?->id ?? 0));
        $sessionId = (int) ($request->integer('session_id')
            ?: ($currentSession?->id ?? 0));

        $selected = $curriculumId
            ? $curricula->firstWhere('id', $curriculumId)
            : null;

        if ($curriculumId && ! $selected) {
            abort(404, 'The selected parallel curriculum is unavailable.');
        }

        $enrolments = ($selected && $sessionId)
            ? ParallelCurriculumEnrolment::with([
                    'student',
                    'curriculumClass',
                    'curriculumClassArm',
                ])
                ->where('parallel_curriculum_id', $selected->id)
                ->where('session_id', $sessionId)
                ->where('is_active', true)
                ->orderBy('parallel_curriculum_class_id')
                ->orderBy('student_id')
                ->get()
            : collect();

        $transfers = $selected
            ? ParallelCurriculumTransfer::with([
                    'student',
                    'fromClass',
                    'toClass',
                    'fromArm',
                    'toArm',
                    'session',
                ])
                ->where('parallel_curriculum_id', $selected->id)
                ->latest('processed_at')
                ->limit(30)
                ->get()
            : collect();

        $promotions = $selected
            ? ParallelCurriculumPromotion::with([
                    'student',
                    'sourceClass',
                    'destinationClass',
                    'sourceArm',
                    'destinationArm',
                    'sourceSession',
                    'targetSession',
                ])
                ->where('parallel_curriculum_id', $selected->id)
                ->latest('processed_at')
                ->limit(30)
                ->get()
            : collect();

        return response()->json([
            'contract_version' => 3,
            'selected_curriculum_id' => $selected?->id,
            'selected_session_id' => $sessionId ?: null,
            'assessment_templates' => $assessmentTemplates->map(fn (AssessmentTemplate $template) => [
                'id' => (int) $template->id,
                'name' => (string) $template->name,
            ])->values(),
            'curricula' => $curricula->map(fn (ParallelCurriculum $curriculum) => [
                'id' => (int) $curriculum->id,
                'name' => (string) $curriculum->name,
                'code' => $curriculum->code,
                'default_assessment_template_id' => $curriculum->default_assessment_template_id
                    ? (int) $curriculum->default_assessment_template_id
                    : null,
                'default_assessment_template_name' => $curriculum->defaultAssessmentTemplate?->name,
                'subjects' => $curriculum->subjects->map(fn (ParallelCurriculumSubject $subject) => [
                    'id' => (int) $subject->id,
                    'name' => (string) $subject->name,
                    'code' => $subject->code,
                    'is_active' => (bool) $subject->is_active,
                ])->values(),
                'grades' => $curriculum->grades->map(fn (ParallelCurriculumGrade $grade) => [
                    'id' => (int) $grade->id,
                    'grade_letter' => (string) $grade->grade_letter,
                    'min_score' => (float) $grade->min_score,
                    'max_score' => (float) $grade->max_score,
                    'remark' => $grade->remark,
                    'is_pass_grade' => (bool) $grade->is_pass_grade,
                    'grade_point' => null,
                ])->values(),
                'classes' => $curriculum->classes->map(fn (ParallelCurriculumClass $class) => [
                    'id' => (int) $class->id,
                    'name' => (string) $class->name,
                    'code' => $class->code,
                    'sort_order' => (int) $class->sort_order,
                    'assessment_template_id' => $class->assessment_template_id
                        ? (int) $class->assessment_template_id
                        : null,
                    'assessment_template_name' => $class->assessmentTemplate?->name,
                    'is_active' => (bool) $class->is_active,
                    'subjects' => $class->subjectAssignments
                        ->where('is_active', true)
                        ->map(fn (ParallelCurriculumClassSubject $assignment) => [
                            'assignment_id' => (int) $assignment->id,
                            'subject_id' => (int) $assignment->parallel_curriculum_subject_id,
                            'subject_name' => $assignment->subject?->name,
                            'default_teacher_id' => $assignment->teacher_id
                                ? (int) $assignment->teacher_id
                                : null,
                            'default_teacher_name' => $assignment->teacher?->name,
                        ])->values(),
                    'arms' => $class->arms->map(fn (ParallelCurriculumClassArm $arm) => [
                        'id' => (int) $arm->id,
                        'name' => (string) $arm->name,
                        'code' => $arm->code,
                        'capacity' => $arm->capacity ? (int) $arm->capacity : null,
                        'is_active' => (bool) $arm->is_active,
                        'subject_teachers' => $armTeacherOverridesReady
                            ? $arm->subjectTeachers
                                ->where('is_active', true)
                                ->map(fn (ParallelCurriculumArmSubjectTeacher $override) => [
                                    'subject_id' => (int) $override->parallel_curriculum_subject_id,
                                    'teacher_id' => (int) $override->teacher_id,
                                    'teacher_name' => $override->teacher?->name,
                                ])->values()
                            : collect(),
                    ])->values(),
                    'class_grades' => $class->classGrades->map(fn (ParallelCurriculumClassGrade $grade) => [
                        'id' => (int) $grade->id,
                        'grade_letter' => (string) $grade->grade_letter,
                        'min_score' => (float) $grade->min_score,
                        'max_score' => (float) $grade->max_score,
                        'remark' => $grade->remark,
                        'is_pass_grade' => (bool) $grade->is_pass_grade,
                        'grade_point' => $grade->grade_point !== null ? (float) $grade->grade_point : null,
                    ])->values(),
                    'promotion_rule' => $class->promotionRule ? [
                        'id' => (int) $class->promotionRule->id,
                        'destination_class_id' => $class->promotionRule->destination_class_id
                            ? (int) $class->promotionRule->destination_class_id
                            : null,
                        'destination_class_name' => $class->promotionRule->destinationClass?->name,
                        'minimum_average' => (float) $class->promotionRule->minimum_average,
                        'max_failed_subjects' => (int) $class->promotionRule->max_failed_subjects,
                        'require_complete_result' => (bool) $class->promotionRule->require_complete_result,
                        'failure_action' => (string) $class->promotionRule->failure_action,
                        'arm_strategy' => (string) $class->promotionRule->arm_strategy,
                        'is_terminal' => (bool) $class->promotionRule->is_terminal,
                        'is_active' => (bool) $class->promotionRule->is_active,
                    ] : null,
                ])->values(),
            ])->values(),
            'arm_teacher_overrides_ready' => $armTeacherOverridesReady,
            'staff' => $staff->map(fn (User $person) => [
                'id' => (int) $person->id,
                'name' => (string) $person->name,
            ])->values(),
            'sessions' => $sessions->map(fn (AcademicSession $session) => [
                'id' => (int) $session->id,
                'name' => (string) $session->name,
                'is_current' => (bool) $session->is_current,
            ])->values(),
            'enrolments' => $enrolments->map(fn (ParallelCurriculumEnrolment $enrolment) => [
                'id' => (int) $enrolment->id,
                'student_id' => (int) $enrolment->student_id,
                'student_name' => $enrolment->student?->full_name,
                'admission_number' => $enrolment->student?->admission_number,
                'class_id' => (int) $enrolment->parallel_curriculum_class_id,
                'class_name' => $enrolment->curriculumClass?->name,
                'arm_id' => $enrolment->parallel_curriculum_class_arm_id
                    ? (int) $enrolment->parallel_curriculum_class_arm_id
                    : null,
                'arm_name' => $enrolment->curriculumClassArm?->name,
            ])->values(),
            'transfers' => $transfers->map(fn (ParallelCurriculumTransfer $transfer) => [
                'id' => (int) $transfer->id,
                'student_name' => $transfer->student?->full_name,
                'movement_type' => (string) $transfer->movement_type,
                'from_class' => $transfer->fromClass?->name,
                'from_arm' => $transfer->fromArm?->name,
                'to_class' => $transfer->toClass?->name,
                'to_arm' => $transfer->toArm?->name,
                'session' => $transfer->session?->name,
                'reason' => $transfer->reason,
                'effective_date' => optional($transfer->effective_date)->toDateString(),
                'processed_at' => optional($transfer->processed_at)->toIso8601String(),
            ])->values(),
            'promotions' => $promotions->map(fn (ParallelCurriculumPromotion $promotion) => [
                'id' => (int) $promotion->id,
                'student_name' => $promotion->student?->full_name,
                'decision' => (string) $promotion->decision,
                'source_class' => $promotion->sourceClass?->name,
                'source_arm' => $promotion->sourceArm?->name,
                'destination_class' => $promotion->destinationClass?->name,
                'destination_arm' => $promotion->destinationArm?->name,
                'source_session' => $promotion->sourceSession?->name,
                'target_session' => $promotion->targetSession?->name,
                'average_score' => $promotion->average_score !== null
                    ? (float) $promotion->average_score
                    : null,
                'failed_subjects' => (int) $promotion->failed_subjects,
                'reason' => $promotion->reason,
                'processed_at' => optional($promotion->processed_at)->toIso8601String(),
            ])->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function storeProgramme(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('parallel_curricula', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'code' => ['nullable', 'string', 'max:40'],
            'default_assessment_template_id' => [
                'required',
                Rule::exists('assessment_templates', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', AssessmentTemplate::STATUS_ACTIVE),
            ],
        ]);

        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $tenantId,
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'default_assessment_template_id' => (int) $data['default_assessment_template_id'],
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Parallel curriculum programme created.',
            'parallel_curriculum_id' => (int) $curriculum->id,
        ], 201);
    }

    public function updateProgramme(
        Request $request,
        ParallelCurriculum $curriculum
    ): JsonResponse {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $curriculum->tenant_id === $tenantId, 403);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('parallel_curricula', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($curriculum->id),
            ],
            'code' => ['nullable', 'string', 'max:40'],
            'default_assessment_template_id' => [
                'required',
                Rule::exists('assessment_templates', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', AssessmentTemplate::STATUS_ACTIVE),
            ],
        ]);

        $curriculum->fill([
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'default_assessment_template_id' => (int) $data['default_assessment_template_id'],
        ]);

        if (! $curriculum->isDirty()) {
            return response()->json(['message' => 'Parallel curriculum programme is already up to date.']);
        }

        abort_if(
            ParallelCurriculumReportPublication::where(
                    'parallel_curriculum_id',
                    $curriculum->id
                )
                ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
                ->exists(),
            423,
            'Unpublish this programme\'s results before editing its programme details.'
        );

        if ($curriculum->isDirty('default_assessment_template_id')) {
            abort_if(
                ParallelCurriculumScore::where(
                    'parallel_curriculum_id',
                    $curriculum->id
                )->exists(),
                423,
                'The default assessment template cannot be changed after parallel scores have been recorded.'
            );
        }

        $curriculum->save();

        return response()->json(['message' => 'Parallel curriculum programme updated.']);
    }

    public function storeClass(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('parallel_curriculum_classes', 'name')
                    ->where(fn ($query) => $query
                        ->where('parallel_curriculum_id', $request->integer('parallel_curriculum_id'))),
            ],
            'code' => ['nullable', 'string', 'max:40'],
            'assessment_template_id' => [
                'nullable',
                Rule::exists('assessment_templates', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', AssessmentTemplate::STATUS_ACTIVE),
            ],
        ]);

        $class = DB::transaction(function () use ($tenantId, $data): ParallelCurriculumClass {
            $sortOrder = ((int) ParallelCurriculumClass::where(
                    'parallel_curriculum_id',
                    $data['parallel_curriculum_id']
                )->max('sort_order')) + 1;

            $class = ParallelCurriculumClass::create([
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => (int) $data['parallel_curriculum_id'],
                'assessment_template_id' => $data['assessment_template_id'] ?? null,
                'name' => trim($data['name']),
                'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);

            ParallelCurriculumClassArm::create([
                'tenant_id' => $tenantId,
                'parallel_curriculum_class_id' => $class->id,
                'name' => 'A',
                'code' => 'A',
                'sort_order' => 1,
                'is_active' => true,
            ]);

            return $class;
        });

        return response()->json([
            'message' => "{$class->name} created with default Arm A.",
            'class_id' => (int) $class->id,
        ], 201);
    }

    public function updateClass(
        Request $request,
        ParallelCurriculumClass $class
    ): JsonResponse {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $class->tenant_id === $tenantId, 403);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('parallel_curriculum_classes', 'name')
                    ->where(fn ($query) => $query
                        ->where('parallel_curriculum_id', $class->parallel_curriculum_id))
                    ->ignore($class->id),
            ],
            'code' => ['nullable', 'string', 'max:40'],
            'assessment_template_id' => [
                'nullable',
                Rule::exists('assessment_templates', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', AssessmentTemplate::STATUS_ACTIVE),
            ],
        ]);

        $class->fill([
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'assessment_template_id' => $data['assessment_template_id'] ?? null,
        ]);

        if (! $class->isDirty()) {
            return response()->json(['message' => 'Parallel curriculum class is already up to date.']);
        }

        abort_if(
            $this->parallel->classStructureLocked($class),
            423,
            'Unpublish this class\'s parallel results before editing its class details.'
        );

        if ($class->isDirty('assessment_template_id')) {
            abort_if(
                ParallelCurriculumScore::where(
                    'parallel_curriculum_class_id',
                    $class->id
                )->exists(),
                423,
                'The class assessment template cannot be changed after parallel scores have been recorded.'
            );
        }

        $class->save();

        return response()->json(['message' => 'Parallel curriculum class updated.']);
    }

    public function storeSubject(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
        ]);

        $subject = ParallelCurriculumSubject::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => (int) $data['parallel_curriculum_id'],
                'name' => trim($data['name']),
            ],
            [
                'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Parallel curriculum subject saved.',
            'subject_id' => (int) $subject->id,
        ]);
    }

    public function updateSubject(
        Request $request,
        ParallelCurriculumSubject $subject
    ): JsonResponse {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $subject->tenant_id === $tenantId, 403);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('parallel_curriculum_subjects', 'name')
                    ->where(fn ($query) => $query
                        ->where('parallel_curriculum_id', $subject->parallel_curriculum_id))
                    ->ignore($subject->id),
            ],
            'code' => ['nullable', 'string', 'max:40'],
        ]);

        $subject->fill([
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
        ]);

        if (! $subject->isDirty()) {
            return response()->json(['message' => 'Parallel curriculum subject is already up to date.']);
        }

        $classIds = ParallelCurriculumClassSubject::where(
                'parallel_curriculum_subject_id',
                $subject->id
            )
            ->pluck('parallel_curriculum_class_id');

        abort_if(
            $classIds->isNotEmpty()
                && ParallelCurriculumReportPublication::whereIn(
                    'parallel_curriculum_class_id',
                    $classIds
                )
                ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
                ->exists(),
            423,
            'Unpublish every result that uses this programme subject before renaming it.'
        );

        $subject->save();

        return response()->json(['message' => 'Parallel curriculum subject updated.']);
    }

    public function storeClassSubject(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'parallel_curriculum_subject_id' => [
                'required',
                Rule::exists('parallel_curriculum_subjects', 'id')->where('tenant_id', $tenantId),
            ],
            'teacher_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $class = ParallelCurriculumClass::findOrFail(
            $data['parallel_curriculum_class_id']
        );
        $subject = ParallelCurriculumSubject::findOrFail(
            $data['parallel_curriculum_subject_id']
        );

        abort_unless(
            (int) $subject->parallel_curriculum_id
                === (int) $class->parallel_curriculum_id,
            422,
            'The selected subject belongs to a different parallel curriculum.'
        );

        if (! empty($data['teacher_id'])) {
            $teacher = User::where('is_active', true)
                ->findOrFail($data['teacher_id']);

            abort_unless(
                ! $teacher->isAccountant()
                    && (
                        $teacher->canAccessExactModule('scores')
                        || $teacher->canAccessExactModule('scores.entry')
                    ),
                422,
                'The selected staff member does not have academic score-entry permission.'
            );
        }

        $existing = ParallelCurriculumClassSubject::where(
                'parallel_curriculum_class_id',
                $class->id
            )
            ->where('parallel_curriculum_subject_id', $subject->id)
            ->first();

        $structureChanged = ! $existing || ! $existing->is_active;

        if ($structureChanged && $this->parallel->classStructureLocked($class)) {
            abort(
                423,
                'This parallel class has published results. Unpublish them before adding or reactivating subjects.'
            );
        }

        $this->operations->validateTeacherChange(
            $tenantId,
            (int) $class->id,
            (int) $subject->id,
            null,
            ! empty($data['teacher_id']) ? (int) $data['teacher_id'] : null,
        );

        $assignment = ParallelCurriculumClassSubject::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'parallel_curriculum_class_id' => $class->id,
                'parallel_curriculum_subject_id' => $subject->id,
            ],
            [
                'teacher_id' => $data['teacher_id'] ?? null,
                'is_active' => true,
            ]
        );

        $this->operations->syncTimetableTeachers(
            $tenantId,
            (int) $class->id,
            (int) $subject->id,
        );

        if ($structureChanged) {
            $this->parallel->reconcileClassStructure($class);
        }

        return response()->json([
            'message' => $structureChanged
                ? 'Subject assigned and dependent parallel composites refreshed.'
                : 'Subject teacher assignment updated.',
            'assignment_id' => (int) $assignment->id,
        ]);
    }

    public function destroyClassSubject(
        Request $request,
        ParallelCurriculumClassSubject $assignment
    ): JsonResponse {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $assignment->tenant_id === $tenantId, 403);

        $class = ParallelCurriculumClass::findOrFail(
            $assignment->parallel_curriculum_class_id
        );

        abort_if(
            ParallelCurriculumTimetablePeriod::where('parallel_curriculum_class_id', $class->id)
                ->where('parallel_curriculum_subject_id', $assignment->parallel_curriculum_subject_id)
                ->exists(),
            423,
            'Remove this subject from the parallel timetable before removing it from the class.'
        );

        abort_if(
            $this->parallel->classStructureLocked($class),
            423,
            'This parallel class has published results. Unpublish them before removing subjects from its result structure.'
        );

        $hasScores = ParallelCurriculumScore::where(
                'parallel_curriculum_class_id',
                $assignment->parallel_curriculum_class_id
            )
            ->where(
                'parallel_curriculum_subject_id',
                $assignment->parallel_curriculum_subject_id
            )
            ->exists();

        if ($hasScores) {
            $assignment->update(['is_active' => false]);
            $this->parallel->reconcileClassStructure($class);

            return response()->json([
                'message' => 'Subject archived because result records already exist.',
                'archived' => true,
            ]);
        }

        $assignment->delete();
        $this->parallel->reconcileClassStructure($class);

        return response()->json([
            'message' => 'Subject removed from the parallel class.',
            'archived' => false,
        ]);
    }

    public function storeProgrammeGrade(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'grade_letter' => ['required', 'string', 'max:20'],
            'min_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'gte:min_score', 'max:100'],
            'remark' => ['nullable', 'string', 'max:100'],
            'is_pass_grade' => ['nullable', 'boolean'],
        ]);

        $curriculumId = (int) $data['parallel_curriculum_id'];

        abort_if(
            ParallelCurriculumReportPublication::where(
                    'parallel_curriculum_id',
                    $curriculumId
                )
                ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
                ->exists(),
            423,
            'Unpublish this programme\'s results before changing its grading scale.'
        );

        $letter = strtoupper(trim($data['grade_letter']));
        $existing = ParallelCurriculumGrade::where(
                'parallel_curriculum_id',
                $curriculumId
            )
            ->where('grade_letter', $letter)
            ->first();

        $overlap = ParallelCurriculumGrade::where(
                'parallel_curriculum_id',
                $curriculumId
            )
            ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
            ->where('min_score', '<=', (float) $data['max_score'])
            ->where('max_score', '>=', (float) $data['min_score'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'min_score' => 'This score range overlaps another programme grade band.',
            ]);
        }

        ParallelCurriculumGrade::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => $curriculumId,
                'grade_letter' => $letter,
            ],
            [
                'min_score' => round((float) $data['min_score'], 2),
                'max_score' => round((float) $data['max_score'], 2),
                'remark' => filled($data['remark'] ?? null) ? trim($data['remark']) : null,
                'is_pass_grade' => $request->boolean('is_pass_grade', true),
            ]
        );

        return response()->json([
            'message' => "Programme grade {$letter} saved.",
        ]);
    }

    public function destroyProgrammeGrade(
        Request $request,
        ParallelCurriculumGrade $grade
    ): JsonResponse {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $grade->tenant_id === $tenantId, 403);

        abort_if(
            ParallelCurriculumReportPublication::where(
                    'parallel_curriculum_id',
                    $grade->parallel_curriculum_id
                )
                ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
                ->exists(),
            423,
            'Unpublish this programme\'s results before changing its grading scale.'
        );

        $grade->delete();

        return response()->json([
            'message' => 'Parallel curriculum programme grade removed.',
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId),
            ],
            'conventional_class_arm_id' => [
                'nullable',
                Rule::exists('class_arms', 'id')->where('tenant_id', $tenantId),
            ],
            'assignment_status' => ['nullable', Rule::in(['all', 'assigned', 'unassigned'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $curriculum = ParallelCurriculum::where('is_active', true)
            ->findOrFail($data['parallel_curriculum_id']);
        $session = AcademicSession::findOrFail($data['session_id']);
        $assignmentStatus = (string) ($data['assignment_status'] ?? 'all');
        $gender = trim((string) ($data['gender'] ?? ''));
        $search = trim((string) ($data['q'] ?? ''));
        $conventionalClassArmId = isset($data['conventional_class_arm_id'])
            ? (int) $data['conventional_class_arm_id']
            : null;
        $perPage = (int) ($data['per_page'] ?? 50);

        $programmeEnrolments = ParallelCurriculumEnrolment::where(
                'parallel_curriculum_id',
                $curriculum->id
            )
            ->where('session_id', $session->id)
            ->where('is_active', true);

        $assignedStudentIds = (clone $programmeEnrolments)->pluck('student_id');

        $studentsQuery = Student::active()
            ->with('currentClassArm.classLevel')
            ->when(
                $conventionalClassArmId,
                function ($query) use ($conventionalClassArmId, $session): void {
                    $query->where(function ($studentQuery) use ($conventionalClassArmId, $session): void {
                        $studentQuery->where('current_class_arm_id', $conventionalClassArmId)
                            ->orWhereHas(
                                'enrollments',
                                fn ($enrolmentQuery) => $enrolmentQuery
                                    ->where('session_id', $session->id)
                                    ->where('class_arm_id', $conventionalClassArmId)
                            );
                    });
                }
            )
            ->when($gender !== '', fn ($query) => $query->where('gender', $gender))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($studentQuery) use ($search): void {
                    $studentQuery
                        ->where('admission_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when(
                $assignmentStatus === 'assigned',
                fn ($query) => $query->whereIn('id', $assignedStudentIds)
            )
            ->when(
                $assignmentStatus === 'unassigned',
                fn ($query) => $query->whereNotIn('id', $assignedStudentIds)
            )
            ->orderBy('last_name')
            ->orderBy('first_name');

        $students = $studentsQuery->paginate($perPage);

        $activeAssignments = $students->isNotEmpty()
            ? ParallelCurriculumEnrolment::with(['curriculumClass', 'curriculumClassArm'])
                ->where('parallel_curriculum_id', $curriculum->id)
                ->where('session_id', $session->id)
                ->where('is_active', true)
                ->whereIn('student_id', $students->getCollection()->pluck('id'))
                ->get()
                ->keyBy('student_id')
            : collect();

        $classArms = ClassArm::with('classLevel')
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'contract_version' => 1,
            'parallel_curriculum_id' => (int) $curriculum->id,
            'session_id' => (int) $session->id,
            'filters' => [
                'conventional_class_arm_id' => $conventionalClassArmId,
                'assignment_status' => $assignmentStatus,
                'gender' => $gender !== '' ? $gender : null,
                'q' => $search,
            ],
            'conventional_class_arms' => $classArms->map(fn (ClassArm $arm) => [
                'id' => (int) $arm->id,
                'name' => (string) $arm->full_name,
            ])->values(),
            'students' => $students->getCollection()->map(function (Student $student) use ($activeAssignments) {
                $assignment = $activeAssignments->get($student->id);

                return [
                    'id' => (int) $student->id,
                    'name' => (string) $student->full_name,
                    'admission_number' => (string) $student->admission_number,
                    'gender' => $student->gender,
                    'conventional_class_arm_id' => $student->current_class_arm_id
                        ? (int) $student->current_class_arm_id
                        : null,
                    'conventional_class_name' => $student->currentClassArm?->full_name,
                    'assignment' => $assignment ? [
                        'enrolment_id' => (int) $assignment->id,
                        'class_id' => (int) $assignment->parallel_curriculum_class_id,
                        'class_name' => $assignment->curriculumClass?->name,
                        'arm_id' => $assignment->parallel_curriculum_class_arm_id
                            ? (int) $assignment->parallel_curriculum_class_arm_id
                            : null,
                        'arm_name' => $assignment->curriculumClassArm?->name,
                    ] : null,
                ];
            })->values(),
            'pagination' => [
                'current_page' => (int) $students->currentPage(),
                'last_page' => (int) $students->lastPage(),
                'per_page' => (int) $students->perPage(),
                'total' => (int) $students->total(),
            ],
        ]);
    }

    public function downloadStudentAssignmentTemplate(Request $request)
    {
        $this->assertManage($request);

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['admission_number', 'parallel_class', 'parallel_arm']);
            fputcsv($handle, ['STU001', 'Mutawassitah 1', 'A']);
            fputcsv($handle, ['STU002', 'M2', 'B']);
            fclose($handle);
        }, 'parallel_curriculum_student_assignment_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function importStudentAssignments(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId),
            ],
            'assignment_file' => ['required', 'file', 'mimes:csv,txt,xls,xlsx', 'max:5120'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $result = $this->assignmentImport->import(
            $tenantId,
            (int) $data['parallel_curriculum_id'],
            (int) $data['session_id'],
            $request->file('assignment_file'),
            (bool) ($data['dry_run'] ?? false)
        );

        return response()->json([
            'message' => $result['dry_run']
                ? $result['count'].' student assignment row(s) validated successfully. No placements were changed.'
                : $result['count'].' student assignment(s) imported successfully for '.
                    $result['curriculum_name'].'.',
            'imported' => $result['dry_run'] ? 0 : $result['count'],
            'validated' => $result['count'],
            'dry_run' => $result['dry_run'],
            'parallel_curriculum_id' => $result['curriculum_id'],
            'session_id' => $result['session_id'],
        ]);
    }

    public function assignStudents(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'parallel_curriculum_class_arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId),
            ],
            'session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId),
            ],
            'student_ids' => ['required', 'array', 'min:1', 'max:500'],
            'student_ids.*' => [
                'integer',
                Rule::exists('students', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $class = ParallelCurriculumClass::with('curriculum')
            ->findOrFail($data['parallel_curriculum_class_id']);
        $arm = ParallelCurriculumClassArm::where(
                'parallel_curriculum_class_id',
                $class->id
            )
            ->where('is_active', true)
            ->findOrFail($data['parallel_curriculum_class_arm_id']);

        abort_unless(
            $class->is_active && $class->curriculum?->is_active,
            422,
            'Students can only be assigned to an active parallel curriculum class and programme.'
        );

        $studentIds = collect($data['student_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $students = Student::active()
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        if ($studentIds->diff($students->keys()->map(fn ($id) => (int) $id))->isNotEmpty()) {
            throw ValidationException::withMessages([
                'student_ids' => ['Only active students can be assigned to a parallel curriculum class.'],
            ]);
        }

        $existingEnrolments = ParallelCurriculumEnrolment::where(
                'parallel_curriculum_id',
                $class->parallel_curriculum_id
            )
            ->where('session_id', $data['session_id'])
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $destinationLocked = null;
        $placementErrors = [];

        foreach ($studentIds as $studentId) {
            $existing = $existingEnrolments->get($studentId);
            $changesPlacement = ! $existing
                || (int) $existing->parallel_curriculum_class_id !== (int) $class->id
                || (int) $existing->parallel_curriculum_class_arm_id !== (int) $arm->id;

            if (! $changesPlacement) {
                continue;
            }

            $student = $students->get($studentId);
            $studentLabel = $student?->admission_number ?: 'Student #'.$studentId;

            if ($existing && $this->parallel->enrolmentPlacementLocked($existing)) {
                $placementErrors[] = "{$studentLabel} cannot be moved because the current parallel-class result is published.";
                continue;
            }

            $destinationLocked ??= $this->parallel->classPlacementLocked(
                $class,
                (int) $data['session_id']
            );

            if ($destinationLocked) {
                $placementErrors[] = "{$studentLabel} cannot be assigned to {$class->name} because that class already has a published result for this session.";
            }
        }

        if ($placementErrors !== []) {
            throw ValidationException::withMessages([
                'student_ids' => array_slice($placementErrors, 0, 20),
            ]);
        }

        if ($arm->capacity) {
            $existingInArm = ParallelCurriculumEnrolment::where(
                    'parallel_curriculum_class_arm_id',
                    $arm->id
                )
                ->where('session_id', $data['session_id'])
                ->where('is_active', true)
                ->whereNotIn('student_id', $studentIds)
                ->count();

            if ($existingInArm + $studentIds->count() > (int) $arm->capacity) {
                throw ValidationException::withMessages([
                    'parallel_curriculum_class_arm_id' =>
                        'The selected arm has capacity for '.
                        max(0, (int) $arm->capacity - $existingInArm).
                        ' additional learner(s).',
                ]);
            }
        }

        DB::transaction(function () use ($studentIds, $tenantId, $class, $arm, $data): void {
            foreach ($studentIds as $studentId) {
                ParallelCurriculumEnrolment::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'parallel_curriculum_id' => $class->parallel_curriculum_id,
                        'student_id' => $studentId,
                        'session_id' => $data['session_id'],
                    ],
                    [
                        'parallel_curriculum_class_id' => $class->id,
                        'parallel_curriculum_class_arm_id' => $arm->id,
                        'is_active' => true,
                    ]
                );
            }
        });

        return response()->json([
            'message' => $studentIds->count().
                ' student parallel placement(s) updated. Existing students in this programme were moved to the selected parallel class arm.',
            'updated' => $studentIds->count(),
            'parallel_curriculum_class_id' => (int) $class->id,
            'parallel_curriculum_class_arm_id' => (int) $arm->id,
            'session_id' => (int) $data['session_id'],
        ]);
    }

    public function removeStudent(
        Request $request,
        ParallelCurriculumEnrolment $enrolment
    ): JsonResponse {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $enrolment->tenant_id === $tenantId, 403);

        abort_if(
            $this->parallel->enrolmentPlacementLocked($enrolment),
            423,
            'This student placement belongs to a published parallel result. Unpublish the class result before removing or moving the student.'
        );

        $hasScores = ParallelCurriculumScore::where(
                'parallel_curriculum_id',
                $enrolment->parallel_curriculum_id
            )
            ->where('student_id', $enrolment->student_id)
            ->where('session_id', $enrolment->session_id)
            ->exists();

        if ($hasScores) {
            $enrolment->update(['is_active' => false]);

            return response()->json([
                'message' => 'Student assignment archived because result records already exist.',
                'archived' => true,
            ]);
        }

        $enrolment->delete();

        return response()->json([
            'message' => 'Student removed from the parallel curriculum class.',
            'archived' => false,
        ]);
    }

    public function previewPromotion(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'source_session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId),
            ],
            'target_session_id' => [
                'required',
                'different:source_session_id',
                Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId),
            ],
            'source_class_ids' => ['nullable', 'array', 'min:1'],
            'source_class_ids.*' => [
                'integer',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
        ]);

        $curriculum = ParallelCurriculum::findOrFail($data['parallel_curriculum_id']);
        $sourceSession = AcademicSession::findOrFail($data['source_session_id']);
        $targetSession = AcademicSession::findOrFail($data['target_session_id']);

        $sourceClassIds = collect($data['source_class_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $preview = $this->lifecycle->promotionPreview(
            $curriculum,
            $sourceSession,
            $targetSession,
            $sourceClassIds->all()
        );

        return response()->json([
            'contract_version' => 1,
            'source_session' => [
                'id' => (int) $sourceSession->id,
                'name' => (string) $sourceSession->name,
            ],
            'target_session' => [
                'id' => (int) $targetSession->id,
                'name' => (string) $targetSession->name,
            ],
            'source_class_ids' => $sourceClassIds,
            'counts' => $preview['counts'],
            'rows' => $preview['rows']->map(fn (array $row) => [
                'student_id' => (int) $row['student']->id,
                'student_name' => $row['student']->full_name,
                'admission_number' => $row['student']->admission_number,
                'source_class' => $row['enrolment']->curriculumClass?->name,
                'source_arm' => $row['enrolment']->curriculumClassArm?->name,
                'average' => $row['average'],
                'failed_subjects' => (int) $row['failed_subjects'],
                'decision' => (string) $row['decision'],
                'destination_class' => $row['destination_class']?->name,
                'destination_arm' => $row['destination_arm']?->name,
                'reason' => (string) $row['reason'],
            ])->values(),
        ]);
    }

    public function executePromotion(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'source_session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId),
            ],
            'target_session_id' => [
                'required',
                'different:source_session_id',
                Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId),
            ],
            'source_class_ids' => ['nullable', 'array', 'min:1'],
            'source_class_ids.*' => [
                'integer',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
        ]);

        $result = $this->lifecycle->executePromotion(
            ParallelCurriculum::findOrFail($data['parallel_curriculum_id']),
            AcademicSession::findOrFail($data['source_session_id']),
            AcademicSession::findOrFail($data['target_session_id']),
            $request->user()?->id,
            collect($data['source_class_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all()
        );

        return response()->json([
            'message' => "{$result['total']} promotion decision(s) processed.",
            'result' => $result,
        ]);
    }

    public function transfer(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'enrolment_id' => [
                'required',
                Rule::exists('parallel_curriculum_enrolments', 'id')->where('tenant_id', $tenantId),
            ],
            'destination_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'destination_arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId),
            ],
            'reason' => ['required', 'string', 'max:2000'],
            'effective_date' => ['nullable', 'date'],
        ]);

        $transfer = $this->lifecycle->transfer(
            ParallelCurriculumEnrolment::findOrFail($data['enrolment_id']),
            ParallelCurriculumClass::findOrFail($data['destination_class_id']),
            ParallelCurriculumClassArm::findOrFail($data['destination_arm_id']),
            $data['reason'],
            $data['effective_date'] ?? null,
            $request->user()?->id
        );

        return response()->json([
            'message' => $transfer->movement_type === ParallelCurriculumTransfer::TYPE_INTRA_CLASS
                ? 'Intra-class arm transfer completed.'
                : 'Inter-class parallel curriculum transfer completed.',
            'transfer_id' => (int) $transfer->id,
            'movement_type' => (string) $transfer->movement_type,
        ]);
    }

    public function storeArm(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $class = ParallelCurriculumClass::findOrFail($data['parallel_curriculum_class_id']);
        $duplicate = ParallelCurriculumClassArm::where('parallel_curriculum_class_id', $class->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'This parallel class already has an arm with that name.',
            ]);
        }

        $arm = ParallelCurriculumClassArm::create([
            'tenant_id' => $tenantId,
            'parallel_curriculum_class_id' => $class->id,
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'capacity' => $data['capacity'] ?? null,
            'sort_order' => ((int) $class->arms()->max('sort_order')) + 1,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Parallel class arm created.',
            'arm_id' => (int) $arm->id,
        ], 201);
    }

    public function updateArm(Request $request, ParallelCurriculumClassArm $arm): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $arm->tenant_id === $tenantId, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $class = ParallelCurriculumClass::findOrFail($arm->parallel_curriculum_class_id);
        $duplicate = ParallelCurriculumClassArm::where('parallel_curriculum_class_id', $class->id)
            ->where('id', '!=', $arm->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'This parallel class already has an arm with that name.',
            ]);
        }

        $arm->fill([
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'capacity' => $data['capacity'] ?? null,
        ]);

        if (
            ($arm->isDirty('name') || $arm->isDirty('code'))
            && $this->parallel->classStructureLocked($class)
        ) {
            abort(423, 'Unpublish the affected result before renaming this class arm.');
        }

        $this->lifecycle->assertArmCapacityChange(
            $arm,
            array_key_exists('capacity', $data) && $data['capacity'] !== null
                ? (int) $data['capacity']
                : null
        );

        $arm->save();

        return response()->json(['message' => 'Parallel class arm updated.']);
    }

    public function archiveArm(Request $request, ParallelCurriculumClassArm $arm): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $arm->tenant_id === $tenantId, 403);

        if ($this->lifecycle->armHasOperationalPlacements($arm)) {
            throw ValidationException::withMessages([
                'arm' => 'Move all current/future-session learners out of this arm before archiving it.',
            ]);
        }

        $arm->update(['is_active' => false]);

        return response()->json(['message' => 'Parallel class arm archived.']);
    }

    public function storeClassGrade(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => [
                'integer',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'grade_letter' => ['required', 'string', 'max:20'],
            'min_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'gte:min_score', 'max:100'],
            'remark' => ['nullable', 'string', 'max:100'],
            'is_pass_grade' => ['nullable', 'boolean'],
            'grade_point' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $classIds = collect($data['class_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $classes = ParallelCurriculumClass::whereIn('id', $classIds)->get();

        if (
            $classes->count() !== $classIds->count()
            || $classes->contains(fn (ParallelCurriculumClass $class) =>
                (int) $class->parallel_curriculum_id !== (int) $data['parallel_curriculum_id']
            )
        ) {
            throw ValidationException::withMessages([
                'class_ids' => 'All selected classes must belong to the selected parallel curriculum.',
            ]);
        }

        foreach ($classes as $class) {
            abort_if(
                $this->parallel->classStructureLocked($class),
                423,
                "Unpublish {$class->name}'s result before changing its grade system."
            );
        }

        $letter = strtoupper(trim($data['grade_letter']));
        foreach ($classes as $class) {
            $existing = ParallelCurriculumClassGrade::where('parallel_curriculum_class_id', $class->id)
                ->where('grade_letter', $letter)
                ->first();

            $overlap = ParallelCurriculumClassGrade::where('parallel_curriculum_class_id', $class->id)
                ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                ->where('min_score', '<=', (float) $data['max_score'])
                ->where('max_score', '>=', (float) $data['min_score'])
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'min_score' => "{$class->name}: this range overlaps another grade band.",
                ]);
            }
        }

        foreach ($classes as $class) {
            ParallelCurriculumClassGrade::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'parallel_curriculum_class_id' => $class->id,
                    'grade_letter' => $letter,
                ],
                [
                    'parallel_curriculum_id' => $class->parallel_curriculum_id,
                    'min_score' => round((float) $data['min_score'], 2),
                    'max_score' => round((float) $data['max_score'], 2),
                    'remark' => filled($data['remark'] ?? null) ? trim($data['remark']) : null,
                    'is_pass_grade' => $request->boolean('is_pass_grade', true),
                    'grade_point' => $data['grade_point'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => "Grade {$letter} applied to {$classes->count()} parallel class level(s).",
        ]);
    }

    public function destroyClassGrade(Request $request, ParallelCurriculumClassGrade $grade): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $grade->tenant_id === $tenantId, 403);

        $class = ParallelCurriculumClass::findOrFail($grade->parallel_curriculum_class_id);
        abort_if(
            $this->parallel->classStructureLocked($class),
            423,
            'Unpublish the affected result before changing this class grade system.'
        );

        $grade->delete();

        return response()->json(['message' => 'Class-specific parallel grade removed.']);
    }

    public function storeArmTeacher(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);

        abort_unless(
            Schema::hasTable('parallel_curriculum_arm_subject_teachers'),
            503,
            'Arm-specific teacher assignments are not available until the latest database migration has been applied.'
        );

        $data = $request->validate([
            'parallel_curriculum_class_arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'parallel_curriculum_subject_id' => [
                'required',
                Rule::exists('parallel_curriculum_subjects', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'teacher_id' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true),
            ],
        ]);

        $arm = ParallelCurriculumClassArm::with('curriculumClass')
            ->findOrFail($data['parallel_curriculum_class_arm_id']);
        $class = $arm->curriculumClass;
        $subject = ParallelCurriculumSubject::findOrFail(
            $data['parallel_curriculum_subject_id']
        );

        abort_unless(
            $class
                && (int) $subject->parallel_curriculum_id
                    === (int) $class->parallel_curriculum_id,
            422,
            'The selected subject and class arm must belong to the same parallel curriculum.'
        );

        $classSubject = ParallelCurriculumClassSubject::where(
                'parallel_curriculum_class_id',
                $class->id
            )
            ->where(
                'parallel_curriculum_subject_id',
                $subject->id
            )
            ->where('is_active', true)
            ->first();

        abort_unless(
            $classSubject,
            422,
            'Assign this subject to the parallel class before setting an arm-specific teacher.'
        );

        $teacherId = ! empty($data['teacher_id'])
            ? (int) $data['teacher_id']
            : null;

        $effectiveTeacherId = $teacherId ?: ($classSubject->teacher_id ? (int) $classSubject->teacher_id : null);
        $this->operations->validateTeacherChange(
            $tenantId,
            (int) $class->id,
            (int) $subject->id,
            (int) $arm->id,
            $effectiveTeacherId,
        );

        if ($teacherId) {
            $teacher = User::findOrFail($teacherId);

            abort_if(
                $teacher->isAccountant()
                    || (
                        ! $teacher->canAccessExactModule('scores')
                        && ! $teacher->canAccessExactModule('scores.entry')
                    ),
                422,
                'The selected staff member does not have academic score-entry access.'
            );

            ParallelCurriculumArmSubjectTeacher::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'parallel_curriculum_class_id' => $class->id,
                    'parallel_curriculum_class_arm_id' => $arm->id,
                    'parallel_curriculum_subject_id' => $subject->id,
                ],
                [
                    'teacher_id' => $teacherId,
                    'is_active' => true,
                ]
            );

            $this->operations->syncTimetableTeachers(
                $tenantId,
                (int) $class->id,
                (int) $subject->id,
                (int) $arm->id,
            );

            return response()->json([
                'message' => "{$subject->name} in {$class->name} {$arm->name} is now assigned to {$teacher->name}.",
                'effective_teacher_id' => $teacherId,
                'effective_teacher_name' => $teacher->name,
            ]);
        }

        ParallelCurriculumArmSubjectTeacher::where(
                'parallel_curriculum_class_arm_id',
                $arm->id
            )
            ->where(
                'parallel_curriculum_subject_id',
                $subject->id
            )
            ->delete();

        $this->operations->syncTimetableTeachers(
            $tenantId,
            (int) $class->id,
            (int) $subject->id,
            (int) $arm->id,
        );

        return response()->json([
            'message' => "{$subject->name} in {$class->name} {$arm->name} now uses the class-level default teacher.",
            'effective_teacher_id' => $classSubject->teacher_id
                ? (int) $classSubject->teacher_id
                : null,
            'effective_teacher_name' => $classSubject->teacher?->name,
        ]);
    }

    public function storePromotionRule(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'source_class_ids' => ['nullable', 'array', 'min:1'],
            'source_class_ids.*' => [
                'integer',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'source_class_id' => [
                'nullable',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'destination_mode' => [
                'nullable',
                Rule::in(['next_by_order', 'explicit', 'terminal']),
            ],
            'destination_class_id' => [
                'nullable',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'minimum_average' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_failed_subjects' => ['required', 'integer', 'min:0', 'max:50'],
            'require_complete_result' => ['nullable', 'boolean'],
            'failure_action' => ['required', Rule::in(['repeat', 'retain'])],
            'arm_strategy' => ['required', Rule::in(['same_name', 'first_available'])],
            'is_terminal' => ['nullable', 'boolean'],
        ]);

        $sourceClassIds = collect($data['source_class_ids'] ?? [])
            ->push($data['source_class_id'] ?? null)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($sourceClassIds->isEmpty()) {
            throw ValidationException::withMessages([
                'source_class_ids' => 'Select at least one source class level.',
            ]);
        }

        $classes = ParallelCurriculumClass::where(
                'parallel_curriculum_id',
                $data['parallel_curriculum_id']
            )
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sources = $classes->whereIn('id', $sourceClassIds)->values();

        if ($sources->count() !== $sourceClassIds->count()) {
            throw ValidationException::withMessages([
                'source_class_ids' =>
                    'All selected source classes must belong to this parallel curriculum.',
            ]);
        }

        $mode = $data['destination_mode']
            ?? ($request->boolean('is_terminal')
                ? 'terminal'
                : (filled($data['destination_class_id'] ?? null)
                    ? 'explicit'
                    : 'next_by_order'));

        if ($mode === 'explicit' && $sourceClassIds->count() !== 1) {
            throw ValidationException::withMessages([
                'destination_mode' =>
                    'A specific destination can only be used with one source class. Use automatic next-level routing for multiple classes.',
            ]);
        }

        $explicitDestination = null;
        if ($mode === 'explicit') {
            $explicitDestination = $classes->firstWhere(
                'id',
                (int) ($data['destination_class_id'] ?? 0)
            );

            if (! $explicitDestination) {
                throw ValidationException::withMessages([
                    'destination_class_id' =>
                        'Choose a destination class from this parallel curriculum.',
                ]);
            }
        }

        $savedRuleIds = [];

        foreach ($sources as $source) {
            $terminal = $mode === 'terminal';
            $destination = null;

            if ($mode === 'next_by_order') {
                $sourceIndex = $classes->search(
                    fn (ParallelCurriculumClass $class) =>
                        (int) $class->id === (int) $source->id
                );
                $destination = $sourceIndex !== false
                    ? $classes->get($sourceIndex + 1)
                    : null;
                $terminal = ! $destination;
            } elseif ($mode === 'explicit') {
                $destination = $explicitDestination;
            }

            if ($destination && (int) $destination->id === (int) $source->id) {
                throw ValidationException::withMessages([
                    'destination_class_id' =>
                        'Promotion destination must be different from its source class.',
                ]);
            }

            $rule = ParallelCurriculumPromotionRule::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'source_class_id' => $source->id,
                ],
                [
                    'parallel_curriculum_id' => $source->parallel_curriculum_id,
                    'destination_class_id' => $terminal ? null : $destination?->id,
                    'minimum_average' => round((float) $data['minimum_average'], 2),
                    'max_failed_subjects' => (int) $data['max_failed_subjects'],
                    'require_complete_result' => $request->boolean(
                        'require_complete_result',
                        true
                    ),
                    'failure_action' => $data['failure_action'],
                    'arm_strategy' => $data['arm_strategy'],
                    'is_terminal' => $terminal,
                    'is_active' => true,
                ]
            );

            $savedRuleIds[] = (int) $rule->id;
        }

        return response()->json([
            'message' => count($savedRuleIds).' parallel promotion rule(s) saved.',
            'rule_ids' => $savedRuleIds,
        ]);
    }

    private function assertManage(Request $request): int
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->isSuperAdmin() || $user->canAccessExactModule('scores')),
            403,
            'Only academic administrators can manage the parallel curriculum lifecycle.'
        );

        $tenantId = (int) $user->tenant_id;
        abort_unless($tenantId > 0, 403);

        abort_unless(
            $this->parallel->enabledForTenant($tenantId),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );

        return $tenantId;
    }
}
