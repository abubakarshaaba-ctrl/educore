<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\Term;
use App\Services\ParallelCurriculumOperationsService;
use App\Services\ParallelCurriculumService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParallelCurriculumOperationsController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumOperationsService $operations,
        private readonly ParallelCurriculumService $parallel,
    ) {}

    private function assertAvailable(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isTenantStaff() || $user->isSuperAdmin()), 403);
        abort_unless(
            $this->operations->canViewOperations($user),
            403,
            'You do not have access to parallel timetable and attendance.'
        );
        abort_unless(
            $this->parallel->enabledForTenant((int) $user->tenant_id),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );
    }

    public function attendance(Request $request)
    {
        return $this->index($request);
    }

    public function index(Request $request)
    {
        $this->assertAvailable($request);
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;
        $attendanceOnly = $request->routeIs('parallel-curriculum.attendance.index');
        $canManageOperations = $this->operations->canManageOperations($user);

        $curricula = ParallelCurriculum::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if (! $canManageOperations) {
            $curricula = $curricula
                ->filter(
                    fn (ParallelCurriculum $curriculum) =>
                        $this->operations->canClockParallelStaff(
                            $user,
                            (int) $curriculum->id
                        )
                )
                ->values();
        }

        $sessions = AcademicSession::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $terms = Term::query()
            ->with('session')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get();

        $requestedCurriculumId = $request->integer('parallel_curriculum_id');
        if (
            $requestedCurriculumId
            && ! $curricula->contains('id', $requestedCurriculumId)
        ) {
            abort(403, 'You are not assigned to the selected parallel curriculum.');
        }

        $curriculumId = $requestedCurriculumId
            ?: (int) optional($curricula->first())->id;
        $sessionId = $request->integer('session_id')
            ?: (int) optional($sessions->firstWhere('is_current', true) ?? $sessions->first())->id;

        $selectedCurriculum = $curriculumId
            ? ParallelCurriculum::query()
                ->where('tenant_id', $tenantId)
                ->with([
                    'classes' => fn ($query) => $query->where('is_active', true)
                        ->with([
                            'arms' => fn ($armQuery) => $armQuery->where('is_active', true),
                            'subjectAssignments' => fn ($subjectQuery) => $subjectQuery
                                ->where('is_active', true)
                                ->with(['subject', 'teacher']),
                        ]),
                ])
                ->find($curriculumId)
            : null;

        if ($selectedCurriculum && ! $canManageOperations) {
            $selectedCurriculum->classes->each(function ($class) use ($user, $tenantId): void {
                $class->setRelation(
                    'arms',
                    $class->arms
                        ->filter(
                            fn (ParallelCurriculumClassArm $arm) =>
                                $this->operations->canMarkAttendance(
                                    $user,
                                    $arm
                                )
                        )
                        ->values()
                );

                $class->setRelation(
                    'subjectAssignments',
                    $class->subjectAssignments
                        ->filter(function ($assignment) use ($class, $user, $tenantId): bool {
                            return $class->arms->contains(
                                fn (ParallelCurriculumClassArm $arm) =>
                                    (int) (
                                        $this->operations->effectiveTeacherId(
                                            $tenantId,
                                            (int) $class->id,
                                            (int) $arm->id,
                                            (int) $assignment->parallel_curriculum_subject_id
                                        ) ?? 0
                                    ) === (int) $user->id
                            );
                        })
                        ->values()
                );
            });

            $selectedCurriculum->setRelation(
                'classes',
                $selectedCurriculum->classes
                    ->filter(fn ($class) => $class->arms->isNotEmpty())
                    ->values()
            );
        }

        $classes = $selectedCurriculum?->classes ?? collect();

        $requestedClassId = $request->integer('class_id');
        if ($requestedClassId && ! $classes->contains('id', $requestedClassId)) {
            abort(403, 'You are not assigned to the selected parallel class.');
        }

        $classId = $requestedClassId ?: (int) optional($classes->first())->id;
        $selectedClass = $classes->firstWhere('id', $classId);

        $arms = $selectedClass?->arms ?? collect();

        $requestedArmId = $request->integer('arm_id');
        if ($requestedArmId && ! $arms->contains('id', $requestedArmId)) {
            abort(403, 'You are not assigned to the selected parallel class arm.');
        }

        $armId = $requestedArmId ?: (int) optional($arms->first())->id;
        $selectedArm = $arms->firstWhere('id', $armId);

        $terms = $terms->where('session_id', $sessionId)->values();

        $requestedTermId = $request->integer('term_id');
        $termId = $terms->contains('id', $requestedTermId)
            ? $requestedTermId
            : (int) optional($terms->firstWhere('is_current', true) ?? $terms->first())->id;

        $date = $request->input('date', now()->toDateString());

        $workingDays = $selectedCurriculum
            ? $this->operations->workingDays(
                $tenantId,
                (int) $selectedCurriculum->id
            )
            : collect();

        $staffAttendance = $selectedCurriculum
            ? $this->operations->staffAttendanceSheet(
                $user,
                (int) $selectedCurriculum->id,
                $date
            )
            : null;

        $periods = collect();
        if ($selectedArm && $sessionId) {
            $periods = ParallelCurriculumTimetablePeriod::query()
                ->with(['subject', 'teacher'])
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_arm_id', $selectedArm->id)
                ->where('session_id', $sessionId)
                ->when(
                    ! $canManageOperations,
                    fn ($query) => $query->where('teacher_id', $user->id)
                )
                ->orderByRaw("CASE day_of_week
                    WHEN 'monday' THEN 1
                    WHEN 'tuesday' THEN 2
                    WHEN 'wednesday' THEN 3
                    WHEN 'thursday' THEN 4
                    WHEN 'friday' THEN 5
                    WHEN 'saturday' THEN 6
                    WHEN 'sunday' THEN 7
                    ELSE 8 END")
                ->orderBy('start_time')
                ->get();
        }

        $attendance = null;
        $canMarkAttendance = false;
        if ($selectedArm && $termId) {
            $canMarkAttendance = $this->operations->canMarkAttendance($user, $selectedArm);
            if ($canMarkAttendance) {
                $attendance = $this->operations->attendanceSheet(
                    $user,
                    (int) $selectedArm->id,
                    (int) $termId,
                    $date,
                );
            }
        }

        return view('parallel-curriculum.operations', [
            'curricula' => $curricula,
            'sessions' => $sessions,
            'terms' => $terms,
            'selectedCurriculum' => $selectedCurriculum,
            'selectedClass' => $selectedClass,
            'selectedArm' => $selectedArm,
            'curriculumId' => $curriculumId,
            'sessionId' => $sessionId,
            'termId' => $termId,
            'classId' => $classId,
            'armId' => $armId,
            'date' => $date,
            'periods' => $periods,
            'attendance' => $attendance,
            'canManageOperations' => $canManageOperations,
            'canManageTimetable' => $this->operations->canManageTimetable($user),
            'canMarkAttendance' => $canMarkAttendance,
            'canExportAttendance' => $selectedArm
                ? $this->operations->canExportAttendance($user)
                    && (
                        $canManageOperations
                        || $this->operations->canMarkAttendance(
                            $user,
                            $selectedArm
                        )
                    )
                : false,
            'workingDays' => $workingDays,
            'staffAttendance' => $staffAttendance,
            'canClockParallelStaff' => $selectedCurriculum
                ? $this->operations->canClockParallelStaff(
                    $user,
                    (int) $selectedCurriculum->id
                )
                : false,
            'attendanceOnly' => $attendanceOnly,
        ]);
    }

    public function storePeriod(Request $request)
    {
        $this->assertAvailable($request);
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'parallel_curriculum_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'parallel_curriculum_class_arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId),
            ],
            'parallel_curriculum_subject_id' => [
                'required',
                Rule::exists('parallel_curriculum_subjects', 'id')->where('tenant_id', $tenantId),
            ],
            'session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId),
            ],
            'day_of_week' => ['required', Rule::in(ParallelCurriculumOperationsService::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'venue' => ['nullable', 'string', 'max:100'],
        ]);

        $period = $this->operations->createPeriod($request->user(), $validated);

        return redirect()->route('parallel-curriculum.operations.index', [
            'parallel_curriculum_id' => $period->parallel_curriculum_id,
            'session_id' => $period->session_id,
            'class_id' => $period->parallel_curriculum_class_id,
            'arm_id' => $period->parallel_curriculum_class_arm_id,
        ])->with('success', 'Parallel timetable period added.');
    }

    public function saveWorkingDays(Request $request)
    {
        $this->assertAvailable($request);
        $tenantId = (int) $request->user()->tenant_id;

        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'days' => ['required', 'array'],
            'days.*.is_working' => ['nullable', 'boolean'],
            'days.*.resumption_time' => ['nullable', 'date_format:H:i'],
            'days.*.closing_time' => ['nullable', 'date_format:H:i'],
            'days.*.grace_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:180',
            ],
        ]);

        $days = [];

        foreach (ParallelCurriculumOperationsService::DAYS as $day) {
            $row = $request->input("days.$day", []);

            $days[$day] = [
                'is_working' => (bool) ($row['is_working'] ?? false),
                'resumption_time' => $row['resumption_time'] ?? null,
                'closing_time' => $row['closing_time'] ?? null,
                'grace_minutes' => (int) ($row['grace_minutes'] ?? 0),
            ];
        }

        $this->operations->saveWorkingDays(
            $request->user(),
            (int) $data['parallel_curriculum_id'],
            $days
        );

        return back()->with(
            'success',
            'Parallel working days and daily attendance hours saved.'
        );
    }

    public function clockInStaff(Request $request)
    {
        $this->assertAvailable($request);
        $tenantId = (int) $request->user()->tenant_id;

        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')
                    ->where('tenant_id', $tenantId),
            ],
        ]);

        $record = $this->operations->clockInParallelStaff(
            $request->user(),
            (int) $data['parallel_curriculum_id'],
            'parallel_web'
        );

        return back()->with(
            'success',
            'Parallel attendance clock-in recorded at '.
                substr((string) $record->clock_in_time, 0, 5).'.'
        );
    }

    public function clockOutStaff(Request $request)
    {
        $this->assertAvailable($request);
        $tenantId = (int) $request->user()->tenant_id;

        $data = $request->validate([
            'parallel_curriculum_id' => [
                'required',
                Rule::exists('parallel_curricula', 'id')
                    ->where('tenant_id', $tenantId),
            ],
        ]);

        $record = $this->operations->clockOutParallelStaff(
            $request->user(),
            (int) $data['parallel_curriculum_id']
        );

        return back()->with(
            'success',
            'Parallel attendance clock-out recorded at '.
                substr((string) $record->clock_out_time, 0, 5).'.'
        );
    }

    public function destroyPeriod(Request $request, ParallelCurriculumTimetablePeriod $period)
    {
        $this->assertAvailable($request);
        $context = [
            'parallel_curriculum_id' => $period->parallel_curriculum_id,
            'session_id' => $period->session_id,
            'class_id' => $period->parallel_curriculum_class_id,
            'arm_id' => $period->parallel_curriculum_class_arm_id,
        ];

        $this->operations->deletePeriod($request->user(), $period);

        return redirect()->route('parallel-curriculum.operations.index', $context)
            ->with('success', 'Parallel timetable period removed.');
    }

    public function exportAttendance(Request $request)
    {
        $this->assertAvailable($request);
        $tenantId = (int) $request->user()->tenant_id;
        $data = $request->validate([
            'arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
            'format' => ['required', Rule::in(['csv', 'pdf'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $report = $this->operations->attendanceReport(
            $request->user(),
            (int) $data['arm_id'],
            (int) $data['term_id'],
            $data['from'] ?? null,
            $data['to'] ?? null,
        );

        $filenameBase = str(
            ($report['arm']->curriculumClass?->curriculum?->name ?? 'parallel')
            .'-'.($report['arm']->curriculumClass?->name ?? 'class')
            .'-'.$report['arm']->name
            .'-attendance'
        )->slug('_')->toString();

        if ($data['format'] === 'csv') {
            return response()->streamDownload(function () use ($report): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, [
                    'admission_number',
                    'student_name',
                    'present',
                    'absent',
                    'late',
                    'excused',
                    'recorded_days',
                    'attendance_rate_percent',
                ]);
                foreach ($report['rows'] as $row) {
                    fputcsv($handle, [
                        $row['admission_number'],
                        $row['student_name'],
                        $row['present'],
                        $row['absent'],
                        $row['late'],
                        $row['excused'],
                        $row['total'],
                        $row['rate'],
                    ]);
                }
                fclose($handle);
            }, $filenameBase.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return Pdf::loadView('parallel-curriculum.attendance-report-pdf', [
            'report' => $report,
            'schoolName' => $request->user()?->tenant?->name ?? config('app.name'),
        ])->setPaper('a4', 'landscape')->download($filenameBase.'.pdf');
    }

    public function saveAttendance(Request $request)
    {
        $this->assertAvailable($request);
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'parallel_curriculum_class_arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'version' => ['nullable', 'string', 'max:64'],
            'records' => ['required', 'array'],
            'records.*.enrolment_id' => ['required', 'integer'],
            'records.*.status' => ['required', Rule::in(['present','absent','late','excused'])],
            'records.*.remark' => ['nullable', 'string', 'max:200'],
        ]);

        $result = $this->operations->saveAttendance(
            $request->user(),
            (int) $validated['parallel_curriculum_class_arm_id'],
            (int) $validated['term_id'],
            $validated['attendance_date'],
            $validated['records'],
            $validated['version'] ?? null,
        );

        $arm = ParallelCurriculumClassArm::query()
            ->with('curriculumClass')
            ->where('tenant_id', $tenantId)
            ->findOrFail((int) $validated['parallel_curriculum_class_arm_id']);

        return redirect()->route('parallel-curriculum.operations.index', [
            'parallel_curriculum_id' => $arm->curriculumClass?->parallel_curriculum_id,
            'session_id' => optional(Term::find($validated['term_id']))->session_id,
            'term_id' => $validated['term_id'],
            'class_id' => $arm->parallel_curriculum_class_id,
            'arm_id' => $arm->id,
            'date' => $validated['attendance_date'],
        ])->with('success', "Parallel attendance saved for {$result['saved']} learner(s).");
    }
}
