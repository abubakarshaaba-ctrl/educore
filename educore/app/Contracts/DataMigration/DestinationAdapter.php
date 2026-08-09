<?php

namespace App\Contracts\DataMigration;

use App\Models\MigrationExportPackage;

interface DestinationAdapter
{
    public function identifier(): string;

    public function validate(MigrationExportPackage $package): array;

    public function deliver(MigrationExportPackage $package): array;
}
