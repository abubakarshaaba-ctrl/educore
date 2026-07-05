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

        // Staff self-attendance — clock-in/out reuse the proven web JSON
        // endpoints (QR verification + geo-fence live in that controller)
        Route::get('staff-attendance',            [\App\Http\Controllers\Api\StaffAttendanceApiController::class, 'me']);
        Route::post('staff-attendance/clock-in',  [\App\Http\Controllers\StaffAttendanceController::class, 'clockInQr']);
        Route::post('staff-attendance/clock-out', [\App\Http\Controllers\StaffAttendanceController::class, 'clockOut']);

        // Score entry (subject teachers)
        Route::get('scores/teaching', [\App\Http\Controllers\Api\ScoreController::class, 'teaching']);
        Route::get('scores/sheet',    [\App\Http\Controllers\Api\ScoreController::class, 'sheet']);
        Route::post('scores/save',    [\App\Http\Controllers\Api\ScoreController::class, 'save']);

        // Timetables
        Route::get('timetable/mine',       [\App\Http\Controllers\Api\TimetableController::class, 'mine']);
        Route::get('timetable/form-class', [\App\Http\Controllers\Api\TimetableController::class, 'formClass']);

        // Staff self-service: ID card + payslips
        Route::get('id-card',              [\App\Http\Controllers\Api\StaffCardController::class, 'idCard']);
        Route::post('id-card/photo',       [\App\Http\Controllers\Api\StaffCardController::class, 'uploadPhoto']);
        Route::get('payslips',             [\App\Http\Controllers\Api\StaffCardController::class, 'payslips']);
        Route::get('payslips/{item}',      [\App\Http\Controllers\Api\StaffCardController::class, 'payslip']);
        Route::get('payslips/{item}/pdf',  [\App\Http\Controllers\Api\StaffCardController::class, 'payslipPdf']);
    });
});
