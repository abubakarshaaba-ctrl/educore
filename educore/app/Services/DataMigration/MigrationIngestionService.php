<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Models\DataMigration;
use App\Models\MigrationCheckpoint;
use App\Models\MigrationDataset;
use App\Models\MigrationFile;
use App\Models\MigrationIssue;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MigrationIngestionService
{
    public function __construct(
        private readonly SourceAdapterRegistry $adapters,
        private readonly ImmutableSourceStorage $storage,
        private readonly MigrationRowStager $stager,
        private readonly MigrationStateMachine $states,
        private readonly LifecycleAuditLogger $audit,
    ) {}

    public function ingest(DataMigration $migration, User $actor, ?MigrationFile $onlyFile = null): DataMigration
    {
        $this->authorise($migration, $actor);
        if ($onlyFile && (int) $onlyFile->migration_id !== (int) $migration->id) {
            throw new RuntimeException('Migration file does not belong to this batch.');
        }

        if (in_array($migration->status, [DataMigrationStatus::Uploaded, DataMigrationStatus::Failed], true)) {
            $migration = $this->states->transition($migration, DataMigrationStatus::Inspecting, $actor, 'structured_source_ingestion_started');
        } elseif ($migration->status !== DataMigrationStatus::Inspecting) {
            throw new RuntimeException("Batch cannot be ingested from status {$migration->status->value}.");
        }

        $files = $onlyFile ? collect([$onlyFile]) : $migration->files()->orderBy('id')->get();
        if ($files->isEmpty()) {
            throw new RuntimeException('Batch has no immutable source files.');
        }

        $activeFileId = $onlyFile?->id;
        try {
            foreach ($files as $file) {
                $activeFileId = $file->id;
                $this->ingestFile($migration, $file, $actor);
            }

            $migration->update([
                'total_datasets' => $migration->datasets()->count(),
                'total_source_rows' => $migration->rows()->count(),
            ]);

            return $this->states->transition($migration->refresh(), DataMigrationStatus::Extracted, $actor, 'structured_source_ingestion_completed');
        } catch (\Throwable $exception) {
            MigrationIssue::create([
                'migration_id' => $migration->id,
                'migration_file_id' => $activeFileId,
                'severity' => 'critical',
                'category' => 'source_ingestion',
                'message' => $exception->getMessage(),
                'suggested_resolution' => 'Verify file integrity and format, then resume ingestion from the last checkpoint.',
                'status' => 'open',
            ]);
            if ($migration->fresh()->status === DataMigrationStatus::Inspecting) {
                $this->states->transition($migration->fresh(), DataMigrationStatus::Failed, $actor, 'structured_source_ingestion_failed');
            }
            throw $exception;
        }
    }

    private function ingestFile(DataMigration $migration, MigrationFile $file, User $actor): void
    {
        if (! $this->storage->verify($file)) {
            throw new RuntimeException("Immutable source checksum failed for {$file->original_filename}.");
        }

        $adapter = $this->adapters->resolve($file);
        $inspection = $adapter->inspect($file);
        $file->update(['metadata' => array_merge($file->metadata ?? [], ['inspected_format' => $inspection->format, 'inspection' => $inspection->metadata])]);

        $extension = strtolower(pathinfo($file->sanitized_filename, PATHINFO_EXTENSION));
        if ($extension !== '' && ! in_array($extension, [$inspection->format, $inspection->format === 'csv' ? 'txt' : ''], true)) {
            MigrationIssue::firstOrCreate([
                'migration_id' => $migration->id,
                'migration_file_id' => $file->id,
                'severity' => 'warning',
                'category' => 'file_type_mismatch',
                'field' => 'filename',
            ], [
                'source_value' => $file->original_filename,
                'message' => "File extension {$extension} does not match detected format {$inspection->format}.",
                'suggested_resolution' => 'Review the source provenance; parsing follows the verified content signature.',
                'status' => 'open',
            ]);
        }

        foreach ($inspection->datasets as $definition) {
            $dataset = MigrationDataset::firstOrCreate(
                ['migration_id' => $migration->id, 'migration_file_id' => $file->id, 'source_name' => $definition->name],
                [
                    'classification_status' => 'unclassified',
                    'source_row_count' => $definition->estimatedRows ?? 0,
                    'source_schema' => ['headers' => $definition->headers],
                    'metadata' => ['locator' => $definition->locator, 'adapter_metadata' => $definition->metadata],
                ]
            );

            $checkpoint = MigrationCheckpoint::where('migration_id', $migration->id)
                ->where('dataset_id', $dataset->id)
                ->where('stage', 'extraction')
                ->latest('id')
                ->first();
            $startRow = ((int) ($checkpoint?->last_row_number ?? 0)) + 1;
            $buffer = [];
            $lastRow = $startRow - 1;
            $chunkSize = max(1, (int) config('data_migration.staging_chunk_rows'));

            foreach ($adapter->streamRows($file, $definition, $startRow) as $rowNumber => $payload) {
                $lastRow = (int) $rowNumber;
                $buffer[] = ['row_number' => $lastRow, 'raw_payload' => $payload];
                if (count($buffer) >= $chunkSize) {
                    $this->flush($migration, $dataset, $buffer, $lastRow);
                    $buffer = [];
                }
            }
            if ($buffer !== []) {
                $this->flush($migration, $dataset, $buffer, $lastRow);
            }

            $dataset->update([
                'staged_row_count' => $dataset->rows()->count(),
                'source_row_count' => $definition->estimatedRows ?? $dataset->rows()->count(),
            ]);
        }

        $this->audit->record($migration->tenant_id, $actor, $migration, 'data_migration.file_extracted', [], [
            'migration_file_id' => $file->id,
            'format' => $inspection->format,
            'datasets' => count($inspection->datasets),
        ]);
    }

    private function flush(DataMigration $migration, MigrationDataset $dataset, array $buffer, int $lastRow): void
    {
        DB::transaction(function () use ($migration, $dataset, $buffer, $lastRow): void {
            $this->stager->stageMany($migration, $dataset, $buffer);
            MigrationCheckpoint::create([
                'migration_id' => $migration->id,
                'dataset_id' => $dataset->id,
                'stage' => 'extraction',
                'last_row_number' => $lastRow,
                'processed_rows' => $dataset->rows()->count(),
                'checkpoint_checksum' => hash('sha256', $migration->id.'|'.$dataset->id.'|'.$lastRow),
                'state' => ['last_chunk_size' => count($buffer)],
                'created_at' => now(),
            ]);
        });
    }

    private function authorise(DataMigration $migration, User $actor): void
    {
        if (! $actor->isSuperAdmin() && ((int) $actor->tenant_id !== (int) $migration->tenant_id || ! $actor->isAdmin())) {
            throw new RuntimeException('User is not authorised to ingest this tenant migration.');
        }
    }
}
