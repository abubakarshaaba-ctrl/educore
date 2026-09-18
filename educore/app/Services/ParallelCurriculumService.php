<?php

namespace App\Services;

use App\Models\AssessmentTemplate;
use App\Models\AssessmentTemplateComponent;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\ClassLevelSubject;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumArmSubjectTeacher;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumComposite;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumIntegration;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumScore;
use App\Models\ReportCardPublication;
use App\Models\SchoolSetting;
use App\Models\Score;
use App\Models\StudentEnrollment;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public function effectiveTeacherId(
        ParallelCurriculumClassSubject $assignment,
        ?ParallelCurriculumClassArm $arm = null
    ): ?int {
        if ($arm && Schema::hasTable('parallel_curriculum_arm_subject_teachers')) {
            $override = ParallelCurriculumArmSubjectTeacher::withoutTenantScope()
                ->where('tenant_id', $assignment->tenant_id)
                ->where('parallel_curriculum_class_id', $assignment->parallel_curriculum_class_id)
                ->where('parallel_curriculum_class_arm_id', $arm->id)
                ->where('parallel_curriculum_subject_id', $assignment->parallel_curriculum_subject_id)
                ->where('is_active', true)
                ->first();

            if ($override) {
                return (int) $override->teacher_id;
            }
        }

        return $assignment->teacher_id ? (int) $assignment->teacher_id : null;
    }

    public function componentsForClass(ParallelCurriculumClass $class): Collection
    {
        $template = $this->templateForClass($class);
        if (! $template) {
            return collect();
        }

        return $template->components()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function classStructureLocked(ParallelCurriculumClass $class): bool
    {
        if (Schema::hasTable('parallel_curriculum_report_publications')) {
            $parallelPublished = ParallelCurriculumReportPublication::withoutTenantScope()
                ->where('tenant_id', $class->tenant_id)
                ->where('parallel_curriculum_class_id', $class->id)
                ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
                ->exists();

            if ($parallelPublished) {
                return true;
            }
        }

        if (
            ! Schema::hasTable('parallel_curriculum_composites')
            || ! Schema::hasTable('report_card_publications')
        ) {
            return false;
        }

        return ParallelCurriculumComposite::withoutTenantScope()
            ->where('tenant_id', $class->tenant_id)
            ->where('parallel_curriculum_class_id', $class->id)
            ->get(['conventional_class_arm_id', 'term_id'])
            ->contains(fn (ParallelCurriculumComposite $composite) =>
                $this->compositeConventionalResultPublished($composite)
            );
    }

    public function classPlacementLocked(ParallelCurriculumClass $class, int $sessionId): bool
    {
        if (! Schema::hasTable('parallel_curriculum_report_publications')) {
            return false;
        }

        $termIds = Term::withoutTenantScope()
            ->where('tenant_id', $class->tenant_id)
            ->where('session_id', $sessionId)
            ->pluck('id');

        if ($termIds->isEmpty()) {
            return false;
        }

        return ParallelCurriculumReportPublication::withoutTenantScope()
            ->where('tenant_id', $class->tenant_id)
            ->where('parallel_curriculum_class_id', $class->id)
            ->whereIn('term_id', $termIds)
            ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
            ->exists();
    }

    public function enrolmentPlacementLocked(ParallelCurriculumEnrolment $enrolment): bool
    {
        $class = ParallelCurriculumClass::withoutTenantScope()
            ->where('tenant_id', $enrolment->tenant_id)
            ->find($enrolment->parallel_curriculum_class_id);

        return $class
            ? $this->classPlacementLocked($class, (int) $enrolment->session_id)
            : false;
    }

    public function scoreEntryLocked(ParallelCurriculumEnrolment $enrolment, Term $term): bool
    {
        if ((int) $term->session_id !== (int) $enrolment->session_id) {
            return false;
        }

        if (Schema::hasTable('parallel_curriculum_report_publications')) {
            $parallelPublished = ParallelCurriculumReportPublication::withoutTenantScope()
                ->where('tenant_id', $enrolment->tenant_id)
                ->where('parallel_curriculum_class_id', $enrolment->parallel_curriculum_class_id)
                ->where('term_id', $term->id)
                ->where('status', ParallelCurriculumReportPublication::STATUS_PUBLISHED)
                ->exists();

            if ($parallelPublished) {
                return true;
            }
        }

        $enrolment->loadMissing('student.currentClassArm');
        $student = $enrolment->student;
        if (! $student) {
            return false;
        }

        $conventionalArm = $this->resolveConventionalClassArm($student->id, $term)
            ?: $student->currentClassArm;

        if (! $conventionalArm) {
            return false;
        }

        return ReportCardPublication::withoutTenantScope()
            ->where('tenant_id', $enrolment->tenant_id)
            ->where('class_arm_id', $conventionalArm->id)
            ->where('term_id', $term->id)
            ->where('status', 'published')
            ->exists();
    }

    public function reconcileClassStructure(ParallelCurriculumClass $class): array
    {
        $tenantId = (int) $class->tenant_id;

        $enrolments = ParallelCurriculumEnrolment::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('is_active', true)
            ->get();

        $summary = [
            'synced' => 0,
            'pending' => 0,
            'conflict' => 0,
            'locked' => 0,
            'unmapped' => 0,
            'skipped' => 0,
        ];

        if ($enrolments->isEmpty()) {
            return $summary;
        }

        $termIds = collect();

        if (Schema::hasTable('parallel_curriculum_composites')) {
            $termIds = $termIds->merge(
                ParallelCurriculumComposite::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('parallel_curriculum_class_id', $class->id)
                    ->pluck('term_id')
            );
        }

        if (Schema::hasTable('parallel_curriculum_scores')) {
            $termIds = $termIds->merge(
                ParallelCurriculumScore::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('parallel_curriculum_class_id', $class->id)
                    ->pluck('term_id')
            );
        }

        $currentTerm = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->first();

        if (
            $currentTerm
            && $enrolments->contains(
                fn (ParallelCurriculumEnrolment $enrolment) =>
                    (int) $enrolment->session_id === (int) $currentTerm->session_id
            )
        ) {
            $termIds->push($currentTerm->id);
        }

        $terms = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $termIds->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        foreach ($enrolments as $enrolment) {
            foreach ($terms as $term) {
                if ((int) $term->session_id !== (int) $enrolment->session_id) {
                    continue;
                }

                $existingComposite = ParallelCurriculumComposite::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('parallel_curriculum_id', $enrolment->parallel_curriculum_id)
                    ->where('student_id', $enrolment->student_id)
                    ->where('term_id', $term->id)
                    ->first();

                if ($existingComposite) {
                    if ($this->compositeConventionalResultPublished($existingComposite)) {
                        $summary['locked']++;
                        continue;
                    }

                    // A subject-structure change invalidates any unpublished
                    // conventional row produced from the previous denominator.
                    $this->clearDerivedScoresIfSafe($existingComposite);
                }

                $refreshed = $this->syncStudent($enrolment, $term, false);
                $status = $refreshed?->sync_status;

                if ($status && array_key_exists($status, $summary)) {
                    $summary[$status]++;
                } else {
                    $summary['skipped']++;
                }
            }
        }

        return $summary;
    }

    public function conventionalSubjectAvailableForArm(
        int $tenantId,
        int $subjectId,
        ClassArm $classArm
    ): bool {
        if (! Schema::hasTable('class_level_subjects')) {
            return true;
        }

        $hasCurriculumRules = ClassLevelSubject::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('class_level_id', $classArm->class_level_id)
            ->where('is_active', true)
            ->exists();

        // Preserve legacy schools that have not yet configured the master
        // ClassLevelSubject curriculum for this class level.
        if (! $hasCurriculumRules) {
            return true;
        }

        return ClassLevelSubject::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('class_level_id', $classArm->class_level_id)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->where('subject_status', '!=', 'not_offered')
            ->where(function ($query) use ($classArm): void {
                if ($classArm->academic_track_id) {
                    $query->whereNull('academic_track_id')
                        ->orWhere('academic_track_id', $classArm->academic_track_id);
                } else {
                    $query->whereNull('academic_track_id');
                }
            })
            ->exists();
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

    public function cleanupAfterConventionalUnpublish(
        int $tenantId,
        array $classArmIds,
        int $termId
    ): int {
        if (
            ! Schema::hasTable('parallel_curriculum_composites')
            || ! Schema::hasTable('parallel_curriculum_integrations')
            || ! Schema::hasTable('scores')
        ) {
            return 0;
        }

        $armIds = collect($classArmIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($armIds->isEmpty()) {
            return 0;
        }

        $composites = ParallelCurriculumComposite::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('term_id', $termId)
            ->whereIn('conventional_class_arm_id', $armIds)
            ->get();

        $cleaned = 0;

        foreach ($composites as $composite) {
            $integration = $composite->parallel_curriculum_integration_id
                ? ParallelCurriculumIntegration::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->find($composite->parallel_curriculum_integration_id)
                : null;

            if ($integration?->is_active) {
                continue;
            }

            $this->clearDerivedScoresIfSafe($composite);

            $composite->update([
                'parallel_curriculum_integration_id' => null,
                'destination_subject_id' => null,
                'average_score' => null,
                'subject_count' => 0,
                'completed_subject_count' => 0,
                'subject_breakdown' => [],
                'sync_status' => 'unmapped',
                'sync_message' => 'The conventional result-integration mapping is inactive.',
                'computed_at' => now(),
            ]);

            $cleaned++;
        }

        return $cleaned;
    }

    public function integrationHasPublishedDependencies(ParallelCurriculumIntegration $integration): bool
    {
        if (! Schema::hasTable('report_card_publications')) {
            return false;
        }

        return ParallelCurriculumComposite::withoutTenantScope()
            ->where('tenant_id', $integration->tenant_id)
            ->where('parallel_curriculum_integration_id', $integration->id)
            ->get(['conventional_class_arm_id', 'term_id'])
            ->contains(fn (ParallelCurriculumComposite $composite) =>
                $this->compositeConventionalResultPublished($composite)
            );
    }

    public function reconcileIntegration(ParallelCurriculumIntegration $integration, bool $configurationChanged = false): array
    {
        $tenantId = (int) $integration->tenant_id;
        $summary = [
            'synced' => 0,
            'pending' => 0,
            'conflict' => 0,
            'locked' => 0,
            'unmapped' => 0,
            'skipped' => 0,
        ];

        $composites = ParallelCurriculumComposite::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_integration_id', $integration->id)
            ->get();

        foreach ($composites as $composite) {
            if ($this->compositeConventionalResultPublished($composite)) {
                $summary['locked']++;
                continue;
            }

            $term = Term::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->find($composite->term_id);

            $enrolment = ParallelCurriculumEnrolment::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_id', $composite->parallel_curriculum_id)
                ->where('student_id', $composite->student_id)
                ->where('session_id', $composite->session_id)
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->first();

            if (! $term || ! $enrolment) {
                $summary['skipped']++;
                continue;
            }

            if ($configurationChanged && ! $integration->auto_sync) {
                // A configuration change invalidates any unpublished derived
                // rows produced under the previous mapping. Manual-sync mode
                // must not leave those stale values on the conventional sheet.
                $this->clearDerivedScoresIfSafe($composite);
            }

            $refreshed = $this->syncStudent(
                $enrolment,
                $term,
                $integration->auto_sync
            );
            $status = $refreshed?->sync_status;

            if ($status && array_key_exists($status, $summary)) {
                $summary[$status]++;
            } else {
                $summary['skipped']++;
            }
        }

        return $summary;
    }

    public function deactivateIntegration(ParallelCurriculumIntegration $integration): array
    {
        $tenantId = (int) $integration->tenant_id;

        $integration->update(['is_active' => false]);

        $composites = ParallelCurriculumComposite::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_integration_id', $integration->id)
            ->get();

        $result = [
            'recomputed' => 0,
            'cleared' => 0,
            'preserved' => 0,
        ];

        foreach ($composites as $composite) {
            $derivedQuery = Score::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('score_source', self::SCORE_SOURCE)
                ->where('source_reference_type', self::SOURCE_REFERENCE_TYPE)
                ->where('source_reference_id', $composite->id);

            $hadDerivedScores = (clone $derivedQuery)->exists();

            // A published conventional report is an immutable historical
            // artifact. Keep both its generated score rows and the composite
            // provenance that explains how those rows were produced.
            if ($this->compositeConventionalResultPublished($composite)) {
                if ($hadDerivedScores) {
                    $result['preserved']++;
                }
                continue;
            }

            $term = Term::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->find($composite->term_id);

            $enrolment = ParallelCurriculumEnrolment::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_id', $composite->parallel_curriculum_id)
                ->where('student_id', $composite->student_id)
                ->where('session_id', $composite->session_id)
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->first();

            if ($term && $enrolment) {
                $this->syncStudent($enrolment, $term, true);
            } else {
                $composite->update([
                    'parallel_curriculum_integration_id' => null,
                    'destination_subject_id' => null,
                    'average_score' => null,
                    'subject_count' => 0,
                    'completed_subject_count' => 0,
                    'subject_breakdown' => [],
                    'sync_status' => 'unmapped',
                    'sync_message' => 'The conventional result-integration mapping was removed.',
                    'computed_at' => now(),
                ]);
                $this->clearDerivedScoresIfSafe($composite);
            }

            $hasDerivedScores = (clone $derivedQuery)->exists();
            if ($hadDerivedScores && ! $hasDerivedScores) {
                $result['cleared']++;
            }

            $result['recomputed']++;
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
            $composite = $this->persistStatus(
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
            $this->clearDerivedScoresIfSafe($composite, $existingComposite?->conventional_class_arm_id);

            return $composite;
        }

        $integration = ParallelCurriculumIntegration::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculum->id)
            ->where('destination_class_level_id', $conventionalArm->class_level_id)
            ->where('is_active', true)
            ->with(['destinationSubject', 'destinationClassLevel'])
            ->first();

        if (! $integration) {
            $composite = $this->persistStatus(
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
            $this->clearDerivedScoresIfSafe($composite);

            return $composite;
        }

        if (! $this->conventionalSubjectAvailableForArm(
            $tenantId,
            (int) $integration->destination_subject_id,
            $conventionalArm
        )) {
            $composite = $this->persistStatus(
                $existingComposite,
                $enrolment,
                $term,
                $conventionalArm,
                $integration,
                null,
                0,
                0,
                [],
                'unmapped',
                'The mapped conventional subject is not offered for this student\'s class level or academic track.'
            );
            $this->clearDerivedScoresIfSafe($composite);

            return $composite;
        }

        $template = $this->templateForClass($class);
        $components = $this->componentsForClass($class);
        $componentWeight = round((float) $components->sum('weight_percentage'), 2);
        if (! $template || $components->isEmpty() || $componentWeight <= 0) {
            $composite = $this->persistStatus(
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
            $this->clearDerivedScoresIfSafe($composite);

            return $composite;
        }

        $subjectAssignments = $class->subjectAssignments
            ->where('is_active', true)
            ->filter(fn ($assignment) => $assignment->subject)
            ->values();

        if ($subjectAssignments->isEmpty()) {
            $composite = $this->persistStatus(
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
            $this->clearDerivedScoresIfSafe($composite);

            return $composite;
        }

        $scores = ParallelCurriculumScore::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->whereIn('parallel_curriculum_subject_id', $subjectAssignments->pluck('parallel_curriculum_subject_id'))
            ->whereIn('assessment_template_component_id', $components->pluck('id'))
            ->get()
            ->groupBy('parallel_curriculum_subject_id');

        $breakdown = [];
        $completedTotals = [];

        foreach ($subjectAssignments as $assignment) {
            $rows = $scores->get($assignment->parallel_curriculum_subject_id, collect());
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
                'parallel_curriculum_subject_id' => (int) $assignment->parallel_curriculum_subject_id,
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

        if (! $force && ! $integration->auto_sync) {
            $composite->update([
                'sync_status' => 'pending',
                'sync_message' => 'Composite calculated. Conventional score auto-sync is disabled; run a manual sync when ready.',
            ]);

            return $composite->refresh();
        }

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
            $this->clearDerivedScoresIfSafe($composite);

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
            $this->clearDerivedScoresIfSafe($composite);

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

    private function compositeConventionalResultPublished(ParallelCurriculumComposite $composite): bool
    {
        if (
            ! $composite->conventional_class_arm_id
            || ! Schema::hasTable('report_card_publications')
        ) {
            return false;
        }

        return ReportCardPublication::withoutTenantScope()
            ->where('tenant_id', $composite->tenant_id)
            ->where('class_arm_id', $composite->conventional_class_arm_id)
            ->where('term_id', $composite->term_id)
            ->where('status', 'published')
            ->exists();
    }

    private function clearDerivedScoresIfSafe(
        ParallelCurriculumComposite $composite,
        ?int $fallbackConventionalClassArmId = null
    ): void {
        $classArmId = $composite->conventional_class_arm_id ?: $fallbackConventionalClassArmId;

        $published = $classArmId
            ? ReportCardPublication::withoutTenantScope()
            ->where('tenant_id', $composite->tenant_id)
            ->where('class_arm_id', $classArmId)
            ->where('term_id', $composite->term_id)
            ->where('status', 'published')
            ->exists()
            : false;

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
