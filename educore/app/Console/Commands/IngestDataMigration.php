<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use App\Models\MigrationFile;
use App\Models\User;
use App\Services\DataMigration\MigrationIngestionService;
use Illuminate\Console\Command;

class IngestDataMigration extends Command
{
    protected $signature = 'data-migration:ingest
        {migration : Batch number or database ID}
        {--actor= : Authorised EduCore user ID}
        {--file= : Optional migration_files ID to ingest}';

    protected $description = 'Inspect and stream immutable structured sources into lossless staging.';

    public function handle(MigrationIngestionService $ingestion): int
    {
        $actorId = $this->option('actor');
        if (! is_numeric($actorId) || ! ($actor = User::find((int) $actorId))) {
            $this->error('A valid --actor user ID is required.');

            return self::FAILURE;
        }

        $value = (string) $this->argument('migration');
        $migration = DataMigration::query()
            ->where('batch_number', $value)
            ->when(ctype_digit($value), fn ($query) => $query->orWhereKey((int) $value))
            ->first();
        if (! $migration) {
            $this->error('Data migration batch not found.');

            return self::FAILURE;
        }

        $file = null;
        if ($this->option('file') !== null) {
            $file = MigrationFile::find((int) $this->option('file'));
            if (! $file) {
                $this->error('Migration source file not found.');

                return self::FAILURE;
            }
        }

        try {
            $migration = $ingestion->ingest($migration, $actor, $file);
            $this->info("{$migration->batch_number} extracted successfully.");
            $this->line("Datasets: {$migration->total_datasets}; staged rows: {$migration->total_source_rows}");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
