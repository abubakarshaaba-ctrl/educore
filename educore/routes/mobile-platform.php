<?php

use App\Http\Controllers\Api\MobilePlatformExtendedController;
use App\Http\Controllers\Api\MobilePlatformTenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->group(function (): void {
    Route::get('analytics', [MobilePlatformExtendedController::class, 'analytics']);
    Route::get('groups', [MobilePlatformExtendedController::class, 'groups']);
    Route::get('support', [MobilePlatformExtendedController::class, 'support']);
    Route::post('support/{ticket}/reply', [MobilePlatformExtendedController::class, 'replySupport'])->whereNumber('ticket');
    Route::post('support/{ticket}/close', [MobilePlatformExtendedController::class, 'closeSupport'])->whereNumber('ticket');
    Route::get('broadcasts', [MobilePlatformExtendedController::class, 'broadcasts']);
    Route::post('broadcasts', [MobilePlatformExtendedController::class, 'createBroadcast']);
    Route::post('broadcasts/{broadcast}/expire', [MobilePlatformExtendedController::class, 'expireBroadcast'])->whereNumber('broadcast');
    Route::get('settings', [MobilePlatformExtendedController::class, 'settings']);
    Route::get('gateways', [MobilePlatformExtendedController::class, 'gateways']);

    Route::get('tenants/{tenant}', [MobilePlatformTenantController::class, 'show'])->whereNumber('tenant');
    Route::post('tenants/{tenant}/extend', [MobilePlatformTenantController::class, 'extend'])->whereNumber('tenant');
});
