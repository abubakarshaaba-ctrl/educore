<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use App\Models\User;
use App\Services\DataMigration\CoreEntityPlanningService;
use Illuminate\Console\Command;

class PlanCoreEntityMigration extends Command
{
    protected $signature = 'data-migration:plan-core {migration : Batch number or ID} {--actor= : User ID}';

    protected $description = 'Normalize and plan core entity migration without changing operational records';

    public function handle(CoreEntityPlanningService $service): int
    {
        $migration = DataMigration::query()->where('batch_number', $this->argument('migration'))->orWhere('id', $this->argument('migration'))->firstOrFail();
        $actor = User::query()->findOrFail($this->option('actor') ?: $migration->initiated_by);
        $counts = $service->plan($migration, $actor);
        $this->table(['Decision', 'Count'], collect($counts)->map(fn ($count, $decision) => [$decision, $count])->values()->all());

        return self::SUCCESS;
    }
}
