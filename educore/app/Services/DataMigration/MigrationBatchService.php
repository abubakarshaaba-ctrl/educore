<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Models\DataMigration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MigrationBatchService
{
    public function __construct(private readonly LifecycleAuditLogger $audit) {}

    public function create(Tenant $tenant, User $actor, string $direction, string $type, ?string $sourceSystem = null, ?string $destinationSystem = null, array $metadata = []): DataMigration
    {
        if (! in_array($direction, config('data_migration.allowed_directions'), true)) {
            throw new InvalidArgumentException('Unsupported migration direction.');
        }
        if (! in_array($type, config('data_migration.allowed_types'), true)) {
            throw new InvalidArgumentException('Unsupported migration type.');
        }
        if (! $actor->isSuperAdmin() && (int) $actor->tenant_id !== (int) $tenant->id) {
            throw new InvalidArgumentException('The initiating user does not belong to the target tenant.');
        }

        return DB::transaction(function () use ($tenant, $actor, $direction, $type, $sourceSystem, $destinationSystem, $metadata): DataMigration {
            $prefix = $direction === 'outbound' ? 'EXP' : 'MIG';
            $batch = sprintf('%s-%s-%s', $prefix, now()->format('Ymd'), Str::upper((string) Str::ulid()));
            $migration = DataMigration::create([
                'tenant_id' => $tenant->id,
                'batch_number' => $batch,
                'direction' => $direction,
                'migration_type' => $type,
                'source_system' => $sourceSystem,
                'destination_system' => $destinationSystem,
                'status' => DataMigrationStatus::Uploaded,
                'initiated_by' => $actor->id,
                'metadata' => ['schema_version' => config('data_migration.schema_version')] + $metadata,
            ]);

            $this->audit->record($tenant->id, $actor, $migration, 'data_migration.requested', [], [
                'batch_number' => $batch,
                'direction' => $direction,
                'migration_type' => $type,
            ]);

            return $migration;
        });
    }
}
