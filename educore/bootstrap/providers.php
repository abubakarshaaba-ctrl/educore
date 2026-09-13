<?php

use App\Providers\AppServiceProvider;
use App\Providers\AssessmentConfigurationServiceProvider;
use App\Providers\DataMigrationServiceProvider;

return [
    AppServiceProvider::class,
    AssessmentConfigurationServiceProvider::class,
    DataMigrationServiceProvider::class,
];
