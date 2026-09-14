<?php

namespace App\Providers;

use App\Services\Mobile\LatestMobileDashboardService;
use App\Services\Mobile\MobileDashboardService;
use Illuminate\Support\ServiceProvider;

class MobileDashboardPresentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MobileDashboardService::class, LatestMobileDashboardService::class);
    }
}
