<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API (v1) — EduCore Staff App
|--------------------------------------------------------------------------
| Bearer-token auth via AuthenticateApiToken (see ApiToken model).
| All data access is tenant-scoped through the authenticated user.
*/

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware(\App\Http\Middleware\AuthenticateApiToken::class)->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('me',            [TeacherController::class, 'me']);
        Route::get('classes',       [TeacherController::class, 'classes']);
        Route::get('classes/{classArm}/students', [TeacherController::class, 'students']);
        Route::get('announcements', [TeacherController::class, 'announcements']);

        Route::get('classes/{classArm}/attendance',  [AttendanceController::class, 'index']);
        Route::post('classes/{classArm}/attendance', [AttendanceController::class, 'store']);
    });
});
