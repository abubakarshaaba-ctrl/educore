<?php

use App\Http\Controllers\Api\MobilePlatformBroadcastController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform/broadcasts')->group(function (): void {
    Route::get('/', [MobilePlatformBroadcastController::class, 'index']);
    Route::post('/', [MobilePlatformBroadcastController::class, 'store']);
    Route::post('{broadcast}/expire', [MobilePlatformBroadcastController::class, 'expire'])->whereNumber('broadcast');
});
