<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ClassLevelSubject;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumComposite;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumGrade;
use App\Models\ParallelCurriculumIntegration;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumScore;
use App\Models\ParallelCurriculumSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\ParallelCurriculumService;
use App\Services\ParallelCurriculumOperationsService;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Services\ParallelCurriculumStudentAssignmentImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParallelCurriculumController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $service,
        private readonly ParallelCurriculumStudentAssignmentImportService $assignmentImport,
        private readonly ParallelCurriculumOperationsService $operations,
    ) {}

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user && $this->service->canManageLifecycle($user);
    }

    private function assertEnabled(): void
    {
        abort_unless(
            $this->service->enabledForTenant($this->tenantId()),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );
    }

    private function assertManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only administrators can configure parallel curricula.');
    }

    private function classForTenant(int $id): ParallelCurriculumClass
    {
        return ParallelCurriculumClass::with(['curriculum', 'assessmentTemplate', 'curriculum.defaultAssessmentTemplate'])
            ->findOrFail($id);
    }

    private function assertCanEnter(
        ParallelCurriculumClass $class,
        int $subjectId,
        ?ParallelCurriculumClassArm $arm = null
    ): void {
        if ($this->canManage()) {
            return;
        }

        $assignment = ParallelCurriculumClassSubject::where(
                'parallel_curriculum_class_id',
                $class->id
            )
            ->where('parallel_curriculum_subject_id', $subjectId)
            ->where('is_active', true)
            ->first();

        $allowed = $assignment
            && (int) $this->service->effectiveTeacherId($assignment, $arm)
                === (int) auth()->id();

        abort_unless(
            $allowed,
            403,
            'You are not assigned to this parallel-curriculum class arm and subject.'
        );
    }

    public function index()
    {
        $this->assertEnabled();

        $tenantId = $this->tenantId();
        $canManage = $this->canManage();
        $currentSession = AcademicSession::current()->first();
        $currentTerm = Term::current()->with('session')->first();

        $armLifecycleReady = Schema::hasTable('parallel_curriculum_class_arms')
            && Schema::hasTable('parallel_curriculum_enrolments')
            && Schema::hasColumn(
                'parallel_curriculum_enrolments',
                'parallel_curriculum_class_arm_id'
            );

        // Result/lifecycle migrations were introduced after the original
        // parallel-curriculum schema. Keep the workspace renderable while a
        // shared-host deployment is reconciling an older database instead of
        // throwing a 500 merely because one optional table is still pending.
        $subjectSchemaReady = Schema::hasTable('parallel_curriculum_subjects')
            && Schema::hasTable('parallel_curriculum_class_subjects')
            && Schema::hasColumn(
                'parallel_curriculum_class_subjects',
                'parallel_curriculum_subject_id'
            );
        $gradingReady = Schema::hasTable('parallel_curriculum_grades');
        $integrationReady = Schema::hasTable('parallel_curriculum_integrations');
        $enrolmentReady = Schema::hasTable('parallel_curriculum_enrolments');
        $compositeReady = Schema::hasTable('parallel_curriculum_composites');
        $reportPublicationReady = Schema::hasTable('parallel_curriculum_report_publications');
        $armTeacherReady = Schema::hasTable('parallel_curriculum_arm_subject_teachers');

        $schemaReconciliationPending = ! $subjectSchemaReady
            || ! $gradingReady
            || ! $armLifecycleReady
            || ! $reportPublicationReady
            || ! $armTeacherReady;

        $curriculumRelations = [
            'defaultAssessmentTemplate',
            'classes.assessmentTemplate',
            'classes.subjectAssignments.teacher',
        ];

        if ($subjectSchemaReady) {
            $curriculumRelations[] = 'subjects';
            $curriculumRelations[] = 'classes.subjectAssignments.subject';
        }

        if ($gradingReady) {
            $curriculumRelations[] = 'grades';
        }

        if ($armLifecycleReady) {
            $curriculumRelations[] = 'classes.arms';
        }

        $curricula = ParallelCurriculum::with($curriculumRelations)
            ->orderBy('name')
            ->get();

        if (! $subjectSchemaReady) {
            $curricula->each(function (ParallelCurriculum $curriculum): void {
                $curriculum->setRelation('subjects', collect());

                $curriculum->classes->each(function (ParallelCurriculumClass $class): void {
                    $class->subjectAssignments->each(
                        fn ($assignment) => $assignment->setRelation('subject', null)
                    );
                });
            });
        }

        if (! $gradingReady) {
            $curricula->each(
                fn (ParallelCurriculum $curriculum) =>
                    $curriculum->setRelation('grades', collect())
            );
        }

        $workspaces = $curricula->flatMap(
            function (ParallelCurriculum $curriculum) use (
                $canManage,
                $armLifecycleReady,
                $currentTerm
            ) {
                return $curriculum->classes
                    ->where('is_active', true)
                    ->flatMap(
                        function (ParallelCurriculumClass $class) use (
                            $curriculum,
                            $canManage,
                            $armLifecycleReady,
                            $currentTerm
                        ) {
                            $arms = $armLifecycleReady
                                ? $class->arms->where('is_active', true)->values()
                                : collect();

                            if ($arms->isEmpty()) {
                                $arms = collect([null]);
                            }

                            return $arms->flatMap(
                                function ($arm) use (
                                    $class,
                                    $curriculum,
                                    $canManage,
                                    $currentTerm
                                ) {
                                    return $class->subjectAssignments
                                        ->where('is_active', true)
                                        ->map(
                                            function ($assignment) use (
                                                $arm,
                                                $class,
                                                $curriculum,
                                                $canManage,
                                                $currentTerm
                                            ) {
                                                $effectiveTeacherId =
                                                    $this->service->effectiveTeacherId(
                                                        $assignment,
                                                        $arm
                                                    );

                                                if (
                                                    ! $canManage
                                                    && (int) $effectiveTeacherId
                                                        !== (int) auth()->id()
                                                ) {
                                                    return null;
                                                }

                                                $scoreSheetUrl = '#';
                                                if ($currentTerm) {
                                                    $routeParameters = [
                                                        'class_id' => $class->id,
                                                        'subject_id' => $assignment->parallel_curriculum_subject_id,
                                                        'term_id' => $currentTerm->id,
                                                    ];

                                                    if ($arm) {
                                                        $routeParameters['arm_id'] = $arm->id;
                                                    }

                                                    $scoreSheetUrl = route(
                                                        'parallel-curriculum.score-sheet',
                                                        $routeParameters
                                                    );
                                                }

                                                return [
                                                    'curriculum' => $curriculum,
                                                    'class' => $class,
                                                    'arm' => $arm,
                                                    'assignment' => $assignment,
                                                    'score_sheet_url' => $scoreSheetUrl,
                                                    'subject_name' => $assignment->subject
                                                        ? $assignment->subject->name
                                                        : 'Subject',
                                                    'class_label' => $class->name
                                                        . ($arm ? ' '.$arm->name : ''),
                                                    'curriculum_name' => $curriculum->name,
                                                    'effective_teacher_id' =>
                                                        $effectiveTeacherId,
                                                    'effective_teacher_name' =>
                                                        $effectiveTeacherId
                                                            ? User::whereKey(
                                                                $effectiveTeacherId
                                                            )->value('name')
                                                            : null,
                                                ];
                                            }
                                        )
                                        ->filter();
                                }
                            );
                        }
                    );
            }
        )->values();

        $templates = $canManage
            ? AssessmentTemplate::with('components')
                ->where('status', AssessmentTemplate::STATUS_ACTIVE)
                ->orderBy('name')
                ->get()
            : collect();

        $conventionalSubjects = $canManage
            ? Subject::where('is_active', true)->orderBy('name')->get()
            : collect();

        $classLevels = $canManage
            ? ClassLevel::orderBy('order_index')->orderBy('name')->get()
            : collect();

        $staff = $canManage
            ? User::where('tenant_id', $tenantId)
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
                ->values()
            : collect();

        $integrations = ($canManage && $integrationReady)
            ? ParallelCurriculumIntegration::with([
                    'curriculum',
                    'destinationClassLevel',
                    'destinationSubject',
                ])
                ->orderBy('parallel_curriculum_id')
                ->orderBy('destination_class_level_id')
                ->get()
            : collect();

        $enrolments = collect();
        if ($canManage && $currentSession && $enrolmentReady) {
            $enrolmentRelations = [
                'student.currentClassArm.classLevel',
                'curriculumClass',
                'curriculum',
            ];

            if ($armLifecycleReady) {
                $enrolmentRelations[] = 'curriculumClassArm';
            }

            $enrolments = ParallelCurriculumEnrolment::with($enrolmentRelations)
                ->where('session_id', $currentSession->id)
                ->where('is_active', true)
                ->orderBy('parallel_curriculum_class_id')
                ->get();
        }

        $recentComposites = ($currentTerm && $compositeReady)
            ? ParallelCurriculumComposite::with([
                    'student',
                    'curriculum',
                    'curriculumClass',
                    'destinationSubject',
                ])
                ->where('term_id', $currentTerm->id)
                ->latest('computed_at')
                ->limit(30)
                ->get()
            : collect();

        return view('parallel-curriculum.index', compact(
            'canManage',
            'currentSession',
            'currentTerm',
            'curricula',
            'workspaces',
            'templates',
            'conventionalSubjects',
            'classLevels',
            'staff',
            'integrations',
            'enrolments',
            'recentComposites',
            'armLifecycleReady',
            'schemaReconciliationPending'
        ));
    }

    public function studentAssignments(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $currentSession = AcademicSession::current()->first();

        $sessionId = (int) ($request->integer('session_id') ?: ($currentSession?->id ?? 0));
        $session = $sessionId
            ? AcademicSession::where('tenant_id', $tenantId)->findOrFail($sessionId)
            : null;

        $curricula = ParallelCurriculum::with([
            'classes' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['arms' => fn ($armQuery) => $armQuery
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name'),
        ])->where('is_active', true)->orderBy('name')->get();

        $curriculumId = (int) ($request->integer('parallel_curriculum_id') ?: ($curricula->first()?->id ?? 0));
        $selectedCurriculum = $curriculumId
            ? $curricula->firstWhere('id', $curriculumId)
            : null;

        abort_if($curriculumId && ! $selectedCurriculum, 404, 'The selected parallel curriculum is unavailable.');

        $classArms = ClassArm::with('classLevel')
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();

        $conventionalClassArmId = $request->integer('conventional_class_arm_id');
        $parallelClassId = $request->integer('parallel_class_id');
        $gender = trim((string) $request->query('gender', ''));
        $assignmentStatus = trim((string) $request->query('assignment_status', 'all'));
        $search = trim((string) $request->query('q', ''));

        if ($parallelClassId) {
            abort_unless(
                $selectedCurriculum?->classes->contains('id', $parallelClassId),
                422,
                'The selected source parallel class does not belong to this programme.'
            );
        }

        $assignedStudentIds = collect();
        $parallelClassStudentIds = collect();
        if ($session && $selectedCurriculum) {
            $programmeEnrolments = ParallelCurriculumEnrolment::where('parallel_curriculum_id', $selectedCurriculum->id)
                ->where('session_id', $session->id)
                ->where('is_active', true);

            $assignedStudentIds = (clone $programmeEnrolments)->pluck('student_id');

            if ($parallelClassId) {
                $parallelClassStudentIds = (clone $programmeEnrolments)
                    ->where('parallel_curriculum_class_id', $parallelClassId)
                    ->pluck('student_id');
            }
        }

        $studentsQuery = Student::active()
            ->with(['currentClassArm.classLevel'])
            ->when($conventionalClassArmId, function ($query) use ($conventionalClassArmId, $session): void {
                $query->where(function ($studentQuery) use ($conventionalClassArmId, $session): void {
                    $studentQuery->where('current_class_arm_id', $conventionalClassArmId);

                    if ($session) {
                        $studentQuery->orWhereHas('enrollments', fn ($enrolmentQuery) => $enrolmentQuery
                            ->where('session_id', $session->id)
                            ->where('class_arm_id', $conventionalClassArmId));
                    }
                });
            })
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
                $parallelClassId && $selectedCurriculum,
                fn ($query) => $query->whereIn('id', $parallelClassStudentIds)
            )
            ->when(
                $assignmentStatus === 'assigned' && $selectedCurriculum,
                fn ($query) => $query->whereIn('id', $assignedStudentIds)
            )
            ->when(
                $assignmentStatus === 'unassigned' && $selectedCurriculum,
                fn ($query) => $query->whereNotIn('id', $assignedStudentIds)
            )
            ->orderBy('last_name')
            ->orderBy('first_name');

        $students = $studentsQuery->paginate(100)->withQueryString();

        $activeAssignments = collect();
        if ($session && $selectedCurriculum && $students->isNotEmpty()) {
            $activeAssignments = ParallelCurriculumEnrolment::with(['curriculumClass', 'curriculumClassArm'])
                ->where('parallel_curriculum_id', $selectedCurriculum->id)
                ->where('session_id', $session->id)
                ->where('is_active', true)
                ->whereIn('student_id', $students->getCollection()->pluck('id'))
                ->get()
                ->keyBy('student_id');
        }

        $sessions = AcademicSession::where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        return view('parallel-curriculum.student-assignments', compact(
            'curricula',
            'selectedCurriculum',
            'curriculumId',
            'sessions',
            'session',
            'sessionId',
            'classArms',
            'conventionalClassArmId',
            'parallelClassId',
            'gender',
            'assignmentStatus',
            'search',
            'students',
            'activeAssignments'
        ));
    }

    public function storeCurriculum(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'default_assessment_template_id' => [
                'required',
                Rule::exists('assessment_templates', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        ParallelCurriculum::create([
            'tenant_id' => $tenantId,
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'default_assessment_template_id' => $data['default_assessment_template_id'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Parallel curriculum created.');
    }

    public function updateCurriculum(Request $request, ParallelCurriculum $curriculum)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $curriculum->tenant_id === $this->tenantId(), 403);

        $tenantId = $this->tenantId();
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
            return back()->with('success', 'Parallel curriculum is already up to date.');
        }

        abort_if(
            ParallelCurriculumReportPublication::where('parallel_curriculum_id', $curriculum->id)
                ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
                ->exists(),
            423,
            'Unpublish this programme\'s results before editing its programme details.'
        );

        if ($curriculum->isDirty('default_assessment_template_id')) {
            abort_if(
                ParallelCurriculumScore::where('parallel_curriculum_id', $curriculum->id)->exists(),
                423,
                'The default assessment template cannot be changed after parallel scores have been recorded. Create a new programme or clear the unpublished scores first.'
            );
        }

        $curriculum->save();

        return back()->with('success', 'Parallel curriculum updated.');
    }

    public function storeSubject(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_id' => ['required', Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
        ]);

        ParallelCurriculumSubject::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => $data['parallel_curriculum_id'],
                'name' => trim($data['name']),
            ],
            [
                'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Parallel curriculum subject saved.');
    }

    public function updateSubject(Request $request, ParallelCurriculumSubject $subject)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $subject->tenant_id === $this->tenantId(), 403);

        $tenantId = $this->tenantId();
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
            return back()->with('success', 'Parallel curriculum subject is already up to date.');
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

        return back()->with('success', 'Parallel curriculum subject updated.');
    }

    public function storeGrade(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
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
            ParallelCurriculumReportPublication::where('parallel_curriculum_id', $curriculumId)
                ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
                ->exists(),
            423,
            'Unpublish this programme\'s results before changing its grading scale.'
        );

        $gradeLetter = strtoupper(trim($data['grade_letter']));
        $existing = ParallelCurriculumGrade::where('parallel_curriculum_id', $curriculumId)
            ->where('grade_letter', $gradeLetter)
            ->first();

        $overlap = ParallelCurriculumGrade::where('parallel_curriculum_id', $curriculumId)
            ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
            ->where('min_score', '<=', (float) $data['max_score'])
            ->where('max_score', '>=', (float) $data['min_score'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'min_score' => 'This score range overlaps another grade in the selected parallel curriculum.',
            ]);
        }

        ParallelCurriculumGrade::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => $curriculumId,
                'grade_letter' => $gradeLetter,
            ],
            [
                'min_score' => round((float) $data['min_score'], 2),
                'max_score' => round((float) $data['max_score'], 2),
                'remark' => filled($data['remark'] ?? null) ? trim($data['remark']) : null,
                'is_pass_grade' => $request->boolean('is_pass_grade', true),
            ]
        );

        return back()->with('success', "Grade {$gradeLetter} saved.");
    }

    public function destroyGrade(ParallelCurriculumGrade $grade)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $grade->tenant_id === $this->tenantId(), 403);

        abort_if(
            ParallelCurriculumReportPublication::where('parallel_curriculum_id', $grade->parallel_curriculum_id)
                ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
                ->exists(),
            423,
            'Unpublish this programme\'s results before changing its grading scale.'
        );

        $grade->delete();

        return back()->with('success', 'Parallel curriculum grade removed.');
    }

    public function storeClass(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_id' => ['required', Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'assessment_template_id' => ['nullable', Rule::exists('assessment_templates', 'id')->where('tenant_id', $tenantId)],
        ]);

        $class = DB::transaction(function () use ($tenantId, $data): ParallelCurriculumClass {
            $class = ParallelCurriculumClass::create([
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => $data['parallel_curriculum_id'],
                'assessment_template_id' => $data['assessment_template_id'] ?? null,
                'name' => trim($data['name']),
                'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
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

        return back()->with(
            'success',
            "Parallel curriculum class level {$class->name} created with default Arm A."
        );
    }

    public function updateClass(Request $request, ParallelCurriculumClass $class)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $class->tenant_id === $this->tenantId(), 403);

        $tenantId = $this->tenantId();
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
            return back()->with('success', 'Parallel curriculum class is already up to date.');
        }

        abort_if(
            $this->service->classStructureLocked($class),
            423,
            'Unpublish this class\'s parallel results before editing its class details.'
        );

        if ($class->isDirty('assessment_template_id')) {
            abort_if(
                ParallelCurriculumScore::where('parallel_curriculum_class_id', $class->id)->exists(),
                423,
                'The class assessment template cannot be changed after parallel scores have been recorded. Clear the unpublished scores first or create a new class.'
            );
        }

        $class->save();

        return back()->with('success', 'Parallel curriculum class updated.');
    }

    public function storeClassSubject(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'parallel_curriculum_subject_id' => ['required', Rule::exists('parallel_curriculum_subjects', 'id')->where('tenant_id', $tenantId)],
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        $class = $this->classForTenant((int) $data['parallel_curriculum_class_id']);
        $parallelSubject = ParallelCurriculumSubject::findOrFail($data['parallel_curriculum_subject_id']);
        abort_unless(
            (int) $parallelSubject->parallel_curriculum_id === (int) $class->parallel_curriculum_id,
            422,
            'The selected subject belongs to a different parallel curriculum.'
        );

        if (! empty($data['teacher_id'])) {
            $teacher = User::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->findOrFail($data['teacher_id']);

            abort_unless(
                ! $teacher->isAccountant()
                    && ($teacher->canAccessExactModule('scores') || $teacher->canAccessExactModule('scores.entry')),
                422,
                'The selected staff member does not have academic score-entry permission.'
            );
        }

        $existingAssignment = ParallelCurriculumClassSubject::where(
                'parallel_curriculum_class_id',
                $class->id
            )
            ->where('parallel_curriculum_subject_id', $parallelSubject->id)
            ->first();

        $structureChanged = ! $existingAssignment || ! $existingAssignment->is_active;

        if (
            $this->service->classStructureLocked($class)
            && $structureChanged
        ) {
            abort(
                423,
                'This parallel class has published results. Unpublish them before adding or reactivating subjects.'
            );
        }

        $this->operations->validateTeacherChange(
            $tenantId,
            (int) $class->id,
            (int) $parallelSubject->id,
            null,
            ! empty($data['teacher_id']) ? (int) $data['teacher_id'] : null,
        );

        ParallelCurriculumClassSubject::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'parallel_curriculum_class_id' => $class->id,
                'parallel_curriculum_subject_id' => $parallelSubject->id,
            ],
            [
                'teacher_id' => $data['teacher_id'] ?? null,
                'is_active' => true,
            ]
        );

        $this->operations->syncTimetableTeachers(
            $tenantId,
            (int) $class->id,
            (int) $parallelSubject->id,
        );

        if ($structureChanged) {
            $this->service->reconcileClassStructure($class);
        }

        return back()->with(
            'success',
            $structureChanged
                ? 'Subject assigned and dependent parallel composites refreshed.'
                : 'Subject teacher assignment updated.'
        );
    }

    public function destroyClassSubject(ParallelCurriculumClassSubject $assignment)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $assignment->tenant_id === $this->tenantId(), 403);

        $class = $this->classForTenant((int) $assignment->parallel_curriculum_class_id);
        abort_if(
            ParallelCurriculumTimetablePeriod::where('parallel_curriculum_class_id', $class->id)
                ->where('parallel_curriculum_subject_id', $assignment->parallel_curriculum_subject_id)
                ->exists(),
            423,
            'Remove this subject from the parallel timetable before removing it from the class.'
        );
        abort_if(
            $this->service->classStructureLocked($class),
            423,
            'This parallel class has published results. Unpublish them before removing subjects from its result structure.'
        );

        $hasScores = ParallelCurriculumScore::where('parallel_curriculum_class_id', $assignment->parallel_curriculum_class_id)
            ->where('parallel_curriculum_subject_id', $assignment->parallel_curriculum_subject_id)
            ->exists();

        if ($hasScores) {
            $assignment->update(['is_active' => false]);
            $this->service->reconcileClassStructure($class);

            return back()->with(
                'success',
                'Subject archived and dependent parallel composites refreshed because result records already exist.'
            );
        }

        $assignment->delete();
        $this->service->reconcileClassStructure($class);

        return back()->with(
            'success',
            'Subject removed and dependent parallel composites refreshed.'
        );
    }

    public function downloadStudentAssignmentTemplate()
    {
        $this->assertEnabled();
        $this->assertManage();

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['admission_number', 'parallel_class', 'parallel_arm']);
            fputcsv($handle, ['STU001', 'Mutawassitah 1', 'A']);
            fputcsv($handle, ['STU002', 'Mutawassitah 2', 'B']);
            fclose($handle);
        }, 'parallel_curriculum_student_assignment_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importStudentAssignments(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
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
        ]);

        $result = $this->assignmentImport->import(
            $tenantId,
            (int) $data['parallel_curriculum_id'],
            (int) $data['session_id'],
            $request->file('assignment_file')
        );

        return back()->with(
            'success',
            $result['count'].' student assignment(s) imported successfully for '.
                $result['curriculum_name'].'.'
        );
    }

    public function assignStudents(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'parallel_curriculum_class_arm_id' => ['required', Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId)],
            'session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId)],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->where('tenant_id', $tenantId)],
        ]);

        $class = $this->classForTenant((int) $data['parallel_curriculum_class_id']);
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

        $studentIds = collect($data['student_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $students = Student::active()
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        $inactiveStudentIds = $studentIds->diff($students->keys()->map(fn ($id) => (int) $id));
        if ($inactiveStudentIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'student_ids' => ['Only active students can be assigned to a parallel curriculum class.'],
            ]);
        }

        $existingEnrolments = ParallelCurriculumEnrolment::where('parallel_curriculum_id', $class->parallel_curriculum_id)
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

            if ($existing && $this->service->enrolmentPlacementLocked($existing)) {
                $placementErrors[] = "{$studentLabel} cannot be moved because the current parallel-class result is published.";
                continue;
            }

            $destinationLocked ??= $this->service->classPlacementLocked($class, (int) $data['session_id']);
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
                        "The selected arm has capacity for ".max(0, (int) $arm->capacity - $existingInArm)." additional learner(s).",
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

        return back()->with(
            'success',
            $studentIds->count().
            ' student parallel placement(s) updated. Existing students in this programme were moved to the selected parallel class arm.'
        );
    }

    public function destroyEnrolment(ParallelCurriculumEnrolment $enrolment)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $enrolment->tenant_id === $this->tenantId(), 403);

        abort_if(
            $this->service->enrolmentPlacementLocked($enrolment),
            423,
            'This student placement belongs to a published parallel result. Unpublish the class result before removing or moving the student.'
        );

        $hasScores = ParallelCurriculumScore::where('parallel_curriculum_id', $enrolment->parallel_curriculum_id)
            ->where('student_id', $enrolment->student_id)
            ->where('session_id', $enrolment->session_id)
            ->exists();

        if ($hasScores) {
            $enrolment->update(['is_active' => false]);

            return back()->with('success', 'Student assignment archived because result records already exist.');
        }

        $enrolment->delete();

        return back()->with('success', 'Student removed from the parallel curriculum class.');
    }

    public function storeIntegration(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true),
            ],
            'destination_class_level_ids' => ['required', 'array', 'min:1'],
            'destination_class_level_ids.*' => [
                'integer',
                Rule::exists('class_levels', 'id')->where('tenant_id', $tenantId),
            ],
            'destination_subject_id' => [
                'required',
                Rule::exists('subjects', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true),
            ],
            'minimum_completed_subjects' => ['nullable', 'integer', 'min:1', 'max:50'],
            'require_all_subjects' => ['nullable', 'boolean'],
            'auto_sync' => ['nullable', 'boolean'],
        ]);

        $classLevelIds = collect($data['destination_class_level_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $levels = ClassLevel::with('classArms')
            ->whereIn('id', $classLevelIds)
            ->get()
            ->keyBy('id');

        $mappingErrors = [];
        foreach ($classLevelIds as $classLevelId) {
            $level = $levels->get($classLevelId);
            if (! $level) {
                continue;
            }

            $hasCurriculumRules = ClassLevelSubject::where('class_level_id', $level->id)
                ->where('is_active', true)
                ->exists();

            if (! $hasCurriculumRules) {
                continue;
            }

            if ($level->classArms->isEmpty()) {
                $subjectOffered = ClassLevelSubject::where('class_level_id', $level->id)
                    ->where('subject_id', $data['destination_subject_id'])
                    ->where('is_active', true)
                    ->where('subject_status', '!=', 'not_offered')
                    ->exists();

                if (! $subjectOffered) {
                    $mappingErrors[] = "{$level->name}: the selected destination subject is not offered.";
                }

                continue;
            }

            $invalidArms = $level->classArms->filter(
                fn (ClassArm $arm) => ! $this->service->conventionalSubjectAvailableForArm(
                    $tenantId,
                    (int) $data['destination_subject_id'],
                    $arm
                )
            );

            if ($invalidArms->isNotEmpty()) {
                $mappingErrors[] = "{$level->name}: the selected destination subject is unavailable in ".
                    $invalidArms->map(fn (ClassArm $arm) => $arm->full_name)->join(', ').'.';
            }
        }

        if ($mappingErrors !== []) {
            throw ValidationException::withMessages([
                'destination_subject_id' => $mappingErrors,
            ]);
        }

        $mappingAttributes = [
            'destination_subject_id' => (int) $data['destination_subject_id'],
            'calculation_method' => 'arithmetic_mean',
            'require_all_subjects' => $request->boolean('require_all_subjects', true),
            'minimum_completed_subjects' => (int) ($data['minimum_completed_subjects'] ?? 1),
            'auto_sync' => $request->boolean('auto_sync', true),
            'is_active' => true,
        ];

        $existingByLevel = ParallelCurriculumIntegration::where(
                'parallel_curriculum_id',
                $data['parallel_curriculum_id']
            )
            ->whereIn('destination_class_level_id', $classLevelIds)
            ->get()
            ->keyBy('destination_class_level_id');

        $lockedChanges = [];
        foreach ($classLevelIds as $classLevelId) {
            $existing = $existingByLevel->get($classLevelId);
            if (! $existing) {
                continue;
            }

            $configurationChanged =
                (int) $existing->destination_subject_id !== $mappingAttributes['destination_subject_id']
                || (bool) $existing->require_all_subjects !== $mappingAttributes['require_all_subjects']
                || (int) $existing->minimum_completed_subjects !== $mappingAttributes['minimum_completed_subjects']
                || (bool) $existing->auto_sync !== $mappingAttributes['auto_sync']
                || ! $existing->is_active;

            if (
                $configurationChanged
                && $this->service->integrationHasPublishedDependencies($existing)
            ) {
                $lockedChanges[] = $levels->get($classLevelId)?->name ?: "Class level #{$classLevelId}";
            }
        }

        if ($lockedChanges !== []) {
            throw ValidationException::withMessages([
                'destination_class_level_ids' => [
                    'Unpublish the conventional report card before changing the parallel result mapping for: '.
                    implode(', ', $lockedChanges).'.',
                ],
            ]);
        }

        $savedIntegrations = collect();

        DB::transaction(function () use (
            $classLevelIds,
            $tenantId,
            $data,
            $mappingAttributes,
            $existingByLevel,
            $savedIntegrations
        ): void {
            foreach ($classLevelIds as $classLevelId) {
                $existing = $existingByLevel->get($classLevelId);

                $configurationChanged = ! $existing
                    || (int) $existing->destination_subject_id !== $mappingAttributes['destination_subject_id']
                    || (bool) $existing->require_all_subjects !== $mappingAttributes['require_all_subjects']
                    || (int) $existing->minimum_completed_subjects !== $mappingAttributes['minimum_completed_subjects']
                    || (bool) $existing->auto_sync !== $mappingAttributes['auto_sync']
                    || ! $existing->is_active;

                $integration = ParallelCurriculumIntegration::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'parallel_curriculum_id' => $data['parallel_curriculum_id'],
                        'destination_class_level_id' => $classLevelId,
                    ],
                    $mappingAttributes
                );

                $savedIntegrations->push([
                    'integration' => $integration,
                    'changed' => $configurationChanged,
                ]);
            }
        });

        foreach ($savedIntegrations as $saved) {
            if ($saved['changed']) {
                $this->service->reconcileIntegration($saved['integration'], true);
            }
        }

        $curriculum = ParallelCurriculum::findOrFail($data['parallel_curriculum_id']);
        $currentTerm = Term::current()->first();
        if ($currentTerm) {
            // New mappings have no existing composite rows to reconcile.
            // Refreshing the current term materializes them immediately while
            // still respecting each mapping's auto_sync flag.
            $this->service->syncCurriculum($curriculum, $currentTerm, false);
        }

        return back()->with(
            'success',
            'Conventional result integration rule saved and current result mappings refreshed.'
        );
    }

    public function destroyIntegration(ParallelCurriculumIntegration $integration)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $integration->tenant_id === $this->tenantId(), 403);

        if (! $integration->is_active) {
            return back()->with('success', 'This conventional result mapping is already inactive.');
        }

        $result = $this->service->deactivateIntegration($integration);

        $message = 'Conventional result mapping removed.';
        if ($result['cleared'] > 0) {
            $message .= " {$result['cleared']} unpublished derived score set(s) were cleared.";
        }
        if ($result['preserved'] > 0) {
            $message .= " {$result['preserved']} published derived score set(s) were preserved for result integrity.";
        }

        return back()->with('success', $message);
    }

    public function scoreSheet(Request $request)
    {
        $this->assertEnabled();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', Rule::exists('parallel_curriculum_subjects', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
            'arm_id' => ['nullable', Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId)],
        ]);

        $class = $this->classForTenant((int) $data['class_id']);
        abort_unless(
            $class->is_active && $class->curriculum?->is_active,
            422,
            'Score entry is unavailable because this parallel curriculum class or programme is inactive.'
        );
        $term = Term::with('session')->findOrFail($data['term_id']);
        $subject = ParallelCurriculumSubject::findOrFail($data['subject_id']);
        $arm = ! empty($data['arm_id'])
            ? ParallelCurriculumClassArm::where('parallel_curriculum_class_id', $class->id)
                ->where('is_active', true)
                ->findOrFail($data['arm_id'])
            : null;

        $this->assertCanEnter($class, (int) $data['subject_id'], $arm);

        abort_unless(
            (int) $subject->parallel_curriculum_id === (int) $class->parallel_curriculum_id
                && ParallelCurriculumClassSubject::where('parallel_curriculum_class_id', $class->id)
                ->where('parallel_curriculum_subject_id', $subject->id)
                ->where('is_active', true)
                ->exists(),
            422,
            'This subject is not active in the selected parallel curriculum class.'
        );

        $components = $this->service->componentsForClass($class);
        $template = $this->service->templateForClass($class);

        $enrolments = ParallelCurriculumEnrolment::with(['student', 'curriculumClassArm'])
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->when($arm, fn ($query) => $query->where('parallel_curriculum_class_arm_id', $arm->id))
            ->get()
            ->sortBy(fn ($enrolment) => strtolower($enrolment->student?->last_name.' '.$enrolment->student?->first_name))
            ->values();

        $scores = ParallelCurriculumScore::where('parallel_curriculum_class_id', $class->id)
            ->where('parallel_curriculum_subject_id', $subject->id)
            ->where('term_id', $term->id)
            ->whereIn('student_id', $enrolments->pluck('student_id'))
            ->get()
            ->keyBy(fn ($score) => $score->student_id.':'.$score->assessment_template_component_id);

        $lockedStudents = $enrolments->mapWithKeys(fn (ParallelCurriculumEnrolment $enrolment) => [
            (int) $enrolment->student_id => $this->service->scoreEntryLocked($enrolment, $term),
        ]);

        return view('parallel-curriculum.score-sheet', compact(
            'class', 'arm', 'subject', 'term', 'template', 'components', 'enrolments', 'scores', 'lockedStudents'
        ));
    }

    public function saveScores(Request $request)
    {
        $this->assertEnabled();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', Rule::exists('parallel_curriculum_subjects', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
            'arm_id' => ['nullable', Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId)],
            'scores' => ['required', 'array'],
        ]);

        $class = $this->classForTenant((int) $data['class_id']);
        abort_unless(
            $class->is_active && $class->curriculum?->is_active,
            422,
            'Score entry is unavailable because this parallel curriculum class or programme is inactive.'
        );
        $parallelSubject = ParallelCurriculumSubject::findOrFail($data['subject_id']);
        abort_unless(
            (int) $parallelSubject->parallel_curriculum_id === (int) $class->parallel_curriculum_id
                && ParallelCurriculumClassSubject::where('parallel_curriculum_class_id', $class->id)
                    ->where('parallel_curriculum_subject_id', $parallelSubject->id)
                    ->where('is_active', true)
                    ->exists(),
            422,
            'This subject is not active in the selected parallel curriculum class.'
        );
        $term = Term::findOrFail($data['term_id']);
        $arm = ! empty($data['arm_id'])
            ? ParallelCurriculumClassArm::where('parallel_curriculum_class_id', $class->id)
                ->where('is_active', true)
                ->findOrFail($data['arm_id'])
            : null;

        $this->assertCanEnter($class, (int) $data['subject_id'], $arm);

        $components = $this->service->componentsForClass($class)->keyBy('id');

        abort_if($components->isEmpty(), 422, 'Assign an assessment template to this parallel curriculum class first.');

        $enrolments = ParallelCurriculumEnrolment::where('parallel_curriculum_class_id', $class->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->when($arm, fn ($query) => $query->where('parallel_curriculum_class_arm_id', $arm->id))
            ->get()
            ->keyBy('student_id');

        $errors = [];
        foreach ($data['scores'] as $studentId => $componentValues) {
            if (! $enrolments->has((int) $studentId) || ! is_array($componentValues)) {
                $errors["scores.{$studentId}"][] = 'Student is not active in this parallel curriculum class.';
                continue;
            }

            $enrolment = $enrolments->get((int) $studentId);
            if ($this->service->scoreEntryLocked($enrolment, $term)) {
                $errors["scores.{$studentId}"][] =
                    'A published result depends on this student\'s parallel source scores. Unpublish the relevant result before changing them.';
                continue;
            }

            foreach ($componentValues as $componentId => $value) {
                $component = $components->get((int) $componentId);
                if (! $component) {
                    $errors["scores.{$studentId}.{$componentId}"][] = 'Invalid assessment component.';
                    continue;
                }
                if ($value === null || $value === '') {
                    continue;
                }
                if (! is_numeric($value) || (float) $value < 0 || (float) $value > (float) $component->weight_percentage) {
                    $errors["scores.{$studentId}.{$componentId}"][] =
                        "Enter a score from 0 to {$component->weight_percentage}.";
                }
            }
        }

        if ($errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($data, $class, $term, $components, $enrolments, $tenantId): void {
            foreach ($data['scores'] as $studentId => $componentValues) {
                $enrolment = $enrolments->get((int) $studentId);
                foreach ($componentValues as $componentId => $value) {
                    $component = $components->get((int) $componentId);

                    $key = [
                        'tenant_id' => $tenantId,
                        'parallel_curriculum_id' => $class->parallel_curriculum_id,
                        'student_id' => (int) $studentId,
                        'parallel_curriculum_subject_id' => (int) $data['subject_id'],
                        'assessment_template_component_id' => $component->id,
                        'term_id' => $term->id,
                    ];

                    if ($value === null || $value === '') {
                        ParallelCurriculumScore::where($key)->delete();
                        continue;
                    }

                    ParallelCurriculumScore::updateOrCreate($key, [
                        'parallel_curriculum_class_id' => $class->id,
                        'session_id' => $term->session_id,
                        'entered_by' => auth()->id(),
                        'score' => round(min((float) $value, (float) $component->weight_percentage), 2),
                        'entered_at' => now(),
                    ]);
                }
            }
        });

        foreach ($enrolments as $enrolment) {
            $this->service->syncStudent($enrolment, $term, false);
        }

        return back()->with(
            'success',
            'Parallel curriculum scores saved. Programme composites were refreshed and conventional synchronization followed the configured auto-sync rules.'
        );
    }

    public function sync(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_id' => ['required', Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
        ]);

        $curriculum = ParallelCurriculum::findOrFail($data['parallel_curriculum_id']);
        $term = Term::findOrFail($data['term_id']);
        $result = $this->service->syncCurriculum($curriculum, $term, true);

        return back()->with(
            'success',
            "Parallel results synchronized: {$result['synced']} synced, {$result['pending']} pending, ".
            "{$result['conflict']} conflict, {$result['locked']} locked, {$result['unmapped']} unmapped."
        );
    }

    public function breakdown(ParallelCurriculumComposite $composite)
    {
        $this->assertEnabled();
        abort_unless((int) $composite->tenant_id === $this->tenantId(), 403);

        $composite->load([
            'student',
            'curriculum',
            'curriculumClass',
            'conventionalClassArm.classLevel',
            'destinationSubject',
            'term.session',
            'integration',
        ]);

        $distribution = collect();
        if ($composite->destination_subject_id) {
            $distribution = \App\Models\Score::where('student_id', $composite->student_id)
                ->where('subject_id', $composite->destination_subject_id)
                ->where('term_id', $composite->term_id)
                ->where('score_source', ParallelCurriculumService::SCORE_SOURCE)
                ->where('source_reference_id', $composite->id)
                ->with('assessmentType')
                ->orderBy('assessment_type_id')
                ->get();
        }

        return view('parallel-curriculum.breakdown', compact('composite', 'distribution'));
    }
}
