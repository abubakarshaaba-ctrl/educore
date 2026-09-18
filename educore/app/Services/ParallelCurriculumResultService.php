<?php

namespace App\Services;

use App\Models\AssessmentTemplateComponent;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassGrade;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumGrade;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumScore;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Support\Collection;

class ParallelCurriculumResultService
{
    public function __construct(private readonly ParallelCurriculumService $parallel) {}

    public function classReport(ParallelCurriculumClass $class, Term $term): array
    {
        $class->loadMissing([
            'curriculum.grades',
            'classGrades',
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

        $grades = $class->classGrades->isNotEmpty()
            ? $class->classGrades
            : ($class->curriculum?->grades ?? collect());

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
            'grading_source' => $gradingSource,
            'grading_scale_complete' => $this->gradingScaleCoversAllScores($grades),
            'publication' => $publication,
            'is_published' => $publication?->isPublished() ?? false,
            'students_count' => $results->count(),
            'subjects_count' => $subjectAssignments->count(),
            'complete_students_count' => $results->where('complete', true)->count(),
            'ungraded_subject_results_count' => $results->sum(
                fn (array $row) => $row['subjects']->filter(
                    fn (array $subject) => $subject['percentage'] !== null && $subject['grade'] === null
                )->count()
            ),
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

    public function publishedForStudent(Student $student): Collection
    {
        $enrolments = ParallelCurriculumEnrolment::with('curriculumClass.curriculum')
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->get();

        if ($enrolments->isEmpty()) {
            return collect();
        }

        $classIds = $enrolments->pluck('parallel_curriculum_class_id')->unique()->values();
        $enrolmentsByClassAndSession = $enrolments->keyBy(
            fn (ParallelCurriculumEnrolment $enrolment) =>
                $enrolment->parallel_curriculum_class_id.':'.$enrolment->session_id
        );

        $publications = ParallelCurriculumReportPublication::with([
                'curriculum',
                'curriculumClass.curriculum',
                'term.session',
            ])
            ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
            ->whereIn('parallel_curriculum_class_id', $classIds)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return $publications
            ->filter(function (ParallelCurriculumReportPublication $publication) use ($enrolmentsByClassAndSession): bool {
                $term = $publication->term;
                if (! $term) {
                    return false;
                }

                return $enrolmentsByClassAndSession->has(
                    $publication->parallel_curriculum_class_id.':'.$term->session_id
                );
            })
            ->map(function (ParallelCurriculumReportPublication $publication) use ($student): ?array {
                $class = $publication->curriculumClass;
                $term = $publication->term;

                if (! $class || ! $term) {
                    return null;
                }

                $report = $this->studentReport($class, $term, (int) $student->id);
                if (! $report) {
                    return null;
                }

                $row = $report['student_result'];

                return [
                    'id' => (int) $publication->id,
                    'result_type' => 'parallel_curriculum',
                    'curriculum_id' => (int) $class->parallel_curriculum_id,
                    'curriculum' => $report['curriculum']?->name,
                    'class_id' => (int) $class->id,
                    'class_name' => $class->name,
                    'term_id' => (int) $term->id,
                    'term' => $term->name,
                    'session' => $term->session?->name,
                    'average' => $row['average'],
                    'total_score' => $row['grand_total'],
                    'maximum_total' => $row['maximum_total'],
                    'position' => $row['position'],
                    'class_size' => $report['students_count'],
                    'subjects_offered' => $row['subject_count'],
                    'subjects_failed' => $row['failed_subjects'],
                    'publication_status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
                    'published_at' => $publication->published_at?->toIso8601String(),
                    'subjects' => $row['subjects']->map(fn (array $subject) => [
                        'subject_id' => $subject['subject_id'],
                        'subject' => $subject['subject'],
                        'assessments' => collect($subject['components'])->map(fn (array $component) => [
                            'id' => $component['id'],
                            'name' => $component['name'],
                            'score' => $component['score'],
                            'maximum' => $component['maximum'],
                        ])->values()->all(),
                        'total' => $subject['percentage'],
                        'grade' => $subject['grade'] ?? '—',
                        'remark' => $subject['remark'] ?? '—',
                        'is_pass' => $subject['is_pass'],
                    ])->values()->all(),
                ];
            })
            ->filter()
            ->values();
    }

    public function publishedReportForStudent(Student $student, int $publicationId): ?array
    {
        $publication = ParallelCurriculumReportPublication::withoutTenantScope()
            ->with(['curriculumClass.curriculum', 'term.session'])
            ->where('tenant_id', $student->tenant_id)
            ->where('id', $publicationId)
            ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
            ->first();

        if (! $publication || ! $publication->curriculumClass || ! $publication->term) {
            return null;
        }

        $enrolled = ParallelCurriculumEnrolment::withoutTenantScope()
            ->where('tenant_id', $student->tenant_id)
            ->where('parallel_curriculum_class_id', $publication->parallel_curriculum_class_id)
            ->where('student_id', $student->id)
            ->where('session_id', $publication->term->session_id)
            ->where('is_active', true)
            ->exists();

        if (! $enrolled) {
            return null;
        }

        $class = ParallelCurriculumClass::withoutTenantScope()
            ->with(['curriculum.grades', 'curriculum.defaultAssessmentTemplate', 'assessmentTemplate', 'subjectAssignments.subject'])
            ->where('tenant_id', $student->tenant_id)
            ->find($publication->parallel_curriculum_class_id);

        $term = Term::withoutTenantScope()
            ->with('session')
            ->where('tenant_id', $student->tenant_id)
            ->find($publication->term_id);

        if (! $class || ! $term) {
            return null;
        }

        $report = $this->studentReport($class, $term, (int) $student->id);
        if (! $report || ! $report['is_published']) {
            return null;
        }

        return $report;
    }

    public function canPublish(array $report): bool
    {
        if ($report['students_count'] < 1 || $report['subjects_count'] < 1) {
            return false;
        }

        if ($report['components']->isEmpty() || abs((float) $report['component_weight'] - 100.0) > 0.001) {
            return false;
        }

        if (
            $report['grades']->isEmpty()
            || ! $report['grading_scale_complete']
            || $report['ungraded_subject_results_count'] > 0
        ) {
            return false;
        }

        return $report['complete_students_count'] === $report['students_count'];
    }

    private function gradingScaleCoversAllScores(Collection $grades): bool
    {
        if ($grades->isEmpty()) {
            return false;
        }

        $ordered = $grades->sortBy('min_score')->values();
        $first = $ordered->first();
        if (! $first || (float) $first->min_score > 0.001) {
            return false;
        }

        $coveredThrough = (float) $first->max_score;

        foreach ($ordered->skip(1) as $grade) {
            $minimum = (float) $grade->min_score;
            $maximum = (float) $grade->max_score;

            // Parallel scores are stored to two decimal places. A next band may
            // therefore start at most 0.01 above the previous inclusive maximum.
            if ($minimum > $coveredThrough + 0.01001) {
                return false;
            }

            $coveredThrough = max($coveredThrough, $maximum);
        }

        return $coveredThrough >= 99.999;
    }

    private function resolveGrade(Collection $grades, float $score): ParallelCurriculumGrade|ParallelCurriculumClassGrade|null
    {
        return $grades->first(
            fn ($grade) =>
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
