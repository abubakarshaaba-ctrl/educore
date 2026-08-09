<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use App\Models\User;
use App\Services\DataMigration\AcademicHistoryPlanningService;
use Illuminate\Console\Command;

class PlanAcademicHistoryMigration extends Command
{
    protected $signature = 'data-migration:plan-history {migration : Batch number or ID} {--actor= : User ID}';

    protected $description = 'Normalize and plan academic historical migration without operational writes';

    public function handle(AcademicHistoryPlanningService $service): int
    {
        $migration = DataMigration::query()->where('batch_number', $this->argument('migration'))->orWhere('id', $this->argument('migration'))->firstOrFail();
        $actor = User::findOrFail($this->option('actor') ?: $migration->initiated_by);
        $counts = $service->plan($migration, $actor);
        $this->table(['Decision', 'Count'], collect($counts)->map(fn ($count, $decision) => [$decision, $count])->values()->all());

        return self::SUCCESS;
    }
}
