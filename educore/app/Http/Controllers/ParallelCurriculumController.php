<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ParallelCurriculumController extends Controller
{
    public function __construct(private readonly ParallelCurriculumService $service) {}

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->canAccessExactModule('scores');
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

    private function assertCanEnter(ParallelCurriculumClass $class, int $subjectId): void
    {
        if ($this->canManage()) {
            return;
        }

        $allowed = ParallelCurriculumClassSubject::where('parallel_curriculum_class_id', $class->id)
            ->where('parallel_curriculum_subject_id', $subjectId)
            ->where('teacher_id', auth()->id())
            ->where('is_active', true)
            ->exists();

        abort_unless($allowed, 403, 'You are not assigned to this parallel-curriculum class and subject.');
    }

    public function index()
    {
        $this->assertEnabled();

        $tenantId = $this->tenantId();
        $canManage = $this->canManage();
        $currentSession = AcademicSession::current()->first();
        $currentTerm = Term::current()->with('session')->first();

        $curricula = ParallelCurriculum::with([
            'defaultAssessmentTemplate',
            'grades',
            'subjects',
            'classes.assessmentTemplate',
            'classes.subjectAssignments.subject',
            'classes.subjectAssignments.teacher',
        ])->orderBy('name')->get();

        $workspaces = $curricula->flatMap(function (ParallelCurriculum $curriculum) use ($canManage) {
            return $curriculum->classes
                ->where('is_active', true)
                ->flatMap(function (ParallelCurriculumClass $class) use ($curriculum, $canManage) {
                    return $class->subjectAssignments
                        ->where('is_active', true)
                        ->filter(fn ($assignment) => $canManage || (int) $assignment->teacher_id === (int) auth()->id())
                        ->map(fn ($assignment) => [
                            'curriculum' => $curriculum,
                            'class' => $class,
                            'assignment' => $assignment,
                        ]);
                });
        })->values();

        $templates = $canManage
            ? AssessmentTemplate::with('components')->where('status', AssessmentTemplate::STATUS_ACTIVE)->orderBy('name')->get()
            : collect();

        $conventionalSubjects = $canManage ? Subject::where('is_active', true)->orderBy('name')->get() : collect();
        $classLevels = $canManage ? ClassLevel::orderBy('order_index')->orderBy('name')->get() : collect();
        $staff = $canManage
            ? User::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereIn('role', User::staffRoleNames())
                ->orderBy('name')
                ->get()
                ->filter(fn (User $person) =>
                    ! $person->isAccountant()
                    && ($person->canAccessExactModule('scores') || $person->canAccessExactModule('scores.entry'))
                )
                ->values()
            : collect();

        $integrations = $canManage
            ? ParallelCurriculumIntegration::with(['curriculum', 'destinationClassLevel', 'destinationSubject'])
                ->orderBy('parallel_curriculum_id')->orderBy('destination_class_level_id')->get()
            : collect();

        $enrolments = ($canManage && $currentSession)
            ? ParallelCurriculumEnrolment::with(['student.currentClassArm.classLevel', 'curriculumClass', 'curriculum'])
                ->where('session_id', $currentSession->id)
                ->where('is_active', true)
                ->orderBy('parallel_curriculum_class_id')
                ->get()
            : collect();

        $recentComposites = $currentTerm
            ? ParallelCurriculumComposite::with(['student', 'curriculum', 'curriculumClass', 'destinationSubject'])
                ->where('term_id', $currentTerm->id)
                ->latest('computed_at')
                ->limit(30)
                ->get()
            : collect();

        return view('parallel-curriculum.index', compact(
            'canManage', 'currentSession', 'currentTerm', 'curricula', 'workspaces',
            'templates', 'conventionalSubjects', 'classLevels', 'staff',
            'integrations', 'enrolments', 'recentComposites'
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
            'classes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name'),
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
            $activeAssignments = ParallelCurriculumEnrolment::with('curriculumClass')
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

        ParallelCurriculumClass::create([
            'tenant_id' => $tenantId,
            'parallel_curriculum_id' => $data['parallel_curriculum_id'],
            'assessment_template_id' => $data['assessment_template_id'] ?? null,
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Parallel curriculum class created.');
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

        if (
            $this->service->classStructureLocked($class)
            && (! $existingAssignment || ! $existingAssignment->is_active)
        ) {
            abort(
                423,
                'This parallel class has published results. Unpublish them before adding or reactivating subjects.'
            );
        }

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

        return back()->with('success', 'Subject assigned to parallel curriculum class.');
    }

    public function destroyClassSubject(ParallelCurriculumClassSubject $assignment)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $assignment->tenant_id === $this->tenantId(), 403);

        $class = $this->classForTenant((int) $assignment->parallel_curriculum_class_id);
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

            return back()->with('success', 'Subject archived because result records already exist.');
        }

        $assignment->delete();

        return back()->with('success', 'Subject removed from parallel curriculum class.');
    }

    public function downloadStudentAssignmentTemplate()
    {
        $this->assertEnabled();
        $this->assertManage();

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['admission_number', 'parallel_class']);
            fputcsv($handle, ['STU001', 'Mutawassitah 1']);
            fputcsv($handle, ['STU002', 'Mutawassitah 2']);
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

        $curriculum = ParallelCurriculum::with([
            'classes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name'),
        ])->findOrFail($data['parallel_curriculum_id']);

        abort_if($curriculum->classes->isEmpty(), 422, 'Create an active parallel class before importing student assignments.');

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('assignment_file')->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'assignment_file' => 'EduCore could not read the uploaded file. Use the assignment template or a valid CSV/XLS/XLSX file.',
            ]);
        }

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'assignment_file' => 'The assignment file must contain a header row and at least one student row.',
            ]);
        }

        $header = array_map(
            fn ($value) => str((string) $value)->trim()->lower()->replace([' ', '-'], '_')->toString(),
            array_shift($rows)
        );

        $admissionIndex = array_search('admission_number', $header, true);
        $classIndex = array_search('parallel_class', $header, true);
        if ($classIndex === false) {
            $classIndex = array_search('parallel_class_code', $header, true);
        }

        if ($admissionIndex === false || $classIndex === false) {
            throw ValidationException::withMessages([
                'assignment_file' => 'Required columns are admission_number and parallel_class (or parallel_class_code).',
            ]);
        }

        $entries = [];
        $seenAdmissions = [];
        $errors = [];

        foreach ($rows as $offset => $row) {
            $rowNumber = $offset + 2;
            $admissionNumber = trim((string) ($row[$admissionIndex] ?? ''));
            $parallelClass = trim((string) ($row[$classIndex] ?? ''));

            if ($admissionNumber === '' && $parallelClass === '') {
                continue;
            }

            if ($admissionNumber === '' || $parallelClass === '') {
                $errors[] = "Row {$rowNumber}: both admission_number and parallel_class are required.";
                continue;
            }

            $admissionKey = mb_strtolower($admissionNumber);
            if (isset($seenAdmissions[$admissionKey])) {
                $errors[] = "Row {$rowNumber}: admission number {$admissionNumber} appears more than once.";
                continue;
            }

            $seenAdmissions[$admissionKey] = true;
            $entries[] = [
                'row' => $rowNumber,
                'admission_number' => $admissionNumber,
                'admission_key' => $admissionKey,
                'parallel_class' => $parallelClass,
                'class_key' => mb_strtolower($parallelClass),
            ];
        }

        if ($entries === []) {
            throw ValidationException::withMessages([
                'assignment_file' => $errors ?: ['No usable student assignment rows were found in the uploaded file.'],
            ]);
        }

        $students = Student::active()
            ->whereIn('admission_number', collect($entries)->pluck('admission_number')->all())
            ->get()
            ->keyBy(fn (Student $student) => mb_strtolower(trim((string) $student->admission_number)));

        $classesByName = $curriculum->classes
            ->keyBy(fn (ParallelCurriculumClass $class) => mb_strtolower(trim($class->name)));
        $classesByCode = $curriculum->classes
            ->filter(fn (ParallelCurriculumClass $class) => filled($class->code))
            ->keyBy(fn (ParallelCurriculumClass $class) => mb_strtolower(trim((string) $class->code)));

        $assignments = [];

        foreach ($entries as $entry) {
            $student = $students->get($entry['admission_key']);
            if (! $student) {
                $errors[] = "Row {$entry['row']}: active student {$entry['admission_number']} was not found.";
                continue;
            }

            $class = $classesByName->get($entry['class_key']) ?: $classesByCode->get($entry['class_key']);
            if (! $class) {
                $errors[] = "Row {$entry['row']}: parallel class {$entry['parallel_class']} was not found in {$curriculum->name}.";
                continue;
            }

            $assignments[] = [$student, $class, $entry['row']];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'assignment_file' => array_slice($errors, 0, 20),
            ]);
        }

        $studentIds = collect($assignments)->map(fn (array $assignment) => (int) $assignment[0]->id)->unique()->values();
        $existingEnrolments = ParallelCurriculumEnrolment::where('parallel_curriculum_id', $curriculum->id)
            ->where('session_id', $data['session_id'])
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');
        $destinationLocks = [];

        foreach ($assignments as [$student, $class, $rowNumber]) {
            $existing = $existingEnrolments->get($student->id);
            $changesPlacement = ! $existing || (int) $existing->parallel_curriculum_class_id !== (int) $class->id;

            if (! $changesPlacement) {
                continue;
            }

            if ($existing && $this->service->enrolmentPlacementLocked($existing)) {
                $errors[] = "Row {$rowNumber}: {$student->admission_number} cannot be moved because the current parallel-class result is published.";
                continue;
            }

            if (! array_key_exists($class->id, $destinationLocks)) {
                $destinationLocks[$class->id] = $this->service->classPlacementLocked($class, (int) $data['session_id']);
            }

            if ($destinationLocks[$class->id]) {
                $errors[] = "Row {$rowNumber}: {$class->name} already has a published result for this session. Unpublish it before adding or moving students.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'assignment_file' => array_slice($errors, 0, 20),
            ]);
        }

        DB::transaction(function () use ($assignments, $tenantId, $data): void {
            foreach ($assignments as [$student, $class]) {
                ParallelCurriculumEnrolment::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'parallel_curriculum_id' => $class->parallel_curriculum_id,
                        'student_id' => $student->id,
                        'session_id' => $data['session_id'],
                    ],
                    [
                        'parallel_curriculum_class_id' => $class->id,
                        'is_active' => true,
                    ]
                );
            }
        });

        return back()->with(
            'success',
            count($assignments).' student assignment(s) imported successfully for '.$curriculum->name.'.'
        );
    }

    public function assignStudents(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId)],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->where('tenant_id', $tenantId)],
        ]);

        $class = $this->classForTenant((int) $data['parallel_curriculum_class_id']);
        $studentIds = collect($data['student_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $students = Student::active()
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        $existingEnrolments = ParallelCurriculumEnrolment::where('parallel_curriculum_id', $class->parallel_curriculum_id)
            ->where('session_id', $data['session_id'])
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $destinationLocked = null;
        $placementErrors = [];

        foreach ($studentIds as $studentId) {
            $existing = $existingEnrolments->get($studentId);
            $changesPlacement = ! $existing || (int) $existing->parallel_curriculum_class_id !== (int) $class->id;

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

        DB::transaction(function () use ($studentIds, $tenantId, $class, $data): void {
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
                        'is_active' => true,
                    ]
                );
            }
        });

        return back()->with(
            'success',
            $studentIds->count().
            ' student parallel-class assignment(s) updated. Existing students in this programme were moved to the selected parallel class.'
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
            'parallel_curriculum_id' => ['required', Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId)],
            'destination_class_level_ids' => ['required', 'array', 'min:1'],
            'destination_class_level_ids.*' => ['integer', Rule::exists('class_levels', 'id')->where('tenant_id', $tenantId)],
            'destination_subject_id' => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'minimum_completed_subjects' => ['nullable', 'integer', 'min:1', 'max:50'],
            'require_all_subjects' => ['nullable', 'boolean'],
            'auto_sync' => ['nullable', 'boolean'],
        ]);

        foreach (collect($data['destination_class_level_ids'])->map(fn ($id) => (int) $id)->unique() as $classLevelId) {
            ParallelCurriculumIntegration::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'parallel_curriculum_id' => $data['parallel_curriculum_id'],
                    'destination_class_level_id' => $classLevelId,
                ],
                [
                    'destination_subject_id' => $data['destination_subject_id'],
                    'calculation_method' => 'arithmetic_mean',
                    'require_all_subjects' => $request->boolean('require_all_subjects', true),
                    'minimum_completed_subjects' => (int) ($data['minimum_completed_subjects'] ?? 1),
                    'auto_sync' => $request->boolean('auto_sync', true),
                    'is_active' => true,
                ]
            );
        }

        return back()->with('success', 'Conventional result integration rule saved.');
    }

    public function scoreSheet(Request $request)
    {
        $this->assertEnabled();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', Rule::exists('parallel_curriculum_subjects', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
        ]);

        $class = $this->classForTenant((int) $data['class_id']);
        $this->assertCanEnter($class, (int) $data['subject_id']);

        $term = Term::with('session')->findOrFail($data['term_id']);
        $subject = ParallelCurriculumSubject::findOrFail($data['subject_id']);

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

        $enrolments = ParallelCurriculumEnrolment::with('student')
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
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
            'class', 'subject', 'term', 'template', 'components', 'enrolments', 'scores', 'lockedStudents'
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
            'scores' => ['required', 'array'],
        ]);

        $class = $this->classForTenant((int) $data['class_id']);
        $this->assertCanEnter($class, (int) $data['subject_id']);
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
        $components = $this->service->componentsForClass($class)->keyBy('id');

        abort_if($components->isEmpty(), 422, 'Assign an assessment template to this parallel curriculum class first.');

        $enrolments = ParallelCurriculumEnrolment::where('parallel_curriculum_class_id', $class->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
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
