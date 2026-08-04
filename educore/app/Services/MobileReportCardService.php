<?php

namespace App\Services;

use App\Models\AssessmentType;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TermlySummary;
use Illuminate\Support\Collection;

/**
 * Builds the native-app report-card payload from the same computed summaries
 * and raw assessment scores used by the web report card.
 */
class MobileReportCardService
{
    public function forStudent(Student $student): Collection
    {
        $summaries = TermlySummary::with(['term.session'])
            ->where('student_id', $student->id)
            ->latest('computed_at')
            ->get();

        if ($summaries->isEmpty()) {
            return collect();
        }

        $termIds = $summaries->pluck('term_id')->unique()->values();
        $scores = Score::with(['subject', 'assessmentType'])
            ->where('student_id', $student->id)
            ->whereIn('term_id', $termIds)
            ->get()
            ->groupBy('term_id');

        $assessmentTypes = AssessmentType::whereIn('term_id', $termIds)
            ->orderBy('is_exam')
            ->orderBy('name')
            ->get()
            ->groupBy('term_id');

        return $summaries->map(function (TermlySummary $summary) use ($scores, $assessmentTypes) {
            $termScores = $scores->get($summary->term_id, collect());
            $types = $assessmentTypes->get($summary->term_id, collect());
            $computed = collect($summary->subject_breakdown ?? [])
                ->keyBy(fn (array $row) => (int) ($row['subject_id'] ?? 0));

            $subjects = $termScores
                ->groupBy('subject_id')
                ->map(function (Collection $subjectScores, int|string $subjectId) use ($types, $computed) {
                    $first = $subjectScores->first();
                    $row = $computed->get((int) $subjectId, []);

                    return [
                        'subject_id' => (int) $subjectId,
                        'subject' => $first?->subject?->name
                            ?? Subject::find($subjectId)?->name
                            ?? 'Subject',
                        'assessments' => $types->map(function (AssessmentType $type) use ($subjectScores) {
                            $score = $subjectScores->firstWhere('assessment_type_id', $type->id);

                            return [
                                'id' => $type->id,
                                'name' => $type->name,
                                'score' => $score?->score,
                                'objective_score' => $score?->objective_score,
                                'theory_score' => $score?->theory_score,
                                'maximum' => $type->weight_percentage,
                            ];
                        })->values(),
                        'total' => (float) ($row['total'] ?? $subjectScores->sum('score')),
                        'grade' => $row['grade'] ?? '—',
                        'remark' => $row['remark'] ?? '—',
                        'is_pass' => (bool) ($row['is_pass'] ?? false),
                        'position' => $row['position'] ?? null,
                        'class_highest' => $row['class_highest'] ?? null,
                        'class_lowest' => $row['class_lowest'] ?? null,
                        'class_average' => $row['class_avg'] ?? null,
                        'term_totals' => $row['term_totals'] ?? null,
                        'cumulative_average' => $row['cumulative_avg'] ?? null,
                    ];
                })
                ->sortBy('subject')
                ->values();

            return [
                'id' => $summary->id,
                'term_id' => $summary->term_id,
                'term' => $summary->term?->name,
                'session' => $summary->term?->session?->name,
                'average' => $summary->final_average,
                'total_score' => $summary->total_score,
                'position' => $summary->position_in_class,
                'class_size' => $summary->total_students_in_class,
                'subjects_offered' => $summary->subjects_offered,
                'subjects_failed' => $summary->subjects_failed,
                'promotion_status' => $summary->promotion_status,
                'form_tutor_remark' => $summary->form_tutor_remark,
                'principal_remark' => $summary->principal_remark,
                'computed_at' => $summary->computed_at?->toIso8601String(),
                'subjects' => $subjects,
            ];
        })->values();
    }
}
