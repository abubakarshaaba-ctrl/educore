<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\DataMigration;
use App\Models\Guardian;
use App\Models\MigrationBlueprintNode;
use App\Models\MigrationCoreDependency;
use App\Models\MigrationCoreRecord;
use App\Models\MigrationIssue;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CoreEntityPlanningService
{
    private const ENTITIES = ['student', 'guardian', 'staff', 'student_enrollment'];

    public function __construct(private readonly CanonicalSchemaRegistry $schemas, private readonly CanonicalValueNormaliser $normaliser, private readonly MigrationStateMachine $states, private readonly LifecycleAuditLogger $audit) {}

    public function plan(DataMigration $migration, User $actor): array
    {
        $this->authorise($migration, $actor);
        if (! in_array($migration->status, [DataMigrationStatus::Mapped, DataMigrationStatus::Normalising], true)) {
            throw new InvalidArgumentException('Migration mappings must be complete before core entity planning.');
        }
        if ($migration->status === DataMigrationStatus::Mapped) {
            $migration = $this->states->transition($migration, DataMigrationStatus::Normalising, $actor, 'Core entity normalization and planning started.');
        }
        $counts = ['create' => 0, 'update' => 0, 'unchanged' => 0, 'conflict' => 0, 'blocked' => 0, 'relationships' => 0];

        DB::transaction(function () use ($migration, $actor, &$counts): void {
            $order = array_flip(self::ENTITIES);
            $datasets = $migration->datasets()->whereIn('canonical_entity', self::ENTITIES)->get()->sortBy(fn ($dataset) => $order[$dataset->canonical_entity]);
            foreach ($datasets as $dataset) {
                $schema = $this->schemas->entity($dataset->canonical_entity);
                $mappings = $migration->mappings()->where('dataset_id', $dataset->id)->where('decision', MigrationMappingDecision::AutoMap->value)->whereNotNull('destination_field')->get();
                $dataset->rows()->orderBy('id')->chunkById(config('data_migration.staging_chunk_rows', 500), function (Collection $rows) use ($migration, $dataset, $schema, $mappings): void {
                    foreach ($rows as $row) {
                        $mapped = [];
                        $normalised = [];
                        $rules = [];
                        $warnings = [];
                        foreach ($mappings as $mapping) {
                            $raw = $row->raw_payload[$mapping->source_column] ?? null;
                            $mapped[$mapping->destination_field] = $raw;
                            $field = $schema->field($mapping->destination_field);
                            if (! $field) {
                                continue;
                            }
                            $result = $this->normaliser->normalise($raw, $field);
                            $normalised[$mapping->destination_field] = $result->value;
                            $rules[$mapping->destination_field] = $result->rule;
                            if ($result->warning) {
                                $warnings[] = ['field' => $mapping->destination_field, 'message' => $result->warning, 'raw_value' => $raw];
                            }
                        }
                        $row->update(['mapped_payload' => $mapped, 'normalised_payload' => $normalised, 'warnings' => $warnings ?: null, 'mapping_confidence' => $mappings->min('confidence') ?: null]);
                        $this->stageRecord($migration, $dataset->id, $row->id, $dataset->canonical_entity, $normalised, $rules);
                    }
                });
            }
            $this->buildGuardianRelationships($migration);
            $this->buildDependencies($migration);
            $this->matchEnrollments($migration);
            foreach (['create', 'update', 'unchanged', 'conflict', 'blocked'] as $decision) {
                $counts[$decision] = $migration->coreRecords()->where('decision', $decision)->count();
            }
            $counts['relationships'] = $migration->coreRecords()->where('entity_type', 'guardian_student')->count();
            $metadata = $migration->metadata ?? [];
            $metadata['core_entity_plan'] = ['completed_at' => now()->toIso8601String(), 'schema_version' => config('data_migration_schema.version'), 'counts' => $counts, 'dry_run_only' => true];
            $migration->update(['metadata' => $metadata]);
            $this->audit->record($migration->tenant_id, $actor, $migration, 'data_migration.core_entities_planned', [], $counts);
        });

        return $counts;
    }

    private function stageRecord(DataMigration $migration, int $datasetId, int $rowId, string $entity, array $payload, array $rules): void
    {
        $missingRequired = collect($this->schemas->entity($entity)->fields)
            ->filter(fn ($field) => $field->required && ($payload[$field->name] ?? null) === null)
            ->keys()->values()->all();
        $key = $this->sourceKey($entity, $payload);
        if ($key === '') {
            $this->issue($migration, $datasetId, 'core_identity_missing', $entity, 'A core record has no usable business identity.');

            return;
        }
        $checksum = hash('sha256', json_encode($this->sortRecursive($payload), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
        $existing = $migration->coreRecords()->where(['entity_type' => $entity, 'source_key' => $key])->first();
        if ($existing && $existing->payload_checksum !== $checksum) {
            $existing->update(['decision' => 'conflict', 'metadata' => array_merge($existing->metadata ?? [], ['conflicting_row_ids' => array_values(array_unique([...($existing->metadata['conflicting_row_ids'] ?? []), $rowId]))])]);
            $this->issue($migration, $datasetId, 'core_duplicate_conflict', $entity, "Conflicting source records share identity [{$key}].");

            return;
        }
        if ($existing) {
            return;
        }
        [$decision, $matchedId, $candidates, $reason] = $this->match($migration, $entity, $payload);
        if ($missingRequired) {
            $decision = 'blocked';
            $reason = 'Required canonical fields are missing: '.implode(', ', $missingRequired).'.';
            $this->issue($migration, $datasetId, 'core_required_field_missing', $entity, $reason);
        }
        MigrationCoreRecord::create(['migration_id' => $migration->id, 'dataset_id' => $datasetId, 'migration_row_id' => $rowId, 'tenant_id' => $migration->tenant_id, 'entity_type' => $entity, 'source_key' => $key, 'canonical_payload' => $payload, 'decision' => $decision, 'matched_record_id' => $matchedId, 'payload_checksum' => $checksum, 'match_candidates' => $candidates, 'metadata' => ['transformation_rules' => $rules, 'match_reason' => $reason, 'dry_run_only' => true]]);
        if ($decision === 'conflict') {
            $this->issue($migration, $datasetId, 'core_identity_conflict', $entity, $reason);
        }
    }

    private function match(DataMigration $migration, string $entity, array $payload): array
    {
        if ($entity === 'student') {
            $record = Student::withTrashed()->where('tenant_id', $migration->tenant_id)->where('admission_number', $payload['admission_number'])->first();

            return $record ? [$this->samePayload($record, $payload) ? 'unchanged' : 'update', $record->id, [['id' => $record->id]], 'Tenant admission number matched.'] : ['create', null, [], 'No tenant student matched admission number.'];
        }
        if ($entity === 'staff') {
            $record = User::withTrashed()->whereRaw('LOWER(email) = ?', [mb_strtolower($payload['email'] ?? '')])->first();
            if ($record && (int) $record->tenant_id !== (int) $migration->tenant_id) {
                return ['conflict', null, [['id' => $record->id, 'tenant_id' => $record->tenant_id]], 'Staff email already belongs to another tenant.'];
            }

            return $record ? [$this->samePayload($record, $payload) ? 'unchanged' : 'update', $record->id, [['id' => $record->id]], 'Email matched an existing tenant staff account.'] : ['create', null, [], 'No account matched staff email.'];
        }
        if ($entity === 'guardian') {
            $query = Guardian::withTrashed()->where('tenant_id', $migration->tenant_id);
            if (filled($payload['email'] ?? null)) {
                $query->whereRaw('LOWER(email) = ?', [mb_strtolower($payload['email'])]);
            } elseif (filled($payload['phone'] ?? null)) {
                $query->where('phone', $payload['phone']);
            } else {
                $query->whereRaw('LOWER(first_name) = ? AND LOWER(last_name) = ?', [mb_strtolower($payload['first_name'] ?? ''), mb_strtolower($payload['last_name'] ?? '')]);
            }
            $records = $query->limit(3)->get();
            if ($records->count() > 1) {
                return ['conflict', null, $records->map(fn ($r) => ['id' => $r->id])->all(), 'Guardian identity matches multiple tenant records.'];
            }
            $record = $records->first();

            return $record ? [$this->samePayload($record, $payload) ? 'unchanged' : 'update', $record->id, [['id' => $record->id]], 'Guardian identity matched.'] : ['create', null, [], 'No guardian identity matched.'];
        }

        return ['create', null, [], 'Dependency-based entity is planned after parent resolution.'];
    }

    private function buildDependencies(DataMigration $migration): void
    {
        MigrationCoreDependency::where('migration_id', $migration->id)->delete();
        foreach ($migration->coreRecords()->whereIn('entity_type', ['student', 'guardian', 'student_enrollment', 'guardian_student'])->get() as $record) {
            $relationships = match ($record->entity_type) {
                'student' => ['current_class_arm' => 'class_arm'],
                'guardian' => ['student_admission_number' => 'student'],
                'student_enrollment' => ['student_admission_number' => 'student', 'class_arm' => 'class_arm', 'session' => 'academic_session', 'term' => 'term'],
                'guardian_student' => ['guardian_source_key' => 'guardian', 'student_admission_number' => 'student'],
            };
            foreach ($relationships as $field => $parentEntity) {
                $key = $this->normalKey($record->canonical_payload[$field] ?? null);
                if ($key === '') {
                    continue;
                }
                [$status, $source, $resolvedId] = $this->resolveParent($migration, $parentEntity, $key);
                MigrationCoreDependency::create(['migration_id' => $migration->id, 'core_record_id' => $record->id, 'relationship_field' => $field, 'parent_entity_type' => $parentEntity, 'parent_source_key' => $key, 'parent_source' => $source, 'resolved_record_id' => $resolvedId, 'resolution_status' => $status]);
                if ($status === 'unresolved') {
                    $record->update(['decision' => 'blocked']);
                    $this->issue($migration, $record->dataset_id, 'core_parent_unresolved', $field, "Required {$parentEntity} [{$key}] is unresolved for {$record->entity_type} [{$record->source_key}].");
                }
            }
        }
    }

    private function resolveParent(DataMigration $migration, string $entity, string $key): array
    {
        if ($entity === 'student') {
            $core = $migration->coreRecords()->where('entity_type', 'student')->where('source_key', $key)->first();
            if ($core && ! in_array($core->decision, ['conflict', 'blocked'], true)) {
                return ['resolved', 'core_plan', $core->matched_record_id];
            }
            $live = Student::where('tenant_id', $migration->tenant_id)->whereRaw('LOWER(admission_number) = ?', [$key])->first();

            return $live ? ['resolved', 'operational', $live->id] : ['unresolved', null, null];
        }
        if ($entity === 'guardian') {
            $core = $migration->coreRecords()->where('entity_type', 'guardian')->where('source_key', $key)->first();

            return $core && ! in_array($core->decision, ['conflict', 'blocked'], true) ? ['resolved', 'core_plan', $core->matched_record_id] : ['unresolved', null, null];
        }
        $node = MigrationBlueprintNode::where('migration_id', $migration->id)->where('entity_type', $entity)->where(function ($q) use ($key): void {
            $q->where('source_key', $key)->orWhere('source_key', 'like', $key.'|%');
        })->first();
        if ($node && in_array($node->decision, ['matched', 'proposed'], true)) {
            return ['resolved', 'blueprint', $node->matched_record_id];
        }

        return ['unresolved', null, null];
    }

    private function buildGuardianRelationships(DataMigration $migration): void
    {
        foreach ($migration->coreRecords()->where('entity_type', 'guardian')->whereNotIn('decision', ['conflict', 'blocked'])->get() as $guardian) {
            $studentKey = $this->normalKey($guardian->canonical_payload['student_admission_number'] ?? null);
            if ($studentKey === '') {
                continue;
            }
            $key = $guardian->source_key.'|'.$studentKey;
            $payload = ['guardian_source_key' => $guardian->source_key, 'student_admission_number' => $studentKey, 'is_primary_contact' => (bool) ($guardian->canonical_payload['is_primary_contact'] ?? false)];
            MigrationCoreRecord::updateOrCreate(['migration_id' => $migration->id, 'entity_type' => 'guardian_student', 'source_key' => $key], ['dataset_id' => $guardian->dataset_id, 'migration_row_id' => $guardian->migration_row_id, 'tenant_id' => $migration->tenant_id, 'canonical_payload' => $payload, 'decision' => 'create', 'payload_checksum' => hash('sha256', json_encode($payload)), 'metadata' => ['dry_run_only' => true]]);
        }
    }

    private function matchEnrollments(DataMigration $migration): void
    {
        foreach ($migration->coreRecords()->where('entity_type', 'student_enrollment')->where('decision', 'create')->get() as $record) {
            $dependencies = $record->dependencies->keyBy('relationship_field');
            if ($dependencies->count() !== 4 || $dependencies->contains(fn ($dependency) => $dependency->resolution_status !== 'resolved')) {
                continue;
            }
            if ($dependencies->contains(fn ($dependency) => $dependency->resolved_record_id === null)) {
                continue;
            }
            $existing = StudentEnrollment::where('tenant_id', $migration->tenant_id)
                ->where('student_id', $dependencies['student_admission_number']->resolved_record_id)
                ->where('class_arm_id', $dependencies['class_arm']->resolved_record_id)
                ->where('session_id', $dependencies['session']->resolved_record_id)
                ->where('term_id', $dependencies['term']->resolved_record_id)->first();
            if ($existing) {
                $record->update(['decision' => $this->samePayload($existing, $record->canonical_payload) ? 'unchanged' : 'update', 'matched_record_id' => $existing->id, 'metadata' => array_merge($record->metadata ?? [], ['match_reason' => 'All enrollment parents and composite identity matched.'])]);
            }
        }
    }

    private function sourceKey(string $entity, array $payload): string
    {
        return match ($entity) {
            'student' => $this->normalKey($payload['admission_number'] ?? null),
            'staff' => $this->normalKey($payload['email'] ?? $payload['staff_id'] ?? null),
            'guardian' => $this->normalKey($payload['email'] ?? $payload['phone'] ?? (($payload['first_name'] ?? '').'|'.($payload['last_name'] ?? ''))),
            'student_enrollment' => implode('|', array_map(fn ($field) => $this->normalKey($payload[$field] ?? null), ['student_admission_number', 'session', 'term'])),
            default => '',
        };
    }

    private function samePayload($record, array $payload): bool
    {
        foreach ($payload as $field => $value) {
            if (! str_ends_with($field, '_admission_number') && ! in_array($field, ['current_class_arm', 'session', 'term', 'class_arm'], true) && (string) ($record->{$field} ?? '') !== (string) $value) {
                return false;
            }
        }

return true;
    }

    private function normalKey(mixed $value): string
    {
        return Str::of((string) $value)->trim()->lower()->ascii()->replaceMatches('/\s+/', ' ')->toString();
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
        MigrationIssue::firstOrCreate(['migration_id' => $migration->id, 'dataset_id' => $datasetId, 'category' => $category, 'field' => $field, 'message' => $message, 'status' => 'open'], ['severity' => 'error', 'suggested_resolution' => 'Resolve the core entity plan before dry run.']);
    }

    private function authorise(DataMigration $migration, User $actor): void
    {
        if (! $actor->isSuperAdmin() && (int) $actor->tenant_id !== (int) $migration->tenant_id) {
            throw new InvalidArgumentException('The user does not belong to the migration tenant.');
        }
    }
}
