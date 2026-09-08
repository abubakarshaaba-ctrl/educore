<?php

use App\Http\Controllers\Api\MobilePlatformAgentController;
use App\Http\Controllers\Api\MobilePlatformExtendedController;
use App\Http\Controllers\Api\MobilePlatformGroupController;
use App\Http\Controllers\Api\MobilePlatformProvisioningController;
use App\Http\Controllers\Api\MobilePlatformSettingsController;
use App\Http\Controllers\Api\MobilePlatformTenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->group(function (): void {
    Route::get('analytics', [MobilePlatformExtendedController::class, 'analytics']);
    Route::get('groups', [MobilePlatformExtendedController::class, 'groups']);
    Route::post('groups', [MobilePlatformGroupController::class, 'store']);
    Route::get('groups/{group}', [MobilePlatformGroupController::class, 'show'])->whereNumber('group');
    Route::post('groups/{group}/members', [MobilePlatformGroupController::class, 'addMember'])->whereNumber('group');
    Route::delete('groups/{group}/members/{tenant}', [MobilePlatformGroupController::class, 'removeMember'])->whereNumber('group')->whereNumber('tenant');
    Route::post('groups/{group}/members/{tenant}/lead', [MobilePlatformGroupController::class, 'setLead'])->whereNumber('group')->whereNumber('tenant');

    Route::post('agents', [MobilePlatformAgentController::class, 'store']);
    Route::patch('agents/{agent}', [MobilePlatformAgentController::class, 'update'])->whereNumber('agent');

    Route::get('support', [MobilePlatformExtendedController::class, 'support']);
    Route::post('support/{ticket}/reply', [MobilePlatformExtendedController::class, 'replySupport'])->whereNumber('ticket');
    Route::post('support/{ticket}/close', [MobilePlatformExtendedController::class, 'closeSupport'])->whereNumber('ticket');
    Route::get('broadcasts', [MobilePlatformExtendedController::class, 'broadcasts']);
    Route::post('broadcasts', [MobilePlatformExtendedController::class, 'createBroadcast']);
    Route::post('broadcasts/{broadcast}/expire', [MobilePlatformExtendedController::class, 'expireBroadcast'])->whereNumber('broadcast');
    Route::get('settings', [MobilePlatformExtendedController::class, 'settings']);
    Route::put('settings', [MobilePlatformSettingsController::class, 'updateSettings']);
    Route::get('gateways', [MobilePlatformExtendedController::class, 'gateways']);
    Route::put('gateways/{provider}', [MobilePlatformSettingsController::class, 'updateGateway'])
        ->where('provider', 'paystack|monnify|flutterwave');

    Route::post('tenants', [MobilePlatformProvisioningController::class, 'store']);
    Route::get('tenants/{tenant}', [MobilePlatformTenantController::class, 'show'])->whereNumber('tenant');
    Route::post('tenants/{tenant}/extend', [MobilePlatformTenantController::class, 'extend'])->whereNumber('tenant');
});
