<?php

namespace App\Services\StaffLifecycle;

use App\Models\StaffStatusHistory;
use App\Models\StaffWorkHistory;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReinstateStaff
{
    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function execute(User $actor, User $staff, array $data, ?Request $request = null): User
    {
        $tenantId = (int) $actor->tenant_id;

        if (!$actor->can('staff.reinstate')) {
            throw ValidationException::withMessages([
                'staff' => 'You do not have permission to reinstate staff.',
            ]);
        }

        Validator::make($data, [
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'position_title' => ['required', 'string', 'max:255'],
            'department_name' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'functional_role' => ['nullable', 'string', 'max:150'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'appointment_type' => ['nullable', 'string', 'max:100'],
            'document_path' => ['nullable', 'string', 'max:2048'],
        ])->validate();

        return DB::transaction(function () use ($actor, $staff, $data, $request, $tenantId) {
            $lockedStaff = User::where('tenant_id', $tenantId)
                ->whereKey($staff->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedStaff->isTenantStaff()) {
                throw ValidationException::withMessages([
                    'staff' => 'Only tenant staff accounts can be reinstated.',
                ]);
            }

            $oldStatus = $lockedStaff->employmentStatus();

            if (!StaffLifecycleRules::canReinstate($oldStatus)) {
                throw ValidationException::withMessages([
                    'staff' => 'Only archived staff can be reinstated.',
                ]);
            }

            if ($oldStatus === User::STAFF_STATUS_TERMINATED && !$actor->can('staff.reinstate-terminated')) {
                throw ValidationException::withMessages([
                    'staff' => 'Reinstating terminated staff requires the restricted terminated-reinstatement permission.',
                ]);
            }

            $this->assertReinstatementEffectiveDate($lockedStaff, $data['effective_date']);

            $openHistories = StaffWorkHistory::where('tenant_id', $tenantId)
                ->where('user_id', $lockedStaff->id)
                ->whereNull('end_date')
                ->lockForUpdate()
                ->get();

            if ($openHistories->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'staff' => 'This staff member already has an open work-history period.',
                ]);
            }

            $oldValues = [
                'employment_status' => $oldStatus,
                'is_active' => (bool) $lockedStaff->is_active,
                'employment_started_at' => optional($lockedStaff->employment_started_at)->toDateString(),
                'employment_ended_at' => optional($lockedStaff->employment_ended_at)->toDateString(),
                'exit_reason' => $lockedStaff->exit_reason,
            ];

            $lockedStaff->forceFill([
                'employment_status' => User::STAFF_STATUS_ACTIVE,
                'is_active' => true,
                'employment_started_at' => $data['effective_date'],
                'employment_ended_at' => null,
                'status_changed_at' => now(),
                'exit_reason' => null,
            ])->save();

            $workHistory = StaffWorkHistory::create([
                'tenant_id' => $tenantId,
                'user_id' => $lockedStaff->id,
                'position_title' => $data['position_title'],
                'department_name' => $data['department_name'] ?? null,
                'employment_type' => $data['employment_type'] ?? null,
                'functional_role' => $data['functional_role'] ?? null,
                'grade_level' => $data['grade_level'] ?? null,
                'appointment_type' => $data['appointment_type'] ?? null,
                'start_date' => $data['effective_date'],
                'end_date' => null,
                'change_type' => StaffWorkHistory::CHANGE_REINSTATEMENT,
                'reason' => $data['reason'],
                'document_path' => $data['document_path'] ?? null,
                'recorded_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $statusHistory = StaffStatusHistory::create([
                'tenant_id' => $tenantId,
                'user_id' => $lockedStaff->id,
                'old_status' => $oldStatus,
                'new_status' => User::STAFF_STATUS_ACTIVE,
                'effective_date' => $data['effective_date'],
                'last_working_date' => null,
                'reason' => $data['reason'],
                'document_path' => $data['document_path'] ?? null,
                'changed_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $newValues = [
                'employment_status' => User::STAFF_STATUS_ACTIVE,
                'is_active' => true,
                'employment_started_at' => $data['effective_date'],
                'employment_ended_at' => null,
                'status_history_id' => $statusHistory->id,
                'work_history_id' => $workHistory->id,
            ];

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $lockedStaff,
                'staff.reinstated',
                $oldValues,
                $newValues,
                $data['reason'],
                $request
            );

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $workHistory,
                'staff.work-history.created',
                [],
                $workHistory->only([
                    'position_title',
                    'department_name',
                    'employment_type',
                    'functional_role',
                    'grade_level',
                    'appointment_type',
                    'start_date',
                    'change_type',
                ]),
                $data['reason'],
                $request
            );

            return $lockedStaff->fresh();
        });
    }

    private function assertReinstatementEffectiveDate(User $staff, string $effectiveDate): void
    {
        $effective = Carbon::parse($effectiveDate)->startOfDay();

        if ($effective->gt(today())) {
            throw ValidationException::withMessages([
                'effective_date' => 'Scheduled future reinstatements are not supported in this workflow. Use today or an earlier date.',
            ]);
        }

        if (!$staff->employment_ended_at) {
            throw ValidationException::withMessages([
                'effective_date' => 'The previous employment end date is required before reinstatement can be processed.',
            ]);
        }

        if ($effective->lte($staff->employment_ended_at->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'effective_date' => 'The reinstatement date must be after the previous employment end date.',
            ]);
        }

        $overlapExists = StaffWorkHistory::where('tenant_id', $staff->tenant_id)
            ->where('user_id', $staff->id)
            ->whereDate('start_date', '<=', $effective->toDateString())
            ->where(function ($query) use ($effective) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $effective->toDateString());
            })
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'effective_date' => 'The reinstatement date overlaps an existing work-history period.',
            ]);
        }
    }
}
