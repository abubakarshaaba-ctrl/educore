<?php

namespace App\Services\Exams;

use App\Models\ClassArmSubject;
use App\Models\ExamPeriod;
use App\Models\ExamSupervisor;
use App\Models\ExamTimetableEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamSchedulerService
{
    /**
     * Build the exam timetable: for each selected class level, pull the
     * distinct subjects assigned to its arms and place them one-per-slot
     * across the period's working dates x sessions, in order.
     *
     * Returns a report: ['placed' => n, 'unplaced' => [[class_level, subject], ...]]
     */
    public function generateTimetable(ExamPeriod $period): array
    {
        $slots = $period->slots(); // ordered [date, session]
        if (empty($slots)) {
            throw new \RuntimeException('No working exam dates/sessions configured.');
        }

        $classLevels = $period->classLevels;
        if ($classLevels->isEmpty()) {
            throw new \RuntimeException('No classes selected for this exam period.');
        }

        $placed = 0;
        $unplaced = [];

        DB::transaction(function () use ($period, $classLevels, $slots, &$placed, &$unplaced) {
            $period->entries()->delete(); // clear previous plan (also cascades supervisors)

            foreach ($classLevels as $level) {
                $subjectIds = ClassArmSubject::whereHas('classArm', fn ($q) => $q->where('class_level_id', $level->id))
                    ->distinct()
                    ->pluck('subject_id');

                $slotIndex = 0;
                foreach ($subjectIds as $subjectId) {
                    if ($slotIndex >= count($slots)) {
                        $unplaced[] = ['class_level_id' => $level->id, 'class_level' => $level->name, 'subject_id' => $subjectId];
                        continue;
                    }

                    $slot = $slots[$slotIndex];
                    ExamTimetableEntry::create([
                        'exam_period_id'  => $period->id,
                        'class_level_id'  => $level->id,
                        'subject_id'      => $subjectId,
                        'exam_date'       => $slot['date']->toDateString(),
                        'exam_session_id' => $slot['session']->id,
                    ]);
                    $placed++;
                    $slotIndex++;
                }
            }

            $period->update(['status' => 'timetabled']);
        });

        return ['placed' => $placed, 'unplaced' => $unplaced];
    }

    /**
     * Assign one supervisor per timetable entry from the selected staff
     * pool. Balances load (fewest-assignments-first), never double-books a
     * staff member in the same date+session, and avoids assigning a
     * teacher to supervise a class/subject they teach when an alternative
     * is available.
     */
    public function generateSupervision(ExamPeriod $period): array
    {
        $staffPool = $period->staffPool;
        if ($staffPool->isEmpty()) {
            throw new \RuntimeException('No staff selected for the supervision pool.');
        }

        $entries = $period->entries()->with('examSession')->orderBy('exam_date')->get()
            ->sortBy(fn ($e) => $e->exam_date->toDateString() . '-' . $e->examSession->sort_order);

        if ($entries->isEmpty()) {
            throw new \RuntimeException('Build the timetable before planning supervision.');
        }

        // Teachers of each (class_level -> subject) pair, to deprioritise as their own invigilator.
        $ownTeacherMap = ClassArmSubject::whereIn('class_arm_id', function ($q) use ($period) {
                $q->select('id')->from('class_arms')->whereIn('class_level_id', $period->classLevels->pluck('id'));
            })
            ->get()
            ->groupBy(fn ($cas) => $cas->subject_id)
            ->map(fn ($group) => $group->pluck('teacher_id')->filter()->unique()->all());

        $counts = $staffPool->mapWithKeys(fn ($u) => [$u->id => 0]);
        $busy = []; // "date|session_id" => [user_id, ...]
        $unassigned = [];

        DB::transaction(function () use ($period, $entries, $staffPool, $ownTeacherMap, &$counts, &$busy, &$unassigned) {
            ExamSupervisor::whereIn('exam_timetable_entry_id', $entries->pluck('id'))->delete();

            foreach ($entries as $entry) {
                $slotKey = $entry->exam_date->toDateString() . '|' . $entry->exam_session_id;
                $busyHere = $busy[$slotKey] ?? [];
                $avoid = $ownTeacherMap[$entry->subject_id] ?? [];

                $candidate = $staffPool
                    ->reject(fn ($u) => in_array($u->id, $busyHere, true))
                    ->sortBy(fn ($u) => [in_array($u->id, $avoid, true) ? 1 : 0, $counts[$u->id]])
                    ->first();

                if (!$candidate) {
                    $unassigned[] = $entry->id;
                    continue;
                }

                ExamSupervisor::create([
                    'exam_timetable_entry_id' => $entry->id,
                    'user_id'                 => $candidate->id,
                ]);

                $counts[$candidate->id]++;
                $busy[$slotKey][] = $candidate->id;
            }

            $period->update(['status' => 'supervision_planned']);
        });

        return ['assigned' => $entries->count() - count($unassigned), 'unassigned' => count($unassigned)];
    }
}
