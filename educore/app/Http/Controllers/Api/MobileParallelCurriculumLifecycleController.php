<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassGrade;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumPromotion;
use App\Models\ParallelCurriculumPromotionRule;
use App\Models\ParallelCurriculumTransfer;
use App\Services\ParallelCurriculumLifecycleService;
use App\Services\ParallelCurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileParallelCurriculumLifecycleController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
        private readonly ParallelCurriculumLifecycleService $lifecycle,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);

        $curricula = ParallelCurriculum::with([
                'classes' => fn ($query) => $query
                    ->with([
                        'arms' => fn ($armQuery) => $armQuery
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                        'classGrades',
                        'promotionRule.destinationClass',
                    ])
                    ->orderBy('sort_order')
                    ->orderBy('name'),
                'grades',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

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
            'contract_version' => 1,
            'selected_curriculum_id' => $selected?->id,
            'selected_session_id' => $sessionId ?: null,
            'curricula' => $curricula->map(fn (ParallelCurriculum $curriculum) => [
                'id' => (int) $curriculum->id,
                'name' => (string) $curriculum->name,
                'code' => $curriculum->code,
                'classes' => $curriculum->classes->map(fn (ParallelCurriculumClass $class) => [
                    'id' => (int) $class->id,
                    'name' => (string) $class->name,
                    'code' => $class->code,
                    'is_active' => (bool) $class->is_active,
                    'arms' => $class->arms->map(fn (ParallelCurriculumClassArm $arm) => [
                        'id' => (int) $arm->id,
                        'name' => (string) $arm->name,
                        'code' => $arm->code,
                        'capacity' => $arm->capacity ? (int) $arm->capacity : null,
                        'is_active' => (bool) $arm->is_active,
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
        ]);

        $curriculum = ParallelCurriculum::findOrFail($data['parallel_curriculum_id']);
        $sourceSession = AcademicSession::findOrFail($data['source_session_id']);
        $targetSession = AcademicSession::findOrFail($data['target_session_id']);

        $preview = $this->lifecycle->promotionPreview(
            $curriculum,
            $sourceSession,
            $targetSession
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
        ]);

        $result = $this->lifecycle->executePromotion(
            ParallelCurriculum::findOrFail($data['parallel_curriculum_id']),
            AcademicSession::findOrFail($data['source_session_id']),
            AcademicSession::findOrFail($data['target_session_id']),
            $request->user()?->id
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

        $arm->save();

        return response()->json(['message' => 'Parallel class arm updated.']);
    }

    public function archiveArm(Request $request, ParallelCurriculumClassArm $arm): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        abort_unless((int) $arm->tenant_id === $tenantId, 403);

        $currentSession = AcademicSession::current()->first();
        if (
            $currentSession
            && ParallelCurriculumEnrolment::where('parallel_curriculum_class_arm_id', $arm->id)
                ->where('session_id', $currentSession->id)
                ->where('is_active', true)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'arm' => 'Move current-session learners out of this arm before archiving it.',
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

    public function storePromotionRule(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'source_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
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

        $source = ParallelCurriculumClass::findOrFail($data['source_class_id']);
        if ((int) $source->parallel_curriculum_id !== (int) $data['parallel_curriculum_id']) {
            throw ValidationException::withMessages([
                'source_class_id' => 'Source class belongs to a different parallel curriculum.',
            ]);
        }

        $terminal = $request->boolean('is_terminal');
        $destinationId = $terminal ? null : ($data['destination_class_id'] ?? null);

        if (! $terminal && ! $destinationId) {
            throw ValidationException::withMessages([
                'destination_class_id' => 'Choose the next parallel class or mark the source class as terminal.',
            ]);
        }

        if ($destinationId) {
            $destination = ParallelCurriculumClass::findOrFail($destinationId);
            if ((int) $destination->parallel_curriculum_id !== (int) $source->parallel_curriculum_id) {
                throw ValidationException::withMessages([
                    'destination_class_id' => 'Destination class must belong to the same parallel curriculum.',
                ]);
            }
            if ((int) $destination->id === (int) $source->id) {
                throw ValidationException::withMessages([
                    'destination_class_id' => 'Promotion destination must be a different parallel class.',
                ]);
            }
        }

        $rule = ParallelCurriculumPromotionRule::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'source_class_id' => $source->id,
            ],
            [
                'parallel_curriculum_id' => $source->parallel_curriculum_id,
                'destination_class_id' => $destinationId,
                'minimum_average' => round((float) $data['minimum_average'], 2),
                'max_failed_subjects' => (int) $data['max_failed_subjects'],
                'require_complete_result' => $request->boolean('require_complete_result', true),
                'failure_action' => $data['failure_action'],
                'arm_strategy' => $data['arm_strategy'],
                'is_terminal' => $terminal,
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Parallel promotion rule saved.',
            'rule_id' => (int) $rule->id,
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
