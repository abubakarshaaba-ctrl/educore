<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\AttendanceRecord;
use App\Models\DataMigration;
use App\Models\MigrationBlueprintNode;
use App\Models\MigrationCoreRecord;
use App\Models\MigrationHistoricalDependency;
use App\Models\MigrationHistoricalRecord;
use App\Models\MigrationIssue;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AcademicHistoryPlanningService
{
    private const ENTITIES = ['subject_registration', 'score', 'termly_summary', 'attendance'];

    public function __construct(private CanonicalSchemaRegistry $schemas, private CanonicalValueNormaliser $normaliser, private LifecycleAuditLogger $audit) {}

    public function plan(DataMigration $migration, User $actor): array
    {
        $this->authorise($migration, $actor);
        if ($migration->status !== DataMigrationStatus::Normalising) {
            throw new InvalidArgumentException('Core entity planning must be complete before academic history planning.');
        }
        $counts = ['create' => 0, 'update' => 0, 'unchanged' => 0, 'conflict' => 0, 'blocked' => 0];
        DB::transaction(function () use ($migration, $actor, &$counts): void {
            foreach ($migration->datasets()->whereIn('canonical_entity', self::ENTITIES)->orderBy('id')->get() as $dataset) {
                $schema = $this->schemas->entity($dataset->canonical_entity);
                $mappings = $migration->mappings()->where('dataset_id', $dataset->id)->where('decision', MigrationMappingDecision::AutoMap->value)->whereNotNull('destination_field')->get();
                $dataset->rows()->orderBy('id')->chunkById(config('data_migration.staging_chunk_rows', 500), function (Collection $rows) use ($migration, $dataset, $schema, $mappings): void {
                    foreach ($rows as $row) {
                        $mapped = [];
                        $normal = [];
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
                            $normal[$field->name] = $result->value;
                            $rules[$field->name] = $result->rule;
                            if ($result->warning) {
                                $warnings[] = ['field' => $field->name, 'message' => $result->warning, 'raw_value' => $raw];
                            }
                        }
                        $row->update(['mapped_payload' => $mapped, 'normalised_payload' => $normal, 'warnings' => $warnings ?: null]);
                        $this->stage($migration, $dataset->id, $row->id, $dataset->canonical_entity, $normal, $rules);
                    }
                });
            }
            $this->dependencies($migration);
            $this->matchLive($migration);
            foreach (array_keys($counts) as $decision) {
                $counts[$decision] = $migration->historicalRecords()->where('decision', $decision)->count();
            }
            $metadata = $migration->metadata ?? [];
            $metadata['academic_history_plan'] = ['completed_at' => now()->toIso8601String(), 'counts' => $counts, 'dry_run_only' => true];
            $migration->update(['metadata' => $metadata]);
            $this->audit->record($migration->tenant_id, $actor, $migration, 'data_migration.academic_history_planned', [], $counts);
        });

        return $counts;
    }

    private function stage(DataMigration $migration, int $datasetId, int $rowId, string $entity, array $payload, array $rules): void
    {
        $schema = $this->schemas->entity($entity);
        $missing = collect($schema->fields)->filter(fn ($f) => $f->required && ($payload[$f->name] ?? null) === null)->keys()->all();
        $keyFields = match ($entity) {
            'subject_registration' => ['student_admission_number', 'subject_code', 'session', 'term'],
            'score' => ['student_admission_number', 'subject_code', 'assessment_type', 'term', 'session'],
            'termly_summary' => ['student_admission_number', 'term', 'session'],
            'attendance' => ['student_admission_number', 'attendance_date'],
        };
        $key = implode('|', array_map(fn ($field) => $this->key($payload[$field] ?? null), $keyFields));
        $invalid = $this->invalid($entity, $payload);
        $decision = ($missing || $invalid) ? 'blocked' : 'create';
        if ($missing || $invalid) {
            $this->issue($migration, $datasetId, 'historical_validation', $entity, implode(' ', array_filter([$missing ? 'Missing: '.implode(', ', $missing).'.' : null, $invalid])));
        }
        $checksum = hash('sha256', json_encode($this->sort($payload), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
        $existing = $migration->historicalRecords()->where(['entity_type' => $entity, 'source_key' => $key])->first();
        if ($existing && $existing->payload_checksum !== $checksum) {
            $existing->update(['decision' => 'conflict']);
            $this->issue($migration, $datasetId, 'historical_duplicate_conflict', $entity, "Conflicting historical rows share [{$key}].");

            return;
        }
        if ($existing) {
            return;
        }
        MigrationHistoricalRecord::create(['migration_id' => $migration->id, 'dataset_id' => $datasetId, 'migration_row_id' => $rowId, 'tenant_id' => $migration->tenant_id, 'entity_type' => $entity, 'source_key' => $key, 'canonical_payload' => $payload, 'decision' => $decision, 'payload_checksum' => $checksum, 'metadata' => ['transformation_rules' => $rules, 'dry_run_only' => true]]);
    }

    private function dependencies(DataMigration $migration): void
    {
        MigrationHistoricalDependency::where('migration_id', $migration->id)->delete();
        foreach ($migration->historicalRecords as $record) {
            $relations = match ($record->entity_type) {
                'subject_registration' => ['student_admission_number' => 'student', 'class_level' => 'class_level', 'subject_code' => 'subject', 'session' => 'academic_session', 'term' => 'term'],
                'score' => ['student_admission_number' => 'student', 'subject_code' => 'subject', 'assessment_type' => 'assessment_type', 'term' => 'term', 'session' => 'academic_session'],
                'termly_summary' => ['student_admission_number' => 'student', 'class_arm' => 'class_arm', 'term' => 'term', 'session' => 'academic_session'],
                'attendance' => ['student_admission_number' => 'student', 'class_arm' => 'class_arm', 'term' => 'term'],
            };
            foreach ($relations as $field => $parent) {
                $key = $this->key($record->canonical_payload[$field] ?? null);
                if ($key === '') {
                    continue;
                }
                [$status, $source, $id] = $this->resolve($migration, $parent, $key);
                MigrationHistoricalDependency::create(['migration_id' => $migration->id, 'historical_record_id' => $record->id, 'relationship_field' => $field, 'parent_entity_type' => $parent, 'parent_source_key' => $key, 'parent_source' => $source, 'resolved_record_id' => $id, 'resolution_status' => $status]);
                if ($status === 'unresolved') {
                    $record->update(['decision' => 'blocked']);
                    $this->issue($migration, $record->dataset_id, 'historical_parent_unresolved', $field, "Required {$parent} [{$key}] is unresolved.");
                }
            }
        }
    }

    private function resolve(DataMigration $migration, string $entity, string $key): array
    {
        if ($entity === 'student') {
            $core = MigrationCoreRecord::where('migration_id', $migration->id)->where('entity_type', 'student')->where('source_key', $key)->first();
            if ($core && ! in_array($core->decision, ['blocked', 'conflict'], true)) {
                return ['resolved', 'core_plan', $core->matched_record_id];
            }
            $live = Student::where('tenant_id', $migration->tenant_id)->whereRaw('LOWER(admission_number) = ?', [$key])->first();

            return $live ? ['resolved', 'operational', $live->id] : ['unresolved', null, null];
        }
        $node = MigrationBlueprintNode::where('migration_id', $migration->id)->where('entity_type', $entity)->get()->first(function ($node) use ($key): bool {
            if ($node->source_key === $key || str_starts_with($node->source_key, $key.'|')) {
                return true;
            }

            return collect($node->canonical_payload)->contains(fn ($value) => $this->key($value) === $key);
        });

        return $node && in_array($node->decision, ['matched', 'proposed'], true) ? ['resolved', 'blueprint', $node->matched_record_id] : ['unresolved', null, null];
    }

    private function matchLive(DataMigration $migration): void
    {
        foreach ($migration->historicalRecords()->where('decision', 'create')->whereIn('entity_type', ['score', 'attendance'])->get() as $record) {
            $deps = $record->dependencies->keyBy('relationship_field');
            if ($deps->contains(fn ($d) => ! $d->resolved_record_id)) {
                continue;
            }
            $live = $record->entity_type === 'score'
                ? Score::where('tenant_id', $migration->tenant_id)->where('student_id', $deps['student_admission_number']->resolved_record_id)->where('subject_id', $deps['subject_code']->resolved_record_id)->where('assessment_type_id', $deps['assessment_type']->resolved_record_id)->where('term_id', $deps['term']->resolved_record_id)->first()
                : AttendanceRecord::where('tenant_id', $migration->tenant_id)->where('student_id', $deps['student_admission_number']->resolved_record_id)->where('attendance_date', $record->canonical_payload['attendance_date'])->first();
            if ($live) {
                $record->update(['decision' => 'update', 'matched_record_id' => $live->id]);
            }
        }
    }

    private function invalid(string $entity, array $p): ?string
    {
        if ($entity === 'score' && isset($p['score']) && (! is_numeric($p['score']) || $p['score'] < 0)) {
            return 'Score must be a non-negative number.';
        }
        if ($entity === 'termly_summary' && isset($p['final_average']) && ($p['final_average'] < 0 || $p['final_average'] > 100)) {
            return 'Final average must be between 0 and 100.';
        }
        if ($entity === 'attendance' && isset($p['status']) && ! in_array($p['status'], ['present', 'absent', 'late', 'excused'], true)) {
            return 'Attendance status is invalid.';
        }

        return null;
    }

    private function key(mixed $v): string
    {
        return Str::of((string) $v)->trim()->lower()->ascii()->replaceMatches('/\s+/', ' ')->toString();
    }

    private function sort(array $v): array
    {
        ksort($v);

        return $v;
    }

    private function issue(DataMigration $m, ?int $d, string $c, string $f, string $msg): void
    {
        MigrationIssue::firstOrCreate(['migration_id' => $m->id, 'dataset_id' => $d, 'category' => $c, 'field' => $f, 'message' => $msg, 'status' => 'open'], ['severity' => 'error', 'suggested_resolution' => 'Resolve academic history before dry run.']);
    }

    private function authorise(DataMigration $m, User $a): void
    {
        if (! $a->isSuperAdmin() && (int) $a->tenant_id !== (int) $m->tenant_id) {
            throw new InvalidArgumentException('The user does not belong to the migration tenant.');
        }
    }
}
