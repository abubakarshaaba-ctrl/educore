<?php

namespace App\Console\Commands;

use App\Models\StaffWorkHistory;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class RepairStaffWorkHistory extends Command
{
    protected $signature = 'lifecycle:repair-staff-work-history
        {--dry-run : Preview repairs without changing data}
        {--apply : Apply safe repairs}
        {--tenant= : Limit to one tenant ID}
        {--staff= : Limit to one staff user ID}';

    protected $description = 'Detect and safely repair staff employment status and work-history consistency.';

    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Use either --dry-run or --apply, not both.');

            return self::FAILURE;
        }

        $dryRun = !$this->option('apply');

        try {
            $tenantId = $this->integerOption('tenant');
            $staffId = $this->integerOption('staff');
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($tenantId && $staffId) {
            $staffMatchesTenant = User::tenantStaff($tenantId)
                ->whereKey($staffId)
                ->exists();

            if (!$staffMatchesTenant) {
                $this->error('The supplied --staff does not belong to the supplied --tenant or is not tenant staff.');

                return self::FAILURE;
            }
        }

        $repairMetric = $dryRun ? 'would_repair' : 'repaired';
        $summary = [
            'inspected' => 0,
            'valid' => 0,
            $repairMetric => 0,
            'unresolved' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $candidateRows = [];

        $this->info($dryRun
            ? 'Dry run only. No users or staff_work_histories records will be modified.'
            : 'Applying safe staff work-history repairs.');

        $query = User::tenantStaff($tenantId)
            ->when($staffId, fn ($q) => $q->whereKey($staffId))
            ->orderBy('id');

        foreach ($query->lazyById(100) as $staff) {
            $this->processStaff($staff, $dryRun, $summary, $candidateRows);
        }

        if ($dryRun && $candidateRows !== []) {
            $this->newLine();
            $this->table([
                'Tenant',
                'User ID',
                'Staff No',
                'Name',
                'Email',
                'Role(s)',
                'Status',
                'Is Active',
                'Started At',
                'Open Histories',
                'Proposed Action',
                'Reason',
                'Required Admin Action',
                'Safety',
            ], $candidateRows);
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], collect($summary)->map(fn ($count, $key) => [
            str_replace('_', ' ', $key),
            $count,
        ])->values()->all());

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processStaff(User $staff, bool $dryRun, array &$summary, array &$candidateRows): void
    {
        $summary['inspected']++;

        try {
            if ($dryRun) {
                $analysis = $this->analyseStaff($staff);
                $this->recordDryRunSummary($staff, $analysis, $summary, $candidateRows);

                return;
            }

            DB::transaction(function () use ($staff, &$summary): void {
                $lockedStaff = User::tenantStaff((int) $staff->tenant_id)
                    ->whereKey($staff->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedStaff) {
                    $summary['skipped']++;

                    return;
                }

                $analysis = $this->analyseStaff($lockedStaff, true);

                if ($analysis['state'] === 'valid') {
                    $summary['valid']++;

                    return;
                }

                if ($analysis['state'] === 'unresolved') {
                    $summary['unresolved']++;
                    $this->warn($this->staffLabel($lockedStaff) . ' unresolved: ' . $analysis['resolution']);

                    return;
                }

                $this->applyRepair($lockedStaff, $analysis);
                $summary['repaired']++;

                $this->auditLogger->record(
                    $lockedStaff->tenant_id,
                    null,
                    $lockedStaff,
                    'staff.work-history.repaired',
                    ['issues' => $analysis['issues']],
                    ['resolution' => $analysis['resolution']],
                    'CLI staff work-history repair'
                );

                $this->line($this->staffLabel($lockedStaff) . ' repaired.');
            });
        } catch (Throwable $exception) {
            $summary['failed']++;
            $this->error($this->staffLabel($staff) . ' failed: ' . $exception->getMessage());
        }
    }

    private function analyseStaff(User $staff, bool $lock = false): array
    {
        if (!$staff->isTenantStaff()) {
            return [
                'state' => 'skipped',
                'issues' => ['not_tenant_staff'],
                'resolution' => 'Account is not tenant staff.',
                'open_histories' => collect(),
            ];
        }

        $historyQuery = StaffWorkHistory::where('tenant_id', $staff->tenant_id)
            ->where('user_id', $staff->id)
            ->whereNull('end_date')
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if ($lock) {
            $historyQuery->lockForUpdate();
        }

        $openHistories = $historyQuery->get();
        $status = $staff->employmentStatus();
        $issues = [];

        if ($staff->employment_status === null) {
            $issues[] = 'null_employment_status';
        }

        if ($status === User::STAFF_STATUS_ACTIVE && $openHistories->isEmpty()) {
            $issues[] = 'active_staff_no_open_work_history';
        }

        if ($status === User::STAFF_STATUS_ACTIVE && $openHistories->count() > 1) {
            $issues[] = 'active_staff_multiple_open_work_histories';
        }

        if (in_array($status, User::STAFF_ARCHIVE_STATUSES, true) && $openHistories->isNotEmpty()) {
            $issues[] = 'inactive_staff_open_work_history';
        }

        if ($status === User::STAFF_STATUS_ACTIVE && !$staff->is_active) {
            $issues[] = 'active_status_but_login_inactive';
        }

        if (in_array($status, User::STAFF_ARCHIVE_STATUSES, true) && $staff->is_active) {
            $issues[] = 'archived_status_but_login_active';
        }

        if ($issues === []) {
            return [
                'state' => 'valid',
                'issues' => [],
                'resolution' => 'No repair needed.',
                'open_histories' => $openHistories,
            ];
        }

        if (in_array('active_status_but_login_inactive', $issues, true)) {
            return [
                'state' => 'unresolved',
                'issues' => $issues,
                'resolution' => 'Login is inactive while employment status is active; this may be a security block and requires manual review.',
                'open_histories' => $openHistories,
            ];
        }

        if (in_array('active_staff_no_open_work_history', $issues, true) && !$staff->employment_started_at) {
            return [
                'state' => 'unresolved',
                'issues' => $issues,
                'resolution' => 'Cannot create an open work-history period without employment_started_at.',
                'open_histories' => $openHistories,
            ];
        }

        if (in_array('inactive_staff_open_work_history', $issues, true) && !$staff->employment_ended_at) {
            return [
                'state' => 'unresolved',
                'issues' => $issues,
                'resolution' => 'Cannot close open work-history periods without employment_ended_at.',
                'open_histories' => $openHistories,
            ];
        }

        return [
            'state' => 'repairable',
            'issues' => $issues,
            'resolution' => $this->repairDescription($issues),
            'open_histories' => $openHistories,
        ];
    }

    private function applyRepair(User $staff, array $analysis): void
    {
        $issues = $analysis['issues'];

        if (in_array('null_employment_status', $issues, true)) {
            $staff->forceFill(['employment_status' => User::STAFF_STATUS_ACTIVE])->save();
        }

        if (in_array('active_staff_no_open_work_history', $issues, true)) {
            StaffWorkHistory::create([
                'tenant_id' => $staff->tenant_id,
                'user_id' => $staff->id,
                'position_title' => $staff->roleLabel(),
                'department_name' => null,
                'start_date' => $staff->employment_started_at,
                'end_date' => null,
                'change_type' => StaffWorkHistory::CHANGE_APPOINTMENT,
                'reason' => 'Created by staff work-history repair command.',
                'recorded_by' => $staff->id,
            ]);
        }

        if (in_array('active_staff_multiple_open_work_histories', $issues, true)) {
            $keep = $analysis['open_histories']->first();
            StaffWorkHistory::where('tenant_id', $staff->tenant_id)
                ->where('user_id', $staff->id)
                ->whereNull('end_date')
                ->where('id', '!=', $keep->id)
                ->update([
                    'end_date' => $keep->start_date,
                    'reason' => 'Closed by staff work-history repair command; newer open period kept current.',
                ]);
        }

        if (in_array('inactive_staff_open_work_history', $issues, true)) {
            StaffWorkHistory::where('tenant_id', $staff->tenant_id)
                ->where('user_id', $staff->id)
                ->whereNull('end_date')
                ->update([
                    'end_date' => $staff->employment_ended_at,
                    'reason' => $staff->exit_reason ?: 'Closed by staff work-history repair command.',
                ]);
        }

        if (in_array('archived_status_but_login_active', $issues, true)) {
            $staff->forceFill(['is_active' => false])->save();
        }
    }

    private function recordDryRunSummary(User $staff, array $analysis, array &$summary, array &$candidateRows): void
    {
        if ($analysis['state'] === 'valid') {
            $summary['valid']++;

            return;
        }

        if ($analysis['state'] === 'skipped') {
            $summary['skipped']++;

            return;
        }

        if ($analysis['state'] === 'unresolved') {
            $summary['unresolved']++;
        } else {
            $summary['would_repair']++;
        }

        $candidateRows[] = [
            $staff->tenant_id,
            $staff->id,
            $staff->staff_id ?: '-',
            $staff->name,
            $staff->email,
            $this->staffRoles($staff),
            $staff->employmentStatus(),
            $staff->is_active ? 'yes' : 'no',
            optional($staff->employment_started_at)->toDateString() ?: '-',
            $analysis['open_histories']->count(),
            $analysis['resolution'],
            implode(', ', $analysis['issues']),
            $this->administratorAction($analysis['issues']),
            $analysis['state'] === 'repairable' ? 'repairable' : 'unresolved',
        ];
    }

    private function repairDescription(array $issues): string
    {
        $actions = [];

        if (in_array('null_employment_status', $issues, true)) {
            $actions[] = 'set employment_status to active';
        }
        if (in_array('active_staff_no_open_work_history', $issues, true)) {
            $actions[] = 'create open work-history period from employment_started_at';
        }
        if (in_array('active_staff_multiple_open_work_histories', $issues, true)) {
            $actions[] = 'keep newest open work-history row and close the others';
        }
        if (in_array('inactive_staff_open_work_history', $issues, true)) {
            $actions[] = 'close open work-history rows using employment_ended_at';
        }
        if (in_array('archived_status_but_login_active', $issues, true)) {
            $actions[] = 'set is_active false';
        }

        return implode('; ', $actions);
    }

    private function staffLabel(User $staff): string
    {
        return "Tenant {$staff->tenant_id}, staff {$staff->id} ({$staff->name})";
    }

    private function staffRoles(User $staff): string
    {
        $roles = collect([$staff->roleKey() ?? $staff->role])
            ->merge(method_exists($staff, 'getRoleNames') ? $staff->getRoleNames() : [])
            ->filter()
            ->unique()
            ->values();

        return $roles->isEmpty() ? '-' : $roles->implode(', ');
    }

    private function administratorAction(array $issues): string
    {
        if (in_array('active_staff_no_open_work_history', $issues, true)) {
            return 'Record employment_started_at and appointment details, then rerun dry-run.';
        }

        if (in_array('inactive_staff_open_work_history', $issues, true)) {
            return 'Record employment_ended_at or review exit data, then rerun dry-run.';
        }

        if (in_array('active_status_but_login_inactive', $issues, true)) {
            return 'Review whether login was intentionally blocked before applying repair.';
        }

        if (in_array('active_staff_multiple_open_work_histories', $issues, true)) {
            return 'Review open work-history rows; apply can keep newest and close others.';
        }

        if (in_array('archived_status_but_login_active', $issues, true)) {
            return 'Apply repair to disable login or review status mismatch.';
        }

        if (in_array('null_employment_status', $issues, true)) {
            return 'Apply repair to set employment_status to active after confirming staff record.';
        }

        return 'Review manually.';
    }

    private function integerOption(string $name): ?int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return null;
        }

        if (!ctype_digit((string) $value) || (int) $value < 1) {
            throw new InvalidArgumentException("--{$name} must be a positive integer.");
        }

        return (int) $value;
    }
}
