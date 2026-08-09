<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use App\Models\User;
use App\Services\DataMigration\SchoolBlueprintReconstructionService;
use Illuminate\Console\Command;

class ReconstructSchoolBlueprint extends Command
{
    protected $signature = 'data-migration:blueprint {migration : Batch number or ID} {--actor= : User ID}';

    protected $description = 'Discover and match a staged school structure without changing operational records';

    public function handle(SchoolBlueprintReconstructionService $service): int
    {
        $migration = DataMigration::query()->where('batch_number', $this->argument('migration'))->orWhere('id', $this->argument('migration'))->firstOrFail();
        $actor = User::query()->findOrFail($this->option('actor') ?: $migration->initiated_by);
        $counts = $service->reconstruct($migration, $actor);
        $this->table(['Decision', 'Count'], collect($counts)->map(fn ($count, $decision) => [$decision, $count])->values()->all());

        return self::SUCCESS;
    }
}
