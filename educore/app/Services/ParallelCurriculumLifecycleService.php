<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumPromotion;
use App\Models\ParallelCurriculumPromotionRule;
use App\Models\ParallelCurriculumScore;
use App\Models\ParallelCurriculumTransfer;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParallelCurriculumLifecycleService
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
        private readonly ParallelCurriculumResultService $results,
    ) {}

    public function transfer(
        ParallelCurriculumEnrolment $enrolment,
        ParallelCurriculumClass $destinationClass,
        ParallelCurriculumClassArm $destinationArm,
        string $reason,
        ?string $effectiveDate,
        ?int $actorId
    ): ParallelCurriculumTransfer {
        $tenantId = (int) $enrolment->tenant_id;

        abort_unless(
            (int) $destinationClass->tenant_id === $tenantId
            && (int) $destinationArm->tenant_id === $tenantId,
            403
        );

        if (! $enrolment->is_active) {
            throw ValidationException::withMessages([
                'student_id' => 'Only an active parallel-curriculum placement can be transferred.',
            ]);
        }

        if (
            ! $destinationClass->is_active
            || ! $destinationArm->is_active
            || (int) $destinationArm->parallel_curriculum_class_id !== (int) $destinationClass->id
        ) {
            throw ValidationException::withMessages([
                'destination_arm_id' => 'Select an active arm that belongs to the destination parallel class.',
            ]);
        }

        if (
            (int) $destinationClass->parallel_curriculum_id
            !== (int) $enrolment->parallel_curriculum_id
        ) {
            throw ValidationException::withMessages([
                'destination_class_id' => 'Inter-programme movement is not a class transfer. Assign the learner to the other programme separately.',
            ]);
        }

        if (
            (int) $enrolment->parallel_curriculum_class_id === (int) $destinationClass->id
            && (int) $enrolment->parallel_curriculum_class_arm_id === (int) $destinationArm->id
        ) {
            throw ValidationException::withMessages([
                'destination_arm_id' => 'The learner is already in the selected parallel class arm.',
            ]);
        }

        if ($this->parallel->enrolmentPlacementLocked($enrolment)) {
            throw ValidationException::withMessages([
                'student_id' => 'This placement belongs to a published parallel result. Unpublish the affected result before transferring the learner.',
            ]);
        }

        $movementType =
            (int) $enrolment->parallel_curriculum_class_id === (int) $destinationClass->id
                ? ParallelCurriculumTransfer::TYPE_INTRA_CLASS
                : ParallelCurriculumTransfer::TYPE_INTER_CLASS;

        if (
            $movementType === ParallelCurriculumTransfer::TYPE_INTER_CLASS
            && ParallelCurriculumScore::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $enrolment->student_id)
                ->where('session_id', $enrolment->session_id)
                ->where('parallel_curriculum_class_id', $enrolment->parallel_curriculum_class_id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'destination_class_id' => 'Inter-class transfer is blocked after parallel scores have been recorded in the source class. Remove/correct the unpublished score records first, or complete the movement through the next-session promotion workflow.',
            ]);
        }

        $this->assertArmCapacity($destinationArm, $enrolment->session_id, $enrolment->id);

        return DB::transaction(function () use (
            $enrolment,
            $destinationClass,
            $destinationArm,
            $reason,
            $effectiveDate,
            $actorId,
            $movementType,
            $tenantId
        ): ParallelCurriculumTransfer {
            $locked = ParallelCurriculumEnrolment::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereKey($enrolment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromClassId = (int) $locked->parallel_curriculum_class_id;
            $fromArmId = $locked->parallel_curriculum_class_arm_id
                ? (int) $locked->parallel_curriculum_class_arm_id
                : null;

            $locked->forceFill([
                'parallel_curriculum_class_id' => $destinationClass->id,
                'parallel_curriculum_class_arm_id' => $destinationArm->id,
            ])->save();

            $transfer = ParallelCurriculumTransfer::withoutTenantScope()->create([
                'tenant_id' => $tenantId,
                'parallel_curriculum_id' => $locked->parallel_curriculum_id,
                'student_id' => $locked->student_id,
                'enrolment_id' => $locked->id,
                'session_id' => $locked->session_id,
                'from_class_id' => $fromClassId,
                'from_arm_id' => $fromArmId,
                'to_class_id' => $destinationClass->id,
                'to_arm_id' => $destinationArm->id,
                'movement_type' => $movementType,
                'reason' => trim($reason),
                'effective_date' => $effectiveDate,
                'status' => 'completed',
                'processed_by' => $actorId,
                'processed_at' => now(),
            ]);

            // Intra-class arm movement leaves score ownership unchanged. An
            // inter-class move is allowed only before score entry, so any
            // pending composites can safely be recalculated for the new level.
            if ($movementType === ParallelCurriculumTransfer::TYPE_INTER_CLASS) {
                $terms = Term::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('session_id', $locked->session_id)
                    ->get();

                foreach ($terms as $term) {
                    $this->parallel->syncStudent($locked->fresh(), $term, false);
                }
            }

            return $transfer;
        });
    }

    /**
     * @return array{
     *   source_session: AcademicSession,
     *   target_session: AcademicSession,
     *   final_term: ?Term,
     *   rows: Collection,
     *   counts: array<string,int>
     * }
     */
    public function promotionPreview(
        ParallelCurriculum $curriculum,
        AcademicSession $sourceSession,
        AcademicSession $targetSession
    ): array {
        $tenantId = (int) $curriculum->tenant_id;

        if (
            (int) $sourceSession->tenant_id !== $tenantId
            || (int) $targetSession->tenant_id !== $tenantId
            || (int) $sourceSession->id === (int) $targetSession->id
        ) {
            throw ValidationException::withMessages([
                'target_session_id' => 'Choose a different source and destination academic session from this school.',
            ]);
        }

        $finalTerm = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('session_id', $sourceSession->id)
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();

        $rules = ParallelCurriculumPromotionRule::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculum->id)
            ->where('is_active', true)
            ->with(['destinationClass.arms', 'sourceClass.arms'])
            ->get()
            ->keyBy('source_class_id');

        $enrolments = ParallelCurriculumEnrolment::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculum->id)
            ->where('session_id', $sourceSession->id)
            ->where('is_active', true)
            ->with(['student', 'curriculumClass.arms', 'curriculumClassArm'])
            ->orderBy('parallel_curriculum_class_id')
            ->orderBy('student_id')
            ->get();

        $rows = $enrolments->map(function (ParallelCurriculumEnrolment $enrolment) use (
            $rules,
            $finalTerm,
            $targetSession
        ): array {
            $rule = $rules->get($enrolment->parallel_curriculum_class_id);

            if (! $rule) {
                return $this->blockedPromotionRow(
                    $enrolment,
                    'No active promotion rule is configured for this parallel class.'
                );
            }

            if (! $finalTerm) {
                return $this->blockedPromotionRow(
                    $enrolment,
                    'The source session has no academic term to use for promotion assessment.'
                );
            }

            $report = $this->results->studentReport(
                $enrolment->curriculumClass,
                $finalTerm,
                (int) $enrolment->student_id
            );

            if (! $report) {
                return $this->blockedPromotionRow(
                    $enrolment,
                    'No final-term result is available for this learner.'
                );
            }

            $studentResult = $report['student_result'];
            $average = $studentResult['average'];
            $failedSubjects = (int) $studentResult['failed_subjects'];
            $complete = (bool) $studentResult['complete'];

            if ($rule->require_complete_result && ! $complete) {
                return $this->blockedPromotionRow(
                    $enrolment,
                    'Final-term result is incomplete.',
                    $average,
                    $failedSubjects
                );
            }

            if ($rule->require_complete_result && ! $report['is_published']) {
                return $this->blockedPromotionRow(
                    $enrolment,
                    'Publish the final parallel result before promotion.',
                    $average,
                    $failedSubjects
                );
            }

            $passed = $average !== null
                && (float) $average >= (float) $rule->minimum_average
                && $failedSubjects <= (int) $rule->max_failed_subjects;

            if ($passed && $rule->is_terminal) {
                return $this->promotionRow(
                    $enrolment,
                    ParallelCurriculumPromotion::DECISION_GRADUATED,
                    null,
                    null,
                    $average,
                    $failedSubjects,
                    'Terminal parallel class completed.'
                );
            }

            if ($passed) {
                $destinationClass = $rule->destinationClass;
                if (! $destinationClass || ! $destinationClass->is_active) {
                    return $this->blockedPromotionRow(
                        $enrolment,
                        'The configured destination parallel class is unavailable.',
                        $average,
                        $failedSubjects
                    );
                }

                $destinationArm = $this->resolveDestinationArm(
                    $enrolment,
                    $destinationClass,
                    $rule->arm_strategy,
                    $targetSession->id
                );

                if (! $destinationArm) {
                    return $this->blockedPromotionRow(
                        $enrolment,
                        'No active destination arm with available capacity could be resolved.',
                        $average,
                        $failedSubjects
                    );
                }

                return $this->promotionRow(
                    $enrolment,
                    ParallelCurriculumPromotion::DECISION_PROMOTED,
                    $destinationClass,
                    $destinationArm,
                    $average,
                    $failedSubjects,
                    'Promotion criteria satisfied.'
                );
            }

            $repeatDecision = $rule->failure_action === ParallelCurriculumPromotionRule::FAILURE_RETAIN
                ? ParallelCurriculumPromotion::DECISION_RETAIN
                : ParallelCurriculumPromotion::DECISION_REPEAT;

            $repeatArm = $enrolment->curriculumClassArm;
            if (! $repeatArm || ! $repeatArm->is_active) {
                $repeatArm = $this->resolveDestinationArm(
                    $enrolment,
                    $enrolment->curriculumClass,
                    'same_name',
                    $targetSession->id
                );
            }

            if (! $repeatArm) {
                return $this->blockedPromotionRow(
                    $enrolment,
                    'No active arm is available for the repeat/retain placement.',
                    $average,
                    $failedSubjects
                );
            }

            return $this->promotionRow(
                $enrolment,
                $repeatDecision,
                $enrolment->curriculumClass,
                $repeatArm,
                $average,
                $failedSubjects,
                'Promotion criteria were not satisfied.'
            );
        })->values();

        return [
            'source_session' => $sourceSession,
            'target_session' => $targetSession,
            'final_term' => $finalTerm,
            'rows' => $rows,
            'counts' => [
                'total' => $rows->count(),
                'promoted' => $rows->where('decision', ParallelCurriculumPromotion::DECISION_PROMOTED)->count(),
                'repeat' => $rows->where('decision', ParallelCurriculumPromotion::DECISION_REPEAT)->count(),
                'retain' => $rows->where('decision', ParallelCurriculumPromotion::DECISION_RETAIN)->count(),
                'graduated' => $rows->where('decision', ParallelCurriculumPromotion::DECISION_GRADUATED)->count(),
                'blocked' => $rows->where('decision', 'blocked')->count(),
            ],
        ];
    }

    public function executePromotion(
        ParallelCurriculum $curriculum,
        AcademicSession $sourceSession,
        AcademicSession $targetSession,
        ?int $actorId
    ): array {
        $preview = $this->promotionPreview($curriculum, $sourceSession, $targetSession);

        if ($preview['counts']['blocked'] > 0) {
            throw ValidationException::withMessages([
                'promotion' => $preview['counts']['blocked'].
                    ' learner(s) are blocked. Resolve the preview issues before running promotion.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $graduated = 0;

        DB::transaction(function () use (
            $preview,
            $curriculum,
            $sourceSession,
            $targetSession,
            $actorId,
            &$created,
            &$updated,
            &$graduated
        ): void {
            foreach ($preview['rows'] as $row) {
                /** @var ParallelCurriculumEnrolment $source */
                $source = $row['enrolment'];

                if ($row['decision'] === ParallelCurriculumPromotion::DECISION_GRADUATED) {
                    ParallelCurriculumPromotion::withoutTenantScope()->updateOrCreate(
                        [
                            'tenant_id' => $curriculum->tenant_id,
                            'source_enrolment_id' => $source->id,
                            'target_session_id' => $targetSession->id,
                        ],
                        $this->promotionPayload(
                            $row,
                            $curriculum,
                            $sourceSession,
                            $targetSession,
                            $actorId
                        )
                    );
                    $graduated++;
                    continue;
                }

                $destinationClass = $row['destination_class'];
                $destinationArm = $row['destination_arm'];

                $existing = ParallelCurriculumEnrolment::withoutTenantScope()
                    ->where('tenant_id', $curriculum->tenant_id)
                    ->where('parallel_curriculum_id', $curriculum->id)
                    ->where('student_id', $source->student_id)
                    ->where('session_id', $targetSession->id)
                    ->first();

                ParallelCurriculumEnrolment::withoutTenantScope()->updateOrCreate(
                    [
                        'tenant_id' => $curriculum->tenant_id,
                        'parallel_curriculum_id' => $curriculum->id,
                        'student_id' => $source->student_id,
                        'session_id' => $targetSession->id,
                    ],
                    [
                        'parallel_curriculum_class_id' => $destinationClass->id,
                        'parallel_curriculum_class_arm_id' => $destinationArm->id,
                        'is_active' => true,
                    ]
                );

                $existing ? $updated++ : $created++;

                ParallelCurriculumPromotion::withoutTenantScope()->updateOrCreate(
                    [
                        'tenant_id' => $curriculum->tenant_id,
                        'source_enrolment_id' => $source->id,
                        'target_session_id' => $targetSession->id,
                    ],
                    $this->promotionPayload(
                        $row,
                        $curriculum,
                        $sourceSession,
                        $targetSession,
                        $actorId
                    )
                );
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'graduated' => $graduated,
            'total' => $created + $updated + $graduated,
        ];
    }

    private function promotionPayload(
        array $row,
        ParallelCurriculum $curriculum,
        AcademicSession $sourceSession,
        AcademicSession $targetSession,
        ?int $actorId
    ): array {
        /** @var ParallelCurriculumEnrolment $source */
        $source = $row['enrolment'];

        return [
            'parallel_curriculum_id' => $curriculum->id,
            'student_id' => $source->student_id,
            'source_session_id' => $sourceSession->id,
            'source_class_id' => $source->parallel_curriculum_class_id,
            'source_arm_id' => $source->parallel_curriculum_class_arm_id,
            'destination_class_id' => $row['destination_class']?->id,
            'destination_arm_id' => $row['destination_arm']?->id,
            'decision' => $row['decision'],
            'average_score' => $row['average'],
            'failed_subjects' => $row['failed_subjects'],
            'reason' => $row['reason'],
            'processed_by' => $actorId,
            'processed_at' => now(),
        ];
    }

    private function resolveDestinationArm(
        ParallelCurriculumEnrolment $source,
        ParallelCurriculumClass $destinationClass,
        string $strategy,
        int $targetSessionId
    ): ?ParallelCurriculumClassArm {
        $arms = $destinationClass->arms
            ->where('is_active', true)
            ->values();

        if ($arms->isEmpty()) {
            return null;
        }

        if ($strategy === 'same_name' && $source->curriculumClassArm) {
            $sameName = $arms->first(
                fn (ParallelCurriculumClassArm $arm) =>
                    mb_strtolower(trim($arm->name))
                    === mb_strtolower(trim($source->curriculumClassArm->name))
            );

            if ($sameName && $this->armHasCapacity($sameName, $targetSessionId)) {
                return $sameName;
            }
        }

        return $arms->first(
            fn (ParallelCurriculumClassArm $arm) =>
                $this->armHasCapacity($arm, $targetSessionId)
        );
    }

    private function armHasCapacity(
        ParallelCurriculumClassArm $arm,
        int $sessionId,
        ?int $excludeEnrolmentId = null
    ): bool {
        if (! $arm->capacity) {
            return true;
        }

        $count = ParallelCurriculumEnrolment::withoutTenantScope()
            ->where('tenant_id', $arm->tenant_id)
            ->where('parallel_curriculum_class_arm_id', $arm->id)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->when($excludeEnrolmentId, fn ($query) => $query->where('id', '!=', $excludeEnrolmentId))
            ->count();

        return $count < (int) $arm->capacity;
    }

    private function assertArmCapacity(
        ParallelCurriculumClassArm $arm,
        int $sessionId,
        ?int $excludeEnrolmentId = null
    ): void {
        if (! $this->armHasCapacity($arm, $sessionId, $excludeEnrolmentId)) {
            throw ValidationException::withMessages([
                'destination_arm_id' => 'The destination parallel class arm has reached its configured capacity.',
            ]);
        }
    }

    private function blockedPromotionRow(
        ParallelCurriculumEnrolment $enrolment,
        string $reason,
        ?float $average = null,
        int $failedSubjects = 0
    ): array {
        return [
            'enrolment' => $enrolment,
            'student' => $enrolment->student,
            'decision' => 'blocked',
            'destination_class' => null,
            'destination_arm' => null,
            'average' => $average,
            'failed_subjects' => $failedSubjects,
            'reason' => $reason,
        ];
    }

    private function promotionRow(
        ParallelCurriculumEnrolment $enrolment,
        string $decision,
        ?ParallelCurriculumClass $destinationClass,
        ?ParallelCurriculumClassArm $destinationArm,
        ?float $average,
        int $failedSubjects,
        string $reason
    ): array {
        return [
            'enrolment' => $enrolment,
            'student' => $enrolment->student,
            'decision' => $decision,
            'destination_class' => $destinationClass,
            'destination_arm' => $destinationArm,
            'average' => $average,
            'failed_subjects' => $failedSubjects,
            'reason' => $reason,
        ];
    }
}
