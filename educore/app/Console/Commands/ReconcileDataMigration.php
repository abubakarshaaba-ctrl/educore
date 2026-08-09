<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use App\Models\User;
use App\Services\DataMigration\MigrationReconciliationService;
use Illuminate\Console\Command;

class ReconcileDataMigration extends Command
{
    protected $signature = 'data-migration:reconcile {migration} {--actor=} {--complete}';

    protected $description = 'Run migration reconciliation gates';

    public function handle(MigrationReconciliationService $service): int
    {
        $m = DataMigration::where('batch_number', $this->argument('migration'))->orWhere('id', $this->argument('migration'))->firstOrFail();
        $a = User::findOrFail($this->option('actor'));
        $result = $service->reconcile($m, $a, (bool) $this->option('complete'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return $result['passed'] ? self::SUCCESS : self::FAILURE;
    }
}
