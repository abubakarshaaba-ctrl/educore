<?php

namespace App\Services;

use App\Models\ClassArm;
use App\Models\GradingSystem;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermlySummary;
use Illuminate\Support\Facades\DB;

class ReportCardComputationService
{
    public function compute(int $tenantId, int $classArmId, int $termId): int
    {
        $classArm = ClassArm::where('tenant_id', $tenantId)
            ->whereKey($classArmId)
            ->firstOrFail();
        $term = Term::where('tenant_id', $tenantId)
            ->whereKey($termId)
            ->firstOrFail();
        $students = Student::where('tenant_id', $tenantId)
            ->where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->get();
        $gradingSystem = GradingSystem::where('tenant_id', $tenantId)
            ->where('class_level_id', $classArm->class_level_id)
            ->get();

        return DB::transaction(function () use ($tenantId, $students, $term, $classArm, $gradingSystem): int {
            $termName = strtolower($term->name);
            $isThirdTerm = str_contains($termName, '3rd') || str_contains($termName, 'third');
            $studentIds = $students->pluck('id');
            $allScores = $studentIds->isEmpty()
                ? collect()
                : Score::where('tenant_id', $tenantId)
                    ->whereIn('student_id', $studentIds)
                    ->where('term_id', $term->id)
                    ->get();
            $subjects = Subject::where('tenant_id', $tenantId)
                ->whereIn('id', $allScores->pluck('subject_id')->unique())
                ->orderBy('name')
                ->get()
                ->keyBy('id');

            $subjectClassTotals = [];
            foreach ($subjects as $subjectId => $subject) {
                foreach ($studentIds as $studentId) {
                    $total = $allScores
                        ->where('student_id', $studentId)
                        ->where('subject_id', $subjectId)
                        ->sum('score');
                    if ($total > 0) {
                        $subjectClassTotals[$subjectId][$studentId] = round($total, 1);
                    }
                }
            }

            $priorTermScores = [];
            if ($isThirdTerm) {
                $priorTerms = Term::where('tenant_id', $tenantId)
                    ->where('session_id', $term->session_id)
                    ->where('id', '!=', $term->id)
                    ->orderBy('start_date')
                    ->get();
                foreach ($priorTerms as $priorTerm) {
                    $priorTermScores[$priorTerm->id] = $studentIds->isEmpty()
                        ? collect()
                        : Score::where('tenant_id', $tenantId)
                            ->whereIn('student_id', $studentIds)
                            ->where('term_id', $priorTerm->id)
                            ->get();
                }
            }

            $averages = [];
            foreach ($students as $student) {
                $scores = $allScores->where('student_id', $student->id);
                $subjectTotals = $scores->groupBy('subject_id')
                    ->map(fn ($items) => round($items->sum('score'), 1));
                $subjectCount = $subjectTotals->count();
                $totalScore = round($subjectTotals->sum(), 1);
                $average = $subjectCount > 0 ? round($totalScore / $subjectCount, 2) : 0;

                $failedCount = 0;
                foreach ($subjectTotals as $total) {
                    $grade = $gradingSystem->first(
                        fn ($item) => $total >= $item->min_score && $total <= $item->max_score
                    );
                    if ($grade && !$grade->is_pass_grade) {
                        $failedCount++;
                    }
                }

                $averages[$student->id] = [
                    'student' => $student,
                    'average' => $average,
                    'total_score' => $totalScore,
                    'subjects_offered' => $subjectCount,
                    'subjects_failed' => $failedCount,
                    'subject_totals' => $subjectTotals,
                ];
            }

            $allAverages = collect($averages)->pluck('average');
            $classHighestAvg = $allAverages->max() ?? 0;
            $classLowestAvg = $allAverages->min() ?? 0;

            $sortedAverages = collect($averages)->sortByDesc('average');
            $position = 1;
            $previousAverage = null;
            $previousPosition = 1;
            foreach ($sortedAverages as $studentId => $data) {
                if ($data['average'] === $previousAverage) {
                    $averages[$studentId]['position'] = $previousPosition;
                } else {
                    $averages[$studentId]['position'] = $position;
                    $previousPosition = $position;
                    $previousAverage = $data['average'];
                }
                $position++;
            }

            $classSize = count($averages);
            $computed = 0;
            foreach ($averages as $studentId => $data) {
                $breakdown = [];
                foreach ($subjects as $subjectId => $subject) {
                    if (!isset($data['subject_totals'][$subjectId])) {
                        continue;
                    }
                    $subjectTotal = $data['subject_totals'][$subjectId];
                    $classTotals = $subjectClassTotals[$subjectId] ?? [];
                    $classHighest = $classTotals ? round(max($classTotals), 1) : null;
                    $classLowest = $classTotals ? round(min($classTotals), 1) : null;
                    $classAverage = $classTotals
                        ? round(array_sum($classTotals) / count($classTotals), 1)
                        : null;

                    $subjectPosition = null;
                    if ($classTotals) {
                        arsort($classTotals);
                        $rank = 1;
                        $previousTotal = null;
                        $previousRank = 1;
                        foreach ($classTotals as $candidateStudentId => $candidateTotal) {
                            $candidateRank = $candidateTotal === $previousTotal ? $previousRank : $rank;
                            if ((int) $candidateStudentId === (int) $studentId) {
                                $subjectPosition = $candidateRank;
                                break;
                            }
                            if ($candidateTotal !== $previousTotal) {
                                $previousRank = $rank;
                                $previousTotal = $candidateTotal;
                            }
                            $rank++;
                        }
                    }

                    $grade = $gradingSystem->first(
                        fn ($item) => $subjectTotal >= $item->min_score && $subjectTotal <= $item->max_score
                    );
                    $entry = [
                        'subject_id' => $subjectId,
                        'subject' => $subject->name,
                        'total' => $subjectTotal,
                        'grade' => $grade?->grade_letter ?? '—',
                        'remark' => $grade?->remark ?? '—',
                        'is_pass' => $grade?->is_pass_grade ?? false,
                        'position' => $subjectPosition,
                        'class_highest' => $classHighest,
                        'class_lowest' => $classLowest,
                        'class_avg' => $classAverage,
                    ];

                    if ($isThirdTerm) {
                        $termTotals = [];
                        foreach ($priorTermScores as $priorTermId => $scores) {
                            $termTotals[$priorTermId] = round(
                                $scores->where('student_id', $studentId)
                                    ->where('subject_id', $subjectId)
                                    ->sum('score'),
                                1
                            );
                        }
                        $termTotals[$term->id] = $subjectTotal;
                        $annualTotal = round(array_sum($termTotals), 1);
                        $cumulativeAverage = round($annualTotal / max(count($termTotals), 1), 1);
                        $annualGrade = $gradingSystem->first(
                            fn ($item) => $cumulativeAverage >= $item->min_score && $cumulativeAverage <= $item->max_score
                        );

                        $entry['annual_total'] = $annualTotal;
                        $entry['cumulative_avg'] = $cumulativeAverage;
                        $entry['grade'] = $annualGrade?->grade_letter ?? '—';
                        $entry['remark'] = $annualGrade?->remark ?? '—';
                        $entry['is_pass'] = $annualGrade?->is_pass_grade ?? false;
                        $entry['term_totals'] = $termTotals;
                    }

                    $breakdown[] = $entry;
                }

                TermlySummary::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'student_id' => $studentId,
                        'term_id' => $term->id,
                        'class_arm_id' => $classArm->id,
                    ],
                    [
                        'session_id' => $term->session_id,
                        'total_score' => $data['total_score'],
                        'final_average' => $data['average'],
                        'subjects_offered' => $data['subjects_offered'],
                        'subjects_failed' => $data['subjects_failed'],
                        'position_in_class' => $data['position'],
                        'total_students_in_class' => $classSize,
                        'class_highest_avg' => $classHighestAvg,
                        'class_lowest_avg' => $classLowestAvg,
                        'subject_breakdown' => $breakdown,
                        'computed_at' => now(),
                    ]
                );
                $computed++;
            }

            return $computed;
        });
    }
}
