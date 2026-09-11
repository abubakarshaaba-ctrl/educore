<?php

use App\Http\Controllers\Api\AdminStaffAttendanceController;
use App\Http\Controllers\Api\AdminStaffAttendanceLegacyController;
use App\Http\Controllers\Api\AdminStaffAttendanceOfflineController;
use App\Http\Controllers\Api\AdminStaffAttendanceOfflineSyncController;
use App\Http\Controllers\Api\SchoolOpenDaysController;
use App\Http\Controllers\Api\StaffAttendanceApiController;
use App\Http\Controllers\Api\StaffCardAttendanceScanController;
use Illuminate\Support\Facades\Route;

// Self-service offline reconciliation for the authenticated staff member.
// This intentionally sits outside the admin prefix so ordinary staff with
// attendance.self can replay queued clock-in/out events after connectivity returns.
Route::post('staff-attendance/offline/sync', [StaffAttendanceApiController::class, 'syncOffline']);

// Any authenticated tenant staff member may scan a signed staff ID-card QR or use
// the school QR + Staff ID fallback. The server records the current server timestamp.
Route::post('staff-attendance/scan-card', StaffCardAttendanceScanController::class);

Route::prefix('admin/staff-attendance')->group(function (): void {
    Route::get('daily', [AdminStaffAttendanceController::class, 'daily']);
    Route::get('monthly', [AdminStaffAttendanceController::class, 'monthly']);
    Route::get('settings', [AdminStaffAttendanceController::class, 'settings']);
    Route::put('settings', [AdminStaffAttendanceController::class, 'updateSettings']);
    Route::put('school-open-days', [SchoolOpenDaysController::class, 'update']);
    Route::get('offline', AdminStaffAttendanceOfflineController::class);
    Route::post('offline/sync', AdminStaffAttendanceOfflineSyncController::class);
    Route::get('proxy-reviews', [AdminStaffAttendanceController::class, 'proxyReviews']);
    Route::post('proxy-clock', StaffCardAttendanceScanController::class);
    Route::get('qr', [AdminStaffAttendanceController::class, 'qr']);
    Route::post('qr/reset', [AdminStaffAttendanceController::class, 'resetQr']);

    // Compatibility endpoints that are not already registered in routes/api.php.
    // The legacy root and report GET routes remain registered there; duplicating
    // them here would make route resolution dependent on registration order.
    Route::post('manual', [AdminStaffAttendanceLegacyController::class, 'manual']);
    Route::post('offline/{record}', [AdminStaffAttendanceLegacyController::class, 'reviewOffline']);
    Route::post('proxy-reviews/{record}', [AdminStaffAttendanceLegacyController::class, 'reviewProxy']);
    Route::post('reset-qr', [AdminStaffAttendanceController::class, 'resetQr']);
});
