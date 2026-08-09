<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Exceptions\InvalidMigrationStateTransition;
use App\Models\DataMigration;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Facades\DB;

class MigrationStateMachine
{
    public function __construct(private readonly LifecycleAuditLogger $audit) {}

    public function transition(DataMigration $migration, DataMigrationStatus $next, ?User $actor = null, ?string $reason = null): DataMigration
    {
        $current = $migration->status;

        if (! $current->canTransitionTo($next)) {
            throw new InvalidMigrationStateTransition("Cannot transition migration from {$current->value} to {$next->value}.");
        }

        return DB::transaction(function () use ($migration, $current, $next, $actor, $reason): DataMigration {
            $locked = DataMigration::query()->lockForUpdate()->findOrFail($migration->id);

            if ($locked->status !== $current) {
                throw new InvalidMigrationStateTransition('Migration status changed concurrently; reload before retrying.');
            }

            $locked->status = $next;
            $locked->started_at ??= in_array($next, [DataMigrationStatus::Inspecting, DataMigrationStatus::Importing], true) ? now() : null;
            $locked->completed_at = $next === DataMigrationStatus::Completed ? now() : $locked->completed_at;
            $locked->rolled_back_at = $next === DataMigrationStatus::RolledBack ? now() : $locked->rolled_back_at;
            $locked->save();

            $this->audit->record(
                $locked->tenant_id,
                $actor,
                $locked,
                'data_migration.status_changed',
                ['status' => $current->value],
                ['status' => $next->value],
                $reason
            );

            return $locked->refresh();
        });
    }
}
