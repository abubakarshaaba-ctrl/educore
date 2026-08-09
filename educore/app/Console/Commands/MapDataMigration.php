<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use App\Models\MigrationDataset;
use App\Models\User;
use App\Services\DataMigration\MigrationMappingService;
use Illuminate\Console\Command;

class MapDataMigration extends Command
{
    protected $signature = 'data-migration:map {migration : Batch number or ID} {dataset : Dataset ID} {entity : Canonical entity} {--actor= : User ID}';

    protected $description = 'Generate deterministic canonical field mappings for a staged dataset';

    public function handle(MigrationMappingService $service): int
    {
        $migration = DataMigration::query()->where('batch_number', $this->argument('migration'))->orWhere('id', $this->argument('migration'))->firstOrFail();
        $dataset = MigrationDataset::query()->findOrFail($this->argument('dataset'));
        $actor = User::query()->findOrFail($this->option('actor') ?: $migration->initiated_by);
        $mappings = $service->generate($migration, $dataset, $this->argument('entity'), $actor);
        $this->table(['Source column', 'Destination', 'Decision', 'Confidence'], array_map(fn ($mapping) => [$mapping->source_column, $mapping->destination_field ?: '—', $mapping->decision->value, $mapping->confidence], $mappings));

        return self::SUCCESS;
    }
}
