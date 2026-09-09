<?php

use App\Http\Controllers\Api\StaffCbtApiController;
use App\Http\Controllers\Api\StaffCbtCreateApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('staff/cbt')->group(function (): void {
    Route::get('options', [StaffCbtCreateApiController::class, 'options']);
    Route::post('exams', [StaffCbtCreateApiController::class, 'store']);
    Route::get('exams', [StaffCbtApiController::class, 'index']);
    Route::get('exams/{exam}', [StaffCbtApiController::class, 'show'])->whereNumber('exam');
    Route::post('exams/{exam}/publish', [StaffCbtApiController::class, 'publish'])->whereNumber('exam');
    Route::post('exams/{exam}/close', [StaffCbtApiController::class, 'close'])->whereNumber('exam');
    Route::patch('exams/{exam}/schedule', [StaffCbtApiController::class, 'reschedule'])->whereNumber('exam');
});
