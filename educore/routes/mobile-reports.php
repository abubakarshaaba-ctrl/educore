<?php

use App\Http\Controllers\Api\MobileClassController;
use App\Http\Controllers\Api\MobileReportPdfController;
use App\Http\Controllers\Api\MobileReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->group(function (): void {
    Route::get('/', [MobileReportsController::class, 'index']);
    Route::post('compute', [MobileReportsController::class, 'compute']);
    Route::post('publish', [MobileReportsController::class, 'publish']);
    Route::post('unpublish', [MobileReportsController::class, 'unpublish']);
    Route::get('{summary}/pdf', MobileReportPdfController::class)->whereNumber('summary');
});

// Staff-facing published result route used by the native Report Cards flow.
Route::get('classes/{classArm}/students/{student}/results', [MobileClassController::class, 'results'])
    ->whereNumber('classArm')
    ->whereNumber('student');
