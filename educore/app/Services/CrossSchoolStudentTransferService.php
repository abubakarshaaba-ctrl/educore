<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\PushSubscription;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentStatusHistory;
use App\Models\StudentSubjectSelection;
use App\Models\StudentTransfer;
use App\Models\Tenant;
use App\Models\TransportAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrossSchoolStudentTransferService
{
    public function __construct(private readonly LifecycleAuditLogger $auditLogger)
    {
    }

    public function request(
        User $actor,
        int $studentId,
        int $destinationTenantId,
        ?string $reason,
        ?Request $request = null,
    ): StudentTransfer {
        $sourceTenantId = (int) $actor->tenant_id;
        abort_unless($sourceTenantId > 0, 403, 'A school context is required.');

        if ($destinationTenantId === $sourceTenantId) {
            throw ValidationException::withMessages([
                'to_tenant_id' => 'Destination school must be different from the current school.',
            ]);
        }

        return DB::transaction(function () use (
            $actor,
            $studentId,
            $destinationTenantId,
            $reason,
            $request,
            $sourceTenantId,
        ): StudentTransfer {
            $destination = Tenant::whereKey($destinationTenantId)
                ->where('status', Tenant::STATUS_ACTIVE)
                ->lockForUpdate()
                ->firstOrFail();

            $student = Student::where('tenant_id', $sourceTenantId)
                ->whereKey($studentId)
                ->where('status', Student::STATUS_ACTIVE)
                ->lockForUpdate()
                ->firstOrFail();

            $pending = StudentTransfer::where('student_id', $student->id)
                ->where('status', StudentTransfer::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();
            if ($pending) {
                throw ValidationException::withMessages([
                    'student_id' => 'This student already has a pending cross-school transfer request.',
                ]);
            }

            $transfer = StudentTransfer::create([
                'from_tenant_id' => $sourceTenantId,
                'to_tenant_id' => $destination->id,
                'student_id' => $student->id,
                'destination_student_id' => null,
                'student_name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'reason' => $reason,
                'requested_by' => $actor->id,
                'status' => StudentTransfer::STATUS_PENDING,
            ]);

            $this->auditLogger->record(
                $sourceTenantId,
                $actor,
                $transfer,
                'student.transfer.requested',
                [],
                [
                    'student_id' => $student->id,
                    'from_tenant_id' => $sourceTenantId,
                    'to_tenant_id' => $destination->id,
                    'status' => StudentTransfer::STATUS_PENDING,
                ],
                $reason,
                $request,
            );

            return $transfer;
        });
    }

    /**
     * Complete a cross-school transfer without re-tenanting historical records.
     *
     * The source Student remains owned by the originating school and becomes a
     * transferred-out archive record. A new Student record is created for the
     * receiving school with no class, guardian, finance, academic-history or
     * portal-account inheritance. The receiving school can then complete its
     * normal intake/provisioning workflow.
     */
    public function approve(
        User $actor,
        int $transferId,
        ?Request $request = null,
    ): StudentTransfer {
        $receiverTenantId = (int) $actor->tenant_id;
        abort_unless($receiverTenantId > 0, 403, 'A school context is required.');

        return DB::transaction(function () use ($actor, $transferId, $request, $receiverTenantId): StudentTransfer {
            $transfer = StudentTransfer::whereKey($transferId)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                (int) $transfer->to_tenant_id === $receiverTenantId,
                403,
                'Only the receiving school can approve this transfer.'
            );

            if ($transfer->status !== StudentTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only pending incoming transfers can be approved.',
                ]);
            }

            if ($transfer->destination_student_id) {
                throw ValidationException::withMessages([
                    'transfer' => 'This transfer already has a receiving-school student record.',
                ]);
            }

            $destination = Tenant::whereKey($receiverTenantId)
                ->where('status', Tenant::STATUS_ACTIVE)
                ->lockForUpdate()
                ->firstOrFail();

            $sourceStudent = Student::withoutTenantScope()
                ->whereKey($transfer->student_id)
                ->where('tenant_id', $transfer->from_tenant_id)
                ->lockForUpdate()
                ->first();

            if (!$sourceStudent || !$sourceStudent->isActive()) {
                throw ValidationException::withMessages([
                    'transfer' => 'The source student is no longer an active member of the originating school.',
                ]);
            }

            $destinationStudent = Student::withoutTenantScope()->create([
                'tenant_id' => $receiverTenantId,
                'user_id' => null,
                'admission_number' => $this->destinationAdmissionNumber(
                    $receiverTenantId,
                    $sourceStudent->admission_number,
                ),
                'first_name' => $sourceStudent->first_name,
                'last_name' => $sourceStudent->last_name,
                'middle_name' => $sourceStudent->middle_name,
                'gender' => $sourceStudent->gender,
                'date_of_birth' => $sourceStudent->date_of_birth,
                'state_of_origin' => $sourceStudent->state_of_origin,
                'lga_of_origin' => $sourceStudent->lga_of_origin,
                'religion' => $sourceStudent->religion,
                'blood_group' => $sourceStudent->blood_group,
                'genotype' => $sourceStudent->genotype,
                'passport_photo_path' => null,
                'current_class_arm_id' => null,
                'status' => Student::STATUS_ACTIVE,
                'admission_date' => now()->toDateString(),
                'graduation_date' => null,
            ]);

            StudentEnrollment::withoutTenantScope()
                ->where('tenant_id', $transfer->from_tenant_id)
                ->where('student_id', $sourceStudent->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->get()
                ->each(function (StudentEnrollment $enrollment) use ($transfer): void {
                    $enrollment->forceFill([
                        'is_current' => false,
                        'end_date' => now()->toDateString(),
                        'status' => StudentEnrollment::STATUS_TRANSFERRED_OUT,
                        'ended_by' => $transfer->requested_by,
                        'ended_reason' => 'Cross-school transfer #'.$transfer->id,
                    ])->save();
                });

            StudentSubjectSelection::withoutTenantScope()
                ->where('tenant_id', $transfer->from_tenant_id)
                ->where('student_id', $sourceStudent->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            TransportAssignment::withoutTenantScope()
                ->where('tenant_id', $transfer->from_tenant_id)
                ->where('student_id', $sourceStudent->id)
                ->delete();

            $this->deactivateSourcePortalAccount($sourceStudent, (int) $transfer->from_tenant_id);

            $oldStatus = $sourceStudent->status;
            $sourceStudent->forceFill([
                'current_class_arm_id' => null,
                'status' => Student::STATUS_TRANSFERRED_OUT,
            ])->save();

            StudentStatusHistory::withoutTenantScope()->create([
                'tenant_id' => $transfer->from_tenant_id,
                'student_id' => $sourceStudent->id,
                'old_status' => $oldStatus,
                'new_status' => Student::STATUS_TRANSFERRED_OUT,
                'effective_date' => now()->toDateString(),
                'reason' => $transfer->reason ?: 'Cross-school transfer completed.',
                'destination_school' => $destination->name,
                'changed_by' => $transfer->requested_by,
                'approved_by' => null,
                'approved_at' => now(),
            ]);

            $transfer->forceFill([
                'destination_student_id' => $destinationStudent->id,
                'status' => StudentTransfer::STATUS_COMPLETED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'completed_at' => now(),
            ])->save();

            $this->auditLogger->record(
                $receiverTenantId,
                $actor,
                $transfer,
                'student.transfer.completed',
                [
                    'status' => StudentTransfer::STATUS_PENDING,
                    'source_student_id' => $sourceStudent->id,
                    'from_tenant_id' => $transfer->from_tenant_id,
                ],
                [
                    'status' => StudentTransfer::STATUS_COMPLETED,
                    'destination_student_id' => $destinationStudent->id,
                    'to_tenant_id' => $receiverTenantId,
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
        ?Request $request = null,
    ): StudentTransfer {
        $receiverTenantId = (int) $actor->tenant_id;
        abort_unless($receiverTenantId > 0, 403, 'A school context is required.');

        return DB::transaction(function () use ($actor, $transferId, $request, $receiverTenantId): StudentTransfer {
            $transfer = StudentTransfer::whereKey($transferId)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                (int) $transfer->to_tenant_id === $receiverTenantId,
                403,
                'Only the receiving school can reject this transfer.'
            );

            if ($transfer->status !== StudentTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only pending incoming transfers can be rejected.',
                ]);
            }

            $transfer->forceFill([
                'status' => StudentTransfer::STATUS_REJECTED,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
            ])->save();

            $this->auditLogger->record(
                $receiverTenantId,
                $actor,
                $transfer,
                'student.transfer.rejected',
                ['status' => StudentTransfer::STATUS_PENDING],
                ['status' => StudentTransfer::STATUS_REJECTED],
                null,
                $request,
            );

            return $transfer->fresh();
        });
    }

    private function destinationAdmissionNumber(int $tenantId, ?string $sourceAdmissionNumber): string
    {
        $candidate = trim((string) $sourceAdmissionNumber);
        if ($candidate !== '' && !$this->admissionNumberExists($tenantId, $candidate)) {
            return $candidate;
        }

        do {
            $candidate = 'TRF-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while ($this->admissionNumberExists($tenantId, $candidate));

        return $candidate;
    }

    private function admissionNumberExists(int $tenantId, string $admissionNumber): bool
    {
        return Student::withoutTenantScope()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('admission_number', $admissionNumber)
            ->exists();
    }

    private function deactivateSourcePortalAccount(Student $sourceStudent, int $sourceTenantId): void
    {
        $userIds = collect([$sourceStudent->user_id])
            ->filter()
            ->map(fn ($id) => (int) $id);

        $linkedUsers = User::where('tenant_id', $sourceTenantId)
            ->where('role', 'student')
            ->where(function ($query) use ($sourceStudent): void {
                $query->where('student_id', $sourceStudent->id)
                    ->orWhereKey($sourceStudent->user_id ?: 0);
            })
            ->lockForUpdate()
            ->get();

        $userIds = $userIds->merge($linkedUsers->pluck('id')->map(fn ($id) => (int) $id))->unique();

        if ($userIds->isEmpty()) {
            return;
        }

        User::whereIn('id', $userIds)
            ->where('tenant_id', $sourceTenantId)
            ->where('role', 'student')
            ->update(['is_active' => false]);

        ApiToken::whereIn('user_id', $userIds)->delete();

        // Browser/web-push subscriptions are independent from API bearer tokens.
        // Disable them too so a transferred student cannot continue receiving
        // source-school push notifications after portal access is revoked.
        if (Schema::hasTable('push_subscriptions')) {
            PushSubscription::withoutTenantScope()
                ->where('tenant_id', $sourceTenantId)
                ->whereIn('user_id', $userIds)
                ->update(['is_active' => false]);
        }
    }
}
