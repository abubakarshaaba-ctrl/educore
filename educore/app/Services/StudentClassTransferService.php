<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\Student;
use App\Models\StudentClassTransfer;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StudentClassTransferService
{
    public function __construct(private readonly LifecycleAuditLogger $auditLogger)
    {
    }

    public function request(
        User $actor,
        int $studentId,
        int $destinationClassArmId,
        string $effectiveDate,
        string $reason,
        ?UploadedFile $supportingDocument = null,
        ?Request $request = null,
    ): StudentClassTransfer {
        $tenantId = $this->tenantId($actor);
        $activeContext = $this->activeAcademicContext($tenantId);

        return DB::transaction(function () use (
            $actor,
            $tenantId,
            $studentId,
            $destinationClassArmId,
            $effectiveDate,
            $reason,
            $supportingDocument,
            $request,
            $activeContext,
        ): StudentClassTransfer {
            $student = Student::where('tenant_id', $tenantId)
                ->whereKey($studentId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$student->isActive()) {
                throw ValidationException::withMessages([
                    'student_id' => 'Only active students can be transferred between classes.',
                ]);
            }

            $pendingExists = StudentClassTransfer::where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('status', StudentClassTransfer::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();
            if ($pendingExists) {
                throw ValidationException::withMessages([
                    'student_id' => 'This student already has a pending interclass transfer request.',
                ]);
            }

            $currentEnrollment = $this->currentEnrollmentForUpdate($tenantId, $student);
            $destination = ClassArm::where('tenant_id', $tenantId)
                ->whereKey($destinationClassArmId)
                ->firstOrFail();

            if ((int) $currentEnrollment->class_arm_id === (int) $destination->id) {
                throw ValidationException::withMessages([
                    'to_class_arm_id' => 'Destination class arm must be different from the current class arm.',
                ]);
            }

            $documentPath = $supportingDocument?->store('student-class-transfers/'.$tenantId);

            $transfer = StudentClassTransfer::create([
                'tenant_id' => $tenantId,
                'student_id' => $student->id,
                'academic_session_id' => $activeContext['session']->id,
                'term_id' => $activeContext['term']->id,
                'from_class_arm_id' => $currentEnrollment->class_arm_id,
                'to_class_arm_id' => $destination->id,
                'effective_date' => $effectiveDate,
                'reason' => $reason,
                'status' => StudentClassTransfer::STATUS_PENDING,
                'requested_by' => $actor->id,
                'supporting_document' => $documentPath,
            ]);

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $transfer,
                'student_class_transfer.requested',
                [],
                [
                    'student_id' => $student->id,
                    'from_class_arm_id' => $currentEnrollment->class_arm_id,
                    'to_class_arm_id' => $destination->id,
                    'academic_session_id' => $activeContext['session']->id,
                    'term_id' => $activeContext['term']->id,
                    'effective_date' => $effectiveDate,
                    'status' => StudentClassTransfer::STATUS_PENDING,
                ],
                $reason,
                $request,
            );

            return $transfer;
        });
    }

    public function approve(User $actor, int $transferId, ?Request $request = null): StudentClassTransfer
    {
        $tenantId = $this->tenantId($actor);

        return DB::transaction(function () use ($actor, $tenantId, $transferId, $request): StudentClassTransfer {
            $snapshot = StudentClassTransfer::where('tenant_id', $tenantId)
                ->whereKey($transferId)
                ->firstOrFail();

            $student = Student::where('tenant_id', $tenantId)
                ->whereKey($snapshot->student_id)
                ->lockForUpdate()
                ->firstOrFail();

            $transfer = StudentClassTransfer::where('tenant_id', $tenantId)
                ->whereKey($transferId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->status !== StudentClassTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only pending transfer requests can be approved.',
                ]);
            }
            if (!$student->isActive()) {
                throw ValidationException::withMessages([
                    'transfer' => 'The student is no longer active.',
                ]);
            }

            $currentEnrollment = $this->currentEnrollmentForUpdate($tenantId, $student);
            if ((int) $currentEnrollment->class_arm_id !== (int) $transfer->from_class_arm_id) {
                throw ValidationException::withMessages([
                    'transfer' => 'The student current enrollment no longer matches the transfer source class.',
                ]);
            }
            if ((int) $student->current_class_arm_id !== (int) $transfer->from_class_arm_id) {
                throw ValidationException::withMessages([
                    'transfer' => 'The student current class no longer matches the transfer source class.',
                ]);
            }

            $destination = ClassArm::where('tenant_id', $tenantId)
                ->whereKey($transfer->to_class_arm_id)
                ->lockForUpdate()
                ->firstOrFail();
            if ((int) $transfer->from_class_arm_id === (int) $destination->id) {
                throw ValidationException::withMessages([
                    'transfer' => 'Source and destination class arms must be different.',
                ]);
            }
            $this->assertDestinationHasCapacity($tenantId, $destination);

            $currentEnrollment->forceFill([
                'is_current' => false,
                'end_date' => $transfer->effective_date,
                'status' => StudentEnrollment::STATUS_TRANSFERRED,
                'ended_by' => $actor->id,
                'ended_reason' => 'Interclass transfer #'.$transfer->id,
            ])->save();

            $newEnrollment = StudentEnrollment::create([
                'tenant_id' => $tenantId,
                'student_id' => $student->id,
                'class_arm_id' => $destination->id,
                'session_id' => $transfer->academic_session_id,
                'term_id' => $transfer->term_id,
                'start_date' => $transfer->effective_date,
                'end_date' => null,
                'is_current' => true,
                'status' => StudentEnrollment::STATUS_ACTIVE,
                'created_by' => $actor->id,
            ]);

            $oldClassArmId = $student->current_class_arm_id;
            $student->forceFill(['current_class_arm_id' => $destination->id])->save();
            $syncedSubjects = $student->syncCompulsorySubjects($transfer->academic_session_id);

            $transfer->forceFill([
                'status' => StudentClassTransfer::STATUS_COMPLETED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'completed_at' => now(),
            ])->save();

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $transfer,
                'student_class_transfer.completed',
                [
                    'student_id' => $student->id,
                    'class_arm_id' => $oldClassArmId,
                    'current_enrollment_id' => $currentEnrollment->id,
                    'transfer_status' => StudentClassTransfer::STATUS_PENDING,
                ],
                [
                    'student_id' => $student->id,
                    'class_arm_id' => $destination->id,
                    'new_enrollment_id' => $newEnrollment->id,
                    'transfer_status' => StudentClassTransfer::STATUS_COMPLETED,
                    'synced_subjects' => $syncedSubjects,
                ],
                $transfer->reason,
                $request,
            );

            return $transfer->fresh();
        });
    }

    public function reject(
        User $actor,
        int $transferId,
        string $reason,
        ?Request $request = null,
    ): StudentClassTransfer {
        $tenantId = $this->tenantId($actor);

        return DB::transaction(function () use ($actor, $tenantId, $transferId, $reason, $request): StudentClassTransfer {
            $transfer = StudentClassTransfer::where('tenant_id', $tenantId)
                ->whereKey($transferId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->status !== StudentClassTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only pending transfer requests can be rejected.',
                ]);
            }

            $transfer->forceFill([
                'status' => StudentClassTransfer::STATUS_REJECTED,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $transfer,
                'student_class_transfer.rejected',
                ['status' => StudentClassTransfer::STATUS_PENDING],
                ['status' => StudentClassTransfer::STATUS_REJECTED],
                $reason,
                $request,
            );

            return $transfer->fresh();
        });
    }

    public function cancel(
        User $actor,
        int $transferId,
        string $reason,
        ?Request $request = null,
    ): StudentClassTransfer {
        $tenantId = $this->tenantId($actor);

        return DB::transaction(function () use ($actor, $tenantId, $transferId, $reason, $request): StudentClassTransfer {
            $transfer = StudentClassTransfer::where('tenant_id', $tenantId)
                ->whereKey($transferId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->status !== StudentClassTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only pending transfer requests can be cancelled.',
                ]);
            }

            $transfer->forceFill([
                'status' => StudentClassTransfer::STATUS_CANCELLED,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $transfer,
                'student_class_transfer.cancelled',
                ['status' => StudentClassTransfer::STATUS_PENDING],
                ['status' => StudentClassTransfer::STATUS_CANCELLED],
                $reason,
                $request,
            );

            return $transfer->fresh();
        });
    }

    public function activeAcademicContextOrNull(int $tenantId): ?array
    {
        $sessions = AcademicSession::where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->get();
        if ($sessions->count() !== 1) {
            return null;
        }

        $terms = Term::where('tenant_id', $tenantId)
            ->where('session_id', $sessions->first()->id)
            ->where('is_current', true)
            ->get();
        if ($terms->count() !== 1) {
            return null;
        }

        return [
            'session' => $sessions->first(),
            'term' => $terms->first(),
        ];
    }

    private function activeAcademicContext(int $tenantId): array
    {
        $context = $this->activeAcademicContextOrNull($tenantId);
        if (!$context) {
            throw ValidationException::withMessages([
                'student_id' => 'Exactly one active academic session and active term must exist before requesting a transfer.',
            ]);
        }

        return $context;
    }

    private function currentEnrollmentForUpdate(int $tenantId, Student $student): StudentEnrollment
    {
        $currentEnrollments = StudentEnrollment::where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->where('is_current', true)
            ->lockForUpdate()
            ->get();

        if ($currentEnrollments->count() !== 1) {
            throw ValidationException::withMessages([
                'student_id' => 'Student must have exactly one current enrollment before transfer.',
            ]);
        }

        $currentEnrollment = $currentEnrollments->first();
        if ((int) $currentEnrollment->class_arm_id !== (int) $student->current_class_arm_id) {
            throw ValidationException::withMessages([
                'student_id' => 'Student current enrollment does not match the current class arm.',
            ]);
        }

        return $currentEnrollment;
    }

    private function assertDestinationHasCapacity(int $tenantId, ClassArm $destination): void
    {
        if (!Schema::hasColumn('class_arms', 'capacity') || empty($destination->capacity)) {
            return;
        }

        $activeCount = Student::where('tenant_id', $tenantId)
            ->where('current_class_arm_id', $destination->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->count();
        if ($activeCount >= (int) $destination->capacity) {
            throw ValidationException::withMessages([
                'transfer' => 'Destination class arm has reached its configured capacity.',
            ]);
        }
    }

    private function tenantId(User $actor): int
    {
        $tenantId = (int) $actor->tenant_id;
        abort_unless($tenantId > 0, 403, 'A tenant context is required for interclass transfers.');

        return $tenantId;
    }
}
