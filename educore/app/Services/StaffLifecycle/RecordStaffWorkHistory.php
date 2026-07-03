<?php

namespace App\Services\StaffLifecycle;

use App\Models\StaffWorkHistory;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordStaffWorkHistory
{
    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function execute(User $actor, User $staff, array $data, ?Request $request = null): StaffWorkHistory
    {
        $tenantId = (int) $actor->tenant_id;

        if (!$actor->can('staff.work-history.manage')) {
            throw ValidationException::withMessages([
                'change_type' => 'You do not have permission to manage staff work history.',
            ]);
        }

        if (!$actor->can('staff.work-history.approve')) {
            throw ValidationException::withMessages([
                'change_type' => 'Recording work history requires work-history approval permission.',
            ]);
        }

        Validator::make($data, [
            'position_title' => ['required', 'string', 'max:255'],
            'department_name' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'functional_role' => ['nullable', 'string', 'max:150'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'appointment_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'change_type' => ['required', Rule::in(StaffLifecycleRules::EXPOSED_WORK_CHANGE_TYPES)],
            'reason' => ['nullable', 'string', 'max:2000'],
            'document_path' => ['nullable', 'string', 'max:2048'],
        ])->validate();

        return DB::transaction(function () use ($actor, $staff, $data, $request, $tenantId) {
            $lockedStaff = User::where('tenant_id', $tenantId)
                ->whereKey($staff->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedStaff->isTenantStaff() || !$lockedStaff->isEmploymentActive() || !$lockedStaff->is_active) {
                throw ValidationException::withMessages([
                    'staff' => 'Work history can only be recorded for active tenant staff.',
                ]);
            }

            $openHistories = StaffWorkHistory::where('tenant_id', $tenantId)
                ->where('user_id', $lockedStaff->id)
                ->whereNull('end_date')
                ->lockForUpdate()
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();

            if ($openHistories->count() > 1) {
                throw ValidationException::withMessages([
                    'staff' => 'This staff member has multiple open work-history periods. Run the repair command first.',
                ]);
            }

            $startDate = Carbon::parse($data['start_date'])->startOfDay();

            if ($lockedStaff->employment_started_at && $startDate->lt($lockedStaff->employment_started_at->copy()->startOfDay())) {
                throw ValidationException::withMessages([
                    'start_date' => 'The work-history start date cannot be before the employment start date.',
                ]);
            }

            $closedHistoryId = null;
            $employmentStartCorrected = false;
            if ($openHistories->count() === 1) {
                $current = $openHistories->first();

                if ($startDate->lt($current->start_date->copy()->startOfDay())) {
                    throw ValidationException::withMessages([
                        'start_date' => 'The new work-history start date cannot be before the current work-history start date.',
                    ]);
                }

                $this->assertNoOverlappingHistory($lockedStaff, $startDate, $current->id);

                $current->forceFill([
                    'end_date' => $data['start_date'],
                    'reason' => $data['reason'] ?? $current->reason,
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ])->save();
                $closedHistoryId = $current->id;

                $this->auditLogger->record(
                    $tenantId,
                    $actor,
                    $current,
                    'staff.work-history.closed',
                    ['end_date' => null],
                    [
                        'end_date' => $data['start_date'],
                        'reason' => $data['reason'] ?? null,
                    ],
                    $data['reason'] ?? null,
                    $request
                );
            } else {
                $this->assertNoOverlappingHistory($lockedStaff, $startDate);

                if (!$lockedStaff->employment_started_at) {
                    $lockedStaff->forceFill([
                        'employment_started_at' => $data['start_date'],
                        'status_changed_at' => now(),
                    ])->save();
                    $employmentStartCorrected = true;
                }
            }

            $workHistory = StaffWorkHistory::create([
                'tenant_id' => $tenantId,
                'user_id' => $lockedStaff->id,
                'position_title' => $data['position_title'],
                'department_name' => $data['department_name'] ?? null,
                'employment_type' => $data['employment_type'] ?? null,
                'functional_role' => $data['functional_role'] ?? null,
                'grade_level' => $data['grade_level'] ?? null,
                'appointment_type' => $data['appointment_type'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => null,
                'change_type' => $data['change_type'],
                'reason' => $data['reason'] ?? null,
                'document_path' => $data['document_path'] ?? null,
                'recorded_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $workHistory,
                'staff.work-history.created',
                [
                    'closed_work_history_id' => $closedHistoryId,
                    'employment_started_at_was_missing' => $employmentStartCorrected,
                ],
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
                $data['reason'] ?? null,
                $request
            );

            return $workHistory;
        });
    }

    private function assertNoOverlappingHistory(User $staff, Carbon $startDate, ?int $ignoredHistoryId = null): void
    {
        $overlapExists = StaffWorkHistory::where('tenant_id', $staff->tenant_id)
            ->where('user_id', $staff->id)
            ->when($ignoredHistoryId, fn ($query) => $query->whereKeyNot($ignoredHistoryId))
            ->where(function ($query) use ($startDate) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>', $startDate->toDateString())
                    ->orWhereDate('start_date', '>', $startDate->toDateString());
            })
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'start_date' => 'The new work-history period overlaps an existing period.',
            ]);
        }
    }
}
