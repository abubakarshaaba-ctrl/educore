<?php

namespace App\Services;

use App\Models\AssessmentTemplateComponent;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumGrade;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumScore;
use App\Models\Term;
use Illuminate\Support\Collection;

class ParallelCurriculumResultService
{
    public function __construct(private readonly ParallelCurriculumService $parallel) {}

    public function classReport(ParallelCurriculumClass $class, Term $term): array
    {
        $class->loadMissing([
            'curriculum.grades',
            'curriculum.defaultAssessmentTemplate',
            'assessmentTemplate',
            'subjectAssignments.subject',
        ]);

        $template = $this->parallel->templateForClass($class);
        $components = $this->parallel->componentsForClass($class);
        $componentWeight = round((float) $components->sum('weight_percentage'), 2);

        $subjectAssignments = $class->subjectAssignments
            ->where('is_active', true)
            ->filter(fn ($assignment) => $assignment->subject?->is_active)
            ->values();

        $enrolments = ParallelCurriculumEnrolment::with('student.currentClassArm.classLevel')
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (ParallelCurriculumEnrolment $enrolment) => $enrolment->student?->status === 'active')
            ->sortBy(fn (ParallelCurriculumEnrolment $enrolment) =>
                strtolower(($enrolment->student?->last_name ?? '').' '.($enrolment->student?->first_name ?? ''))
            )
            ->values();

        $scores = collect();
        if ($enrolments->isNotEmpty() && $subjectAssignments->isNotEmpty() && $components->isNotEmpty()) {
            $scores = ParallelCurriculumScore::where('parallel_curriculum_class_id', $class->id)
                ->where('term_id', $term->id)
                ->whereIn('student_id', $enrolments->pluck('student_id'))
                ->whereIn('parallel_curriculum_subject_id', $subjectAssignments->pluck('parallel_curriculum_subject_id'))
                ->whereIn('assessment_template_component_id', $components->pluck('id'))
                ->get()
                ->groupBy(fn (ParallelCurriculumScore $score) =>
                    $score->student_id.':'.$score->parallel_curriculum_subject_id
                );
        }

        $grades = $class->curriculum?->grades ?? collect();

        $results = $enrolments->map(function (ParallelCurriculumEnrolment $enrolment) use (
            $subjectAssignments,
            $components,
            $componentWeight,
            $scores,
            $grades
        ): array {
            $student = $enrolment->student;
            $completedPercentages = [];
            $failedSubjects = 0;

            $subjects = $subjectAssignments->map(function ($assignment) use (
                $student,
                $components,
                $componentWeight,
                $scores,
                $grades,
                &$completedPercentages,
                &$failedSubjects
            ): array {
                $subject = $assignment->subject;
                $rows = $scores->get($student->id.':'.$subject->id, collect());
                $byComponent = $rows->keyBy('assessment_template_component_id');

                $complete = $components->isNotEmpty() && $components->every(
                    fn (AssessmentTemplateComponent $component) =>
                        $byComponent->has($component->id)
                        && $byComponent->get($component->id)?->score !== null
                );

                $rawTotal = round((float) $rows->sum('score'), 2);
                $percentage = $complete && $componentWeight > 0
                    ? round(($rawTotal / $componentWeight) * 100, 2)
                    : null;

                $grade = $percentage === null ? null : $this->resolveGrade($grades, $percentage);
                if ($percentage !== null) {
                    $completedPercentages[] = $percentage;
                    if ($grade && ! $grade->is_pass_grade) {
                        $failedSubjects++;
                    }
                }

                return [
                    'subject_id' => (int) $subject->id,
                    'subject' => $subject->name,
                    'complete' => $complete,
                    'raw_total' => $rawTotal,
                    'percentage' => $percentage,
                    'grade' => $grade?->grade_letter,
                    'remark' => $grade?->remark,
                    'is_pass' => $grade?->is_pass_grade,
                    'components' => $components->map(fn (AssessmentTemplateComponent $component) => [
                        'id' => (int) $component->id,
                        'name' => $component->name,
                        'maximum' => (float) $component->weight_percentage,
                        'score' => $byComponent->get($component->id)?->score,
                    ])->values()->all(),
                ];
            })->values();

            $subjectCount = $subjects->count();
            $completedCount = count($completedPercentages);
            $complete = $subjectCount > 0 && $completedCount === $subjectCount;
            $average = $completedCount > 0
                ? round(array_sum($completedPercentages) / $completedCount, 2)
                : null;
            $grandTotal = round(array_sum($completedPercentages), 2);

            return [
                'student' => $student,
                'enrolment' => $enrolment,
                'subjects' => $subjects,
                'subject_count' => $subjectCount,
                'completed_subject_count' => $completedCount,
                'complete' => $complete,
                'grand_total' => $grandTotal,
                'maximum_total' => $subjectCount * 100,
                'average' => $average,
                'failed_subjects' => $failedSubjects,
                'position' => null,
            ];
        })->values();

        $this->applyPositions($results);

        $publication = ParallelCurriculumReportPublication::where('parallel_curriculum_class_id', $class->id)
            ->where('term_id', $term->id)
            ->first();

        return [
            'curriculum' => $class->curriculum,
            'class' => $class,
            'term' => $term,
            'template' => $template,
            'components' => $components,
            'component_weight' => $componentWeight,
            'subject_assignments' => $subjectAssignments,
            'results' => $results,
            'grades' => $grades,
            'publication' => $publication,
            'is_published' => $publication?->isPublished() ?? false,
            'students_count' => $results->count(),
            'subjects_count' => $subjectAssignments->count(),
            'complete_students_count' => $results->where('complete', true)->count(),
        ];
    }

    public function studentReport(ParallelCurriculumClass $class, Term $term, int $studentId): ?array
    {
        $report = $this->classReport($class, $term);
        $studentResult = $report['results']->first(
            fn (array $row) => (int) $row['student']->id === $studentId
        );

        if (! $studentResult) {
            return null;
        }

        $report['student_result'] = $studentResult;

        return $report;
    }

    public function canPublish(array $report): bool
    {
        if ($report['students_count'] < 1 || $report['subjects_count'] < 1) {
            return false;
        }

        if ($report['components']->isEmpty() || (float) $report['component_weight'] <= 0) {
            return false;
        }

        return $report['complete_students_count'] === $report['students_count'];
    }

    private function resolveGrade(Collection $grades, float $score): ?ParallelCurriculumGrade
    {
        return $grades->first(
            fn (ParallelCurriculumGrade $grade) =>
                $score >= (float) $grade->min_score
                && $score <= (float) $grade->max_score
        );
    }

    private function applyPositions(Collection $results): void
    {
        $rankable = $results
            ->filter(fn (array $row) => $row['complete'] && $row['average'] !== null)
            ->sortByDesc('average')
            ->values();

        $position = 1;
        $previousAverage = null;
        $previousPosition = null;
        $positions = [];

        foreach ($rankable as $row) {
            $studentId = (int) $row['student']->id;
            $average = (float) $row['average'];

            if ($previousAverage !== null && abs($average - $previousAverage) < 0.0001) {
                $positions[$studentId] = $previousPosition;
            } else {
                $positions[$studentId] = $position;
                $previousAverage = $average;
                $previousPosition = $position;
            }

            $position++;
        }

        $results->transform(function (array $row) use ($positions): array {
            $row['position'] = $positions[(int) $row['student']->id] ?? null;

            return $row;
        });
    }
}
