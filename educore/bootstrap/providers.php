<?php

use App\Providers\AppServiceProvider;
use App\Providers\DataMigrationServiceProvider;
use App\Providers\MobileOperationsApiServiceProvider;

return [
    AppServiceProvider::class,
    MobileOperationsApiServiceProvider::class,
    DataMigrationServiceProvider::class,
];
