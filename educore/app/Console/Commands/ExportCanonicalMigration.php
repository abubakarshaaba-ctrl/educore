<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\CanonicalExportService;
use Illuminate\Console\Command;

class ExportCanonicalMigration extends Command
{
    protected $signature = 'data-migration:export {tenant} {--actor=} {--entities=*}';

    protected $description = 'Create a verified tenant-scoped canonical export package';

    public function handle(CanonicalExportService $service): int
    {
        $tenant = Tenant::findOrFail($this->argument('tenant'));
        $actor = User::findOrFail($this->option('actor'));
        $package = $service->export($tenant, $actor, $this->option('entities'));
        $this->info("Package {$package->id}: {$package->sha256}");

        return self::SUCCESS;
    }
}
