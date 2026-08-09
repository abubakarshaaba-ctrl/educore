<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Models\DataMigration;
use App\Models\MigrationChangeJournal;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MigrationRollbackService
{
    public function __construct(private MigrationChangeJournalService $journal, private MigrationStateMachine $states, private LifecycleAuditLogger $audit) {}

    public function rollback(DataMigration $migration, User $actor): array
    {
        if (! $actor->isMigrationAdmin()) {
            throw new InvalidArgumentException('Only a platform migration administrator may execute rollback.');
        }$migration = $migration->refresh();
        if ($migration->status === DataMigrationStatus::Completed) {
            $migration = $this->states->transition($migration, DataMigrationStatus::RollbackRequested, $actor);
        }if ($migration->status !== DataMigrationStatus::RollbackRequested) {
            throw new InvalidArgumentException('Migration is not rollback-ready.');
        }$migration = $this->states->transition($migration, DataMigrationStatus::RollingBack, $actor);
        $counts = ['restored' => 0, 'deleted' => 0, 'compensation_required' => 0];
        DB::transaction(function () use ($migration, &$counts) {
            foreach (MigrationChangeJournal::where('migration_id', $migration->id)->where('rollback_status', 'pending')->orderByDesc('sequence')->lockForUpdate()->get() as $entry) {
                $model = config("data_migration_rollback.entities.{$entry->entity_type}");
                if (! $model) {
                    $this->compensate($entry, 'No rollback model is registered.');
                    $counts['compensation_required']++;

                    continue;
                }
                $record = $model::query()->whereKey($entry->entity_id)->where('tenant_id', $migration->tenant_id)->first();
                if ($entry->classification === 'created_by_migration') {
                    if (! $record) {
                        $entry->update(['rollback_status' => 'rolled_back', 'rolled_back_at' => now()]);

                        continue;
                    }if (! hash_equals($entry->after_checksum, $this->journal->checksum($this->journal->snapshot($record)))) {
                        $this->compensate($entry, 'Record changed after migration.');
                        $counts['compensation_required']++;

                        continue;
                    }
                    method_exists($record, 'forceDelete') ? $record->forceDelete() : $record->delete();
                    $entry->update(['rollback_status' => 'rolled_back', 'rolled_back_at' => now()]);
                    $counts['deleted']++;
                } elseif ($entry->classification === 'updated_by_migration') {
                    if (! $record || ! hash_equals($entry->after_checksum, $this->journal->checksum($this->journal->snapshot($record)))) {
                        $this->compensate($entry, 'Updated record is missing or has drifted.');
                        $counts['compensation_required']++;

                        continue;
                    }$record->forceFill($entry->before_image)->save();
                    $entry->update(['rollback_status' => 'rolled_back', 'rolled_back_at' => now()]);
                    $counts['restored']++;
                } else {
                    $entry->update(['rollback_status' => 'not_required', 'rolled_back_at' => now()]);
                }
            }
        });
        if ($counts['compensation_required'] === 0) {
            $this->states->transition($migration->refresh(), DataMigrationStatus::RolledBack, $actor);
        } else {
            $migration->update(['status' => DataMigrationStatus::Failed]);
        }$this->audit->record($migration->tenant_id, $actor, $migration, 'data_migration.rollback_executed', [], $counts);

        return $counts;
    }

    private function compensate(MigrationChangeJournal $e, string $reason): void
    {
        $e->update(['rollback_status' => 'compensation_required', 'compensation_strategy' => 'manual_review_and_compensating_transaction', 'rollback_error' => $reason]);
    }
}
