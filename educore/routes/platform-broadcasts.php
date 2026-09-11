<?php

use App\Http\Controllers\SuperAdminPlatformBroadcastController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'super.admin'])
    ->prefix('super')
    ->group(function (): void {
        Route::post('platform-broadcasts', [SuperAdminPlatformBroadcastController::class, 'store'])
            ->name('super.platform-broadcasts.store');
    });
