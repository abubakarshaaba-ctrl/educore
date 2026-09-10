<?php

use App\Http\Controllers\Api\AdminStaffAttendanceQrController;
use Illuminate\Support\Facades\Route;

Route::get('admin/staff-attendance/qr', [AdminStaffAttendanceQrController::class, 'show']);
Route::post('admin/staff-attendance/reset-qr', [AdminStaffAttendanceQrController::class, 'reset']);
