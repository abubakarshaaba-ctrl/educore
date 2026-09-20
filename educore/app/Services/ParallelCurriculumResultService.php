<?php

namespace App\Services;

use App\Models\AssessmentTemplateComponent;
use App\Models\ParallelCurriculumSkillRating;
use App\Models\SkillDefinition;
use App\Models\ParallelCurriculumPromotion;
use App\Models\ParallelCurriculumAttendanceRecord;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassGrade;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumGrade;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumResultComment;
use App\Models\ParallelCurriculumScore;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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

        $enrolments = ParallelCurriculumEnrolment::with([
                'student.currentClassArm.classLevel',
                'curriculumClassArm',
            ])
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

        $formTeacherComments = collect();
        if (
            Schema::hasTable('parallel_curriculum_result_comments')
            && $enrolments->isNotEmpty()
        ) {
            $formTeacherComments = ParallelCurriculumResultComment::with('formTeacher')
                ->where('term_id', $term->id)
                ->whereIn('parallel_curriculum_enrolment_id', $enrolments->pluck('id'))
                ->get()
                ->keyBy('parallel_curriculum_enrolment_id');
        }

        $gradingSource = $class->classGrades->isNotEmpty()
            ? 'class'
            : 'programme';

        $grades = $class->classGrades->isNotEmpty()
            ? $class->classGrades
            : ($class->curriculum?->grades ?? collect());

        $results = $enrolments->map(function (ParallelCurriculumEnrolment $enrolment) use (
            $subjectAssignments,
            $components,
            $componentWeight,
            $scores,
            $grades,
            $formTeacherComments
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
            $formTeacherComment = $formTeacherComments->get($enrolment->id);

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
                'form_teacher_comment' => $formTeacherComment?->form_teacher_comment,
                'form_teacher' => $formTeacherComment?->formTeacher,
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
        $enrolments = ParallelCurriculumEnrolment::with([
                'curriculumClass.curriculum',
                'curriculumClassArm',
            ])
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
                    'class_arm_id' => $row['enrolment']->parallel_curriculum_class_arm_id
                        ? (int) $row['enrolment']->parallel_curriculum_class_arm_id
                        : null,
                    'class_arm_name' => $row['enrolment']->curriculumClassArm?->name,
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
                    'form_teacher_comment' => $row['form_teacher_comment'],
                    'form_teacher_name' => $row['form_teacher']?->name,
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
            ->with([
                'curriculum.grades',
                'curriculum.defaultAssessmentTemplate',
                'assessmentTemplate',
                'classGrades',
                'subjectAssignments.subject',
            ])
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

    /**
     * Adapt a standalone parallel result to the same presentation contract used
     * by the conventional EduCore report card. This intentionally centralizes
     * report-card content so parallel and conventional outputs do not drift.
     */
    public function conventionalStyleStudentReport(
        ParallelCurriculumClass $class,
        Term $term,
        int $studentId
    ): ?array {
        $report = $this->studentReport($class, $term, $studentId);
        if (! $report) {
            return null;
        }

        $row = $report['student_result'];
        $student = $row['student'];
        $enrolment = $row['enrolment'];
        $tenantId = (int) $class->tenant_id;

        $arm = $enrolment->curriculumClassArm;
        if ($arm) {
            $arm->loadMissing('classTeacher');
        }

        $armId = (int) ($enrolment->parallel_curriculum_class_arm_id ?? 0);
        $cohort = $report['results']
            ->filter(fn (array $candidate) =>
                ! $armId
                || (int) ($candidate['enrolment']->parallel_curriculum_class_arm_id ?? 0) === $armId
            )
            ->values();

        $classSize = $cohort->count();
        $position = $this->rankStudentWithinCohort($cohort, (int) $student->id);

        $subjectRows = $row['subjects']->map(function (array $subject) use ($cohort): array {
            $subjectId = (int) $subject['subject_id'];

            $cohortScores = $cohort
                ->map(function (array $candidate) use ($subjectId) {
                    $candidateSubject = $candidate['subjects']->first(
                        fn (array $item) => (int) $item['subject_id'] === $subjectId
                    );

                    return $candidateSubject['percentage'] ?? null;
                })
                ->filter(fn ($score) => $score !== null)
                ->map(fn ($score) => (float) $score)
                ->values();

            $score = $subject['percentage'] === null
                ? null
                : (float) $subject['percentage'];

            return [
                'subject_id' => $subjectId,
                'subject_name' => $subject['subject'],
                'scores' => collect($subject['components'])
                    ->mapWithKeys(fn (array $component) => [
                        (int) $component['id'] => $component['score'],
                    ])
                    ->all(),
                'total' => $score,
                'grade' => $subject['grade'] ?? '—',
                'remark' => $subject['remark'] ?? ($subject['complete'] ? '—' : 'Incomplete'),
                'is_pass' => (bool) ($subject['is_pass'] ?? false),
                'class_highest' => $cohortScores->isNotEmpty()
                    ? round((float) $cohortScores->max(), 1)
                    : '—',
                'class_lowest' => $cohortScores->isNotEmpty()
                    ? round((float) $cohortScores->min(), 1)
                    : '—',
                'class_avg' => $cohortScores->isNotEmpty()
                    ? number_format((float) $cohortScores->avg(), 2)
                    : '—',
                'class_position' => $score === null
                    ? '—'
                    : ($this->rankScore($cohortScores, $score) ?? '—'),
                'class_count' => $cohortScores->count(),
            ];
        })->values()->all();

        $termName = strtolower((string) $term->name);
        $isThirdTerm = str_contains($termName, '3rd')
            || str_contains($termName, 'third');

        $overallAverage = $row['average'] === null ? 0.0 : (float) $row['average'];
        $overallTotal = (float) $row['grand_total'];

        if ($isThirdTerm) {
            $sessionTerms = Term::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('session_id', $term->session_id)
                ->orderBy('start_date')
                ->orderBy('id')
                ->get()
                ->take(3)
                ->values();

            $studentRowsByTerm = collect();
            foreach ($sessionTerms as $sessionTerm) {
                if ((int) $sessionTerm->id === (int) $term->id) {
                    $termReport = $report;
                } else {
                    $termReport = $this->classReport($class, $sessionTerm);
                }

                $termStudentRow = $termReport['results']->first(
                    fn (array $candidate) => (int) $candidate['student']->id === (int) $student->id
                );

                $studentRowsByTerm->put((int) $sessionTerm->id, $termStudentRow);
            }

            $termAverages = [];
            foreach ($sessionTerms as $index => $sessionTerm) {
                $termStudentRow = $studentRowsByTerm->get((int) $sessionTerm->id);
                if (($termStudentRow['average'] ?? null) !== null) {
                    $termAverages[] = (float) $termStudentRow['average'];
                }

                foreach ($subjectRows as &$subjectRow) {
                    $termSubject = $termStudentRow
                        ? $termStudentRow['subjects']->first(
                            fn (array $candidateSubject) =>
                                (int) $candidateSubject['subject_id']
                                    === (int) $subjectRow['subject_id']
                        )
                        : null;

                    $subjectRow['term'.($index + 1).'_avg'] =
                        $termSubject['percentage'] ?? null;
                }
                unset($subjectRow);
            }

            foreach ($subjectRows as &$subjectRow) {
                $annualScores = collect([
                    $subjectRow['term1_avg'] ?? null,
                    $subjectRow['term2_avg'] ?? null,
                    $subjectRow['term3_avg'] ?? null,
                ])->filter(fn ($score) => $score !== null)
                  ->map(fn ($score) => (float) $score);

                $annualTotal = round((float) $annualScores->sum(), 1);
                $annualAverage = $annualScores->isNotEmpty()
                    ? round($annualTotal / $annualScores->count(), 1)
                    : null;

                $annualGrade = $annualAverage === null
                    ? null
                    : $this->resolveGrade($report['grades'], $annualAverage);

                $subjectRow['annual_total'] = $annualScores->isNotEmpty()
                    ? $annualTotal
                    : null;
                $subjectRow['cumulative_avg'] = $annualAverage;
                $subjectRow['grade'] = $annualGrade?->grade_letter ?? '—';
                $subjectRow['remark'] = $annualGrade?->remark ?? '—';
                $subjectRow['is_pass'] = (bool) ($annualGrade?->is_pass_grade ?? false);
            }
            unset($subjectRow);

            if ($termAverages !== []) {
                $overallAverage = round(array_sum($termAverages) / count($termAverages), 2);
            }

            $overallTotal = round((float) collect($subjectRows)
                ->sum(fn (array $subjectRow) =>
                    (float) ($subjectRow['cumulative_avg'] ?? 0)
                ), 1);
        }

        $promotion = null;
        if ($isThirdTerm && Schema::hasTable('parallel_curriculum_promotions')) {
            $promotion = ParallelCurriculumPromotion::withoutTenantScope()
                ->with('destinationClass')
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_id', $class->parallel_curriculum_id)
                ->where('student_id', $student->id)
                ->where('source_session_id', $term->session_id)
                ->latest('id')
                ->first();
        }

        $principalRemark = $row['average'] === null
            ? 'Result incomplete. Complete all required parallel subjects before final publication.'
            : PrincipalRemarkService::generate(
                average: $overallAverage,
                position: $position ?? 0,
                totalStudents: $classSize,
                subjectsFailed: (int) $row['failed_subjects'],
                studentName: (string) $student->first_name,
                rotationSeed: (int) $student->id
            );

        $summary = (object) [
            'final_average' => $overallAverage,
            'position_in_class' => $position,
            'total_students_in_class' => $classSize,
            'subjects_offered' => (int) $row['subject_count'],
            'subjects_failed' => (int) $row['failed_subjects'],
            'total_score' => $overallTotal,
            'grand_total' => $overallTotal,
            'form_tutor_remark' => $row['form_teacher_comment'],
            'principal_remark' => $principalRemark,
            'promotion_decision' => $promotion?->decision,
            'promoted_to_class' => $promotion?->destinationClass?->name,
        ];

        $classArm = (object) [
            'id' => $arm?->id,
            'name' => $arm?->name ?? '',
            'classLevel' => (object) [
                'name' => $class->name,
            ],
            'formTutor' => $arm?->classTeacher ?? $row['form_teacher'],
        ];

        $attendanceSummary = [
            'days_open' => '—',
            'days_present' => '—',
            'days_absent' => '—',
            'rate' => '—',
        ];

        if (Schema::hasTable('parallel_curriculum_attendance_records')) {
            $attendanceBase = ParallelCurriculumAttendanceRecord::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_id', $class->id)
                ->where('term_id', $term->id);

            if ($armId) {
                $attendanceBase->where('parallel_curriculum_class_arm_id', $armId);
            }

            $daysOpen = (clone $attendanceBase)
                ->distinct('attendance_date')
                ->count('attendance_date');

            $studentAttendance = (clone $attendanceBase)
                ->where('student_id', $student->id);

            $present = (clone $studentAttendance)
                ->whereIn('status', ['present', 'late'])
                ->count();
            $absent = (clone $studentAttendance)
                ->where('status', 'absent')
                ->count();

            $attendanceSummary = [
                'days_open' => $daysOpen ?: '—',
                'days_present' => $present,
                'days_absent' => $absent,
                'rate' => $daysOpen > 0
                    ? round(($present / $daysOpen) * 100)
                    : '—',
            ];
        }

        $psychomotorSkills = SkillDefinition::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('category', 'psychomotor')
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        $affectiveSkills = SkillDefinition::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('category', 'affective')
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        $skillRatings = Schema::hasTable('parallel_curriculum_skill_ratings')
            ? ParallelCurriculumSkillRating::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_enrolment_id', $enrolment->id)
                ->where('term_id', $term->id)
                ->get()
            : collect();

        return [
            'report' => $report,
            'student' => $student,
            'classArm' => $classArm,
            'term' => $term,
            'session' => $term->session,
            'summary' => $summary,
            'isThirdTerm' => $isThirdTerm,
            'assessmentTypes' => $report['components'],
            'subjectRows' => $subjectRows,
            'gradingSystem' => $report['grades'],
            'psychomotorSkills' => $psychomotorSkills,
            'affectiveSkills' => $affectiveSkills,
            'skillRatings' => $skillRatings,
            'attendanceSummary' => $attendanceSummary,
            'summaries_class_avg' => $cohort->whereNotNull('average')->isNotEmpty()
                ? number_format((float) $cohort->avg('average'), 2)
                : null,
            'parallelProgrammeName' => $report['curriculum']?->name,
            'parallelClassName' => trim($class->name.' '.($arm?->name ?? '')),
            'principalRemark' => $principalRemark,
            'formTeacherName' => $classArm->formTutor?->name,
            'promotion' => $promotion,
        ];
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

    private function rankStudentWithinCohort(
        Collection $cohort,
        int $studentId
    ): ?int {
        $rankable = $cohort
            ->filter(fn (array $row) => $row['average'] !== null)
            ->sortByDesc('average')
            ->values();

        $previousAverage = null;
        $previousPosition = null;

        foreach ($rankable as $index => $row) {
            $average = (float) $row['average'];
            $position = $index + 1;

            if (
                $previousAverage !== null
                && abs($average - $previousAverage) < 0.0001
            ) {
                $position = $previousPosition;
            }

            if ((int) $row['student']->id === $studentId) {
                return $position;
            }

            $previousAverage = $average;
            $previousPosition = $position;
        }

        return null;
    }

    private function rankScore(Collection $scores, float $score): ?int
    {
        if ($scores->isEmpty()) {
            return null;
        }

        $ordered = $scores
            ->map(fn ($value) => (float) $value)
            ->sortDesc()
            ->values();

        $previousScore = null;
        $previousPosition = null;

        foreach ($ordered as $index => $candidate) {
            $position = $index + 1;

            if (
                $previousScore !== null
                && abs($candidate - $previousScore) < 0.0001
            ) {
                $position = $previousPosition;
            }

            if (abs($candidate - $score) < 0.0001) {
                return $position;
            }

            $previousScore = $candidate;
            $previousPosition = $position;
        }

        return null;
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
