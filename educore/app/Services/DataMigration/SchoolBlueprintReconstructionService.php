<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\DataMigration;
use App\Models\MigrationBlueprintDependency;
use App\Models\MigrationBlueprintNode;
use App\Models\MigrationIssue;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SchoolBlueprintReconstructionService
{
    public function __construct(private readonly LifecycleAuditLogger $audit) {}

    public function reconstruct(DataMigration $migration, User $actor): array
    {
        $this->authorise($migration, $actor);
        if (! in_array($migration->status, [DataMigrationStatus::Mapped, DataMigrationStatus::NeedsReview], true)) {
            throw new InvalidArgumentException('Migration mappings must be completed before blueprint reconstruction.');
        }

        $supported = config('data_migration_blueprint.entities', []);
        $order = array_flip(config('data_migration_blueprint.entity_order', []));
        $datasets = $migration->datasets()->whereIn('canonical_entity', array_keys($supported))->get()->sortBy(fn ($dataset) => $order[$dataset->canonical_entity] ?? PHP_INT_MAX);
        $counts = ['matched' => 0, 'proposed' => 0, 'ambiguous' => 0, 'conflict' => 0, 'unresolved_dependencies' => 0];

        DB::transaction(function () use ($migration, $actor, $datasets, $supported, &$counts): void {
            foreach ($datasets as $dataset) {
                $mappings = $migration->mappings()->where('dataset_id', $dataset->id)
                    ->where('decision', MigrationMappingDecision::AutoMap->value)
                    ->whereNotNull('destination_field')->get();

                $dataset->rows()->orderBy('row_number')->chunkById(config('data_migration.staging_chunk_rows', 500), function (Collection $rows) use ($migration, $dataset, $mappings, $supported): void {
                    foreach ($rows as $row) {
                        $payload = [];
                        foreach ($mappings as $mapping) {
                            $payload[$mapping->destination_field] = $row->raw_payload[$mapping->source_column] ?? null;
                        }
                        $payload = array_filter($payload, fn ($value) => $value !== null && trim((string) $value) !== '');
                        if ($payload === []) {
                            continue;
                        }
                        $this->stageNode($migration, $dataset->id, $dataset->canonical_entity, $payload, $row->row_number, $supported[$dataset->canonical_entity]);
                    }
                });
            }

            $this->resolveDependencies($migration);
            foreach (array_keys($counts) as $decision) {
                $counts[$decision] = $decision === 'unresolved_dependencies'
                    ? MigrationBlueprintDependency::where('migration_id', $migration->id)->where('resolution_status', 'unresolved')->count()
                    : MigrationBlueprintNode::where('migration_id', $migration->id)->where('decision', $decision)->count();
            }

            $metadata = $migration->metadata ?? [];
            $metadata['blueprint_reconstruction'] = ['schema_version' => config('data_migration_schema.version'), 'completed_at' => now()->toIso8601String(), 'counts' => $counts];
            $migration->update(['metadata' => $metadata]);
            $this->audit->record($migration->tenant_id, $actor, $migration, 'data_migration.blueprint_reconstructed', [], $counts);
        });

        return $counts;
    }

    public function review(MigrationBlueprintNode $node, User $actor, string $decision, ?int $matchedRecordId = null, ?string $reason = null): MigrationBlueprintNode
    {
        $migration = $node->migration()->firstOrFail();
        $this->authorise($migration, $actor);
        if (! in_array($decision, ['matched', 'proposed', 'ignored'], true)) {
            throw new InvalidArgumentException('Unsupported blueprint review decision.');
        }
        if ($decision === 'matched' && ! $matchedRecordId) {
            throw new InvalidArgumentException('A matched decision requires an existing record ID.');
        }
        if ($decision === 'ignored' && blank($reason)) {
            throw new InvalidArgumentException('Ignoring a blueprint node requires a reason.');
        }
        if ($matchedRecordId && ! $this->recordBelongsToTenant($node->entity_type, $matchedRecordId, $migration->tenant_id)) {
            throw new InvalidArgumentException('The selected structural record does not belong to the migration tenant.');
        }

        $before = $node->toArray();
        $node->update(['decision' => $decision, 'matched_record_id' => $decision === 'matched' ? $matchedRecordId : null, 'confidence' => $decision === 'matched' ? 100 : $node->confidence, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'metadata' => array_merge($node->metadata ?? [], ['review_reason' => $reason])]);
        $this->resolveDependencies($migration);
        $this->audit->record($migration->tenant_id, $actor, $node, 'data_migration.blueprint_reviewed', $before, $node->fresh()->toArray(), $reason);

        return $node->refresh();
    }

    private function stageNode(DataMigration $migration, int $datasetId, string $entity, array $payload, int $rowNumber, array $definition): void
    {
        $sourceKey = $this->sourceKey($payload, $definition['identity'], $entity);
        if ($sourceKey === '') {
            $this->issue($migration, $datasetId, 'blueprint_identity_missing', $entity, "Structural row {$rowNumber} has no usable identity.");

            return;
        }
        $checksum = hash('sha256', json_encode($this->sortRecursive($payload), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
        $existing = MigrationBlueprintNode::where(['migration_id' => $migration->id, 'entity_type' => $entity, 'source_key' => $sourceKey])->first();
        if ($existing && $existing->payload_checksum !== $checksum) {
            $existing->update(['decision' => 'conflict', 'confidence' => 0, 'metadata' => array_merge($existing->metadata ?? [], ['conflicting_row_numbers' => array_values(array_unique([...($existing->metadata['conflicting_row_numbers'] ?? []), $rowNumber]))])]);
            $this->issue($migration, $datasetId, 'blueprint_conflict', $entity, "Conflicting definitions found for [{$sourceKey}].");

            return;
        }
        if ($existing) {
            return;
        }

        [$decision, $confidence, $matchedId, $candidates] = $this->matchExisting($migration, $entity, $payload, $definition);
        MigrationBlueprintNode::create(['migration_id' => $migration->id, 'dataset_id' => $datasetId, 'tenant_id' => $migration->tenant_id, 'entity_type' => $entity, 'source_key' => $sourceKey, 'canonical_payload' => $payload, 'decision' => $decision, 'confidence' => $confidence, 'matched_record_id' => $matchedId, 'payload_checksum' => $checksum, 'match_candidates' => $candidates, 'metadata' => ['source_row_numbers' => [$rowNumber], 'dry_run_only' => true]]);
        if ($decision === 'ambiguous') {
            $this->issue($migration, $datasetId, 'blueprint_ambiguous_match', $entity, "Multiple existing records match [{$sourceKey}].");
        }
    }

    private function matchExisting(DataMigration $migration, string $entity, array $payload, array $definition): array
    {
        $model = $definition['model'];
        $query = $model::query()->where('tenant_id', $migration->tenant_id);
        $directFields = array_values(array_filter($definition['identity'], fn ($field) => ! isset($definition['relationships'][$field]) && isset($payload[$field]) && $payload[$field] !== ''));
        if ($directFields === []) {
            return ['proposed', 0, null, []];
        }
        if ($entity === 'subject' && filled($payload['code'] ?? null)) {
            $directFields = ['code'];
        }
        foreach ($directFields as $field) {
            $query->whereRaw('LOWER(TRIM('.$field.')) = ?', [mb_strtolower(trim((string) $payload[$field]))]);
        }
        foreach (($definition['relationships'] ?? []) as $field => $parentEntity) {
            if (! filled($payload[$field] ?? null)) {
                continue;
            }
            $parentKey = $this->normalise($payload[$field]);
            $parent = MigrationBlueprintNode::where('migration_id', $migration->id)->where('entity_type', $parentEntity)->where(function ($nested) use ($parentKey): void {
                $nested->where('source_key', $parentKey)->orWhere('source_key', 'like', $parentKey.'|%');
            })->where('decision', 'matched')->first();
            $column = $definition['relationship_columns'][$field] ?? null;
            if ($column && $parent?->matched_record_id) {
                $query->where($column, $parent->matched_record_id);
            } elseif ($column) {
                return ['proposed', 0, null, []];
            }
        }
        $matches = $query->limit(6)->get();
        $candidates = $matches->map(fn ($record) => ['id' => $record->getKey(), 'label' => $this->candidateLabel($record, $directFields)])->all();

        return match ($matches->count()) {
            0 => ['proposed', 0, null, []],
            1 => ['matched', 100, $matches->first()->getKey(), $candidates],
            default => ['ambiguous', 60, null, $candidates],
        };
    }

    private function resolveDependencies(DataMigration $migration): void
    {
        MigrationBlueprintDependency::where('migration_id', $migration->id)->delete();
        foreach ($migration->blueprintNodes()->whereNotIn('decision', ['ignored', 'conflict'])->get() as $node) {
            $relationships = config("data_migration_blueprint.entities.{$node->entity_type}.relationships", []);
            foreach ($relationships as $field => $parentEntity) {
                $value = $node->canonical_payload[$field] ?? null;
                if ($value === null || trim((string) $value) === '') {
                    continue;
                }
                $parentKey = $this->normalise($value);
                $parent = MigrationBlueprintNode::where('migration_id', $migration->id)->where('entity_type', $parentEntity)->where(function ($query) use ($parentKey): void {
                    $query->where('source_key', $parentKey)->orWhere('source_key', 'like', $parentKey.'|%');
                })->first();
                $status = $parent && in_array($parent->decision, ['matched', 'proposed'], true) ? 'resolved' : 'unresolved';
                MigrationBlueprintDependency::create(['migration_id' => $migration->id, 'node_id' => $node->id, 'relationship_field' => $field, 'parent_entity_type' => $parentEntity, 'parent_source_key' => $parentKey, 'parent_node_id' => $parent?->id, 'resolution_status' => $status]);
                if ($status === 'unresolved') {
                    $this->issue($migration, $node->dataset_id, 'blueprint_parent_unresolved', $field, "Required {$parentEntity} [{$value}] is unresolved for {$node->entity_type} [{$node->source_key}].");
                }
            }
        }
    }

    private function recordBelongsToTenant(string $entity, int $id, int $tenantId): bool
    {
        $model = config("data_migration_blueprint.entities.{$entity}.model");

        return $model && $model::query()->whereKey($id)->where('tenant_id', $tenantId)->exists();
    }

    private function sourceKey(array $payload, array $identity, string $entity): string
    {
        if ($entity === 'subject' && filled($payload['code'] ?? null)) {
            return $this->normalise($payload['code']);
        }

        return implode('|', array_filter(array_map(fn ($field) => isset($payload[$field]) ? $this->normalise($payload[$field]) : null, $identity)));
    }

    private function normalise(mixed $value): string
    {
        return Str::of((string) $value)->trim()->lower()->ascii()->replaceMatches('/\s+/', ' ')->toString();
    }

    private function candidateLabel($record, array $fields): string
    {
        return implode(' · ', array_filter(array_map(fn ($field) => $record->{$field} ?? null, $fields)));
    }

    private function sortRecursive(array $value): array
    {
        ksort($value);
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->sortRecursive($item);
            }
        }

return $value;
    }

    private function issue(DataMigration $migration, ?int $datasetId, string $category, string $field, string $message): void
    {
        MigrationIssue::firstOrCreate(['migration_id' => $migration->id, 'dataset_id' => $datasetId, 'category' => $category, 'field' => $field, 'message' => $message, 'status' => 'open'], ['severity' => 'warning', 'suggested_resolution' => 'Review the reconstructed school blueprint before execution.']);
    }

    private function authorise(DataMigration $migration, User $actor): void
    {
        if (! $actor->isSuperAdmin() && (int) $actor->tenant_id !== (int) $migration->tenant_id) {
            throw new InvalidArgumentException('The user does not belong to the migration tenant.');
        }
    }
}
