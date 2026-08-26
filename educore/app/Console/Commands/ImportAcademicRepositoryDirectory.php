<?php

namespace App\Console\Commands;

use App\Models\CurriculumSource;
use App\Models\User;
use App\Services\Curriculum\AcademicRepositoryIngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAcademicRepositoryDirectory extends Command
{
    protected $signature = 'academic-repository:import-directory
        {path : Directory containing lesson-note DOCX/PDF/DOC/XLSX files}
        {--actor= : User ID to record as the uploader/reviewer}
        {--label= : Import batch label}
        {--activate : Approve and activate successfully indexed imported resources}';

    protected $description = 'Import a local lesson-note directory into the platform academic repository, skipping checksum duplicates.';

    public function handle(AcademicRepositoryIngestionService $ingestion): int
    {
        $path = (string) $this->argument('path');
        $actor = $this->resolveActor();
        if ($actor === null) {
            return self::FAILURE;
        }

        $meta = [
            'authority' => 'OTHER',
            'source_type' => 'lesson_note',
            'rights_status' => 'institution_authorised',
            'is_official' => false,
        ];

        $this->info('Importing academic repository directory: '.$path);
        $import = $ingestion->ingestDirectory(
            $path,
            $meta,
            $actor,
            (string) ($this->option('label') ?: 'Lesson Notes Directory Import')
        );

        $import->refresh();

        $activated = 0;
        if ($this->option('activate')) {
            $activated = $this->activateImportedResources($import->id, $actor);
        }

        $this->table(
            ['Import ID', 'Status', 'Discovered', 'Imported', 'Duplicates', 'Failed', 'Needs Review', 'Activated'],
            [[
                $import->id,
                $import->status,
                $import->discovered,
                $import->imported,
                $import->duplicates,
                $import->failed,
                $import->needs_review,
                $activated,
            ]]
        );

        return $import->failed ? self::FAILURE : self::SUCCESS;
    }

    private function resolveActor(): ?int
    {
        $option = $this->option('actor');
        if ($option !== null && $option !== '') {
            $user = User::query()->find((int) $option);
            if (!$user) {
                $this->error('The supplied actor user ID does not exist.');

                return null;
            }

            return (int) $user->id;
        }

        $user = User::query()
            ->where('role', 'super_admin')
            ->orWhere('email', 'like', '%admin%')
            ->orderBy('id')
            ->first();

        if (!$user) {
            $this->error('No uploader actor was found. Re-run with --actor=<user-id>.');

            return null;
        }

        return (int) $user->id;
    }

    private function activateImportedResources(int $importId, int $actor): int
    {
        return DB::transaction(function () use ($importId, $actor): int {
            $ids = DB::table('repository_import_items')
                ->where('repository_import_id', $importId)
                ->where('status', 'needs_review')
                ->whereNotNull('curriculum_source_id')
                ->pluck('curriculum_source_id');

            $activated = 0;
            CurriculumSource::query()
                ->whereNull('tenant_id')
                ->whereIn('id', $ids)
                ->where('extraction_status', 'extracted')
                ->where('index_status', 'indexed')
                ->chunkById(500, function ($sources) use ($actor, &$activated): void {
                    foreach ($sources as $source) {
                        if (!$source->fragments()->exists()) {
                            continue;
                        }

                        $source->update([
                            'review_status' => 'approved',
                            'is_active' => true,
                            'needs_review' => false,
                            'reviewed_by' => $actor,
                            'reviewed_at' => now(),
                        ]);
                        $activated++;
                    }
                });

            return $activated;
        });
    }
}
