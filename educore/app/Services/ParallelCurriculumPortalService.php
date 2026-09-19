<?php

namespace App\Services;

use App\Models\ParallelCurriculumAttendanceRecord;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ParallelCurriculumPortalService
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
    ) {}

    private function enabled(Student $student): bool
    {
        return $this->parallel->enabledForTenant((int) $student->tenant_id);
    }

    public function placementsForStudent(Student $student, ?int $sessionId = null): Collection
    {
        if (! $this->enabled($student) || ! Schema::hasTable('parallel_curriculum_enrolments')) {
            return collect();
        }

        return ParallelCurriculumEnrolment::query()
            ->with(['curriculum', 'curriculumClass', 'classArm'])
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->when($sessionId, fn ($query) => $query->where('session_id', $sessionId))
            ->orderBy('parallel_curriculum_id')
            ->get();
    }

    public function timetableForStudent(Student $student, ?int $sessionId = null): Collection
    {
        if (
            ! $this->enabled($student)
            || ! Schema::hasTable('parallel_curriculum_enrolments')
            || ! Schema::hasTable('parallel_curriculum_timetable_periods')
        ) {
            return collect();
        }

        $placements = $this->placementsForStudent($student, $sessionId);
        if ($placements->isEmpty()) {
            return collect();
        }

        $periods = ParallelCurriculumTimetablePeriod::query()
            ->with(['curriculum', 'curriculumClass', 'classArm', 'subject', 'teacher'])
            ->where('tenant_id', $student->tenant_id)
            ->whereIn('parallel_curriculum_class_arm_id', $placements->pluck('parallel_curriculum_class_arm_id')->filter())
            ->when($sessionId, fn ($query) => $query->where('session_id', $sessionId))
            ->get()
            ->sortBy(fn (ParallelCurriculumTimetablePeriod $period) =>
                sprintf(
                    '%02d-%s',
                    array_search($period->day_of_week, ['monday','tuesday','wednesday','thursday','friday'], true) ?: 0,
                    substr((string) $period->start_time, 0, 5)
                )
            )
            ->values();

        return $placements
            ->groupBy('parallel_curriculum_id')
            ->map(function (Collection $programmePlacements, int|string $curriculumId) use ($periods): array {
                $placement = $programmePlacements->first();
                $armIds = $programmePlacements->pluck('parallel_curriculum_class_arm_id')->filter();

                return [
                    'curriculum_id' => (int) $curriculumId,
                    'curriculum_name' => $placement?->curriculum?->name ?? 'Parallel Curriculum',
                    'class_name' => $placement?->curriculumClass?->name,
                    'arm_name' => $placement?->classArm?->name,
                    'periods' => $periods
                        ->whereIn('parallel_curriculum_class_arm_id', $armIds)
                        ->values(),
                ];
            })
            ->values();
    }

    public function attendanceForStudent(Student $student, ?int $termId = null): Collection
    {
        if (
            ! $this->enabled($student)
            || ! Schema::hasTable('parallel_curriculum_enrolments')
            || ! Schema::hasTable('parallel_curriculum_attendance_records')
        ) {
            return collect();
        }

        $term = $termId
            ? Term::query()
                ->where('tenant_id', $student->tenant_id)
                ->find($termId)
            : null;

        $placements = $this->placementsForStudent($student, $term?->session_id);
        if ($placements->isEmpty()) {
            return collect();
        }

        $records = ParallelCurriculumAttendanceRecord::query()
            ->with(['curriculum', 'curriculumClass', 'classArm'])
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->when($termId, fn ($query) => $query->where('term_id', $termId))
            ->orderByDesc('attendance_date')
            ->get();

        return $placements
            ->groupBy('parallel_curriculum_id')
            ->map(function (Collection $programmePlacements, int|string $curriculumId) use ($records): array {
                $placement = $programmePlacements->first();
                $programmeRecords = $records
                    ->where('parallel_curriculum_id', (int) $curriculumId)
                    ->values();

                $stats = [
                    'total' => $programmeRecords->count(),
                    'present' => $programmeRecords->where('status', 'present')->count(),
                    'absent' => $programmeRecords->where('status', 'absent')->count(),
                    'late' => $programmeRecords->where('status', 'late')->count(),
                    'excused' => $programmeRecords->where('status', 'excused')->count(),
                ];
                $stats['rate'] = $stats['total'] > 0
                    ? round(($stats['present'] / $stats['total']) * 100, 1)
                    : 0.0;

                return [
                    'curriculum_id' => (int) $curriculumId,
                    'curriculum_name' => $placement?->curriculum?->name ?? 'Parallel Curriculum',
                    'class_name' => $placement?->curriculumClass?->name,
                    'arm_name' => $placement?->classArm?->name,
                    'records' => $programmeRecords,
                    'stats' => $stats,
                ];
            })
            ->values();
    }
}
