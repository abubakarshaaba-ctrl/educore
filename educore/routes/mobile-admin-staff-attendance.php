<?php

use App\Http\Controllers\Api\AdminStaffAttendanceController;
use App\Http\Controllers\Api\AdminStaffAttendanceLegacyController;
use App\Http\Controllers\Api\StaffAttendanceApiController;
use Illuminate\Support\Facades\Route;

// Self-service offline reconciliation for the authenticated staff member.
// This intentionally sits outside the admin prefix so ordinary staff with
// attendance.self can replay queued clock-in/out events after connectivity returns.
Route::post('staff-attendance/offline/sync', [StaffAttendanceApiController::class, 'syncOffline']);

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

    // Temporary compatibility endpoints for already-installed builds.
    Route::get('/', [AdminStaffAttendanceController::class, 'daily']);
    Route::get('report', [AdminStaffAttendanceController::class, 'monthly']);
    Route::post('manual', [AdminStaffAttendanceLegacyController::class, 'manual']);
    Route::post('offline/{record}', [AdminStaffAttendanceLegacyController::class, 'reviewOffline']);
    Route::post('proxy-reviews/{record}', [AdminStaffAttendanceLegacyController::class, 'reviewProxy']);
    Route::post('reset-qr', [AdminStaffAttendanceController::class, 'resetQr']);
});
