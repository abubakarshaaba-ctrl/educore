<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\Term;
use App\Services\ParallelCurriculumOperationsService;
use App\Services\ParallelCurriculumService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileParallelCurriculumOperationsController extends Controller
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

    public function index(Request $request)
    {
        $this->assertAvailable($request);
        $data = $request->validate([
            'parallel_curriculum_id' => ['nullable', 'integer'],
            'session_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'arm_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $tenantId = (int) $user->tenant_id;
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

        $requestedCurriculumId = (int) ($data['parallel_curriculum_id'] ?? 0);
        if (
            $requestedCurriculumId
            && ! $curricula->contains('id', $requestedCurriculumId)
        ) {
            abort(403, 'You are not assigned to the selected parallel curriculum.');
        }

        $curriculumId = $requestedCurriculumId
            ?: (int) optional($curricula->first())->id;
        $sessionId = (int) ($data['session_id'] ?? 0)
            ?: (int) optional($sessions->firstWhere('is_current', true) ?? $sessions->first())->id;

        $curriculum = $curriculumId
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

        if ($curriculum && ! $canManageOperations) {
            $curriculum->classes->each(function ($item) use ($user): void {
                $item->setRelation(
                    'arms',
                    $item->arms
                        ->filter(
                            fn (ParallelCurriculumClassArm $armItem) =>
                                $this->operations->canMarkAttendance(
                                    $user,
                                    $armItem
                                )
                        )
                        ->values()
                );
            });

            $curriculum->setRelation(
                'classes',
                $curriculum->classes
                    ->filter(fn ($item) => $item->arms->isNotEmpty())
                    ->values()
            );
        }

        $classes = $curriculum?->classes ?? collect();

        $requestedClassId = (int) ($data['class_id'] ?? 0);
        if ($requestedClassId && ! $classes->contains('id', $requestedClassId)) {
            abort(403, 'You are not assigned to the selected parallel class.');
        }

        $classId = $requestedClassId ?: (int) optional($classes->first())->id;
        $class = $classes->firstWhere('id', $classId);

        $arms = $class?->arms ?? collect();

        $requestedArmId = (int) ($data['arm_id'] ?? 0);
        if ($requestedArmId && ! $arms->contains('id', $requestedArmId)) {
            abort(403, 'You are not assigned to the selected parallel class arm.');
        }

        $armId = $requestedArmId ?: (int) optional($arms->first())->id;
        $arm = $arms->firstWhere('id', $armId);

        $terms = $terms->where('session_id', $sessionId)->values();

        $requestedTermId = (int) ($data['term_id'] ?? 0);
        $termId = $terms->contains('id', $requestedTermId)
            ? $requestedTermId
            : (int) optional($terms->firstWhere('is_current', true) ?? $terms->first())->id;

        $date = $data['date'] ?? now()->toDateString();

        $workingDays = $curriculum
            ? $this->operations->workingDays(
                $tenantId,
                (int) $curriculum->id
            )
            : collect();

        $staffAttendance = $curriculum
            ? $this->operations->staffAttendanceSheet(
                $user,
                (int) $curriculum->id,
                $date
            )
            : null;

        $periods = $arm && $sessionId
            ? ParallelCurriculumTimetablePeriod::query()
                ->with(['subject', 'teacher'])
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_arm_id', $arm->id)
                ->where('session_id', $sessionId)
                ->get()
                ->sortBy(fn (ParallelCurriculumTimetablePeriod $period) =>
                    array_search($period->day_of_week, ParallelCurriculumOperationsService::DAYS, true)
                    .'-'.substr((string) $period->start_time, 0, 5)
                )
                ->values()
            : collect();

        $canSaveAttendance = $arm
            ? $this->operations->canMarkAttendance($user, $arm)
            : false;

        $attendance = null;
        if ($arm && $termId && $canSaveAttendance) {
            $sheet = $this->operations->attendanceSheet($user, $arm->id, $termId, $date);
            $canSaveAttendance = (bool) $sheet['can_save'];
            $attendance = [
                'date' => $sheet['date'],
                'version' => $sheet['version'],
                'is_working_day' => (bool) $sheet['is_working_day'],
                'students' => $sheet['enrolments']->map(function ($enrolment) use ($sheet): array {
                    $record = $sheet['records']->get($enrolment->id);

                    return [
                        'enrolment_id' => $enrolment->id,
                        'student_id' => $enrolment->student_id,
                        'name' => $enrolment->student?->full_name ?? 'Student',
                        'admission_number' => $enrolment->student?->admission_number,
                        'status' => $record?->status,
                        'remark' => $record?->remark,
                    ];
                })->values(),
            ];
        }

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'selected' => [
                'parallel_curriculum_id' => $curriculum?->id,
                'session_id' => $sessionId ?: null,
                'term_id' => $termId ?: null,
                'class_id' => $class?->id,
                'arm_id' => $arm?->id,
                'date' => $date,
            ],
            'capabilities' => [
                'manage_timetable' => $this->operations->canManageTimetable($user),
                'save_attendance' => $canSaveAttendance,
                'export_attendance' => $arm
                    ? $this->operations->canExportAttendance($user)
                        && (
                            $canManageOperations
                            || $this->operations->canMarkAttendance($user, $arm)
                        )
                    : false,
                'manage_working_days' =>
                    $this->operations->canManageTimetable($user),
                'clock_parallel_staff' => $curriculum
                    ? $this->operations->canClockParallelStaff(
                        $user,
                        (int) $curriculum->id
                    )
                    : false,
            ],
            'curricula' => $curricula->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
            ])->values(),
            'sessions' => $sessions->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'is_current' => (bool) $item->is_current,
            ])->values(),
            'terms' => $terms->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'session_id' => $item->session_id,
                'session_name' => $item->session?->name,
                'is_current' => (bool) $item->is_current,
            ])->values(),
            'classes' => $classes->map(function ($item) use ($tenantId): array {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'code' => $item->code,
                    'arms' => $item->arms->map(fn ($armItem) => [
                        'id' => $armItem->id,
                        'name' => $armItem->name,
                        'code' => $armItem->code,
                        'capacity' => $armItem->capacity,
                    ])->values(),
                    'subjects' => $item->subjectAssignments->map(function ($assignment) use ($tenantId, $item): array {
                        return [
                            'id' => $assignment->parallel_curriculum_subject_id,
                            'name' => $assignment->subject?->name ?? 'Subject',
                            'code' => $assignment->subject?->code,
                            'teacher_id' => $assignment->teacher_id,
                            'teacher_name' => $assignment->teacher?->name,
                            'class_id' => $item->id,
                        ];
                    })->values(),
                ];
            })->values(),
            'working_days' => $workingDays->map(fn ($day) => [
                'day_of_week' => (string) $day->day_of_week,
                'is_working' => (bool) $day->is_working,
                'resumption_time' => $day->resumption_time
                    ? substr((string) $day->resumption_time, 0, 5)
                    : null,
                'closing_time' => $day->closing_time
                    ? substr((string) $day->closing_time, 0, 5)
                    : null,
                'grace_minutes' => (int) $day->grace_minutes,
            ])->values(),
            'periods' => $periods
                ->map(
                    fn (ParallelCurriculumTimetablePeriod $period) =>
                        $this->periodPayload($period)
                )
                ->values(),
            'attendance' => $attendance,
            'staff_attendance' => $staffAttendance ? [
                'date' => $staffAttendance['date'],
                'day_of_week' => $staffAttendance['day_of_week'],
                'is_working_day' =>
                    (bool) $staffAttendance['schedule']->is_working,
                'resumption_time' =>
                    $staffAttendance['schedule']->resumption_time
                        ? substr(
                            (string) $staffAttendance['schedule']->resumption_time,
                            0,
                            5
                        )
                        : null,
                'closing_time' =>
                    $staffAttendance['schedule']->closing_time
                        ? substr(
                            (string) $staffAttendance['schedule']->closing_time,
                            0,
                            5
                        )
                        : null,
                'grace_minutes' =>
                    (int) $staffAttendance['schedule']->grace_minutes,
                'can_clock_self' =>
                    (bool) $staffAttendance['can_clock_self'],
                'self_record' => ($selfRecord = $staffAttendance['records']->get($request->user()->id)) ? [
                    'status' => $selfRecord->status,
                    'departure_status' => $selfRecord->departure_status,
                    'clock_in_time' => $selfRecord->clock_in_time
                        ? substr((string) $selfRecord->clock_in_time, 0, 5)
                        : null,
                    'clock_out_time' => $selfRecord->clock_out_time
                        ? substr((string) $selfRecord->clock_out_time, 0, 5)
                        : null,
                ] : null,
                'staff' => $staffAttendance['staff']
                    ->map(function ($person) use ($staffAttendance): array {
                        $record = $staffAttendance['records']->get($person->id);

                        return [
                            'user_id' => (int) $person->id,
                            'name' => (string) $person->name,
                            'status' => $record?->status,
                            'departure_status' =>
                                $record?->departure_status,
                            'clock_in_time' => $record?->clock_in_time
                                ? substr(
                                    (string) $record->clock_in_time,
                                    0,
                                    5
                                )
                                : null,
                            'clock_out_time' => $record?->clock_out_time
                                ? substr(
                                    (string) $record->clock_out_time,
                                    0,
                                    5
                                )
                                : null,
                        ];
                    })
                    ->values(),
            ] : null,
        ]);
    }

    public function storePeriod(Request $request)
    {
        $this->assertAvailable($request);
        $tenantId = (int) $request->user()->tenant_id;
        $data = $request->validate([
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

        $period = $this->operations->createPeriod($request->user(), $data);

        return response()->json([
            'message' => 'Parallel timetable period added.',
            'period' => $this->periodPayload($period),
        ], 201);
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
            'days.*.day_of_week' => [
                'required',
                Rule::in(ParallelCurriculumOperationsService::DAYS),
            ],
            'days.*.is_working' => ['required', 'boolean'],
            'days.*.resumption_time' => ['nullable', 'date_format:H:i'],
            'days.*.closing_time' => ['nullable', 'date_format:H:i'],
            'days.*.grace_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:180',
            ],
        ]);

        $days = collect($data['days'])
            ->keyBy('day_of_week')
            ->map(fn ($row) => [
                'is_working' => (bool) $row['is_working'],
                'resumption_time' =>
                    $row['resumption_time'] ?? null,
                'closing_time' =>
                    $row['closing_time'] ?? null,
                'grace_minutes' =>
                    (int) ($row['grace_minutes'] ?? 0),
            ])
            ->all();

        $saved = $this->operations->saveWorkingDays(
            $request->user(),
            (int) $data['parallel_curriculum_id'],
            $days
        );

        return response()->json([
            'message' =>
                'Parallel working days and daily attendance hours saved.',
            'working_days' => $saved->map(fn ($day) => [
                'day_of_week' => (string) $day->day_of_week,
                'is_working' => (bool) $day->is_working,
                'resumption_time' => $day->resumption_time
                    ? substr((string) $day->resumption_time, 0, 5)
                    : null,
                'closing_time' => $day->closing_time
                    ? substr((string) $day->closing_time, 0, 5)
                    : null,
                'grace_minutes' => (int) $day->grace_minutes,
            ])->values(),
        ]);
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
            (int) $data['parallel_curriculum_id']
        );

        return response()->json([
            'message' => 'Parallel attendance clock-in recorded.',
            'status' => $record->status,
            'clock_in_time' =>
                substr((string) $record->clock_in_time, 0, 5),
        ]);
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

        return response()->json([
            'message' => 'Parallel attendance clock-out recorded.',
            'departure_status' => $record->departure_status,
            'clock_out_time' =>
                substr((string) $record->clock_out_time, 0, 5),
        ]);
    }

    public function destroyPeriod(Request $request, ParallelCurriculumTimetablePeriod $period)
    {
        $this->assertAvailable($request);
        $this->operations->deletePeriod($request->user(), $period);

        return response()->json(['message' => 'Parallel timetable period removed.']);
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
        $data = $request->validate([
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
            (int) $data['parallel_curriculum_class_arm_id'],
            (int) $data['term_id'],
            $data['attendance_date'],
            $data['records'],
            $data['version'] ?? null,
        );

        return response()->json([
            'message' => "Parallel attendance saved for {$result['saved']} learner(s).",
            'saved' => $result['saved'],
            'version' => $result['version'],
            'summary' => $result['summary'],
        ]);
    }

    private function periodPayload(ParallelCurriculumTimetablePeriod $period): array
    {
        return [
            'id' => $period->id,
            'class_id' => $period->parallel_curriculum_class_id,
            'arm_id' => $period->parallel_curriculum_class_arm_id,
            'subject_id' => $period->parallel_curriculum_subject_id,
            'subject' => $period->subject?->name ?? 'Subject',
            'teacher_id' => $period->teacher_id,
            'teacher' => $period->teacher?->name,
            'day_of_week' => $period->day_of_week,
            'start_time' => substr((string) $period->start_time, 0, 5),
            'end_time' => substr((string) $period->end_time, 0, 5),
            'venue' => $period->venue,
        ];
    }
}
