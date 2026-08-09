<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use App\Services\DataMigration\ImmutableSourceStorage;
use Illuminate\Console\Command;

class VerifyDataMigration extends Command
{
    protected $signature = 'data-migration:verify {migration : Batch number or database ID}';

    protected $description = 'Verify immutable source files and foundational migration integrity.';

    public function handle(ImmutableSourceStorage $storage): int
    {
        $value = (string) $this->argument('migration');
        $migration = DataMigration::query()
            ->with('files')
            ->where('batch_number', $value)
            ->when(ctype_digit($value), fn ($query) => $query->orWhereKey((int) $value))
            ->first();

        if (! $migration) {
            $this->error('Data migration batch not found.');

            return self::FAILURE;
        }

        $failed = [];
        foreach ($migration->files as $file) {
            if (! $storage->verify($file)) {
                $failed[] = $file->original_filename;
            }
        }

        if ($failed !== []) {
            $this->error('Source integrity verification failed: '.implode(', ', $failed));

            return self::FAILURE;
        }

        if ($migration->files->isEmpty()) {
            $this->warn('No source files are attached to this batch.');
        } else {
            $this->info("Verified {$migration->files->count()} immutable source file(s).");
        }

        $openCritical = $migration->issues()->where('severity', 'critical')->where('status', 'open')->count();
        $this->line("Open critical issues: {$openCritical}");

        return $openCritical === 0 ? self::SUCCESS : self::FAILURE;
    }
}
