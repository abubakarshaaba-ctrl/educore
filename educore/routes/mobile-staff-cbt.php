<?php

use App\Http\Controllers\Api\StaffCbtApiController;
use App\Http\Controllers\Api\StaffCbtCreateApiController;
use Illuminate\Support\Facades\Route;

// Loaded by bootstrap/app.php under /api/v1/staff/cbt with the same bearer
// token middleware used by the primary mobile API route file.
Route::get('options', [StaffCbtCreateApiController::class, 'options']);
Route::post('exams', [StaffCbtCreateApiController::class, 'store']);
Route::get('exams', [StaffCbtApiController::class, 'index']);
Route::get('exams/{exam}', [StaffCbtApiController::class, 'show'])->whereNumber('exam');
Route::post('exams/{exam}/publish', [StaffCbtApiController::class, 'publish'])->whereNumber('exam');
Route::post('exams/{exam}/close', [StaffCbtApiController::class, 'close'])->whereNumber('exam');
Route::patch('exams/{exam}/schedule', [StaffCbtApiController::class, 'reschedule'])->whereNumber('exam');
