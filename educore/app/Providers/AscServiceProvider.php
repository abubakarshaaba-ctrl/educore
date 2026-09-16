<?php

namespace App\Providers;

use App\Services\Asc\AscDataSyncService;
use App\Services\Asc\EnhancedAscDataSyncService;
use Illuminate\Support\ServiceProvider;

class AscServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AscDataSyncService::class, EnhancedAscDataSyncService::class);
    }
}
