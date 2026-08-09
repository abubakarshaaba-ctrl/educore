<?php

namespace App\Services\DataMigration;

use App\Enums\MigrationMappingDecision;
use App\Models\DataMigration;
use App\Models\MigrationDataset;
use App\Models\MigrationMappingProfile;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use InvalidArgumentException;

class MigrationMappingProfileService
{
    public function __construct(private readonly LifecycleAuditLogger $audit) {}

    public function find(DataMigration $migration, string $entity): ?MigrationMappingProfile
    {
        return MigrationMappingProfile::query()->where('is_active', true)
            ->where('source_system', $migration->source_system ?: 'unspecified')
            ->where('canonical_entity', $entity)
            ->where(fn ($q) => $q->where('tenant_id', $migration->tenant_id)->orWhereNull('tenant_id'))
            ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 ELSE 1 END', [$migration->tenant_id])->latest('id')->first();
    }

    public function save(DataMigration $migration, MigrationDataset $dataset, User $actor, string $name, bool $global = false): MigrationMappingProfile
    {
        $this->authorise($migration, $actor);
        if ($global && ! $actor->isSuperAdmin()) {
            throw new InvalidArgumentException('Only a platform administrator may create a global mapping profile.');
        }
        $mappings = $migration->mappings()->where('dataset_id', $dataset->id)->get()->filter(fn ($m) => in_array($m->decision, [MigrationMappingDecision::AutoMap, MigrationMappingDecision::IgnoreExplicitly], true))->mapWithKeys(fn ($m) => [$m->source_column => ['field' => $m->destination_field, 'decision' => $m->decision->value]])->all();
        $profile = MigrationMappingProfile::create(['tenant_id' => $global ? null : $migration->tenant_id, 'name' => $name, 'source_system' => $migration->source_system ?: 'unspecified', 'canonical_entity' => $dataset->canonical_entity, 'schema_version' => config('data_migration_schema.version'), 'mappings' => $mappings, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
        $this->audit->record($migration->tenant_id, $actor, $profile, 'data_migration.mapping_profile_created', [], ['name' => $name, 'mapping_count' => count($mappings)]);

        return $profile;
    }

    private function authorise(DataMigration $migration, User $actor): void
    {
        if (! $actor->isSuperAdmin() && (int) $actor->tenant_id !== (int) $migration->tenant_id) {
            throw new InvalidArgumentException('The user does not belong to the migration tenant.');
        }
    }
}
