<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParallelCurriculumLifecycleController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
        private readonly ParallelCurriculumLifecycleService $lifecycle,
    ) {}

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function assertManage(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->isSuperAdmin() || $user->canAccessExactModule('scores')),
            403,
            'Only academic administrators can manage the parallel curriculum lifecycle.'
        );

        abort_unless(
            $this->parallel->enabledForTenant($this->tenantId()),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );
    }

    public function index(Request $request)
    {
        $this->assertManage();

        $tenantId = $this->tenantId();

        $curricula = ParallelCurriculum::with([
            'classes.arms',
            'classes.classGrades',
            'classes.promotionRule.destinationClass',
            'grades',
        ])->orderBy('name')->get();

        $sessions = AcademicSession::orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $currentSession = $sessions->firstWhere('is_current', true) ?: $sessions->first();
        $curriculumId = (int) ($request->integer('parallel_curriculum_id')
            ?: ($curricula->first()?->id ?? 0));
        $sessionId = (int) ($request->integer('session_id')
            ?: ($currentSession?->id ?? 0));

        $selectedCurriculum = $curriculumId
            ? $curricula->firstWhere('id', $curriculumId)
            : null;

        $enrolments = ($selectedCurriculum && $sessionId)
            ? ParallelCurriculumEnrolment::with([
                    'student.currentClassArm.classLevel',
                    'curriculumClass',
                    'curriculumClassArm',
                ])
                ->where('parallel_curriculum_id', $selectedCurriculum->id)
                ->where('session_id', $sessionId)
                ->where('is_active', true)
                ->orderBy('parallel_curriculum_class_id')
                ->orderBy('student_id')
                ->get()
            : collect();

        $transfers = $selectedCurriculum
            ? ParallelCurriculumTransfer::with([
                    'student',
                    'fromClass',
                    'toClass',
                    'fromArm',
                    'toArm',
                    'session',
                ])
                ->where('parallel_curriculum_id', $selectedCurriculum->id)
                ->latest('processed_at')
                ->limit(30)
                ->get()
            : collect();

        $promotions = $selectedCurriculum
            ? ParallelCurriculumPromotion::with([
                    'student',
                    'sourceClass',
                    'destinationClass',
                    'sourceArm',
                    'destinationArm',
                    'sourceSession',
                    'targetSession',
                ])
                ->where('parallel_curriculum_id', $selectedCurriculum->id)
                ->latest('processed_at')
                ->limit(30)
                ->get()
            : collect();

        $preview = null;
        $sourceSessionId = (int) $request->integer('source_session_id');
        $targetSessionId = (int) $request->integer('target_session_id');

        if (
            $request->boolean('preview_promotion')
            && $selectedCurriculum
            && $sourceSessionId
            && $targetSessionId
        ) {
            $sourceSession = $sessions->firstWhere('id', $sourceSessionId);
            $targetSession = $sessions->firstWhere('id', $targetSessionId);

            abort_unless($sourceSession && $targetSession, 404);

            $preview = $this->lifecycle->promotionPreview(
                $selectedCurriculum,
                $sourceSession,
                $targetSession
            );
        }

        return view('parallel-curriculum.lifecycle.index', compact(
            'curricula',
            'sessions',
            'currentSession',
            'curriculumId',
            'sessionId',
            'selectedCurriculum',
            'enrolments',
            'transfers',
            'promotions',
            'preview',
            'sourceSessionId',
            'targetSessionId'
        ));
    }

    public function storeArm(Request $request)
    {
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $class = ParallelCurriculumClass::findOrFail(
            $data['parallel_curriculum_class_id']
        );

        $duplicate = ParallelCurriculumClassArm::where(
                'parallel_curriculum_class_id',
                $class->id
            )
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'This parallel class already has an arm with that name.',
            ]);
        }

        ParallelCurriculumClassArm::create([
            'tenant_id' => $tenantId,
            'parallel_curriculum_class_id' => $class->id,
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'capacity' => $data['capacity'] ?? null,
            'sort_order' => ((int) $class->arms()->max('sort_order')) + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Parallel class arm created.');
    }

    public function updateArm(Request $request, ParallelCurriculumClassArm $arm)
    {
        $this->assertManage();
        abort_unless((int) $arm->tenant_id === $this->tenantId(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $class = ParallelCurriculumClass::findOrFail(
            $arm->parallel_curriculum_class_id
        );

        $duplicate = ParallelCurriculumClassArm::where(
                'parallel_curriculum_class_id',
                $class->id
            )
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
            abort(
                423,
                'Unpublish the affected parallel/conventional result before renaming this class arm.'
            );
        }

        $arm->save();

        return back()->with('success', 'Parallel class arm updated.');
    }

    public function archiveArm(ParallelCurriculumClassArm $arm)
    {
        $this->assertManage();
        abort_unless((int) $arm->tenant_id === $this->tenantId(), 403);

        $currentSession = AcademicSession::current()->first();

        if (
            $currentSession
            && ParallelCurriculumEnrolment::where(
                'parallel_curriculum_class_arm_id',
                $arm->id
            )
                ->where('session_id', $currentSession->id)
                ->where('is_active', true)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'arm' => 'Move current-session learners out of this arm before archiving it.',
            ]);
        }

        $arm->update(['is_active' => false]);

        return back()->with('success', 'Parallel class arm archived.');
    }

    public function storeClassGrade(Request $request)
    {
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => [
                'integer',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'grade_letter' => ['required', 'string', 'max:20'],
            'min_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'gte:min_score', 'max:100'],
            'remark' => ['nullable', 'string', 'max:100'],
            'is_pass_grade' => ['nullable', 'boolean'],
            'grade_point' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $classIds = collect($data['class_ids'])->map(fn ($id) => (int) $id)
            ->unique()->values();

        $classes = ParallelCurriculumClass::whereIn('id', $classIds)->get();

        if (
            $classes->count() !== $classIds->count()
            || $classes->contains(
                fn (ParallelCurriculumClass $class) =>
                    (int) $class->parallel_curriculum_id
                    !== (int) $data['parallel_curriculum_id']
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
            $existing = ParallelCurriculumClassGrade::where(
                    'parallel_curriculum_class_id',
                    $class->id
                )
                ->where('grade_letter', $letter)
                ->first();

            $overlap = ParallelCurriculumClassGrade::where(
                    'parallel_curriculum_class_id',
                    $class->id
                )
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
                    'remark' => filled($data['remark'] ?? null)
                        ? trim($data['remark'])
                        : null,
                    'is_pass_grade' => $request->boolean('is_pass_grade', true),
                    'grade_point' => $data['grade_point'] ?? null,
                ]
            );
        }

        return back()->with(
            'success',
            "Grade {$letter} applied to {$classes->count()} parallel class level(s)."
        );
    }

    public function destroyClassGrade(ParallelCurriculumClassGrade $grade)
    {
        $this->assertManage();
        abort_unless((int) $grade->tenant_id === $this->tenantId(), 403);

        $class = ParallelCurriculumClass::findOrFail(
            $grade->parallel_curriculum_class_id
        );

        abort_if(
            $this->parallel->classStructureLocked($class),
            423,
            'Unpublish the affected result before changing this class grade system.'
        );

        $grade->delete();

        return back()->with('success', 'Class-specific parallel grade removed.');
    }

    public function storePromotionRule(Request $request)
    {
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId),
            ],
            'source_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'destination_class_id' => [
                'nullable',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'minimum_average' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_failed_subjects' => ['required', 'integer', 'min:0', 'max:50'],
            'require_complete_result' => ['nullable', 'boolean'],
            'failure_action' => ['required', Rule::in(['repeat', 'retain'])],
            'arm_strategy' => ['required', Rule::in(['same_name', 'first_available'])],
            'is_terminal' => ['nullable', 'boolean'],
        ]);

        $source = ParallelCurriculumClass::findOrFail($data['source_class_id']);
        $terminal = $request->boolean('is_terminal');

        if ((int) $source->parallel_curriculum_id !== (int) $data['parallel_curriculum_id']) {
            throw ValidationException::withMessages([
                'source_class_id' => 'Source class belongs to a different parallel curriculum.',
            ]);
        }

        $destinationId = $terminal ? null : ($data['destination_class_id'] ?? null);

        if (! $terminal && ! $destinationId) {
            throw ValidationException::withMessages([
                'destination_class_id' => 'Choose the next parallel class or mark the source class as terminal.',
            ]);
        }

        if ($destinationId) {
            $destination = ParallelCurriculumClass::findOrFail($destinationId);

            if (
                (int) $destination->parallel_curriculum_id
                !== (int) $source->parallel_curriculum_id
            ) {
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

        ParallelCurriculumPromotionRule::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'source_class_id' => $source->id,
            ],
            [
                'parallel_curriculum_id' => $source->parallel_curriculum_id,
                'destination_class_id' => $destinationId,
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

        return back()->with('success', 'Parallel promotion rule saved.');
    }

    public function executePromotion(Request $request)
    {
        $this->assertManage();

        $tenantId = $this->tenantId();
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

        $curriculum = ParallelCurriculum::findOrFail(
            $data['parallel_curriculum_id']
        );
        $sourceSession = AcademicSession::findOrFail($data['source_session_id']);
        $targetSession = AcademicSession::findOrFail($data['target_session_id']);

        $result = $this->lifecycle->executePromotion(
            $curriculum,
            $sourceSession,
            $targetSession,
            auth()->id()
        );

        return redirect()
            ->route('parallel-curriculum.lifecycle.index', [
                'parallel_curriculum_id' => $curriculum->id,
                'session_id' => $targetSession->id,
            ])
            ->with(
                'success',
                "{$result['total']} promotion decision(s) processed: ".
                "{$result['created']} new placement(s), ".
                "{$result['updated']} updated destination placement(s), ".
                "{$result['graduated']} parallel-programme graduate(s)."
            );
    }

    public function transfer(Request $request)
    {
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'enrolment_id' => [
                'required',
                Rule::exists('parallel_curriculum_enrolments', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'destination_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'destination_arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'reason' => ['required', 'string', 'max:2000'],
            'effective_date' => ['nullable', 'date'],
        ]);

        $enrolment = ParallelCurriculumEnrolment::findOrFail(
            $data['enrolment_id']
        );
        $destinationClass = ParallelCurriculumClass::findOrFail(
            $data['destination_class_id']
        );
        $destinationArm = ParallelCurriculumClassArm::findOrFail(
            $data['destination_arm_id']
        );

        $transfer = $this->lifecycle->transfer(
            $enrolment,
            $destinationClass,
            $destinationArm,
            $data['reason'],
            $data['effective_date'] ?? null,
            auth()->id()
        );

        return back()->with(
            'success',
            $transfer->movement_type === ParallelCurriculumTransfer::TYPE_INTRA_CLASS
                ? 'Intra-class arm transfer completed.'
                : 'Inter-class parallel curriculum transfer completed.'
        );
    }
}
