<?php

use App\Http\Controllers\Api\MobileReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->group(function (): void {
    Route::get('/', [MobileReportsController::class, 'index']);
    Route::post('compute', [MobileReportsController::class, 'compute']);
    Route::post('publish', [MobileReportsController::class, 'publish']);
    Route::post('unpublish', [MobileReportsController::class, 'unpublish']);
});
