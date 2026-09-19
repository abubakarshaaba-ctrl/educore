<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumArmSubjectTeacher;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassGrade;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumPromotion;
use App\Models\ParallelCurriculumPromotionRule;
use App\Models\ParallelCurriculumSubject;
use App\Models\ParallelCurriculumTransfer;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\User;
use App\Services\ParallelCurriculumLifecycleService;
use App\Services\ParallelCurriculumOperationsService;
use App\Services\ParallelCurriculumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParallelCurriculumLifecycleController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
        private readonly ParallelCurriculumLifecycleService $lifecycle,
        private readonly ParallelCurriculumOperationsService $operations,
    ) {}

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function assertManage(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $this->parallel->canManageLifecycle($user),
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

        $armTeacherOverridesReady = Schema::hasTable(
            'parallel_curriculum_arm_subject_teachers'
        );
        $armTeachingModesReady = Schema::hasColumn(
            'parallel_curriculum_class_arms',
            'teaching_assignment_mode'
        ) && Schema::hasColumn(
            'parallel_curriculum_class_arms',
            'class_teacher_id'
        );

        $curriculumRelations = [
            'classes.classGrades',
            'classes.promotionRule.destinationClass',
            'classes.subjectAssignments.subject',
            'classes.subjectAssignments.teacher',
            'grades',
        ];

        $curriculumRelations[] = $armTeacherOverridesReady
            ? 'classes.arms.subjectTeachers.teacher'
            : 'classes.arms';

        if ($armTeachingModesReady) {
            $curriculumRelations[] = 'classes.arms.classTeacher';
        }

        $curricula = ParallelCurriculum::with($curriculumRelations)
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

        $staff = User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('role', User::staffRoleNames())
            ->orderBy('name')
            ->get()
            ->filter(fn (User $person) =>
                ! $person->isAccountant()
                && ($person->canAccessExactModule('scores')
                    || $person->canAccessExactModule('scores.entry'))
            )
            ->values();

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
        $promotionSourceClassIds = collect(
                $request->input('source_class_ids', [])
            )
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

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
                $targetSession,
                $promotionSourceClassIds->all()
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
            'targetSessionId',
            'promotionSourceClassIds',
            'staff',
            'armTeacherOverridesReady',
            'armTeachingModesReady'
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

        $this->lifecycle->assertArmCapacityChange(
            $arm,
            array_key_exists('capacity', $data) && $data['capacity'] !== null
                ? (int) $data['capacity']
                : null
        );

        $arm->save();

        return back()->with('success', 'Parallel class arm updated.');
    }

    public function archiveArm(ParallelCurriculumClassArm $arm)
    {
        $this->assertManage();
        abort_unless((int) $arm->tenant_id === $this->tenantId(), 403);

        if ($this->lifecycle->armHasOperationalPlacements($arm)) {
            throw ValidationException::withMessages([
                'arm' => 'Move all current/future-session learners out of this arm before archiving it.',
            ]);
        }

        if (ParallelCurriculumTimetablePeriod::where('parallel_curriculum_class_arm_id', $arm->id)->exists()) {
            throw ValidationException::withMessages([
                'arm' => 'Remove this arm\'s timetable periods before archiving it.',
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

    public function storeArmTeacher(Request $request)
    {
        $this->assertManage();

        if (! Schema::hasTable('parallel_curriculum_arm_subject_teachers')) {
            return back()->withErrors([
                'arm_teacher' => 'Arm-specific teacher assignments are not available until the latest database migration has been applied.',
            ]);
        }

        $tenantId = $this->tenantId();
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
            ->where('parallel_curriculum_subject_id', $subject->id)
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
                    || (! $teacher->canAccessExactModule('scores')
                        && ! $teacher->canAccessExactModule('scores.entry')),
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

            return back()->with(
                'success',
                "{$subject->name} in {$class->name} {$arm->name} is now assigned to {$teacher->name}."
            );
        }

        ParallelCurriculumArmSubjectTeacher::where(
                'parallel_curriculum_class_arm_id',
                $arm->id
            )
            ->where('parallel_curriculum_subject_id', $subject->id)
            ->delete();

        $this->operations->syncTimetableTeachers(
            $tenantId,
            (int) $class->id,
            (int) $subject->id,
            (int) $arm->id,
        );

        return back()->with(
            'success',
            "{$subject->name} in {$class->name} {$arm->name} now uses the class-level default teacher."
        );
    }

    public function storeArmTeachingMode(Request $request)
    {
        $this->assertManage();

        abort_unless(
            Schema::hasColumn(
                'parallel_curriculum_class_arms',
                'teaching_assignment_mode'
            ) && Schema::hasColumn(
                'parallel_curriculum_class_arms',
                'class_teacher_id'
            ),
            503,
            'Teaching assignment modes are not available until the latest database migration has been applied.'
        );

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_class_arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'teaching_assignment_mode' => [
                'required',
                Rule::in(['class_teacher', 'subject_based']),
            ],
            'class_teacher_id' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true),
            ],
        ]);

        $arm = ParallelCurriculumClassArm::with('curriculumClass')
            ->findOrFail($data['parallel_curriculum_class_arm_id']);
        $class = $arm->curriculumClass;

        abort_unless($class, 422, 'The selected class arm is invalid.');

        $teacherId = $data['teaching_assignment_mode'] === 'class_teacher'
            ? (int) ($data['class_teacher_id'] ?? 0)
            : null;

        if ($data['teaching_assignment_mode'] === 'class_teacher' && ! $teacherId) {
            throw ValidationException::withMessages([
                'class_teacher_id' =>
                    'Select the teacher who will take all subjects in this class arm.',
            ]);
        }

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

            foreach (
                $class->subjectAssignments()
                    ->where('is_active', true)
                    ->get() as $assignment
            ) {
                $this->operations->validateTeacherChange(
                    $tenantId,
                    (int) $class->id,
                    (int) $assignment->parallel_curriculum_subject_id,
                    (int) $arm->id,
                    $teacherId
                );
            }
        }

        $arm->update([
            'teaching_assignment_mode' =>
                $data['teaching_assignment_mode'],
            'class_teacher_id' => $teacherId ?: null,
        ]);

        foreach (
            $class->subjectAssignments()
                ->where('is_active', true)
                ->get() as $assignment
        ) {
            $this->operations->syncTimetableTeachers(
                $tenantId,
                (int) $class->id,
                (int) $assignment->parallel_curriculum_subject_id,
                (int) $arm->id
            );
        }

        return back()->with(
            'success',
            $data['teaching_assignment_mode'] === 'class_teacher'
                ? "{$class->name} {$arm->name} now uses one class teacher for all subjects."
                : "{$class->name} {$arm->name} now uses subject-based teacher assignments."
        );
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
            'source_class_ids' => ['nullable', 'array', 'min:1'],
            'source_class_ids.*' => [
                'integer',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'source_class_id' => [
                'nullable',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'destination_mode' => [
                'nullable',
                Rule::in(['next_by_order', 'explicit', 'terminal']),
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
                    'A specific destination can only be used when one source class is selected. Use automatic next-level routing for multiple classes.',
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

        $saved = 0;

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

            ParallelCurriculumPromotionRule::updateOrCreate(
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

            $saved++;
        }

        return back()->with(
            'success',
            "{$saved} parallel promotion rule(s) saved."
        );
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
            'source_class_ids' => ['nullable', 'array', 'min:1'],
            'source_class_ids.*' => [
                'integer',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
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
            auth()->id(),
            collect($data['source_class_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all()
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
