<?php

use App\Http\Controllers\Api\MobileReleaseController;
use Illuminate\Support\Facades\Route;

Route::post('mobile-release/notify', MobileReleaseController::class)
    ->middleware('throttle:6,1')
    ->name('api.mobile-release.notify');
