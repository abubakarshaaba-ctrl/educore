<?php

use App\Providers\AppServiceProvider;
use App\Providers\AscServiceProvider;
use App\Providers\AssessmentConfigurationServiceProvider;
use App\Providers\CbtScoreSheetLinkServiceProvider;
use App\Providers\DataMigrationServiceProvider;
use App\Providers\MobileDashboardPresentationServiceProvider;
use App\Providers\MobileSubscriptionApiServiceProvider;

return [
    AppServiceProvider::class,
    AscServiceProvider::class,
    AssessmentConfigurationServiceProvider::class,
    CbtScoreSheetLinkServiceProvider::class,
    MobileSubscriptionApiServiceProvider::class,
    MobileDashboardPresentationServiceProvider::class,
    DataMigrationServiceProvider::class,
];
