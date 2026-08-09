<?php

namespace App\Services\DataMigration;

use App\Models\DataMigration;
use App\Models\MigrationChangeJournal;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MigrationChangeJournalService
{
    public function __construct(private LifecycleAuditLogger $audit) {}

    public function record(DataMigration $migration, string $entityType, Model $entity, string $classification, array $before, User $actor): MigrationChangeJournal
    {
        $this->authorise($migration, $actor);
        if (! in_array($classification, ['created_by_migration', 'updated_by_migration', 'existed_before_migration'], true)) {
            throw new InvalidArgumentException('Invalid migration change classification.');
        }
        $after = $this->snapshot($entity->refresh());

        return MigrationChangeJournal::create(['migration_id' => $migration->id, 'tenant_id' => $migration->tenant_id, 'entity_type' => $entityType, 'entity_id' => $entity->getKey(), 'classification' => $classification, 'operation' => $classification === 'created_by_migration' ? 'create' : ($classification === 'updated_by_migration' ? 'update' : 'none'), 'sequence' => (int) MigrationChangeJournal::where('migration_id', $migration->id)->max('sequence') + 1, 'before_image' => $before ?: null, 'after_image' => $after, 'before_checksum' => $before ? $this->checksum($before) : null, 'after_checksum' => $this->checksum($after)]);
    }

    public function snapshot(Model $model): array
    {
        $data = $model->getAttributes();
        unset($data['updated_at']);
        ksort($data);

        return $data;
    }

    public function checksum(array $data): string
    {
        unset($data['updated_at']);
        ksort($data);

        return hash('sha256', json_encode($data, JSON_PRESERVE_ZERO_FRACTION));
    }

    private function authorise(DataMigration $m, User $a): void
    {
        if (! $a->isSuperAdmin() && (int) $a->tenant_id !== (int) $m->tenant_id) {
            throw new InvalidArgumentException('Cross-tenant journal access denied.');
        }
    }
}
