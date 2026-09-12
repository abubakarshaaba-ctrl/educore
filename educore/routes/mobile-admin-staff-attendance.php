<?php

use App\Http\Controllers\Api\AdminOfflineAttendanceController;
use App\Http\Controllers\Api\AdminStaffAttendanceController;
use App\Http\Controllers\Api\AdminStaffAttendanceQrController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/staff-attendance')->group(function (): void {
    Route::post('manual', [AdminStaffAttendanceController::class, 'manualOverride']);
    Route::get('offline', [AdminOfflineAttendanceController::class, 'index']);
    Route::post('offline/{record}', [AdminOfflineAttendanceController::class, 'process'])->whereNumber('record');
    Route::get('proxy-reviews', [AdminStaffAttendanceController::class, 'proxyReviews']);
    Route::post('proxy-reviews/{record}', [AdminStaffAttendanceController::class, 'decideProxy'])->whereNumber('record');
    Route::get('qr', [AdminStaffAttendanceQrController::class, 'show']);
    Route::post('reset-qr', [AdminStaffAttendanceQrController::class, 'reset']);
});
