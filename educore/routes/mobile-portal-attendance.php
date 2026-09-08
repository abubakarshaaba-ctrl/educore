<?php

use App\Http\Controllers\Api\MobilePortalAttendanceController;
use Illuminate\Support\Facades\Route;

Route::get('student/attendance', [MobilePortalAttendanceController::class, 'student']);
Route::get('parent/attendance', [MobilePortalAttendanceController::class, 'parent']);
