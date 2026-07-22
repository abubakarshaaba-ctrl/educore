<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\TransportOfficerController;
use App\Http\Controllers\Api\HealthOfficerController;
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
        Route::get('portal/modules', [\App\Http\Controllers\Api\MobilePortalController::class, 'modules']);
        Route::post('portal/session', [\App\Http\Controllers\Api\MobilePortalController::class, 'createSession']);
        Route::get('classes',       [TeacherController::class, 'classes']);
        Route::get('classes/{classArm}/students', [TeacherController::class, 'students']);
        Route::get('announcements', [TeacherController::class, 'announcements']);

        Route::get('classes/{classArm}/attendance',  [AttendanceController::class, 'index']);
        Route::post('classes/{classArm}/attendance', [AttendanceController::class, 'store']);

        // Staff self-attendance — clock-in/out reuse the proven web JSON
        // endpoints (QR verification + geo-fence live in that controller)
        Route::get('staff-attendance',            [\App\Http\Controllers\Api\StaffAttendanceApiController::class, 'me']);
        Route::post('staff-attendance/clock-in',  [\App\Http\Controllers\Api\StaffAttendanceApiController::class, 'clockIn']);
        Route::post('staff-attendance/clock-out', [\App\Http\Controllers\StaffAttendanceController::class, 'clockOut']);

        // Clock in for a colleague (PIN-verified, single step)
        Route::get('staff-attendance/colleagues',       [\App\Http\Controllers\StaffAttendanceController::class, 'staffSearch']);
        Route::post('staff-attendance/proxy-clock-in',  [\App\Http\Controllers\Api\StaffAttendanceApiController::class, 'proxyClockIn']);

        // Score entry (subject teachers)
        Route::get('scores/teaching', [\App\Http\Controllers\Api\ScoreController::class, 'teaching']);
        Route::get('scores/sheet',    [\App\Http\Controllers\Api\ScoreController::class, 'sheet']);
        Route::post('scores/save',    [\App\Http\Controllers\Api\ScoreController::class, 'save']);

        // Timetables
        Route::get('timetable/mine',       [\App\Http\Controllers\Api\TimetableController::class, 'mine']);
        Route::get('timetable/form-class', [\App\Http\Controllers\Api\TimetableController::class, 'formClass']);

        // Staff self-service: ID card + payslips
        Route::get('id-card',              [\App\Http\Controllers\Api\StaffCardController::class, 'idCard']);
        Route::get('id-card/photo-file',   [\App\Http\Controllers\Api\StaffCardController::class, 'photoFile']);
        Route::post('id-card/photo',       [\App\Http\Controllers\Api\StaffCardController::class, 'uploadPhoto']);
        Route::get('payslips',             [\App\Http\Controllers\Api\StaffCardController::class, 'payslips']);
        Route::get('payslips/{item}',      [\App\Http\Controllers\Api\StaffCardController::class, 'payslip']);
        Route::get('payslips/{item}/pdf',  [\App\Http\Controllers\Api\StaffCardController::class, 'payslipPdf']);

        // Exam supervision duties (personal, published only)
        Route::get('exam-duties', [\App\Http\Controllers\Api\ExamDutyController::class, 'index']);

        // Push notification device registration (FCM)
        Route::post('push/register',   [\App\Http\Controllers\Api\PushController::class, 'registerToken']);
        Route::post('push/unregister', [\App\Http\Controllers\Api\PushController::class, 'unregisterToken']);

        // Messages (student-linked / internal threads this staff member is party to)
        Route::get('messages',                [\App\Http\Controllers\Api\MessageController::class, 'index']);
        Route::get('messages/{thread}',       [\App\Http\Controllers\Api\MessageController::class, 'show']);
        Route::post('messages/{thread}/reply', [\App\Http\Controllers\Api\MessageController::class, 'reply']);

        // Student self-service. Every query resolves the student from the
        // authenticated user; no client-supplied student id is accepted.
        Route::prefix('student')->group(function () {
            Route::get('dashboard', [StudentController::class, 'dashboard']);
            Route::get('timetable', [StudentController::class, 'timetable']);
            Route::get('results', [StudentController::class, 'results']);
            Route::get('exams', [StudentController::class, 'exams']);
        });

        Route::prefix('parent')->group(function () {
            Route::get('dashboard', [ParentController::class, 'dashboard']);
            Route::get('invoices', [ParentController::class, 'invoices']);
            Route::get('results', [ParentController::class, 'results']);
            Route::get('attendance', [ParentController::class, 'attendance']);
        });

        Route::prefix('admin')->group(function () {
            Route::get('dashboard', [AdminController::class, 'dashboard']);
            Route::get('students', [AdminController::class, 'students']);
            Route::get('staff', [AdminController::class, 'staff']);
            Route::get('academics', [AdminController::class, 'academics']);
            Route::get('finance', [AdminController::class, 'finance']);
            Route::patch('students/{student}', [AdminController::class, 'updateStudent']);
            Route::patch('staff/{member}', [AdminController::class, 'updateStaff']);
        });

        Route::prefix('platform')->group(function () {
            Route::get('dashboard', [PlatformController::class, 'dashboard']);
            Route::get('tenants', [PlatformController::class, 'tenants']);
            Route::get('billing', [PlatformController::class, 'billing']);
            Route::get('plans', [PlatformController::class, 'plans']);
            Route::patch('tenants/{tenant}', [PlatformController::class, 'updateTenant']);
        });

        Route::prefix('admissions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\AdmissionOfficerController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\AdmissionOfficerController::class, 'store']);
            Route::patch('{admission}/status', [\App\Http\Controllers\Api\AdmissionOfficerController::class, 'updateStatus']);
        });

        Route::prefix('transport-officer')->group(function () {
            Route::get('dashboard', [TransportOfficerController::class, 'dashboard']);
            Route::get('routes/{route}/manifest', [TransportOfficerController::class, 'manifest']);
            Route::post('assignments', [TransportOfficerController::class, 'assign']);
        });

        Route::prefix('health-officer')->group(function () {
            Route::get('dashboard', [HealthOfficerController::class, 'dashboard']);
            Route::get('students/{student}', [HealthOfficerController::class, 'show']);
            Route::post('students/{student}', [HealthOfficerController::class, 'upsert']);
        });
    });
});

// LAN CBT sync-back — receives finished exam sessions from an offline LAN
// instance once it regains internet. Authenticated by an opaque per-exam
// token embedded in the export package (see CbtLanController), not by a
// logged-in session, since the caller is a separate app installation.
Route::post('lan/sync', [\App\Http\Controllers\CbtLanController::class, 'apiSync'])
    ->middleware('throttle:30,1');
