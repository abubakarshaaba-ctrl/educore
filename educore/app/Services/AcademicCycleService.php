<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\ClassLevel;
use App\Models\CbtExam;
use App\Models\CbtStudentSession;
use App\Models\GradingSystem;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentClassTransfer;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AcademicCycleService
{
    public const DECISION_PROMOTE = StudentProgressionDecision::TYPE_PROMOTE;
    public const DECISION_REPEAT = StudentProgressionDecision::TYPE_REPEAT;
    public const DECISION_RETAIN = StudentProgressionDecision::TYPE_RETAIN;
    public const DECISION_GRADUATE = StudentProgressionDecision::TYPE_GRADUATE;
    public const DECISION_DEFER = StudentProgressionDecision::TYPE_DEFER;
    public const DECISION_NOT_ELIGIBLE = StudentProgressionDecision::TYPE_NOT_ELIGIBLE;

    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function currentSessionForTenant(int $tenantId): ?AcademicSession
    {
        $sessions = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->get();

        return $sessions->count() === 1 ? $sessions->first() : null;
    }

    public function currentTermForTenant(int $tenantId): ?Term
    {
        $session = $this->currentSessionForTenant($tenantId);

        if (!$session) {
            return null;
        }

        $terms = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('session_id', $session->id)
            ->where('is_current', true)
            ->get();

        return $terms->count() === 1 ? $terms->first() : null;
    }

    /**
     * @return array{session: AcademicSession, term: Term}
     */
    public function activeAcademicContext(int $tenantId): array
    {
        $session = $this->currentSessionForTenant($tenantId);
        $term = $this->currentTermForTenant($tenantId);

        if (!$session || !$term) {
            throw ValidationException::withMessages([
                'academic_cycle' => 'Exactly one current academic session and one current term are required.',
            ]);
        }

        return compact('session', 'term');
    }

    public function createSession(int $tenantId, array $data, User $actor, ?Request $request = null): AcademicSession
    {
        Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'activate' => ['nullable', 'boolean'],
        ])->validate();

        $name = trim($data['name']);

        return DB::transaction(function () use ($tenantId, $name, $data, $actor, $request) {
            $exists = AcademicSession::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => 'This academic session already exists for this school.',
                ]);
            }

            $session = AcademicSession::withoutTenantScope()->create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'is_current' => false,
            ]);

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $session,
                'academic_session.created',
                [],
                ['name' => $session->name],
                null,
                $request
            );

            if (!empty($data['activate'])) {
                $session = $this->activateSession($tenantId, $session->id, $actor, $request);
            }

            return $session;
        });
    }

    public function activateSession(int $tenantId, int|AcademicSession $session, User $actor, ?Request $request = null): AcademicSession
    {
        $sessionId = $session instanceof AcademicSession ? $session->id : $session;

        return DB::transaction(function () use ($tenantId, $sessionId, $actor, $request) {
            $sessions = AcademicSession::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->get();

            $target = $sessions->firstWhere('id', $sessionId);

            if (!$target) {
                throw (new ModelNotFoundException())->setModel(AcademicSession::class, [$sessionId]);
            }

            AcademicSession::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('id', '!=', $target->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $oldValues = [
                'previous_current_session_ids' => $sessions->where('is_current', true)->pluck('id')->all(),
            ];

            $target->forceFill(['is_current' => true])->save();

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $target,
                'academic_session.activated',
                $oldValues,
                ['session_id' => $target->id, 'name' => $target->name],
                null,
                $request
            );

            return $target->fresh();
        });
    }

    public function closeSession(int $tenantId, int|AcademicSession $session, User $actor, ?Request $request = null): AcademicSession
    {
        $sessionId = $session instanceof AcademicSession ? $session->id : $session;
        $readiness = $this->sessionClosureReadiness($tenantId, $sessionId);

        $target = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereKey($sessionId)
            ->firstOrFail();

        $this->auditLogger->record(
            $tenantId,
            $actor,
            $target,
            'academic_session.closure_attempted',
            [],
            $readiness->allItems(),
            null,
            $request
        );

        if ($readiness->hasBlockingItems()) {
            $this->auditLogger->record(
                $tenantId,
                $actor,
                $target,
                'academic_session.closure_denied',
                [],
                ['blocking' => $readiness->blocking],
                null,
                $request
            );

            throw ValidationException::withMessages([
                'session' => implode(' ', $readiness->blocking),
            ]);
        }

        return DB::transaction(function () use ($tenantId, $sessionId, $actor, $request) {
            $session = AcademicSession::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$session->is_current) {
                throw ValidationException::withMessages([
                    'session' => 'This academic session is already not current.',
                ]);
            }

            $session->forceFill(['is_current' => false])->save();

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $session,
                'academic_session.closed',
                ['is_current' => true],
                ['is_current' => false],
                null,
                $request
            );

            return $session->fresh();
        });
    }

    public function createTerm(int $tenantId, array $data, User $actor, ?Request $request = null): Term
    {
        Validator::make($data, [
            'session_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'activate' => ['nullable', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($tenantId, $data, $actor, $request) {
            $session = AcademicSession::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereKey($data['session_id'])
                ->firstOrFail();

            $this->assertTermDatesWithinSessionIfSupported($session, $data['start_date'], $data['end_date']);

            $duplicate = Term::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('session_id', $session->id)
                ->where('name', trim($data['name']))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'name' => 'This term already exists for the selected academic session.',
                ]);
            }

            $term = Term::withoutTenantScope()->create([
                'tenant_id'        => $tenantId,
                'session_id'       => $session->id,
                'name'             => trim($data['name']),
                'start_date'       => Carbon::parse($data['start_date'])->toDateString(),
                'end_date'         => Carbon::parse($data['end_date'])->toDateString(),
                'next_term_begins' => !empty($data['next_term_begins'])
                                        ? Carbon::parse($data['next_term_begins'])->toDateString()
                                        : null,
                'is_current' => false,
            ]);

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $term,
                'academic_term.created',
                [],
                [
                    'session_id' => $session->id,
                    'name' => $term->name,
                    'start_date' => $term->start_date?->toDateString(),
                    'end_date' => $term->end_date?->toDateString(),
                ],
                null,
                $request
            );

            if (!empty($data['activate'])) {
                $term = $this->activateTerm($tenantId, $term->id, $actor, $request);
            }

            return $term;
        });
    }

    public function activateTerm(int $tenantId, int|Term $term, User $actor, ?Request $request = null): Term
    {
        $termId = $term instanceof Term ? $term->id : $term;

        return DB::transaction(function () use ($tenantId, $termId, $actor, $request) {
            $target = Term::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereKey($termId)
                ->lockForUpdate()
                ->firstOrFail();

            $session = AcademicSession::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereKey($target->session_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$session->is_current) {
                throw ValidationException::withMessages([
                    'term' => 'Only a term in the current academic session can be activated.',
                ]);
            }

            $terms = Term::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->get();

            $oldValues = [
                'previous_current_term_ids' => $terms->where('is_current', true)->pluck('id')->all(),
            ];

            Term::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('id', '!=', $target->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $target->forceFill(['is_current' => true])->save();

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $target,
                'academic_term.activated',
                $oldValues,
                ['term_id' => $target->id, 'session_id' => $target->session_id],
                null,
                $request
            );

            return $target->fresh();
        });
    }

    public function closeTerm(int $tenantId, int|Term $term, User $actor, ?Request $request = null): Term
    {
        $termId = $term instanceof Term ? $term->id : $term;
        $readiness = $this->termClosureReadiness($tenantId, $termId);

        $target = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereKey($termId)
            ->firstOrFail();

        $this->auditLogger->record(
            $tenantId,
            $actor,
            $target,
            'academic_term.closure_attempted',
            [],
            $readiness->allItems(),
            null,
            $request
        );

        if ($readiness->hasBlockingItems()) {
            $this->auditLogger->record(
                $tenantId,
                $actor,
                $target,
                'academic_term.closure_denied',
                [],
                ['blocking' => $readiness->blocking],
                null,
                $request
            );

            throw ValidationException::withMessages([
                'term' => implode(' ', $readiness->blocking),
            ]);
        }

        return DB::transaction(function () use ($tenantId, $termId, $actor, $request) {
            $term = Term::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereKey($termId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$term->is_current) {
                throw ValidationException::withMessages([
                    'term' => 'This term is already not current.',
                ]);
            }

            $term->forceFill(['is_current' => false])->save();

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $term,
                'academic_term.closed',
                ['is_current' => true],
                ['is_current' => false],
                null,
                $request
            );

            return $term->fresh();
        });
    }

    public function termClosureReadiness(int $tenantId, int $termId): AcademicCycleDecision
    {
        $term = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereKey($termId)
            ->firstOrFail();

        $blocking = [];
        $warnings = [];
        $information = [];

        if (!$term->is_current) {
            $blocking[] = 'The selected term is already not current.';
        }

        $openCbtSessions = $this->openCbtSessionsForTerm($tenantId, $term->id);
        if ($openCbtSessions > 0) {
            $blocking[] = "{$openCbtSessions} CBT session(s) are still in progress for this term.";
        }

        $pendingTransfers = StudentClassTransfer::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('term_id', $term->id)
            ->where('status', StudentClassTransfer::STATUS_PENDING)
            ->count();

        if ($pendingTransfers > 0) {
            $blocking[] = "{$pendingTransfers} pending interclass transfer request(s) must be resolved first.";
        }

        $duplicateCurrent = $this->duplicateCurrentEnrollmentCount($tenantId);
        if ($duplicateCurrent > 0) {
            $blocking[] = "{$duplicateCurrent} student(s) have multiple current enrolments.";
        }

        $pendingSummaries = TermlySummary::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('term_id', $term->id)
            ->where('promotion_status', 'pending')
            ->count();

        if ($pendingSummaries > 0) {
            $warnings[] = "{$pendingSummaries} termly summary record(s) still have pending promotion status.";
        }

        $missingGrading = $this->classLevelsMissingGrading($tenantId);
        if ($missingGrading->isNotEmpty()) {
            $warnings[] = 'Missing grading rules for: ' . $missingGrading->implode(', ') . '.';
        }

        $information[] = 'Term closure preserves scores, attendance, CBT records, invoices, and enrolment history.';

        return $blocking === []
            ? AcademicCycleDecision::allow(['term_id' => $term->id], $warnings, $information)
            : AcademicCycleDecision::deny($blocking, ['term_id' => $term->id], $warnings, $information);
    }

    public function sessionClosureReadiness(int $tenantId, int $sessionId): AcademicCycleDecision
    {
        $session = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereKey($sessionId)
            ->firstOrFail();

        $blocking = [];
        $warnings = [];
        $information = [];

        if (!$session->is_current) {
            $blocking[] = 'The selected academic session is already not current.';
        }

        $currentTerms = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('session_id', $session->id)
            ->where('is_current', true)
            ->count();

        if ($currentTerms > 0) {
            $blocking[] = 'Close the current term before closing the academic session.';
        }

        $pendingTransfers = StudentClassTransfer::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('academic_session_id', $session->id)
            ->where('status', StudentClassTransfer::STATUS_PENDING)
            ->count();

        if ($pendingTransfers > 0) {
            $blocking[] = "{$pendingTransfers} pending interclass transfer request(s) must be resolved first.";
        }

        $duplicateCurrent = $this->duplicateCurrentEnrollmentCount($tenantId);
        if ($duplicateCurrent > 0) {
            $blocking[] = "{$duplicateCurrent} student(s) have multiple current enrolments.";
        }

        $activeWithoutCurrent = Student::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->whereDoesntHave('enrolmentHistory', fn ($q) => $q->where('is_current', true))
            ->count();

        if ($activeWithoutCurrent > 0) {
            $blocking[] = "{$activeWithoutCurrent} active student(s) have no current enrolment.";
        }

        $pendingSummaries = TermlySummary::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('session_id', $session->id)
            ->where('promotion_status', 'pending')
            ->count();

        if ($pendingSummaries > 0) {
            $warnings[] = "{$pendingSummaries} promotion decision(s) are still pending in this session.";
        }

        $information[] = 'Session closure does not run rollover or create destination enrolments.';

        return $blocking === []
            ? AcademicCycleDecision::allow(['session_id' => $session->id], $warnings, $information)
            : AcademicCycleDecision::deny($blocking, ['session_id' => $session->id], $warnings, $information);
    }

    public function storePromotionDecisions(
        int $tenantId,
        int $termId,
        array $decisions,
        User $actor,
        ?Request $request = null
    ): array {
        $term = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereKey($termId)
            ->firstOrFail();

        $saved = 0;
        $skipped = 0;

        DB::transaction(function () use ($tenantId, $term, $decisions, $actor, $request, &$saved, &$skipped) {
            foreach ($decisions as $studentId => $decisionType) {
                $decisionType = $this->normaliseDecisionType((string) $decisionType);

                if (!$decisionType || $decisionType === self::DECISION_DEFER || $decisionType === self::DECISION_NOT_ELIGIBLE) {
                    $skipped++;
                    continue;
                }

                $student = Student::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->whereKey((int) $studentId)
                    ->first();

                if (!$student) {
                    $skipped++;
                    continue;
                }

                $enrollment = StudentEnrollment::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('student_id', $student->id)
                    ->where('is_current', true)
                    ->first();

                if (!$enrollment) {
                    $skipped++;
                    continue;
                }

                $promotionStatus = $this->promotionStatusForDecision($decisionType);

                $summary = TermlySummary::withoutTenantScope()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'student_id' => $student->id,
                        'term_id' => $term->id,
                        'session_id' => $term->session_id,
                    ],
                    [
                        'class_arm_id' => $enrollment->class_arm_id,
                        'promotion_status' => $promotionStatus,
                    ]
                );

                $this->auditLogger->record(
                    $tenantId,
                    $actor,
                    $summary,
                    'student_promotion.decision_saved',
                    [],
                    [
                        'student_id' => $student->id,
                        'term_id' => $term->id,
                        'session_id' => $term->session_id,
                        'decision_type' => $decisionType,
                        'promotion_status' => $promotionStatus,
                    ],
                    null,
                    $request
                );

                $saved++;
            }
        });

        return compact('saved', 'skipped');
    }

    public function previewRollover(int $tenantId, int $sourceSessionId, int $targetSessionId): AcademicRolloverResult
    {
        $source = $this->tenantSession($tenantId, $sourceSessionId);
        $target = $this->tenantSession($tenantId, $targetSessionId);

        if ($source->id === $target->id) {
            throw ValidationException::withMessages([
                'target_session_id' => 'Source and target sessions must be different.',
            ]);
        }

        $result = new AcademicRolloverResult($tenantId, $source->id, $target->id, false);

        $enrollments = StudentEnrollment::withoutTenantScope()
            ->with(['student', 'classArm.classLevel'])
            ->where('tenant_id', $tenantId)
            ->where('session_id', $source->id)
            ->where('is_current', true)
            ->orderBy('class_arm_id')
            ->orderBy('student_id')
            ->get();

        foreach ($enrollments as $enrollment) {
            $result->counts['inspected']++;
            $decision = $this->progressionDecisionForEnrollment($tenantId, $enrollment, $target);

            $row = $this->decisionToRow($decision, $enrollment);
            $row['status'] = $decision->canCommit() ? 'ready' : ($decision->blocking ? 'blocked' : 'skipped');
            $result->addRow($row);
        }

        return $result;
    }

    public function commitRollover(
        int $tenantId,
        int $sourceSessionId,
        int $targetSessionId,
        ?User $actor,
        ?Request $request = null
    ): AcademicRolloverResult {
        $source = $this->tenantSession($tenantId, $sourceSessionId);
        $target = $this->tenantSession($tenantId, $targetSessionId);

        if ($source->id === $target->id) {
            throw ValidationException::withMessages([
                'target_session_id' => 'Source and target sessions must be different.',
            ]);
        }

        $targetTerm = $this->firstTermForSession($tenantId, $target->id);
        if (!$targetTerm) {
            throw ValidationException::withMessages([
                'target_session_id' => 'The target session must have at least one term before rollover.',
            ]);
        }

        $result = new AcademicRolloverResult($tenantId, $source->id, $target->id, true);

        $this->auditLogger->record(
            $tenantId,
            $actor,
            $source,
            'academic_rollover.started',
            [],
            ['source_session_id' => $source->id, 'target_session_id' => $target->id],
            null,
            $request
        );

        $preview = $this->previewRollover($tenantId, $source->id, $target->id);

        foreach ($preview->rows as $previewRow) {
            if (($previewRow['status'] ?? null) !== 'ready') {
                $result->counts['inspected']++;
                $result->addRow(array_merge($previewRow, ['status' => 'skipped']));
                continue;
            }

            try {
                $committed = DB::transaction(function () use ($tenantId, $previewRow, $source, $target, $targetTerm, $actor, $request) {
                    $student = Student::withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->whereKey((int) $previewRow['student_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $currentEnrollments = StudentEnrollment::withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->where('student_id', $student->id)
                        ->where('is_current', true)
                        ->lockForUpdate()
                        ->get();

                    if ($currentEnrollments->count() !== 1) {
                        throw ValidationException::withMessages([
                            'student' => 'Student must have exactly one current enrolment before rollover.',
                        ]);
                    }

                    $sourceEnrollment = $currentEnrollments->first();
                    if ((int) $sourceEnrollment->session_id !== (int) $source->id) {
                        throw ValidationException::withMessages([
                            'student' => 'Current enrolment no longer belongs to the source session.',
                        ]);
                    }

                    $decision = $this->progressionDecisionForEnrollment($tenantId, $sourceEnrollment, $target);
                    if (!$decision->canCommit()) {
                        throw ValidationException::withMessages([
                            'student' => implode(' ', $decision->blocking ?: ['Student is not ready for rollover.']),
                        ]);
                    }

                    $oldValues = [
                        'student_status' => $student->status,
                        'current_class_arm_id' => $student->current_class_arm_id,
                        'source_enrollment_id' => $sourceEnrollment->id,
                    ];

                    if ($decision->isGraduation()) {
                        $sourceEnrollment->forceFill([
                            'is_current' => false,
                            'end_date' => now()->toDateString(),
                            'status' => StudentEnrollment::STATUS_GRADUATED,
                            'ended_by' => $actor?->id,
                            'ended_reason' => 'Academic rollover graduation',
                        ])->save();

                        $student->forceFill([
                            'status' => Student::STATUS_GRADUATED,
                            'graduation_date' => now()->toDateString(),
                        ])->save();

                        $this->auditLogger->record(
                            $tenantId,
                            $actor,
                            $student,
                            'student.graduated',
                            $oldValues,
                            [
                                'source_session_id' => $source->id,
                                'source_enrollment_id' => $sourceEnrollment->id,
                                'decision_type' => $decision->decisionType,
                            ],
                            'Academic rollover graduation',
                            $request
                        );

                        return [
                            'status' => 'graduated',
                            'student_id' => $student->id,
                            'student_name' => $student->full_name,
                            'decision_type' => $decision->decisionType,
                            'source_enrollment_id' => $sourceEnrollment->id,
                            'destination_enrollment_id' => null,
                            'destination_class_arm_id' => null,
                        ];
                    }

                    $destination = ClassArm::withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->whereKey($decision->destinationClassArmId)
                        ->firstOrFail();

                    $duplicateDestination = StudentEnrollment::withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->where('student_id', $student->id)
                        ->where('session_id', $target->id)
                        ->where('class_arm_id', $destination->id)
                        ->where('is_current', true)
                        ->exists();

                    if ($duplicateDestination) {
                        throw ValidationException::withMessages([
                            'student' => 'A current destination enrolment already exists for this student.',
                        ]);
                    }

                    $sourceEnrollment->forceFill([
                        'is_current' => false,
                        'end_date' => now()->toDateString(),
                        'status' => StudentEnrollment::STATUS_CLOSED,
                        'ended_by' => $actor?->id,
                        'ended_reason' => 'Academic rollover to session ' . $target->name,
                    ])->save();

                    $newEnrollment = StudentEnrollment::withoutTenantScope()->create([
                        'tenant_id' => $tenantId,
                        'student_id' => $student->id,
                        'class_arm_id' => $destination->id,
                        'session_id' => $target->id,
                        'term_id' => $targetTerm->id,
                        'start_date' => now()->toDateString(),
                        'end_date' => null,
                        'is_current' => true,
                        'status' => StudentEnrollment::STATUS_ACTIVE,
                        'created_by' => $actor?->id,
                    ]);

                    $student->forceFill(['current_class_arm_id' => $destination->id])->save();
                    $syncedSubjects = $student->fresh()->syncCompulsorySubjects($target->id);

                    $action = $decision->decisionType === self::DECISION_PROMOTE
                        ? 'student_promotion.completed'
                        : 'student_repeat.completed';

                    $this->auditLogger->record(
                        $tenantId,
                        $actor,
                        $student,
                        $action,
                        $oldValues,
                        [
                            'target_session_id' => $target->id,
                            'source_enrollment_id' => $sourceEnrollment->id,
                            'destination_enrollment_id' => $newEnrollment->id,
                            'destination_class_arm_id' => $destination->id,
                            'decision_type' => $decision->decisionType,
                            'synced_subjects' => $syncedSubjects,
                        ],
                        'Academic rollover',
                        $request
                    );

                    return [
                        'status' => $decision->decisionType === self::DECISION_PROMOTE ? 'promoted' : 'repeated',
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'decision_type' => $decision->decisionType,
                        'source_enrollment_id' => $sourceEnrollment->id,
                        'destination_enrollment_id' => $newEnrollment->id,
                        'destination_class_arm_id' => $destination->id,
                    ];
                });

                $result->counts['inspected']++;
                $result->addRow($committed);
            } catch (\Throwable $exception) {
                $result->counts['inspected']++;
                $result->addRow(array_merge($previewRow, [
                    'status' => 'failed',
                    'blocking' => [$exception->getMessage()],
                ]));
            }
        }

        $this->auditLogger->record(
            $tenantId,
            $actor,
            $source,
            'academic_rollover.completed',
            [],
            ['counts' => $result->counts],
            null,
            $request
        );

        return $result;
    }

    public function repairCurrentStateAnalysis(int $tenantId): AcademicCycleDecision
    {
        $blocking = [];
        $warnings = [];
        $information = [];

        $currentSessions = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->pluck('id')
            ->all();

        if (count($currentSessions) === 0) {
            $blocking[] = 'No current academic session is configured.';
        } elseif (count($currentSessions) > 1) {
            $blocking[] = 'Multiple current academic sessions found: ' . implode(', ', $currentSessions) . '.';
        }

        $currentTerms = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->pluck('id')
            ->all();

        if (count($currentTerms) === 0) {
            $blocking[] = 'No current term is configured.';
        } elseif (count($currentTerms) > 1) {
            $blocking[] = 'Multiple current terms found: ' . implode(', ', $currentTerms) . '.';
        }

        $duplicateCurrent = $this->duplicateCurrentEnrollmentCount($tenantId);
        if ($duplicateCurrent > 0) {
            $blocking[] = "{$duplicateCurrent} student(s) have multiple current enrolments.";
        }

        $cacheMismatch = $this->currentClassCacheMismatchCount($tenantId);
        if ($cacheMismatch > 0) {
            $warnings[] = "{$cacheMismatch} student(s) have current_class_arm_id that does not match their current enrolment.";
        }

        $termOwnershipMismatch = Term::withoutTenantScope()
            ->join('academic_sessions', 'academic_sessions.id', '=', 'terms.session_id')
            ->where('terms.tenant_id', $tenantId)
            ->whereColumn('terms.tenant_id', '!=', 'academic_sessions.tenant_id')
            ->count();

        if ($termOwnershipMismatch > 0) {
            $blocking[] = "{$termOwnershipMismatch} term(s) point to an academic session owned by another tenant.";
        }

        $duplicateDestination = StudentEnrollment::withoutTenantScope()
            ->select('student_id', 'session_id', 'class_arm_id')
            ->where('tenant_id', $tenantId)
            ->groupBy('student_id', 'session_id', 'class_arm_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicateDestination > 0) {
            $warnings[] = "{$duplicateDestination} duplicate destination enrolment group(s) were detected.";
        }

        $information[] = 'This review is read-only — issues are reported here but not corrected automatically. Resolve them from the relevant setup screens.';

        return $blocking === []
            ? AcademicCycleDecision::allow(['tenant_id' => $tenantId], $warnings, $information)
            : AcademicCycleDecision::deny($blocking, ['tenant_id' => $tenantId], $warnings, $information);
    }

    public function carryForwardClassArmSubjectsPreview(int $tenantId, int $sourceSessionId, int $targetSessionId): array
    {
        $source = $this->tenantSession($tenantId, $sourceSessionId);
        $target = $this->tenantSession($tenantId, $targetSessionId);
        $rows = [];

        ClassArmSubject::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('session_id', $source->id)
            ->orderBy('class_arm_id')
            ->orderBy('subject_id')
            ->get()
            ->each(function (ClassArmSubject $assignment) use ($tenantId, $target, &$rows) {
                $exists = ClassArmSubject::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('class_arm_id', $assignment->class_arm_id)
                    ->where('subject_id', $assignment->subject_id)
                    ->where('session_id', $target->id)
                    ->exists();

                $rows[] = [
                    'source_assignment_id' => $assignment->id,
                    'class_arm_id' => $assignment->class_arm_id,
                    'subject_id' => $assignment->subject_id,
                    'teacher_id' => $assignment->teacher_id,
                    'status' => $exists ? 'skipped' : 'would_create',
                ];
            });

        return $rows;
    }

    private function progressionDecisionForEnrollment(
        int $tenantId,
        StudentEnrollment $enrollment,
        AcademicSession $targetSession
    ): StudentProgressionDecision {
        $student = $enrollment->student;
        $classArm = $enrollment->classArm;
        $blocking = [];
        $warnings = [];

        if (!$student || (int) $student->tenant_id !== $tenantId) {
            $blocking[] = 'Student is missing or belongs to another tenant.';
        }

        if (!$classArm || (int) $classArm->tenant_id !== $tenantId) {
            $blocking[] = 'Source class arm is missing or belongs to another tenant.';
        }

        if ($student && $student->status !== Student::STATUS_ACTIVE) {
            $blocking[] = 'Only active students are eligible for academic rollover.';
        }

        $currentCount = StudentEnrollment::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $enrollment->student_id)
            ->where('is_current', true)
            ->count();

        if ($currentCount !== 1) {
            $blocking[] = 'Student must have exactly one current enrolment.';
        }

        $summary = $this->latestSummaryForEnrollment($tenantId, $enrollment);
        $decisionType = $this->decisionTypeFromSummary($summary);

        if (!$summary) {
            $warnings[] = 'No termly summary decision exists for this student in the source session.';
        }

        $destinationClassArmId = null;
        if ($decisionType === self::DECISION_PROMOTE && $classArm) {
            $resolution = $this->resolveDestinationClassArm($tenantId, $classArm);
            $destinationClassArmId = $resolution['class_arm_id'];
            $blocking = array_merge($blocking, $resolution['blocking']);
            $warnings = array_merge($warnings, $resolution['warnings']);
            if (!$destinationClassArmId && $resolution['terminal']) {
                $decisionType = self::DECISION_GRADUATE;
            }
        }

        if (in_array($decisionType, [self::DECISION_REPEAT, self::DECISION_RETAIN], true)) {
            $destinationClassArmId = $enrollment->class_arm_id;
        }

        if ($decisionType === self::DECISION_GRADUATE && $classArm && !$this->isTerminalClassArm($tenantId, $classArm)) {
            $blocking[] = 'Student is not in a terminal class.';
        }

        if (in_array($decisionType, [self::DECISION_PROMOTE, self::DECISION_REPEAT, self::DECISION_RETAIN], true) && !$destinationClassArmId) {
            $blocking[] = 'A destination class arm could not be determined.';
        }

        if ($destinationClassArmId) {
            $destination = ClassArm::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereKey($destinationClassArmId)
                ->first();

            if (!$destination) {
                $blocking[] = 'Destination class arm belongs to another tenant or no longer exists.';
            }

            $existingCurrentTarget = StudentEnrollment::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $enrollment->student_id)
                ->where('session_id', $targetSession->id)
                ->where('class_arm_id', $destinationClassArmId)
                ->where('is_current', true)
                ->exists();

            if ($existingCurrentTarget) {
                $blocking[] = 'A current destination enrolment already exists.';
            }
        }

        return new StudentProgressionDecision(
            (int) $enrollment->student_id,
            (int) $enrollment->id,
            (int) $enrollment->class_arm_id,
            (int) $enrollment->session_id,
            (int) $targetSession->id,
            $decisionType,
            $destinationClassArmId,
            array_values(array_unique($blocking)),
            array_values(array_unique($warnings)),
            $summary ? 'Based on termly summary #' . $summary->id : null,
        );
    }

    private function decisionToRow(StudentProgressionDecision $decision, StudentEnrollment $enrollment): array
    {
        return [
            'student_id' => $decision->studentId,
            'student_name' => $enrollment->student?->full_name,
            'admission_number' => $enrollment->student?->admission_number,
            'source_enrollment_id' => $decision->sourceEnrollmentId,
            'source_class_arm_id' => $decision->sourceClassArmId,
            'source_class' => $enrollment->classArm?->full_name,
            'decision_type' => $decision->decisionType,
            'destination_class_arm_id' => $decision->destinationClassArmId,
            'destination_class' => $decision->destinationClassArmId
                ? ClassArm::withoutTenantScope()->with('classLevel')->find($decision->destinationClassArmId)?->full_name
                : null,
            'blocking' => $decision->blocking,
            'warnings' => $decision->warnings,
            'reason' => $decision->reason,
        ];
    }

    private function resolveDestinationClassArm(int $tenantId, ClassArm $sourceArm): array
    {
        $sourceLevel = $sourceArm->classLevel;
        if (!$sourceLevel) {
            return [
                'class_arm_id' => null,
                'terminal' => false,
                'blocking' => ['Source class level is missing.'],
                'warnings' => [],
            ];
        }

        $nextLevels = ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('order_index', '>', $sourceLevel->order_index)
            ->orderBy('order_index')
            ->get();

        if ($nextLevels->isEmpty()) {
            return ['class_arm_id' => null, 'terminal' => true, 'blocking' => [], 'warnings' => []];
        }

        $nextOrder = $nextLevels->first()->order_index;
        $candidateLevels = $nextLevels->where('order_index', $nextOrder)->values();

        if ($candidateLevels->count() > 1) {
            return [
                'class_arm_id' => null,
                'terminal' => false,
                'blocking' => ['Multiple next class levels share the next order. Select destination manually.'],
                'warnings' => [],
            ];
        }

        $arms = ClassArm::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('class_level_id', $candidateLevels->first()->id)
            ->orderBy('name')
            ->get();

        if ($arms->isEmpty()) {
            return [
                'class_arm_id' => null,
                'terminal' => false,
                'blocking' => ['The next class level has no class arms.'],
                'warnings' => [],
            ];
        }

        $sameName = $arms->where('name', $sourceArm->name)->values();
        if ($sameName->count() === 1) {
            return ['class_arm_id' => (int) $sameName->first()->id, 'terminal' => false, 'blocking' => [], 'warnings' => []];
        }

        if ($arms->count() === 1) {
            return ['class_arm_id' => (int) $arms->first()->id, 'terminal' => false, 'blocking' => [], 'warnings' => []];
        }

        return [
            'class_arm_id' => null,
            'terminal' => false,
            'blocking' => ['Multiple destination class arms are possible. Select destination manually.'],
            'warnings' => [],
        ];
    }

    private function isTerminalClassArm(int $tenantId, ClassArm $classArm): bool
    {
        $level = $classArm->classLevel;

        if (!$level) {
            return false;
        }

        return !ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('order_index', '>', $level->order_index)
            ->exists();
    }

    private function latestSummaryForEnrollment(int $tenantId, StudentEnrollment $enrollment): ?TermlySummary
    {
        return TermlySummary::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $enrollment->student_id)
            ->where('session_id', $enrollment->session_id)
            ->orderByDesc('term_id')
            ->orderByDesc('id')
            ->first();
    }

    private function decisionTypeFromSummary(?TermlySummary $summary): string
    {
        return match ($summary?->promotion_status) {
            'promoted' => self::DECISION_PROMOTE,
            'repeat' => self::DECISION_REPEAT,
            'graduated' => self::DECISION_GRADUATE,
            default => self::DECISION_NOT_ELIGIBLE,
        };
    }

    private function normaliseDecisionType(string $decisionType): ?string
    {
        $decisionType = trim($decisionType);

        return in_array($decisionType, [
            self::DECISION_PROMOTE,
            self::DECISION_REPEAT,
            self::DECISION_RETAIN,
            self::DECISION_GRADUATE,
            self::DECISION_DEFER,
            self::DECISION_NOT_ELIGIBLE,
        ], true) ? $decisionType : null;
    }

    private function promotionStatusForDecision(string $decisionType): string
    {
        return match ($decisionType) {
            self::DECISION_PROMOTE => 'promoted',
            self::DECISION_REPEAT, self::DECISION_RETAIN => 'repeat',
            self::DECISION_GRADUATE => 'graduated',
            default => 'pending',
        };
    }

    private function tenantSession(int $tenantId, int $sessionId): AcademicSession
    {
        return AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereKey($sessionId)
            ->firstOrFail();
    }

    private function firstTermForSession(int $tenantId, int $sessionId): ?Term
    {
        return Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('session_id', $sessionId)
            ->orderBy('start_date')
            ->orderBy('id')
            ->first();
    }

    private function assertTermDatesWithinSessionIfSupported(AcademicSession $session, string $startDate, string $endDate): void
    {
        if (!Schema::hasColumn('academic_sessions', 'start_date') || !Schema::hasColumn('academic_sessions', 'end_date')) {
            return;
        }

        if ($session->start_date && Carbon::parse($startDate)->lt(Carbon::parse($session->start_date))) {
            throw ValidationException::withMessages(['start_date' => 'Term start date cannot be before the session start date.']);
        }

        if ($session->end_date && Carbon::parse($endDate)->gt(Carbon::parse($session->end_date))) {
            throw ValidationException::withMessages(['end_date' => 'Term end date cannot be after the session end date.']);
        }
    }

    private function openCbtSessionsForTerm(int $tenantId, int $termId): int
    {
        if (!Schema::hasTable('cbt_exams') || !Schema::hasTable('cbt_student_sessions')) {
            return 0;
        }

        $examIds = CbtExam::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('term_id', $termId)
            ->pluck('id');

        if ($examIds->isEmpty()) {
            return 0;
        }

        return CbtStudentSession::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereIn('cbt_exam_id', $examIds)
            ->where('status', 'in_progress')
            ->count();
    }

    private function duplicateCurrentEnrollmentCount(int $tenantId): int
    {
        return StudentEnrollment::withoutTenantScope()
            ->select('student_id')
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }

    private function currentClassCacheMismatchCount(int $tenantId): int
    {
        return Student::withoutTenantScope()
            ->where('students.tenant_id', $tenantId)
            ->join('student_enrollments', function ($join) {
                $join->on('student_enrollments.student_id', '=', 'students.id')
                    ->on('student_enrollments.tenant_id', '=', 'students.tenant_id')
                    ->where('student_enrollments.is_current', true);
            })
            ->whereColumn('students.current_class_arm_id', '!=', 'student_enrollments.class_arm_id')
            ->count();
    }

    private function classLevelsMissingGrading(int $tenantId): Collection
    {
        $levelIds = StudentEnrollment::withoutTenantScope()
            ->where('student_enrollments.tenant_id', $tenantId)
            ->where('student_enrollments.is_current', true)
            ->join('class_arms', 'class_arms.id', '=', 'student_enrollments.class_arm_id')
            ->pluck('class_arms.class_level_id')
            ->unique()
            ->values();

        if ($levelIds->isEmpty()) {
            return collect();
        }

        $gradedLevelIds = GradingSystem::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereIn('class_level_id', $levelIds)
            ->pluck('class_level_id')
            ->unique();

        return ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $levelIds->diff($gradedLevelIds))
            ->orderBy('order_index')
            ->pluck('name');
    }
}
