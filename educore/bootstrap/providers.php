<?php

use App\Providers\AcademicRepositoryKnowledgeServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AssessmentConfigurationServiceProvider;
use App\Providers\DataMigrationServiceProvider;
use App\Providers\MobileAcademicKnowledgeApiServiceProvider;
use App\Providers\MobileSubscriptionApiServiceProvider;

return [
    AppServiceProvider::class,
    AssessmentConfigurationServiceProvider::class,
    AcademicRepositoryKnowledgeServiceProvider::class,
    MobileAcademicKnowledgeApiServiceProvider::class,
    MobileSubscriptionApiServiceProvider::class,
    DataMigrationServiceProvider::class,
];
