<?php

use App\Http\Controllers\Api\AdminStaffAttendanceController;
use Illuminate\Support\Facades\Route;

// Additional school-administrator attendance operations. The daily overview,
// monthly report and settings routes remain in routes/api.php.
Route::prefix('admin/staff-attendance')->group(function (): void {
    Route::post('manual', [AdminStaffAttendanceController::class, 'manualOverride']);

    Route::get('offline', [AdminStaffAttendanceController::class, 'offlineQueue']);
    Route::post('offline/{record}', [AdminStaffAttendanceController::class, 'processOffline'])
        ->whereNumber('record');

    Route::get('proxy-reviews', [AdminStaffAttendanceController::class, 'proxyReviews']);
    Route::post('proxy-reviews/{record}', [AdminStaffAttendanceController::class, 'decideProxy'])
        ->whereNumber('record');

    Route::post('reset-qr', [AdminStaffAttendanceController::class, 'resetQr']);
});
