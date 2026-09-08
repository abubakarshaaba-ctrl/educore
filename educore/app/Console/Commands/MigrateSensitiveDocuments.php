<?php

namespace App\Console\Commands;

use App\Services\SensitiveDocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MigrateSensitiveDocuments extends Command
{
    protected $signature = 'security:migrate-sensitive-documents
        {--dry-run : Inspect files without moving them}
        {--limit=0 : Maximum number of document rows to inspect; 0 means all}';

    protected $description = 'Move admission and recruitment documents from public storage into private storage.';

    public function handle(SensitiveDocumentStorage $storage): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $remaining = $limit > 0 ? $limit : PHP_INT_MAX;
        $counts = [
            'migrated' => 0,
            'already_private' => 0,
            'public_pending' => 0,
            'missing' => 0,
            'invalid' => 0,
            'failed' => 0,
        ];

        foreach (['admission_documents', 'job_applicant_documents'] as $table) {
            if ($remaining <= 0) {
                break;
            }
            if (!Schema::hasTable($table)) {
                $this->warn("Skipping {$table}: table does not exist.");
                continue;
            }

            $this->line('Inspecting '.$table.'...');
            DB::table($table)
                ->select(['id', 'file_path'])
                ->orderBy('id')
                ->chunkById(200, function ($rows) use (
                    $storage,
                    $dryRun,
                    &$remaining,
                    &$counts,
                ): bool {
                    foreach ($rows as $row) {
                        if ($remaining <= 0) {
                            return false;
                        }
                        $remaining--;

                        if ($dryRun) {
                            $result = $this->inspect($storage, $row->file_path ?? null);
                        } else {
                            try {
                                $result = $storage->migrateToPrivate($row->file_path ?? null);
                            } catch (\Throwable $exception) {
                                $result = 'failed';
                                $this->error("Document {$row->id}: {$exception->getMessage()}");
                            }
                        }

                        if (array_key_exists($result, $counts)) {
                            $counts[$result]++;
                        } else {
                            $counts['failed']++;
                        }
                    }

                    return $remaining > 0;
                });
        }

        $this->table(
            ['State', 'Count'],
            collect($counts)->map(fn (int $count, string $state): array => [$state, $count])->values()->all(),
        );

        if ($dryRun) {
            $this->info('Dry run complete. No files were changed.');
            return self::SUCCESS;
        }

        if ($counts['failed'] > 0 || $counts['invalid'] > 0) {
            $this->warn('Migration completed with files requiring manual review.');
            return self::FAILURE;
        }

        $this->info('Sensitive document migration complete.');
        return self::SUCCESS;
    }

    private function inspect(SensitiveDocumentStorage $storage, ?string $path): string
    {
        $normalized = $storage->normalize($path);
        if ($normalized === null) {
            return 'invalid';
        }

        if (Storage::disk(SensitiveDocumentStorage::PRIVATE_DISK)->exists($normalized)) {
            return 'already_private';
        }

        if (Storage::disk(SensitiveDocumentStorage::LEGACY_DISK)->exists($normalized)) {
            return 'public_pending';
        }

        return 'missing';
    }
}
