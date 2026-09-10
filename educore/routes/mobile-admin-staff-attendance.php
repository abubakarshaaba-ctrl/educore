<?php

use App\Http\Controllers\Api\AdminStaffAttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/staff-attendance')->group(function (): void {
    Route::get('daily', [AdminStaffAttendanceController::class, 'daily']);
    Route::get('monthly', [AdminStaffAttendanceController::class, 'monthly']);
    Route::get('settings', [AdminStaffAttendanceController::class, 'settings']);
    Route::put('settings', [AdminStaffAttendanceController::class, 'updateSettings']);

    Route::get('offline', [AdminStaffAttendanceController::class, 'offline']);
    Route::post('offline/sync', [AdminStaffAttendanceController::class, 'syncOffline']);

    Route::get('proxy-reviews', [AdminStaffAttendanceController::class, 'proxyReviews']);
    Route::post('proxy-clock', [AdminStaffAttendanceController::class, 'proxyClock']);

    Route::get('qr', [AdminStaffAttendanceController::class, 'qr']);
    Route::post('qr/reset', [AdminStaffAttendanceController::class, 'resetQr']);

    // Compatibility endpoints for installed builds while they migrate to the
    // canonical contract above. These are protected by the same controller guard.
    Route::get('/', [AdminStaffAttendanceController::class, 'daily']);
    Route::get('report', [AdminStaffAttendanceController::class, 'monthly']);
    Route::post('manual', [AdminStaffAttendanceController::class, 'manual']);
    Route::post('offline/{record}', [AdminStaffAttendanceController::class, 'reviewOffline']);
    Route::post('proxy-reviews/{record}', [AdminStaffAttendanceController::class, 'reviewProxy']);
    Route::post('reset-qr', [AdminStaffAttendanceController::class, 'resetQr']);
});
