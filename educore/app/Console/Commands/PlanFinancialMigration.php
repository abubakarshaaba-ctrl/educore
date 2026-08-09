<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use App\Models\User;
use App\Services\DataMigration\FinancialMigrationPlanningService;
use Illuminate\Console\Command;

class PlanFinancialMigration extends Command
{
    protected $signature = 'data-migration:plan-financial {migration} {--actor=}';

    protected $description = 'Plan and reconcile financial migration without posting funds';

    public function handle(FinancialMigrationPlanningService $service): int
    {
        $m = DataMigration::where('batch_number', $this->argument('migration'))->orWhere('id', $this->argument('migration'))->firstOrFail();
        $a = User::findOrFail($this->option('actor') ?: $m->initiated_by);
        $c = $service->plan($m, $a);
        $this->table(['Decision', 'Count'], collect($c)->map(fn ($v, $k) => [$k, $v])->values()->all());

        return self::SUCCESS;
    }
}
