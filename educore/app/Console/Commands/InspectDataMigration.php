<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use Illuminate\Console\Command;

class InspectDataMigration extends Command
{
    protected $signature = 'data-migration:inspect {migration : Batch number or database ID}';

    protected $description = 'Inspect a data migration batch without changing its state or data.';

    public function handle(): int
    {
        $value = (string) $this->argument('migration');
        $migration = DataMigration::query()
            ->withCount(['files', 'datasets', 'rows', 'issues'])
            ->where('batch_number', $value)
            ->when(ctype_digit($value), fn ($query) => $query->orWhereKey((int) $value))
            ->first();

        if (! $migration) {
            $this->error('Data migration batch not found.');

            return self::FAILURE;
        }

        $this->table(['Field', 'Value'], [
            ['Batch', $migration->batch_number],
            ['Tenant', (string) $migration->tenant_id],
            ['Direction', $migration->direction],
            ['Type', $migration->migration_type],
            ['Status', $migration->status->value],
            ['Files', (string) $migration->files_count],
            ['Datasets', (string) $migration->datasets_count],
            ['Staged rows', (string) $migration->rows_count],
            ['Issues', (string) $migration->issues_count],
            ['Created', $migration->created_at?->toIso8601String() ?? ''],
        ]);

        return self::SUCCESS;
    }
}
