<?php

use App\Http\Controllers\Api\MobilePlatformExtendedController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->group(function (): void {
    Route::get('analytics', [MobilePlatformExtendedController::class, 'analytics']);
    Route::get('groups', [MobilePlatformExtendedController::class, 'groups']);
    Route::get('support', [MobilePlatformExtendedController::class, 'support']);
    Route::get('broadcasts', [MobilePlatformExtendedController::class, 'broadcasts']);
    Route::get('settings', [MobilePlatformExtendedController::class, 'settings']);
    Route::get('gateways', [MobilePlatformExtendedController::class, 'gateways']);
});
