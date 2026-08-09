<?php

namespace App\Console\Commands;

use App\Models\DataMigration;
use App\Models\User;
use App\Services\DataMigration\MigrationRollbackService;
use Illuminate\Console\Command;

class RollbackDataMigration extends Command
{
    protected $signature = 'data-migration:rollback {migration} {--actor=}';

    protected $description = 'Execute a journaled migration rollback';

    public function handle(MigrationRollbackService $service): int
    {
        $m = DataMigration::where('batch_number', $this->argument('migration'))->orWhere('id', $this->argument('migration'))->firstOrFail();
        $a = User::findOrFail($this->option('actor'));
        $this->table(['Outcome', 'Count'], collect($service->rollback($m, $a))->map(fn ($v, $k) => [$k, $v])->values()->all());

        return self::SUCCESS;
    }
}
