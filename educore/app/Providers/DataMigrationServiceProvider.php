<?php

namespace App\Providers;

use App\Services\DataMigration\Adapters\DelimitedTextSourceAdapter;
use App\Services\DataMigration\Adapters\JsonSourceAdapter;
use App\Services\DataMigration\Adapters\SpreadsheetSourceAdapter;
use App\Services\DataMigration\Adapters\SqlDumpSourceAdapter;
use App\Services\DataMigration\Adapters\XmlSourceAdapter;
use App\Services\DataMigration\Adapters\ZipPackageSourceAdapter;
use App\Services\DataMigration\FileSignatureInspector;
use App\Services\DataMigration\SourceAdapterRegistry;
use Illuminate\Support\ServiceProvider;

class DataMigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([
            DelimitedTextSourceAdapter::class,
            SpreadsheetSourceAdapter::class,
            JsonSourceAdapter::class,
            XmlSourceAdapter::class,
            SqlDumpSourceAdapter::class,
            ZipPackageSourceAdapter::class,
        ], 'data-migration.source-adapters');

        $this->app->singleton(SourceAdapterRegistry::class, fn ($app) => new SourceAdapterRegistry(
            $app->tagged('data-migration.source-adapters'),
            $app->make(FileSignatureInspector::class),
        ));
    }
}
