<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\DataMigration;
use App\Models\MigrationDataset;
use App\Models\MigrationIssue;
use App\Models\MigrationMapping;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MigrationMappingService
{
    public function __construct(private CanonicalSchemaRegistry $registry, private DeterministicMappingEngine $engine, private MigrationStateMachine $states, private MigrationMappingProfileService $profiles, private LifecycleAuditLogger $audit) {}

    public function generate(DataMigration $migration, MigrationDataset $dataset, string $entityName, User $actor): array
    {
        $this->authorise($migration, $actor);
        if ((int) $dataset->migration_id !== (int) $migration->id) {
            throw new InvalidArgumentException('Dataset does not belong to the migration.');
        }
        if (! in_array($migration->status, [DataMigrationStatus::Extracted, DataMigrationStatus::Classified, DataMigrationStatus::Mapping, DataMigrationStatus::NeedsReview, DataMigrationStatus::Mapped], true)) {
            throw new InvalidArgumentException('Migration is not ready for mapping.');
        }
        $entity = $this->registry->entity($entityName);
        $profile = $this->profiles->find($migration, $entityName);
        $saved = $profile?->mappings ?? [];
        $headers = $dataset->source_schema['headers'] ?? [];
        if (! $headers) {
            $headers = array_keys($dataset->rows()->orderBy('row_number')->value('raw_payload') ?? []);
        }
        $rows = $dataset->rows()->orderBy('row_number')->limit(config('data_migration.mapping_sample_rows'))->pluck('raw_payload');

        DB::transaction(function () use ($migration, $dataset, $entityName, $entity, $profile, $saved, $headers, $rows): void {
            $dataset->update(['canonical_entity' => $entityName, 'classification_status' => 'classified', 'classification_confidence' => 100, 'metadata' => array_merge($dataset->metadata ?? [], ['canonical_schema_version' => $entity->version])]);
            foreach ($headers as $header) {
                $suggestion = $this->engine->suggest($header, $rows->map(fn ($row) => $row[$header] ?? null)->all(), $entity, $saved[$header] ?? null);
                MigrationMapping::updateOrCreate(['migration_id' => $migration->id, 'dataset_id' => $dataset->id, 'source_column' => $header], ['destination_entity' => $entityName, 'destination_field' => $suggestion->destinationField, 'decision' => $suggestion->decision, 'confidence' => $suggestion->confidence, 'metadata' => ['basis' => $suggestion->basis, 'profile' => $suggestion->profile, 'candidates' => $suggestion->candidates, 'schema_version' => $entity->version]]);
                if ($suggestion->decision === MigrationMappingDecision::Unmapped) {
                    MigrationIssue::firstOrCreate(['migration_id' => $migration->id, 'dataset_id' => $dataset->id, 'category' => 'unmapped_column', 'field' => $header, 'status' => 'open'], ['severity' => 'warning', 'message' => "Source column [{$header}] is not mapped.", 'suggested_resolution' => 'Map it to a canonical field or explicitly ignore it.']);
                }
            }
            if ($profile) {
                $profile->update(['usage_count' => $profile->usage_count + 1, 'last_used_at' => now()]);
            }
        });

        $migration = $migration->refresh();
        if ($migration->status === DataMigrationStatus::Extracted) {
            $migration = $this->states->transition($migration, DataMigrationStatus::Classified, $actor, 'Dataset classified against canonical schema.');
        }
        if (in_array($migration->status, [DataMigrationStatus::Classified, DataMigrationStatus::NeedsReview, DataMigrationStatus::Mapped], true)) {
            $migration = $this->states->transition($migration, DataMigrationStatus::Mapping, $actor, 'Deterministic mapping evaluated.');
        }
        $this->refreshStatus($migration, $actor);
        $this->audit->record($migration->tenant_id, $actor, $dataset, 'data_migration.mapping_generated', [], ['entity' => $entityName, 'columns' => count($headers), 'profile_id' => $profile?->id]);

        return MigrationMapping::where('dataset_id', $dataset->id)->orderBy('id')->get()->all();
    }

    public function override(MigrationMapping $mapping, User $actor, MigrationMappingDecision $decision, ?string $field = null, ?string $reason = null, ?array $transformation = null): MigrationMapping
    {
        $migration = $mapping->migration()->firstOrFail();
        $this->authorise($migration, $actor);
        if ($decision === MigrationMappingDecision::IgnoreExplicitly && blank($reason)) {
            throw new InvalidArgumentException('An explicit ignore decision requires a reason.');
        }
        if ($decision !== MigrationMappingDecision::IgnoreExplicitly && (! $field || ! $this->registry->has($mapping->destination_entity, $field))) {
            throw new InvalidArgumentException('Select a valid canonical destination field.');
        }
        $before = $mapping->toArray();
        $mapping->update(['destination_field' => $decision === MigrationMappingDecision::IgnoreExplicitly ? null : $field, 'decision' => $decision, 'confidence' => 100, 'transformation_rule' => $transformation, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'metadata' => array_merge($mapping->metadata ?? [], ['manual_override' => true, 'reason' => $reason])]);
        MigrationIssue::where(['migration_id' => $migration->id, 'dataset_id' => $mapping->dataset_id, 'category' => 'unmapped_column', 'field' => $mapping->source_column, 'status' => 'open'])->update(['status' => 'resolved', 'resolved_by' => $actor->id, 'resolved_at' => now()]);
        $this->audit->record($migration->tenant_id, $actor, $mapping, 'data_migration.mapping_overridden', $before, $mapping->fresh()->toArray(), $reason);
        $this->refreshStatus($migration->refresh(), $actor);

        return $mapping->refresh();
    }

    private function refreshStatus(DataMigration $migration, User $actor): void
    {
        $pending = $migration->mappings()->whereIn('decision', [MigrationMappingDecision::Review->value, MigrationMappingDecision::Unmapped->value])->exists();
        $next = $pending ? DataMigrationStatus::NeedsReview : DataMigrationStatus::Mapped;
        if ($migration->status !== $next && $migration->status->canTransitionTo($next)) {
            $this->states->transition($migration, $next, $actor, $pending ? 'Mapping review is required.' : 'All source columns have an explicit decision.');
        }
    }

    private function authorise(DataMigration $migration, User $actor): void
    {
        if (! $actor->isSuperAdmin() && (int) $actor->tenant_id !== (int) $migration->tenant_id) {
            throw new InvalidArgumentException('The user does not belong to the migration tenant.');
        }
    }
}
