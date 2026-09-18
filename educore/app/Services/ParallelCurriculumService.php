<?php

namespace App\Services;

use App\Models\AssessmentTemplate;
use App\Models\AssessmentTemplateComponent;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumComposite;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumIntegration;
use App\Models\ParallelCurriculumScore;
use App\Models\ReportCardPublication;
use App\Models\SchoolSetting;
use App\Models\Score;
use App\Models\StudentEnrollment;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ParallelCurriculumService
{
    public const SCORE_SOURCE = 'parallel_curriculum';
    public const SOURCE_REFERENCE_TYPE = 'parallel_curriculum_composite';

    public function enabledForTenant(int $tenantId): bool
    {
        return SchoolSetting::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('key', 'parallel_curriculum_enabled')
            ->value('value') === '1';
    }

    public function templateForClass(ParallelCurriculumClass $class): ?AssessmentTemplate
    {
        $class->loadMissing(['assessmentTemplate', 'curriculum.defaultAssessmentTemplate']);

        return $class->assessmentTemplate ?: $class->curriculum?->defaultAssessmentTemplate;
    }

    public function componentsForClass(ParallelCurriculumClass $class): Collection
    {
        if (! $force && ! $integration->auto_sync) {
            return $this->persistStatus(
                $existingComposite,
                $enrolment,
                $term,
                $conventionalArm,
                $integration,
                null,
                0,
                0,
                [],
                'pending',
                'Composite auto-sync is disabled for this conventional class-level mapping.'
            );
        }

        $template = $this->templateForClass($class);
        if (! $template) {
            return collect();
        }

        return $template->components()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function syncCurriculum(ParallelCurriculum $curriculum, Term $term, bool $force = true): array
    {
        $enrolments = ParallelCurriculumEnrolment::withoutTenantScope()
            ->where('tenant_id', $curriculum->tenant_id)
            ->where('parallel_curriculum_id', $curriculum->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->with(['student.currentClassArm.classLevel', 'curriculumClass.curriculum.defaultAssessmentTemplate', 'curriculumClass.assessmentTemplate'])
            ->get();

        $result = ['synced' => 0, 'pending' => 0, 'conflict' => 0, 'locked' => 0, 'unmapped' => 0];

        foreach ($enrolments as $enrolment) {
            $status = $this->syncStudent($enrolment, $term, $force)?->sync_status ?? 'pending';
            if (array_key_exists($status, $result)) {
                $result[$status]++;
            } else {
                $result['pending']++;
            }
        }

        return $result;
    }

    public function syncStudent(ParallelCurriculumEnrolment $enrolment, Term $term, bool $force = true): ?ParallelCurriculumComposite
    {
        $tenantId = (int) $enrolment->tenant_id;
        if (! $this->enabledForTenant($tenantId) || (int) $term->session_id !== (int) $enrolment->session_id) {
            return null;
        }

        $enrolment->loadMissing([
            'student.currentClassArm.classLevel',
            'curriculum',
            'curriculumClass.curriculum.defaultAssessmentTemplate',
            'curriculumClass.assessmentTemplate',
            'curriculumClass.subjectAssignments.subject',
        ]);

        $student = $enrolment->student;
        $curriculum = $enrolment->curriculum;
        $class = $enrolment->curriculumClass;

        if (! $student || ! $curriculum || ! $class) {
            return null;
        }

        $existingComposite = ParallelCurriculumComposite::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculum->id)
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->first();

        $conventionalArm = $this->resolveConventionalClassArm($student->id, $term)
            ?: $student->currentClassArm;

        if (! $conventionalArm?->class_level_id) {
            return $this->persistStatus(
                $existingComposite,
                $enrolment,
                $term,
                null,
                null,
                null,
                0,
                0,
                [],
                'unmapped',
                'No conventional class is available for this student in the selected session.'
            );
        }

        $integration = ParallelCurriculumIntegration::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculum->id)
            ->where('destination_class_level_id', $conventionalArm->class_level_id)
            ->where('is_active', true)
            ->with(['destinationSubject', 'destinationClassLevel'])
            ->first();

        if (! $integration) {
            return $this->persistStatus(
                $existingComposite,
                $enrolment,
                $term,
                $conventionalArm,
                null,
                null,
                0,
                0,
                [],
                'unmapped',
                'No result-integration rule is configured for the student\'s conventional class level.'
            );
        }

        $template = $this->templateForClass($class);
        $components = $this->componentsForClass($class);
        $componentWeight = round((float) $components->sum('weight_percentage'), 2);
        if (! $template || $components->isEmpty() || $componentWeight <= 0) {
            return $this->persistStatus(
                $existingComposite,
                $enrolment,
                $term,
                $conventionalArm,
                $integration,
                null,
                0,
                0,
                [],
                'pending',
                'The parallel class has no usable assessment template.'
            );
        }

        $subjectAssignments = $class->subjectAssignments
            ->where('is_active', true)
            ->filter(fn ($assignment) => $assignment->subject)
            ->values();

        if ($subjectAssignments->isEmpty()) {
            return $this->persistStatus(
                $existingComposite,
                $enrolment,
                $term,
                $conventionalArm,
                $integration,
                null,
                0,
                0,
                [],
                'pending',
                'No active subjects are assigned to the parallel class.'
            );
        }

        $scores = ParallelCurriculumScore::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->whereIn('subject_id', $subjectAssignments->pluck('subject_id'))
            ->whereIn('assessment_template_component_id', $components->pluck('id'))
            ->get()
            ->groupBy('subject_id');

        $breakdown = [];
        $completedTotals = [];

        foreach ($subjectAssignments as $assignment) {
            $rows = $scores->get($assignment->subject_id, collect());
            $rowsByComponent = $rows->keyBy('assessment_template_component_id');
            $complete = $components->every(fn (AssessmentTemplateComponent $component) =>
                $rowsByComponent->has($component->id) && $rowsByComponent->get($component->id)?->score !== null
            );

            $weightedTotal = round((float) $rows->sum('score'), 2);
            $percentage = $complete
                ? round(($weightedTotal / $componentWeight) * 100, 2)
                : null;

            if ($complete) {
                $completedTotals[] = $percentage;
            }

            $breakdown[] = [
                'subject_id' => (int) $assignment->subject_id,
                'subject_name' => $assignment->subject->name,
                'complete' => $complete,
                'weighted_total' => $weightedTotal,
                'percentage' => $percentage,
                'components' => $components->map(fn (AssessmentTemplateComponent $component) => [
                    'id' => $component->id,
                    'name' => $component->name,
                    'weight' => (float) $component->weight_percentage,
                    'score' => $rowsByComponent->get($component->id)?->score,
                ])->values()->all(),
            ];
        }

        $subjectCount = count($breakdown);
        $completedCount = count($completedTotals);
        $minimum = max(1, (int) $integration->minimum_completed_subjects);
        $isComplete = $integration->require_all_subjects
            ? $completedCount === $subjectCount
            : $completedCount >= $minimum;

        if (! $isComplete) {
            $composite = $this->persistStatus(
                $existingComposite,
                $enrolment,
                $term,
                $conventionalArm,
                $integration,
                null,
                $subjectCount,
                $completedCount,
                $breakdown,
                'pending',
                $integration->require_all_subjects
                    ? "Waiting for all {$subjectCount} parallel subjects to be completed."
                    : "Waiting for at least {$minimum} completed parallel subjects."
            );
            $this->clearDerivedScoresIfSafe($composite);

            return $composite;
        }

        $average = round(array_sum($completedTotals) / max(1, $completedCount), 2);
        $composite = $this->persistStatus(
            $existingComposite,
            $enrolment,
            $term,
            $conventionalArm,
            $integration,
            $average,
            $subjectCount,
            $completedCount,
            $breakdown,
            'pending',
            'Composite calculated; waiting for conventional score distribution.'
        );

        if (ReportCardPublication::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('class_arm_id', $conventionalArm->id)
            ->where('term_id', $term->id)
            ->where('status', 'published')
            ->exists()) {
            $composite->update([
                'sync_status' => 'locked',
                'sync_message' => 'The conventional result is published. Unpublish it before refreshing the derived score.',
            ]);

            return $composite->refresh();
        }

        $assessmentTypes = AssessmentType::resolvedForClassLevel($term->id, $conventionalArm->class_level_id);
        if ($assessmentTypes->isEmpty()) {
            $composite->update([
                'sync_status' => 'pending',
                'sync_message' => 'No conventional assessment template is materialized for this class level and term.',
            ]);

            return $composite->refresh();
        }

        $distribution = $this->distributeScore($average, $assessmentTypes);
        $conflicts = [];

        foreach ($assessmentTypes as $type) {
            $existing = Score::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('subject_id', $integration->destination_subject_id)
                ->where('assessment_type_id', $type->id)
                ->where('term_id', $term->id)
                ->first();

            if (! $existing) {
                continue;
            }

            $ownedByThisComposite = $existing->score_source === self::SCORE_SOURCE
                && (int) $existing->source_reference_id === (int) $composite->id;

            if (! $ownedByThisComposite && $existing->score !== null) {
                $conflicts[] = $type->name;
            }
        }

        if ($conflicts !== []) {
            $composite->update([
                'sync_status' => 'conflict',
                'sync_message' => 'Existing conventional score(s) prevent derived sync: '.implode(', ', $conflicts).'.',
            ]);

            return $composite->refresh();
        }

        DB::transaction(function () use ($tenantId, $student, $term, $integration, $assessmentTypes, $distribution, $composite): void {
            Score::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('score_source', self::SCORE_SOURCE)
                ->where('source_reference_type', self::SOURCE_REFERENCE_TYPE)
                ->where('source_reference_id', $composite->id)
                ->delete();

            foreach ($assessmentTypes as $type) {
                Score::withoutTenantScope()->create([
                    'tenant_id' => $tenantId,
                    'student_id' => $student->id,
                    'subject_id' => $integration->destination_subject_id,
                    'assessment_type_id' => $type->id,
                    'term_id' => $term->id,
                    'session_id' => $term->session_id,
                    'entered_by' => null,
                    'score' => $distribution[$type->id],
                    'objective_score' => null,
                    'theory_score' => null,
                    'cbt_exam_id' => null,
                    'entered_at' => now(),
                    'score_source' => self::SCORE_SOURCE,
                    'source_reference_type' => self::SOURCE_REFERENCE_TYPE,
                    'source_reference_id' => $composite->id,
                    'is_source_locked' => true,
                    'source_synced_at' => now(),
                ]);
            }

            $composite->update([
                'sync_status' => 'synced',
                'sync_message' => 'Composite distributed to the conventional score sheet.',
            ]);
        });

        return $composite->refresh();
    }

    public function distributeScore(float $average, Collection $assessmentTypes): array
    {
        $totalWeight = (float) $assessmentTypes->sum('weight_percentage');
        if ($totalWeight <= 0) {
            return [];
        }

        $average = max(0, min(100, $average));
        $distribution = [];

        foreach ($assessmentTypes as $type) {
            $distribution[(int) $type->id] = round(
                $average * ((float) $type->weight_percentage / $totalWeight),
                2
            );
        }

        return $distribution;
    }

    private function resolveConventionalClassArm(int $studentId, Term $term): ?ClassArm
    {
        $enrolment = StudentEnrollment::withoutTenantScope()
            ->where('student_id', $studentId)
            ->where('session_id', $term->session_id)
            ->where(function ($query) use ($term): void {
                $query->where('term_id', $term->id)->orWhereNull('term_id');
            })
            ->orderByRaw('CASE WHEN term_id = ? THEN 0 ELSE 1 END', [$term->id])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->with('classArm.classLevel')
            ->first();

        return $enrolment?->classArm;
    }

    private function persistStatus(
        ?ParallelCurriculumComposite $composite,
        ParallelCurriculumEnrolment $enrolment,
        Term $term,
        ?ClassArm $conventionalArm,
        ?ParallelCurriculumIntegration $integration,
        ?float $average,
        int $subjectCount,
        int $completedCount,
        array $breakdown,
        string $status,
        string $message
    ): ParallelCurriculumComposite {
        $attributes = [
            'tenant_id' => $enrolment->tenant_id,
            'parallel_curriculum_id' => $enrolment->parallel_curriculum_id,
            'parallel_curriculum_integration_id' => $integration?->id,
            'parallel_curriculum_class_id' => $enrolment->parallel_curriculum_class_id,
            'conventional_class_arm_id' => $conventionalArm?->id,
            'student_id' => $enrolment->student_id,
            'destination_subject_id' => $integration?->destination_subject_id,
            'term_id' => $term->id,
            'session_id' => $term->session_id,
            'average_score' => $average,
            'subject_count' => $subjectCount,
            'completed_subject_count' => $completedCount,
            'subject_breakdown' => $breakdown,
            'sync_status' => $status,
            'sync_message' => $message,
            'computed_at' => now(),
        ];

        if ($composite) {
            $composite->update($attributes);

            return $composite->refresh();
        }

        return ParallelCurriculumComposite::withoutTenantScope()->create($attributes);
    }

    private function clearDerivedScoresIfSafe(ParallelCurriculumComposite $composite): void
    {
        if (! $composite->conventional_class_arm_id) {
            return;
        }

        $published = ReportCardPublication::withoutTenantScope()
            ->where('tenant_id', $composite->tenant_id)
            ->where('class_arm_id', $composite->conventional_class_arm_id)
            ->where('term_id', $composite->term_id)
            ->where('status', 'published')
            ->exists();

        if ($published) {
            return;
        }

        Score::withoutTenantScope()
            ->where('tenant_id', $composite->tenant_id)
            ->where('score_source', self::SCORE_SOURCE)
            ->where('source_reference_type', self::SOURCE_REFERENCE_TYPE)
            ->where('source_reference_id', $composite->id)
            ->delete();
    }
}
