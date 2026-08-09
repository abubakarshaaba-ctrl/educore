<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Models\DataMigration;
use App\Models\MigrationEntityLink;
use App\Models\MigrationIssue;
use App\Models\MigrationReconciliation;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use InvalidArgumentException;

class MigrationReconciliationService
{
    public function __construct(private MigrationStateMachine $states, private LifecycleAuditLogger $audit) {}

    public function reconcile(DataMigration $migration, User $actor, bool $complete = false): array
    {
        if (! $actor->isSuperAdmin() && (int) $actor->tenant_id !== (int) $migration->tenant_id) {
            throw new InvalidArgumentException('Cross-tenant reconciliation denied.');
        }
        $failed = MigrationReconciliation::where('migration_id', $migration->id)->whereIn('status', ['failed', 'mismatch'])->pluck('scope')->all();
        $errors = MigrationIssue::where('migration_id', $migration->id)->where('status', 'open')->whereIn('severity', ['error', 'critical'])->count();
        $expected = (int) $migration->total_created + (int) $migration->total_updated;
        $linked = MigrationEntityLink::where('migration_id', $migration->id)->count();
        $passed = ! $failed && $errors === 0 && ($expected === 0 || $linked === $expected);
        $details = ['failed_scopes' => $failed, 'open_errors' => $errors, 'expected_mutations' => $expected, 'entity_links' => $linked];
        MigrationReconciliation::updateOrCreate(['migration_id' => $migration->id, 'scope' => 'completion_gate'], ['source_count' => $expected, 'destination_count' => $linked, 'status' => $passed ? 'passed' : 'failed', 'details' => $details, 'verified_by' => $actor->id, 'verified_at' => now()]);
        if ($complete) {
            if (! $passed) {
                throw new InvalidArgumentException('Migration cannot complete until reconciliation passes.');
            }
            if ($migration->status !== DataMigrationStatus::Reconciling) {
                throw new InvalidArgumentException('Migration must be reconciling before completion.');
            }
            $this->states->transition($migration, DataMigrationStatus::Completed, $actor, 'All reconciliation gates passed.');
        }
        $this->audit->record($migration->tenant_id, $actor, $migration, 'data_migration.reconciled', [], $details + ['passed' => $passed]);

        return $details + ['passed' => $passed];
    }
}
