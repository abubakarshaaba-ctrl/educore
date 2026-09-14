<?php

use App\Providers\AppServiceProvider;
use App\Providers\AssessmentConfigurationServiceProvider;
use App\Providers\DataMigrationServiceProvider;
use App\Providers\MobileDashboardPresentationServiceProvider;
use App\Providers\MobileSubscriptionApiServiceProvider;

return [
    AppServiceProvider::class,
    AssessmentConfigurationServiceProvider::class,
    MobileSubscriptionApiServiceProvider::class,
    MobileDashboardPresentationServiceProvider::class,
    DataMigrationServiceProvider::class,
];
