<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\ParallelCurriculumArmSubjectTeacher;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumAttendanceRecord;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumStaffAttendanceRecord;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\ParallelCurriculumWorkingDay;
use App\Models\Term;
use App\Models\TimetablePeriod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ParallelCurriculumOperationsService
{
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function createPeriod(User $user, array $data): ParallelCurriculumTimetablePeriod
    {
        abort_unless($this->canManageTimetable($user), 403, 'Only authorized administrators can manage the parallel timetable.');

        $tenantId = (int) $user->tenant_id;
        $class = ParallelCurriculumClass::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail((int) $data['parallel_curriculum_class_id']);

        $arm = ParallelCurriculumClassArm::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('is_active', true)
            ->findOrFail((int) $data['parallel_curriculum_class_arm_id']);

        $assignment = ParallelCurriculumClassSubject::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('parallel_curriculum_subject_id', (int) $data['parallel_curriculum_subject_id'])
            ->where('is_active', true)
            ->first();

        if (! $assignment) {
            throw ValidationException::withMessages([
                'parallel_curriculum_subject_id' => 'The selected subject is not active for this parallel class.',
            ]);
        }

        $session = AcademicSession::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail((int) $data['session_id']);

        $day = strtolower((string) $data['day_of_week']);
        if (! in_array($day, self::DAYS, true)) {
            throw ValidationException::withMessages(['day_of_week' => 'Select a valid school day.']);
        }

        $this->assertInsideParallelWorkingHours(
            $tenantId,
            (int) $class->parallel_curriculum_id,
            $day,
            (string) $data['start_time'],
            (string) $data['end_time']
        );

        $armClash = ParallelCurriculumTimetablePeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_arm_id', $arm->id)
            ->where('session_id', $session->id)
            ->where('day_of_week', $day)
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->exists();

        if ($armClash) {
            throw ValidationException::withMessages([
                'start_time' => 'This parallel class arm already has another period during the selected time.',
            ]);
        }

        $teacherId = $this->effectiveTeacherId(
            $tenantId,
            $class->id,
            $arm->id,
            (int) $data['parallel_curriculum_subject_id']
        );

        if ($teacherId) {
            $parallelTeacherClash = ParallelCurriculumTimetablePeriod::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_id', $teacherId)
                ->where('session_id', $session->id)
                ->where('day_of_week', $day)
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            $conventionalTeacherClash = TimetablePeriod::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_id', $teacherId)
                ->where('session_id', $session->id)
                ->where('day_of_week', $day)
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            if ($parallelTeacherClash || $conventionalTeacherClash) {
                throw ValidationException::withMessages([
                    'start_time' => 'The assigned teacher already has another class during this time.',
                ]);
            }
        }

        return ParallelCurriculumTimetablePeriod::create([
            'tenant_id' => $tenantId,
            'parallel_curriculum_id' => $class->parallel_curriculum_id,
            'parallel_curriculum_class_id' => $class->id,
            'parallel_curriculum_class_arm_id' => $arm->id,
            'parallel_curriculum_subject_id' => (int) $data['parallel_curriculum_subject_id'],
            'teacher_id' => $teacherId,
            'session_id' => $session->id,
            'day_of_week' => $day,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'venue' => $data['venue'] ?? null,
        ])->load(['subject', 'teacher', 'classArm.curriculumClass']);
    }

    public function deletePeriod(User $user, ParallelCurriculumTimetablePeriod $period): void
    {
        abort_unless($this->canManageTimetable($user), 403, 'Only authorized administrators can manage the parallel timetable.');
        abort_unless((int) $period->tenant_id === (int) $user->tenant_id, 404);
        $period->delete();
    }

    public function attendanceSheet(
        User $user,
        int $armId,
        int $termId,
        string $date
    ): array {
        $tenantId = (int) $user->tenant_id;
        $arm = ParallelCurriculumClassArm::query()
            ->with('curriculumClass.curriculum')
            ->where('tenant_id', $tenantId)
            ->findOrFail($armId);

        $term = Term::query()
            ->with('session')
            ->where('tenant_id', $tenantId)
            ->findOrFail($termId);

        $attendanceDate = Carbon::parse($date)->startOfDay();
        if (
            ($term->start_date && $attendanceDate->lt($term->start_date->startOfDay())) ||
            ($term->end_date && $attendanceDate->gt($term->end_date->startOfDay()))
        ) {
            throw ValidationException::withMessages([
                'attendance_date' => 'The attendance date must fall within the selected academic term.',
            ]);
        }

        $curriculumId = (int) $arm->curriculumClass->parallel_curriculum_id;
        $attendanceDay = strtolower($attendanceDate->format('l'));
        $schedule = $this->workingDay($tenantId, $curriculumId, $attendanceDay);

        abort_unless($this->canMarkAttendance($user, $arm), 403, 'You are not assigned to manage attendance for this parallel class arm.');

        $enrolments = ParallelCurriculumEnrolment::query()
            ->with('student')
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_arm_id', $arm->id)
            ->where('parallel_curriculum_class_id', $arm->parallel_curriculum_class_id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->whereHas('student', fn ($query) => $query->where('status', 'active'))
            ->get()
            ->sortBy(fn (ParallelCurriculumEnrolment $enrolment) => strtolower((string) $enrolment->student?->full_name))
            ->values();

        $records = ParallelCurriculumAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_arm_id', $arm->id)
            ->where('term_id', $term->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('parallel_curriculum_enrolment_id');

        return [
            'arm' => $arm,
            'term' => $term,
            'date' => $date,
            'enrolments' => $enrolments,
            'records' => $records,
            'version' => $this->attendanceVersion($records),
            'is_working_day' => (bool) $schedule->is_working,
            'can_save' => (bool) $schedule->is_working,
        ];
    }

    public function saveAttendance(
        User $user,
        int $armId,
        int $termId,
        string $date,
        array $records,
        ?string $version = null
    ): array {
        $sheet = $this->attendanceSheet($user, $armId, $termId, $date);
        /** @var ParallelCurriculumClassArm $arm */
        $arm = $sheet['arm'];
        /** @var Term $term */
        $term = $sheet['term'];

        if (! $sheet['can_save']) {
            $attendanceDay = strtolower(Carbon::parse($date)->format('l'));
            throw ValidationException::withMessages([
                'attendance_date' =>
                    ucfirst($attendanceDay).' is not enabled as a working day for this parallel curriculum.',
            ]);
        }

        $valid = $sheet['enrolments']->keyBy('id');
        $submittedIds = collect($records)->pluck('enrolment_id')->map(fn ($id) => (int) $id);
        $invalid = $submittedIds->filter(fn (int $id) => ! $valid->has($id))->values();

        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'records' => 'Every attendance row must belong to an active learner in the selected parallel class arm.',
            ]);
        }

        $tenantId = (int) $user->tenant_id;

        return DB::transaction(function () use (
            $tenantId,
            $user,
            $arm,
            $term,
            $date,
            $records,
            $version,
            $valid
        ): array {
            $current = ParallelCurriculumAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_arm_id', $arm->id)
                ->where('term_id', $term->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->get()
                ->keyBy('parallel_curriculum_enrolment_id');

            if ($version && ! hash_equals($version, $this->attendanceVersion($current))) {
                abort(409, 'Parallel attendance changed on the server. Reload the sheet before saving.');
            }

            foreach ($records as $record) {
                /** @var ParallelCurriculumEnrolment $enrolment */
                $enrolment = $valid->get((int) $record['enrolment_id']);

                ParallelCurriculumAttendanceRecord::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'parallel_curriculum_enrolment_id' => $enrolment->id,
                        'attendance_date' => $date,
                    ],
                    [
                        'parallel_curriculum_id' => $enrolment->parallel_curriculum_id,
                        'parallel_curriculum_class_id' => $enrolment->parallel_curriculum_class_id,
                        'parallel_curriculum_class_arm_id' => $arm->id,
                        'student_id' => $enrolment->student_id,
                        'term_id' => $term->id,
                        'marked_by' => $user->id,
                        'status' => $record['status'],
                        'remark' => $record['remark'] ?? null,
                    ]
                );
            }

            $saved = ParallelCurriculumAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_arm_id', $arm->id)
                ->where('term_id', $term->id)
                ->whereDate('attendance_date', $date)
                ->get()
                ->keyBy('parallel_curriculum_enrolment_id');

            return [
                'saved' => count($records),
                'version' => $this->attendanceVersion($saved),
                'summary' => [
                    'present' => $saved->where('status', 'present')->count(),
                    'absent' => $saved->where('status', 'absent')->count(),
                    'late' => $saved->where('status', 'late')->count(),
                    'excused' => $saved->where('status', 'excused')->count(),
                ],
            ];
        });
    }

    public function attendanceReport(
        User $user,
        int $armId,
        int $termId,
        ?string $from = null,
        ?string $to = null
    ): array {
        $tenantId = (int) $user->tenant_id;
        $arm = ParallelCurriculumClassArm::query()
            ->with('curriculumClass.curriculum')
            ->where('tenant_id', $tenantId)
            ->findOrFail($armId);
        $term = Term::query()
            ->with('session')
            ->where('tenant_id', $tenantId)
            ->findOrFail($termId);

        abort_unless(
            $this->canExportAttendance($user),
            403,
            'You do not have permission to export parallel attendance.'
        );

        $fromDate = Carbon::parse($from ?: $term->start_date?->toDateString() ?: now()->toDateString())->startOfDay();
        $toDate = Carbon::parse($to ?: $term->end_date?->toDateString() ?: now()->toDateString())->startOfDay();

        if ($term->start_date && $fromDate->lt($term->start_date->startOfDay())) {
            $fromDate = $term->start_date->copy()->startOfDay();
        }
        if ($term->end_date && $toDate->gt($term->end_date->startOfDay())) {
            $toDate = $term->end_date->copy()->startOfDay();
        }
        if ($toDate->lt($fromDate)) {
            throw ValidationException::withMessages([
                'to' => 'The attendance report end date must be on or after the start date.',
            ]);
        }

        $enrolments = ParallelCurriculumEnrolment::query()
            ->with('student')
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_arm_id', $arm->id)
            ->where('parallel_curriculum_class_id', $arm->parallel_curriculum_class_id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->whereHas('student', fn ($query) => $query->where('status', 'active'))
            ->get()
            ->sortBy(fn (ParallelCurriculumEnrolment $enrolment) => strtolower((string) $enrolment->student?->full_name))
            ->values();

        $records = ParallelCurriculumAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_arm_id', $arm->id)
            ->where('term_id', $term->id)
            ->whereBetween('attendance_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->get()
            ->groupBy('parallel_curriculum_enrolment_id');

        $rows = $enrolments->map(function (ParallelCurriculumEnrolment $enrolment) use ($records): array {
            $studentRecords = $records->get($enrolment->id, collect());
            $total = $studentRecords->count();
            $present = $studentRecords->where('status', 'present')->count();

            return [
                'enrolment_id' => $enrolment->id,
                'student_id' => $enrolment->student_id,
                'admission_number' => $enrolment->student?->admission_number,
                'student_name' => $enrolment->student?->full_name ?? 'Student',
                'present' => $present,
                'absent' => $studentRecords->where('status', 'absent')->count(),
                'late' => $studentRecords->where('status', 'late')->count(),
                'excused' => $studentRecords->where('status', 'excused')->count(),
                'total' => $total,
                'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0.0,
            ];
        })->values();

        $allRecords = $records->flatten(1);

        return [
            'arm' => $arm,
            'term' => $term,
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'rows' => $rows,
            'summary' => [
                'students' => $rows->count(),
                'records' => $allRecords->count(),
                'present' => $allRecords->where('status', 'present')->count(),
                'absent' => $allRecords->where('status', 'absent')->count(),
                'late' => $allRecords->where('status', 'late')->count(),
                'excused' => $allRecords->where('status', 'excused')->count(),
            ],
        ];
    }

    public function canMarkAttendance(User $user, ParallelCurriculumClassArm $arm): bool
    {
        if ($this->canManageTimetable($user) || $user->canManage('students')) {
            return true;
        }

        if ((int) $arm->tenant_id !== (int) $user->tenant_id) {
            return false;
        }

        $assignments = ParallelCurriculumClassSubject::query()
            ->where('tenant_id', $arm->tenant_id)
            ->where('parallel_curriculum_class_id', $arm->parallel_curriculum_class_id)
            ->where('is_active', true)
            ->get();

        return $assignments->contains(function (ParallelCurriculumClassSubject $assignment) use ($arm, $user): bool {
            return (int) $this->effectiveTeacherId(
                (int) $arm->tenant_id,
                (int) $arm->parallel_curriculum_class_id,
                (int) $arm->id,
                (int) $assignment->parallel_curriculum_subject_id
            ) === (int) $user->id;
        });
    }

    public function canViewOperations(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->canManage('timetable')
            || $user->canAccessExactModule('scores')
            || $user->canAccessExactModule('scores.entry')
            || $user->canAccessExactModule('timetable.view')
            || $user->canAccessExactModule('attendance');
    }

    public function canExportAttendance(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->canManage('students')
            || $user->canAccessExactModule('attendance')
            || $user->canAccessExactModule('scores');
    }

    public function canManageTimetable(User $user): bool
    {
        return $user->isSuperAdmin() || $user->canManage('timetable');
    }

    public function validateTeacherChange(
        int $tenantId,
        int $classId,
        int $subjectId,
        ?int $armId,
        ?int $teacherId
    ): void {
        if (! $teacherId) {
            return;
        }

        $periodQuery = ParallelCurriculumTimetablePeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $classId)
            ->where('parallel_curriculum_subject_id', $subjectId);

        if ($armId) {
            $periodQuery->where('parallel_curriculum_class_arm_id', $armId);
        } else {
            $independentArmIds = ParallelCurriculumArmSubjectTeacher::query()
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_id', $classId)
                ->where('parallel_curriculum_subject_id', $subjectId)
                ->where('is_active', true)
                ->pluck('parallel_curriculum_class_arm_id');

            if (
                Schema::hasColumn(
                    'parallel_curriculum_class_arms',
                    'teaching_assignment_mode'
                )
                && Schema::hasColumn(
                    'parallel_curriculum_class_arms',
                    'class_teacher_id'
                )
            ) {
                $classTeacherArmIds = ParallelCurriculumClassArm::query()
                    ->where('tenant_id', $tenantId)
                    ->where('parallel_curriculum_class_id', $classId)
                    ->where('is_active', true)
                    ->where('teaching_assignment_mode', 'class_teacher')
                    ->whereNotNull('class_teacher_id')
                    ->pluck('id');

                $independentArmIds = $independentArmIds
                    ->merge($classTeacherArmIds)
                    ->unique()
                    ->values();
            }

            if ($independentArmIds->isNotEmpty()) {
                $periodQuery->whereNotIn(
                    'parallel_curriculum_class_arm_id',
                    $independentArmIds
                );
            }
        }

        $periods = $periodQuery->get();
        if ($periods->isEmpty()) {
            return;
        }

        foreach ($periods as $index => $period) {
            foreach ($periods->slice($index + 1) as $other) {
                if (
                    (int) $period->session_id === (int) $other->session_id
                    && $period->day_of_week === $other->day_of_week
                    && $period->start_time < $other->end_time
                    && $period->end_time > $other->start_time
                ) {
                    throw ValidationException::withMessages([
                        'teacher_id' => 'This teacher change would create an overlap between existing parallel timetable periods.',
                    ]);
                }
            }

            $parallelConflict = ParallelCurriculumTimetablePeriod::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_id', $teacherId)
                ->whereNotIn('id', $periods->pluck('id'))
                ->where('session_id', $period->session_id)
                ->where('day_of_week', $period->day_of_week)
                ->where('start_time', '<', $period->end_time)
                ->where('end_time', '>', $period->start_time)
                ->exists();

            $conventionalConflict = TimetablePeriod::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_id', $teacherId)
                ->where('session_id', $period->session_id)
                ->where('day_of_week', $period->day_of_week)
                ->where('start_time', '<', $period->end_time)
                ->where('end_time', '>', $period->start_time)
                ->exists();

            if ($parallelConflict || $conventionalConflict) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'This teacher change conflicts with an existing timetable period.',
                ]);
            }
        }
    }

    public function syncTimetableTeachers(
        int $tenantId,
        int $classId,
        int $subjectId,
        ?int $armId = null
    ): void {
        $query = ParallelCurriculumTimetablePeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $classId)
            ->where('parallel_curriculum_subject_id', $subjectId);

        if ($armId) {
            $query->where('parallel_curriculum_class_arm_id', $armId);
        }

        $query->get()->each(function (ParallelCurriculumTimetablePeriod $period) use (
            $tenantId,
            $classId,
            $subjectId
        ): void {
            $teacherId = $this->effectiveTeacherId(
                $tenantId,
                $classId,
                (int) $period->parallel_curriculum_class_arm_id,
                $subjectId
            );

            if ((int) ($period->teacher_id ?? 0) !== (int) ($teacherId ?? 0)) {
                $period->update(['teacher_id' => $teacherId]);
            }
        });
    }

    public function effectiveTeacherId(
        int $tenantId,
        int $classId,
        int $armId,
        int $subjectId
    ): ?int {
        $arm = ParallelCurriculumClassArm::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $classId)
            ->find($armId);

        if ($arm && $arm->usesClassTeacherModel()) {
            return $arm->class_teacher_id ? (int) $arm->class_teacher_id : null;
        }

        $override = ParallelCurriculumArmSubjectTeacher::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $classId)
            ->where('parallel_curriculum_class_arm_id', $armId)
            ->where('parallel_curriculum_subject_id', $subjectId)
            ->where('is_active', true)
            ->value('teacher_id');

        if ($override) {
            return (int) $override;
        }

        $default = ParallelCurriculumClassSubject::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $classId)
            ->where('parallel_curriculum_subject_id', $subjectId)
            ->where('is_active', true)
            ->value('teacher_id');

        return $default ? (int) $default : null;
    }

    public function attendanceVersion(Collection $records): string
    {
        if ($records->isEmpty()) {
            return 'empty';
        }

        return hash('sha256', $records->sortKeys()->map(
            fn (ParallelCurriculumAttendanceRecord $record): string => implode(':', [
                $record->parallel_curriculum_enrolment_id,
                $record->status,
                $record->remark ?? '',
                $record->updated_at?->format('Y-m-d H:i:s.u') ?? '',
            ])
        )->implode('|'));
    }

    public function workingDays(int $tenantId, int $curriculumId): Collection
    {
        $existing = ParallelCurriculumWorkingDay::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculumId)
            ->get()
            ->keyBy('day_of_week');

        return collect(self::DAYS)->map(function (string $day) use (
            $existing,
            $tenantId,
            $curriculumId
        ) {
            return $existing->get($day) ?: new ParallelCurriculumWorkingDay([
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => $curriculumId,
                'day_of_week' => $day,
                'is_working' => ! in_array($day, ['saturday', 'sunday'], true),
                'resumption_time' => '08:00:00',
                'closing_time' => '15:00:00',
                'grace_minutes' => 15,
            ]);
        });
    }

    public function saveWorkingDays(User $user, int $curriculumId, array $days): Collection
    {
        abort_unless(
            $this->canManageTimetable($user),
            403,
            'Only authorized administrators can configure parallel working days.'
        );

        $tenantId = (int) $user->tenant_id;
        ParallelCurriculum::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($curriculumId);

        DB::transaction(function () use ($tenantId, $curriculumId, $days): void {
            foreach (self::DAYS as $day) {
                $row = $days[$day] ?? [];
                $isWorking = (bool) ($row['is_working'] ?? false);
                $resumption = $row['resumption_time'] ?? null;
                $closing = $row['closing_time'] ?? null;
                $graceMinutes = (int) ($row['grace_minutes'] ?? 0);

                if (
                    $isWorking
                    && (
                        ! $resumption
                        || ! $closing
                        || $resumption >= $closing
                    )
                ) {
                    throw ValidationException::withMessages([
                        "days.$day.closing_time" =>
                            ucfirst($day).' requires a valid resumption time earlier than closing time.',
                    ]);
                }

                ParallelCurriculumWorkingDay::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'parallel_curriculum_id' => $curriculumId,
                        'day_of_week' => $day,
                    ],
                    [
                        'is_working' => $isWorking,
                        'resumption_time' => $isWorking ? $resumption : null,
                        'closing_time' => $isWorking ? $closing : null,
                        'grace_minutes' => $graceMinutes,
                    ]
                );
            }
        });

        return $this->workingDays($tenantId, $curriculumId);
    }

    public function canClockParallelStaff(User $user, int $curriculumId): bool
    {
        return $this->assignedStaffIds(
            (int) $user->tenant_id,
            $curriculumId
        )->contains((int) $user->id);
    }


    /**
     * Reconcile one physical staff clock-in across every parallel curriculum
     * the staff member is assigned to teach on the supplied date.
     *
     * The conventional/general staff attendance scan remains the single source
     * event. Each parallel curriculum independently evaluates that same
     * timestamp against its own day-specific resumption time and grace period.
     */
    public function reconcileSharedStaffClockIn(
        User $staff,
        string $date,
        string $time,
        string $method = 'shared_qr',
        ?int $recordedBy = null
    ): Collection {
        if (
            ! Schema::hasTable('parallel_curricula')
            || ! Schema::hasTable('parallel_curriculum_staff_attendance_records')
            || ! Schema::hasTable('parallel_curriculum_working_days')
        ) {
            return collect();
        }

        $tenantId = (int) $staff->tenant_id;
        $day = strtolower(Carbon::parse($date)->format('l'));

        $curricula = ParallelCurriculum::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $records = collect();

        foreach ($curricula as $curriculum) {
            $curriculumId = (int) $curriculum->id;

            if (! $this->canClockParallelStaff($staff, $curriculumId)) {
                continue;
            }

            $schedule = $this->workingDay(
                $tenantId,
                $curriculumId,
                $day
            );

            if (
                ! $schedule->is_working
                || ! $schedule->resumption_time
                || ! $schedule->closing_time
            ) {
                continue;
            }

            $existing = ParallelCurriculumStaffAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_id', $curriculumId)
                ->where('user_id', $staff->id)
                ->whereDate('attendance_date', $date)
                ->first();

            // Idempotency: a retry of the same QR event must not overwrite a
            // previously recorded arrival for this curriculum.
            if ($existing?->clock_in_time) {
                $records->push($existing->loadMissing('curriculum'));
                continue;
            }

            $resumption = substr(
                (string) $schedule->resumption_time,
                0,
                8
            );
            $closing = substr(
                (string) $schedule->closing_time,
                0,
                8
            );
            $grace = (int) $schedule->grace_minutes;

            $record = ParallelCurriculumStaffAttendanceRecord::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'parallel_curriculum_id' => $curriculumId,
                    'user_id' => $staff->id,
                    'attendance_date' => $date,
                ],
                [
                    'status' => $this->classifyParallelArrival(
                        $date,
                        $time,
                        $resumption,
                        $grace
                    ),
                    'clock_in_time' => $time,
                    'expected_resumption_time' => $resumption,
                    'expected_closing_time' => $closing,
                    'grace_minutes' => $grace,
                    'clock_in_method' => $method,
                    'recorded_by' => $recordedBy ?: $staff->id,
                ]
            );

            $records->push($record->loadMissing('curriculum'));
        }

        return $records->values();
    }

    /**
     * Apply one physical departure scan to every parallel attendance context
     * created from the shared staff QR for that date.
     */
    public function reconcileSharedStaffClockOut(
        User $staff,
        string $date,
        string $time
    ): Collection {
        if (! Schema::hasTable('parallel_curriculum_staff_attendance_records')) {
            return collect();
        }

        $records = ParallelCurriculumStaffAttendanceRecord::query()
            ->with('curriculum')
            ->where('tenant_id', (int) $staff->tenant_id)
            ->where('user_id', $staff->id)
            ->whereDate('attendance_date', $date)
            ->whereNotNull('clock_in_time')
            ->get();

        foreach ($records as $record) {
            if ($record->clock_out_time) {
                continue;
            }

            $closing = $record->expected_closing_time
                ? substr((string) $record->expected_closing_time, 0, 8)
                : null;

            $record->update([
                'clock_out_time' => $time,
                'departure_status' => $closing && $time < $closing
                    ? 'early'
                    : 'on_time',
            ]);
        }

        return $records
            ->map(fn ($record) => $record->fresh()->loadMissing('curriculum'))
            ->values();
    }

    public function staffAttendanceSheet(
        User $user,
        int $curriculumId,
        string $date
    ): array {
        $tenantId = (int) $user->tenant_id;

        ParallelCurriculum::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($curriculumId);

        $assignedIds = $this->assignedStaffIds($tenantId, $curriculumId);

        $visibleIds = $this->canManageTimetable($user)
            ? $assignedIds
            : $assignedIds
                ->filter(fn ($id) => (int) $id === (int) $user->id)
                ->values();

        $staff = User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $visibleIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $records = ParallelCurriculumStaffAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculumId)
            ->whereDate('attendance_date', $date)
            ->whereIn('user_id', $visibleIds)
            ->get()
            ->keyBy('user_id');

        $day = strtolower(Carbon::parse($date)->format('l'));
        $schedule = $this->workingDay($tenantId, $curriculumId, $day);

        return [
            'date' => $date,
            'day_of_week' => $day,
            'schedule' => $schedule,
            'staff' => $staff,
            'records' => $records,
            'can_clock_self' => $this->canClockParallelStaff($user, $curriculumId)
                && (bool) $schedule->is_working
                && Carbon::parse($date)->isSameDay(today()),
        ];
    }

    public function clockInParallelStaff(
        User $user,
        int $curriculumId,
        string $method = 'parallel_mobile'
    ): ParallelCurriculumStaffAttendanceRecord {
        $tenantId = (int) $user->tenant_id;

        abort_unless(
            $this->canClockParallelStaff($user, $curriculumId),
            403,
            'You are not assigned to teach in this parallel curriculum.'
        );

        $now = now();
        $date = $now->toDateString();
        $day = strtolower($now->format('l'));
        $schedule = $this->workingDay($tenantId, $curriculumId, $day);

        if (! $schedule->is_working) {
            throw ValidationException::withMessages([
                'attendance' =>
                    ucfirst($day).' is not configured as a parallel-curriculum working day.',
            ]);
        }

        $existing = ParallelCurriculumStaffAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculumId)
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if ($existing?->clock_in_time) {
            throw ValidationException::withMessages([
                'attendance' =>
                    'You have already clocked in for this parallel curriculum today.',
            ]);
        }

        $clockIn = $now->format('H:i:s');
        $resumption = substr((string) $schedule->resumption_time, 0, 8);
        $closing = substr((string) $schedule->closing_time, 0, 8);
        $grace = (int) $schedule->grace_minutes;

        $status = $this->classifyParallelArrival(
            $date,
            $clockIn,
            $resumption,
            $grace
        );

        return ParallelCurriculumStaffAttendanceRecord::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => $curriculumId,
                'user_id' => $user->id,
                'attendance_date' => $date,
            ],
            [
                'status' => $status,
                'clock_in_time' => $clockIn,
                'expected_resumption_time' => $resumption,
                'expected_closing_time' => $closing,
                'grace_minutes' => $grace,
                'clock_in_method' => $method,
                'recorded_by' => $user->id,
            ]
        );
    }

    public function clockOutParallelStaff(
        User $user,
        int $curriculumId
    ): ParallelCurriculumStaffAttendanceRecord {
        $tenantId = (int) $user->tenant_id;

        $record = ParallelCurriculumStaffAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculumId)
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', today())
            ->firstOrFail();

        if (! $record->clock_in_time) {
            throw ValidationException::withMessages([
                'attendance' => 'Clock in before clocking out.',
            ]);
        }

        if ($record->clock_out_time) {
            throw ValidationException::withMessages([
                'attendance' => 'You have already clocked out today.',
            ]);
        }

        $time = now()->format('H:i:s');
        $closing = substr((string) $record->expected_closing_time, 0, 8);

        $record->update([
            'clock_out_time' => $time,
            'departure_status' => $closing && $time < $closing
                ? 'early'
                : 'on_time',
        ]);

        return $record->fresh();
    }

    private function assignedStaffIds(
        int $tenantId,
        int $curriculumId
    ): Collection {
        $classes = ParallelCurriculumClass::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculumId)
            ->where('is_active', true)
            ->with([
                'arms' => fn ($query) => $query->where('is_active', true),
                'subjectAssignments' => fn ($query) =>
                    $query->where('is_active', true),
            ])
            ->get();

        $ids = collect();

        foreach ($classes as $class) {
            foreach ($class->arms as $arm) {
                foreach ($class->subjectAssignments as $assignment) {
                    $teacherId = $this->effectiveTeacherId(
                        $tenantId,
                        (int) $class->id,
                        (int) $arm->id,
                        (int) $assignment->parallel_curriculum_subject_id
                    );

                    if ($teacherId) {
                        $ids->push($teacherId);
                    }
                }
            }
        }

        return $ids
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function workingDay(
        int $tenantId,
        int $curriculumId,
        string $day
    ): ParallelCurriculumWorkingDay {
        return ParallelCurriculumWorkingDay::query()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculumId)
            ->where('day_of_week', $day)
            ->first()
            ?: new ParallelCurriculumWorkingDay([
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => $curriculumId,
                'day_of_week' => $day,
                'is_working' => ! in_array($day, ['saturday', 'sunday'], true),
                'resumption_time' => '08:00:00',
                'closing_time' => '15:00:00',
                'grace_minutes' => 15,
            ]);
    }

    private function classifyParallelArrival(
        string $date,
        string $clockIn,
        string $resumption,
        int $graceMinutes
    ): string {
        $actual = Carbon::parse($date.' '.$clockIn);
        $start = Carbon::parse($date.' '.$resumption);
        $graceEnd = (clone $start)->addMinutes($graceMinutes);

        if ($actual->lt($start)) {
            return 'early';
        }

        return $actual->lte($graceEnd)
            ? 'present'
            : 'late';
    }

    private function assertInsideParallelWorkingHours(
        int $tenantId,
        int $curriculumId,
        string $day,
        string $start,
        string $end
    ): void {
        $schedule = $this->workingDay($tenantId, $curriculumId, $day);

        if (! $schedule->is_working) {
            throw ValidationException::withMessages([
                'day_of_week' =>
                    ucfirst($day).' is not enabled for this parallel curriculum.',
            ]);
        }

        $dayStart = substr((string) $schedule->resumption_time, 0, 5);
        $dayEnd = substr((string) $schedule->closing_time, 0, 5);

        if ($start < $dayStart || $end > $dayEnd) {
            throw ValidationException::withMessages([
                'end_time' =>
                    ucfirst($day)." parallel working hours are {$dayStart}–{$dayEnd}.",
            ]);
        }
    }
}
