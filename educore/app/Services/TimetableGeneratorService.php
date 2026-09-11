<?php

namespace App\Services;

use App\Models\ClassArmSubject;
use App\Models\SubjectFrequency;
use App\Models\Tenant;
use App\Models\TimetableConfig;
use App\Models\TimetablePeriod;
use Illuminate\Support\Collection;

/**
 * TimetableGeneratorService v2
 *
 * Configuration-driven timetable generator using the tenant's configured
 * school-open days rather than assuming Monday-Friday.
 */
class TimetableGeneratorService
{
    /**
     * Main entry point.
     */
    public function generate(
        int  $classArmId,
        int  $sessionId,
        int  $tenantId,
        bool $overwrite = true
    ): array {
        $config = TimetableConfig::where('tenant_id', $tenantId)
                                 ->where('session_id', $sessionId)
                                 ->first();

        if (!$config) {
            return [
                'created'   => 0,
                'skipped'   => 0,
                'conflicts' => ['No timetable configuration found for this session. Please set up school hours first.'],
            ];
        }

        $schoolDays = Tenant::find($tenantId)?->schoolOpenDays() ?? Tenant::DEFAULT_SCHOOL_OPEN_DAYS;
        if (empty($schoolDays)) {
            return [
                'created' => 0,
                'skipped' => 0,
                'conflicts' => ['No school-open days are configured. Select at least one school day before generating the timetable.'],
            ];
        }

        $slots = $config->computeSlots();
        $periodSlots = array_values(array_filter($slots, fn($s) => !$s['is_break']));

        if (empty($periodSlots)) {
            return ['created' => 0, 'skipped' => 0, 'conflicts' => ['No period slots could be computed from configuration.']];
        }

        $assignments = ClassArmSubject::where('class_arm_id', $classArmId)
                                      ->where('session_id', $sessionId)
                                      ->with('subject', 'teacher')
                                      ->get();

        if ($assignments->isEmpty()) {
            return ['created' => 0, 'skipped' => 0, 'conflicts' => ['No subjects assigned to this class for the selected session.']];
        }

        $frequencies = SubjectFrequency::where('class_arm_id', $classArmId)
                                       ->where('session_id', $sessionId)
                                       ->get()
                                       ->keyBy('subject_id');

        $pool = $this->buildPool($assignments, $frequencies);

        $totalSlots = count($periodSlots) * count($schoolDays);
        $totalNeeded = count($pool);

        if ($totalNeeded > $totalSlots) {
            return [
                'created'   => 0,
                'skipped'   => 0,
                'conflicts' => ["Total periods needed ({$totalNeeded}) exceeds available slots ({$totalSlots}) across the configured school days. Reduce subject frequencies, add more periods per day, or open additional school days."],
            ];
        }

        if ($overwrite) {
            TimetablePeriod::where('class_arm_id', $classArmId)
                           ->where('session_id', $sessionId)
                           ->delete();
        }

        $teacherCommitments = $this->loadTeacherCommitments($sessionId, $tenantId);
        $weekGrid = $this->buildWeekGrid($periodSlots, count($schoolDays));

        $created   = 0;
        $skipped   = 0;
        $conflicts = [];
        $poolIndex = 0;
        $poolSize  = count($pool);
        $placed = [];

        foreach ($weekGrid as $dayIndex => $daySlots) {
            $day = $schoolDays[$dayIndex];
            foreach ($daySlots as $slot) {
                if ($poolIndex >= $poolSize) break 2;

                $assignment = $this->pickBestSubject(
                    $pool, $poolIndex, $day, $placed,
                    $teacherCommitments, $slot
                );

                if ($assignment === null) {
                    $skipped++;
                    if ($poolIndex < $poolSize) $poolIndex++;
                    $conflicts[] = "Could not place a subject on {$day} {$slot['start']}–{$slot['end']} due to teacher conflicts.";
                    continue;
                }

                $usedIndex = $assignment['pool_index'];
                [$pool[$poolIndex], $pool[$usedIndex]] = [$pool[$usedIndex], $pool[$poolIndex]];
                $asgn = $pool[$poolIndex];

                TimetablePeriod::create([
                    'tenant_id'    => $tenantId,
                    'class_arm_id' => $classArmId,
                    'subject_id'   => $asgn['subject_id'],
                    'teacher_id'   => $asgn['teacher_id'],
                    'session_id'   => $sessionId,
                    'day_of_week'  => $day,
                    'start_time'   => $slot['start'],
                    'end_time'     => $slot['end'],
                    'venue'        => null,
                ]);

                if ($asgn['teacher_id']) {
                    $teacherCommitments[] = [
                        'teacher_id' => $asgn['teacher_id'],
                        'day'        => $day,
                        'start'      => $slot['start'],
                        'end'        => $slot['end'],
                    ];
                }

                $placed[$day][] = $asgn['subject_id'];
                $poolIndex++;
                $created++;
            }
        }

        return [
            'created'   => $created,
            'skipped'   => $skipped,
            'school_days' => $schoolDays,
            'conflicts' => $conflicts,
        ];
    }

