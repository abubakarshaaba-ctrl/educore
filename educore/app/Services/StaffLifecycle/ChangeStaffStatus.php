<?php

namespace App\Services\StaffLifecycle;

use App\Models\StaffStatusHistory;
use App\Models\StaffWorkHistory;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ChangeStaffStatus
{
    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function execute(User $actor, User $staff, array $data, ?Request $request = null): User
    {
        $tenantId = (int) $actor->tenant_id;

        if (!$actor->can('staff.status.change')) {
            throw ValidationException::withMessages([
                'status' => 'You do not have permission to change staff lifecycle status.',
            ]);
        }

        if (!$actor->can('staff.status.approve')) {
            throw ValidationException::withMessages([
                'status' => 'Direct staff lifecycle changes require status approval permission.',
            ]);
        }

        Validator::make($data, [
            'new_status' => ['required', 'string'],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'document_path' => ['nullable', 'string', 'max:2048'],
        ])->validate();

        return DB::transaction(function () use ($actor, $staff, $data, $request, $tenantId) {
            $lockedStaff = User::where('tenant_id', $tenantId)
                ->whereKey($staff->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantStaff($lockedStaff);

            if ((int) $lockedStaff->id === (int) $actor->id) {
                throw ValidationException::withMessages([
                    'staff' => 'You cannot deactivate your own staff account through this workflow.',
                ]);
            }

            $oldStatus = $lockedStaff->employmentStatus();
            $newStatus = $data['new_status'];

            if (!StaffLifecycleRules::canChangeDirectly($oldStatus, $newStatus)) {
                throw ValidationException::withMessages([
                    'new_status' => 'This employment status transition is not allowed from the current staff status.',
                ]);
            }

            $openHistories = StaffWorkHistory::where('tenant_id', $tenantId)
                ->where('user_id', $lockedStaff->id)
                ->whereNull('end_date')
                ->lockForUpdate()
                ->get();

            if ($openHistories->count() !== 1) {
                throw ValidationException::withMessages([
                    'staff' => 'This staff member must have exactly one open work-history period. Run the staff work-history repair command first.',
                ]);
            }

            $this->assertExitEffectiveDate($lockedStaff, $openHistories->first(), $data['effective_date']);
            $this->assertAnotherActiveAdministratorRemains($lockedStaff, $tenantId);

            $oldValues = [
                'employment_status' => $oldStatus,
                'is_active' => (bool) $lockedStaff->is_active,
                'employment_started_at' => optional($lockedStaff->employment_started_at)->toDateString(),
                'employment_ended_at' => optional($lockedStaff->employment_ended_at)->toDateString(),
                'open_work_history_ids' => $openHistories->pluck('id')->all(),
            ];

            foreach ($openHistories as $history) {
                $history->forceFill([
                    'end_date' => $data['effective_date'],
                    'reason' => $data['reason'],
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ])->save();

                $this->auditLogger->record(
                    $tenantId,
                    $actor,
                    $history,
                    'staff.work-history.closed',
                    ['end_date' => null],
                    [
                        'end_date' => $data['effective_date'],
                        'reason' => $data['reason'],
                    ],
                    $data['reason'],
                    $request
                );
            }

            $lockedStaff->forceFill([
                'employment_status' => $newStatus,
                'is_active' => false,
                'employment_ended_at' => $data['effective_date'],
                'status_changed_at' => now(),
                'exit_reason' => $data['reason'],
            ])->save();

            $history = StaffStatusHistory::create([
                'tenant_id' => $tenantId,
                'user_id' => $lockedStaff->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'effective_date' => $data['effective_date'],
                'last_working_date' => $data['effective_date'],
                'reason' => $data['reason'],
                'document_path' => $data['document_path'] ?? null,
                'changed_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $newValues = [
                'employment_status' => $newStatus,
                'is_active' => false,
                'employment_ended_at' => $data['effective_date'],
                'status_history_id' => $history->id,
                'closed_work_history_ids' => $openHistories->pluck('id')->all(),
            ];

            $action = StaffLifecycleRules::auditActionFor($newStatus);
            $this->auditLogger->record(
                $tenantId,
                $actor,
                $lockedStaff,
                $action,
                $oldValues,
                $newValues,
                $data['reason'],
                $request
            );

            if ($action !== 'staff.status.changed') {
                $this->auditLogger->record(
                    $tenantId,
                    $actor,
                    $lockedStaff,
                    'staff.status.changed',
                    $oldValues,
                    $newValues,
                    $data['reason'],
                    $request
                );
            }

            return $lockedStaff->fresh();
        });
    }

    private function assertTenantStaff(User $staff): void
    {
        if (!$staff->isTenantStaff()) {
            throw ValidationException::withMessages([
                'staff' => 'Only tenant staff accounts can enter the staff lifecycle workflow.',
            ]);
        }
    }

    private function assertAnotherActiveAdministratorRemains(User $staff, int $tenantId): void
    {
        if (!StaffLifecycleRules::isContinuityAdmin($staff)) {
            return;
        }

        $continuityRoles = collect(User::STAFF_ADMIN_CONTINUITY_ROLES)
            ->flatMap(fn (string $role) => User::roleAliasesFor($role))
            ->unique()
            ->values()
            ->all();

        $otherAdminExists = User::activeStaff($tenantId)
            ->where('id', '!=', $staff->id)
            ->whereIn('role', $continuityRoles)
            ->exists();

        if (!$otherAdminExists) {
            throw ValidationException::withMessages([
                'staff' => 'At least one other active school administrator must remain.',
            ]);
        }
    }

    private function assertExitEffectiveDate(User $staff, StaffWorkHistory $currentHistory, string $effectiveDate): void
    {
        $effective = Carbon::parse($effectiveDate)->startOfDay();

        if ($effective->gt(today())) {
            throw ValidationException::withMessages([
                'effective_date' => 'Scheduled future staff exits are not supported in this workflow. Use today or an earlier date.',
            ]);
        }

        if ($staff->employment_started_at && $effective->lt($staff->employment_started_at->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'effective_date' => 'The exit date cannot be before the employment start date.',
            ]);
        }

        if ($effective->lt($currentHistory->start_date->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'effective_date' => 'The exit date cannot be before the current work-history start date.',
            ]);
        }
    }
}
