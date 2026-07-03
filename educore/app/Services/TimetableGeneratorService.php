<?php

namespace App\Services;

use App\Models\ClassArmSubject;
use App\Models\SubjectFrequency;
use App\Models\TimetableConfig;
use App\Models\TimetablePeriod;
use Illuminate\Support\Collection;

/**
 * TimetableGeneratorService v2
 *
 * Configuration-driven timetable generator.
 *
 * Flow:
 * 1. Load school config (start/end, periods/day, period duration, breaks)
 * 2. Compute all period time slots for the week (Mon-Fri)
 * 3. Load subjects assigned to class + their required frequency (periods/week)
 * 4. Build a pool of (subject, teacher) pairs repeated by frequency
 * 5. Distribute pool across slots using a balanced round-robin algorithm
 * 6. Check teacher availability across all classes before placing each period
 * 7. Report any unresolved conflicts
 */
class TimetableGeneratorService
{
    const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    /**
     * Main entry point.
     */
    public function generate(
        int  $classArmId,
        int  $sessionId,
        int  $tenantId,
        bool $overwrite = true
    ): array {
        // 1. Load school configuration
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

        // 2. Compute period time slots
        $slots = $config->computeSlots();
        $periodSlots = array_values(array_filter($slots, fn($s) => !$s['is_break']));

        if (empty($periodSlots)) {
            return ['created' => 0, 'skipped' => 0, 'conflicts' => ['No period slots could be computed from configuration.']];
        }

        // 3. Load assigned subjects + frequencies
        $assignments = ClassArmSubject::where('class_arm_id', $classArmId)
                                      ->where('session_id', $sessionId)
                                      ->with('subject', 'teacher')
                                      ->get();

        if ($assignments->isEmpty()) {
            return ['created' => 0, 'skipped' => 0, 'conflicts' => ['No subjects assigned to this class for the selected session.']];
        }

        // Load frequencies (periods per week per subject)
        $frequencies = SubjectFrequency::where('class_arm_id', $classArmId)
                                       ->where('session_id', $sessionId)
                                       ->get()
                                       ->keyBy('subject_id');

        // 4. Build subject pool — each subject repeated by its frequency
        $pool = $this->buildPool($assignments, $frequencies);

        // Total available slots per week
        $totalSlots = count($periodSlots) * count(self::DAYS);
        $totalNeeded = count($pool);

        if ($totalNeeded > $totalSlots) {
            return [
                'created'   => 0,
                'skipped'   => 0,
                'conflicts' => ["Total periods needed ({$totalNeeded}) exceeds available slots ({$totalSlots}). Reduce subject frequencies or add more periods per day."],
            ];
        }

        // 5. Clear existing if overwrite
        if ($overwrite) {
            TimetablePeriod::where('class_arm_id', $classArmId)
                           ->where('session_id', $sessionId)
                           ->delete();
        }

        // 6. Load teacher commitments for clash detection
        $teacherCommitments = $this->loadTeacherCommitments($sessionId, $tenantId);

        // 7. Build week slot grid — distribute slots evenly across days
        $weekGrid = $this->buildWeekGrid($periodSlots, count(self::DAYS));

        // 8. Place subjects using balanced distribution
        $created   = 0;
        $skipped   = 0;
        $conflicts = [];
        $poolIndex = 0;
        $poolSize  = count($pool);

        // Distribute: one subject per day per round to avoid same-day clustering
        $placed = []; // Track placed subjects per day to avoid repeats

        foreach ($weekGrid as $dayIndex => $daySlots) {
            $day = self::DAYS[$dayIndex];
            foreach ($daySlots as $slot) {
                if ($poolIndex >= $poolSize) break 2;

                // Find best subject for this slot (not already on this day if avoidable)
                $assignment = $this->pickBestSubject(
                    $pool, $poolIndex, $day, $placed,
                    $teacherCommitments, $slot
                );

                if ($assignment === null) {
                    // No suitable subject found — skip slot
                    $skipped++;
                    // Advance to next subject anyway to avoid infinite loop
                    if ($poolIndex < $poolSize) $poolIndex++;
                    $conflicts[] = "Could not place a subject on {$day} {$slot['start']}–{$slot['end']} due to teacher conflicts.";
                    continue;
                }

                // Find which pool index this was
                $usedIndex = $assignment['pool_index'];
                // Swap used subject to current position
                [$pool[$poolIndex], $pool[$usedIndex]] = [$pool[$usedIndex], $pool[$poolIndex]];
                $asgn = $pool[$poolIndex];

                // Create period
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

                // Register teacher commitment
                if ($asgn['teacher_id']) {
                    $teacherCommitments[] = [
                        'teacher_id' => $asgn['teacher_id'],
                        'day'        => $day,
                        'start'      => $slot['start'],
                        'end'        => $slot['end'],
                    ];
                }

                // Track placed on this day
                $placed[$day][] = $asgn['subject_id'];
                $poolIndex++;
                $created++;
            }
        }

        return [
            'created'   => $created,
            'skipped'   => $skipped,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Build subject pool: repeat each subject entry by its periods_per_week.
     */
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

        // Shuffle to avoid consecutive same-subject runs
        shuffle($pool);
        return $pool;
    }

    /**
     * Build week grid: distribute period slots evenly across days.
     * Returns [ dayIndex => [slot, slot, ...], ... ]
     */
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

    /**
     * Pick the best subject from the pool for this day/slot.
     * Prefers subjects not already placed on this day.
     * Falls back to any subject without a teacher clash.
     */
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

        // First pass: subject not already on this day + no teacher clash
        for ($i = $startIndex; $i < $size; $i++) {
            $candidate = $pool[$i];
            if (in_array($candidate['subject_id'], $alreadyOnDay)) continue;
            if ($candidate['teacher_id'] && $this->hasTeacherClash(
                $teacherCommitments, $candidate['teacher_id'], $day,
                $slot['start'], $slot['end']
            )) continue;
            return array_merge($candidate, ['pool_index' => $i]);
        }

        // Second pass: allow same-day subject if no teacher clash
        for ($i = $startIndex; $i < $size; $i++) {
            $candidate = $pool[$i];
            if ($candidate['teacher_id'] && $this->hasTeacherClash(
                $teacherCommitments, $candidate['teacher_id'], $day,
                $slot['start'], $slot['end']
            )) continue;
            return array_merge($candidate, ['pool_index' => $i]);
        }

        return null; // Could not place any subject
    }

    /**
     * Load all existing teacher commitments for this session.
     */
    private function loadTeacherCommitments(int $sessionId, int $tenantId): array
    {
        return TimetablePeriod::where('session_id', $sessionId)
            ->whereNotNull('teacher_id')
            ->get(['teacher_id', 'day_of_week', 'start_time', 'end_time'])
            ->map(fn($p) => [
                'teacher_id' => $p->teacher_id,
                'day'        => $p->day_of_week,
                'start'      => $p->start_time,
                'end'        => $p->end_time,
            ])->toArray();
    }

    /**
     * Check if teacher is already booked at this day/time across all classes.
     */
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
