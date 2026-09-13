<?php

namespace App\Console\Commands;

use App\Models\CurriculumSource;
use App\Models\RepositoryImportItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeAcademicRepositorySchemes extends Command
{
    protected $signature = 'academic-repository:purge-schemes {--dry-run : Show what would be deleted without changing data or files}';

    protected $description = 'Permanently remove Scheme of Work resources, their fragments and stored files from the Academic Repository.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $candidates = CurriculumSource::withTrashed()
            ->whereNull('tenant_id')
            ->where(function ($query) {
                $query->whereRaw("LOWER(COALESCE(title, '')) LIKE ?", ['%scheme%work%'])
                    ->orWhereRaw("LOWER(COALESCE(original_filename, '')) LIKE ?", ['%scheme%work%'])
                    ->orWhereRaw("LOWER(COALESCE(source_file_path, '')) LIKE ?", ['%scheme%work%']);
            })
            ->orderBy('id')
            ->get()
            ->filter(fn (CurriculumSource $source) => $this->isSchemeOfWork($source))
            ->values();

        if ($candidates->isEmpty()) {
            $this->info('No Scheme of Work resources were found in the Academic Repository.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Title', 'Original file', 'Stored path', 'Deleted already?'],
            $candidates->map(fn (CurriculumSource $source) => [
                $source->id,
                $source->title,
                $source->original_filename,
                $source->source_file_path,
                $source->trashed() ? 'Yes' : 'No',
            ])->all()
        );

        if ($dryRun) {
            $this->warn(sprintf(
                'Dry run only: %d Scheme of Work resource(s) would be permanently deleted.',
                $candidates->count()
            ));

            return self::SUCCESS;
        }

        $disk = Storage::disk('local');
        $deletedRecords = 0;
        $deletedFiles = 0;
        $missingFiles = 0;
        $fileDeleteFailures = 0;

        foreach ($candidates as $source) {
            $path = trim((string) $source->source_file_path);

            DB::transaction(function () use ($source): void {
                // Remove dependent searchable fragments explicitly so this command
                // remains safe even on installations created before cascade rules.
                $source->fragments()->delete();

                // Historical import audit rows may point at the source. Retain the
                // audit entry but detach it before the source is physically removed.
                RepositoryImportItem::query()
                    ->where('curriculum_source_id', $source->getKey())
                    ->update(['curriculum_source_id' => null]);

                // CurriculumSource uses SoftDeletes. The requirement here is a real
                // purge, so forceDelete removes the database row permanently.
                $source->forceDelete();
            });

            $deletedRecords++;

            if ($path === '') {
                continue;
            }

            if (!$disk->exists($path)) {
                $missingFiles++;
                continue;
            }

            if ($disk->delete($path)) {
                $deletedFiles++;
            } else {
                $fileDeleteFailures++;
                $this->error('Database record deleted, but stored file could not be removed: '.$path);
            }
        }

        $this->newLine();
        $this->info("Permanently deleted {$deletedRecords} Scheme of Work database resource(s).");
        $this->info("Deleted {$deletedFiles} stored source file(s).");

        if ($missingFiles > 0) {
            $this->warn("{$missingFiles} source file(s) were already missing from storage.");
        }

        if ($fileDeleteFailures > 0) {
            $this->error("{$fileDeleteFailures} stored file(s) could not be deleted. Check storage permissions.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function isSchemeOfWork(CurriculumSource $source): bool
    {
        $identity = mb_strtolower(implode(' ', [
            (string) $source->title,
            (string) $source->original_filename,
            (string) $source->source_file_path,
        ]));

        return preg_match('/scheme[\s_\-–—]*of[\s_\-–—]*work/u', $identity) === 1;
    }
}