    private function buildPool(Collection $assignments, Collection $frequencies): array
    {
        $pool = [];
        foreach ($assignments as $asgn) {
            $freq = $frequencies->get($asgn->subject_id)?->periods_per_week ?? 2;
            for ($i = 0; $i < $freq; $i++) {
                $pool[] = [
                    'subject_id'   => $asgn->subject_id,
                    'subject_name' => $asgn->subject->name,
                    'teacher_id'   => $asgn->teacher_id,
                ];
            }
        }

        shuffle($pool);
        return $pool;
    }

    private function buildWeekGrid(array $periodSlots, int $numDays): array
    {
        $grid = array_fill(0, $numDays, []);
        foreach ($periodSlots as $slot) {
            foreach (range(0, $numDays - 1) as $d) {
                $grid[$d][] = $slot;
            }
        }
        return $grid;
    }

    private function pickBestSubject(
        array &$pool,
        int $startIndex,
        string $day,
        array $placed,
        array $teacherCommitments,
        array $slot
    ): ?array {
        $size = count($pool);
        $alreadyOnDay = $placed[$day] ?? [];

        for ($i = $startIndex; $i < $size; $i++) {
            $candidate = $pool[$i];
            if (in_array($candidate['subject_id'], $alreadyOnDay)) continue;
            if ($candidate['teacher_id'] && $this->hasTeacherClash(
                $teacherCommitments, $candidate['teacher_id'], $day,
                $slot['start'], $slot['end']
            )) continue;
            return array_merge($candidate, ['pool_index' => $i]);
        }

        for ($i = $startIndex; $i < $size; $i++) {
            $candidate = $pool[$i];
            if ($candidate['teacher_id'] && $this->hasTeacherClash(
                $teacherCommitments, $candidate['teacher_id'], $day,
                $slot['start'], $slot['end']
            )) continue;
            return array_merge($candidate, ['pool_index' => $i]);
        }

        return null;
    }

    private function loadTeacherCommitments(int $sessionId, int $tenantId): array
    {
        return TimetablePeriod::where('tenant_id', $tenantId)
            ->where('session_id', $sessionId)
            ->whereNotNull('teacher_id')
            ->get(['teacher_id', 'day_of_week', 'start_time', 'end_time'])
            ->map(fn($p) => [
                'teacher_id' => $p->teacher_id,
                'day'        => $p->day_of_week,
                'start'      => $p->start_time,
                'end'        => $p->end_time,
            ])->toArray();
    }

    private function hasTeacherClash(
        array $commitments,
        int $teacherId,
        string $day,
        string $start,
        string $end
    ): bool {
        foreach ($commitments as $c) {
            if ($c['teacher_id'] !== $teacherId || $c['day'] !== $day) continue;
            if ($start < $c['end'] && $end > $c['start']) return true;
        }
        return false;
    }
}
