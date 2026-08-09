<?php

namespace App\Services\DataMigration;

use App\Models\DataMigration;
use App\Models\MigrationApproval;
use App\Models\MigrationChangeJournal;
use App\Models\MigrationIssue;
use App\Models\MigrationReconciliation;
use App\Models\MigrationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\UnauthorizedException;

class MigrationEnterpriseDashboardService
{
    public function summary(User $actor, ?int $tenantId = null): array
    {
        $tenantId = $this->authorisedTenant($actor, $tenantId);
        $migrations = DataMigration::query()->when($tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId));
        $requests = MigrationRequest::query()->when($tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId));
        $migrationIds = (clone $migrations)->pluck('id');

        return [
            'scope' => $tenantId ? ['tenant_id' => $tenantId] : ['platform' => true],
            'migrations' => [
                'total' => (clone $migrations)->count(),
                'awaiting_approval' => (clone $migrations)->where('status', 'awaiting_approval')->count(),
                'in_progress' => (clone $migrations)->whereIn('status', ['queued', 'importing', 'verifying', 'reconciling', 'rolling_back'])->count(),
                'completed' => (clone $migrations)->where('status', 'completed')->count(),
                'attention_required' => (clone $migrations)->whereIn('status', ['needs_review', 'partial', 'failed', 'dry_run_failed'])->count(),
                'rolled_back' => (clone $migrations)->where('status', 'rolled_back')->count(),
            ],
            'requests' => $requests->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status')->map(fn ($value) => (int) $value)->all(),
            'open_issues' => MigrationIssue::query()->whereIn('migration_id', $migrationIds)->where('status', '!=', 'resolved')->count(),
            'failed_reconciliations' => MigrationReconciliation::query()->whereIn('migration_id', $migrationIds)->where('status', 'failed')->count(),
            'recent' => (clone $migrations)->latest()->limit(10)->get(['id', 'tenant_id', 'batch_number', 'direction', 'migration_type', 'status', 'updated_at'])->toArray(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function report(DataMigration $migration, User $actor): array
    {
        $this->authorisedTenant($actor, $migration->tenant_id);

        return [
            'migration' => $migration->only([
                'id', 'tenant_id', 'batch_number', 'direction', 'migration_type', 'source_system', 'destination_system',
                'status', 'total_files', 'total_datasets', 'total_source_rows', 'total_valid_rows', 'total_created',
                'total_updated', 'total_skipped', 'total_failed', 'started_at', 'completed_at', 'rolled_back_at',
            ]),
            'request' => MigrationRequest::query()->where('migration_id', $migration->id)->first()?->toArray(),
            'approvals' => MigrationApproval::query()->where('migration_id', $migration->id)->orderBy('decided_at')->get()->toArray(),
            'issues' => MigrationIssue::query()->where('migration_id', $migration->id)->selectRaw('severity, status, COUNT(*) as aggregate')->groupBy('severity', 'status')->get()->toArray(),
            'reconciliations' => MigrationReconciliation::query()->where('migration_id', $migration->id)->orderBy('scope')->get()->toArray(),
            'journal' => [
                'entries' => MigrationChangeJournal::query()->where('migration_id', $migration->id)->count(),
                'reversed' => MigrationChangeJournal::query()->where('migration_id', $migration->id)->whereNotNull('rolled_back_at')->count(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function authorisedTenant(User $actor, ?int $tenantId): ?int
    {
        if ($actor->isMigrationAdmin()) {
            return $tenantId;
        }
        if ($actor->isAdmin() && $actor->tenant_id && ($tenantId === null || (int) $actor->tenant_id === $tenantId)) {
            return (int) $actor->tenant_id;
        }

        throw new UnauthorizedException('Enterprise migration reporting access denied.');
    }
}
