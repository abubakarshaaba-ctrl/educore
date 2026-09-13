<?php

use App\Http\Controllers\Api\PlatformBroadcastController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform/broadcasts')->group(function (): void {
    Route::get('/', [PlatformBroadcastController::class, 'index']);
    Route::post('/', [PlatformBroadcastController::class, 'store']);
    Route::post('{broadcast}/expire', [PlatformBroadcastController::class, 'expire'])->whereNumber('broadcast');
});
