<?php

use App\Http\Controllers\Api\AcademicRepositoryController as ApiAcademicRepositoryController;
use App\Http\Controllers\Api\AccountantController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminStaffAttendanceController;
use App\Http\Controllers\Api\AdmissionOfficerController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExamDutyController;
use App\Http\Controllers\Api\HealthOfficerController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\MobileBootstrapController;
use App\Http\Controllers\Api\MobileCbtController;
use App\Http\Controllers\Api\MobileClassController;
use App\Http\Controllers\Api\MobileCommunicationController;
use App\Http\Controllers\Api\MobileDashboardController;
use App\Http\Controllers\Api\MobileLessonPlannerController;
use App\Http\Controllers\Api\MobileOperationsController;
use App\Http\Controllers\Api\MobilePortalController;
use App\Http\Controllers\Api\MobileRiskController;
use App\Http\Controllers\Api\MobileScheduleController;
use App\Http\Controllers\Api\MobileStaffDirectoryController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\PushController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\StaffAttendanceApiController;
use App\Http\Controllers\Api\StaffCardController;
use App\Http\Controllers\Api\StaffCbtApiController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TimetableController;
use App\Http\Controllers\Api\TransportOfficerController;
use App\Http\Controllers\CbtLanController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Middleware\AuthenticateApiToken;
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
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');

    Route::middleware(AuthenticateApiToken::class)->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('bootstrap', MobileBootstrapController::class);
        Route::get('dashboard', MobileDashboardController::class);
        Route::get('schedule', MobileScheduleController::class);
        Route::get('operations/{module}', [MobileOperationsController::class, 'show'])
            ->where('module', '[A-Za-z0-9.-]+');

        Route::prefix('risk')->group(function () {
            Route::get('/', [MobileRiskController::class, 'index']);
            Route::post('compute', [MobileRiskController::class, 'compute']);
            Route::put('config', [MobileRiskController::class, 'updateConfig']);
            Route::get('{flag}', [MobileRiskController::class, 'show'])->whereNumber('flag');
            Route::post('{flag}/acknowledge', [MobileRiskController::class, 'acknowledge'])->whereNumber('flag');
            Route::post('{flag}/resolve', [MobileRiskController::class, 'resolve'])->whereNumber('flag');
        });

        Route::prefix('academic-repository')->group(function () {
            Route::get('classes', [ApiAcademicRepositoryController::class, 'classes']);
            Route::get('resources', [ApiAcademicRepositoryController::class, 'resources']);
            Route::get('resources/{source}', [ApiAcademicRepositoryController::class, 'show']);
            Route::get('resources/{source}/content', [ApiAcademicRepositoryController::class, 'content']);
            Route::get('resources/{source}/download', [ApiAcademicRepositoryController::class, 'download']);
        });
        Route::prefix('lesson-plans')->group(function () {
            Route::get('options', [MobileLessonPlannerController::class, 'options']);
            Route::get('/', [MobileLessonPlannerController::class, 'index']);
            Route::post('/', [MobileLessonPlannerController::class, 'store']);
            Route::get('{lessonPlan}', [MobileLessonPlannerController::class, 'show']);
            Route::patch('{lessonPlan}', [MobileLessonPlannerController::class, 'update']);
            Route::post('{lessonPlan}/generate', [MobileLessonPlannerController::class, 'generate']);
            Route::post('{lessonPlan}/generate-note', [MobileLessonPlannerController::class, 'generateNote']);
            Route::patch('{lessonPlan}/note', [MobileLessonPlannerController::class, 'updateNote']);
            Route::post('{lessonPlan}/publish', [MobileLessonPlannerController::class, 'publish']);
            Route::get('{lessonPlan}/pdf', [MobileLessonPlannerController::class, 'pdf']);
            Route::get('{lessonPlan}/note/pdf', [MobileLessonPlannerController::class, 'notePdf']);
        });
        Route::prefix('cbt')->group(function () {
            Route::get('exams', [MobileCbtController::class, 'index']);
            Route::get('exams/{exam}/preflight', [MobileCbtController::class, 'preflight']);
            Route::post('exams/{exam}/begin', [MobileCbtController::class, 'begin']);
            Route::get('sessions/{session}', [MobileCbtController::class, 'show']);
            Route::put('sessions/{session}/answers', [MobileCbtController::class, 'save']);
            Route::post('sessions/{session}/integrity', [MobileCbtController::class, 'integrity']);
            Route::post('sessions/{session}/submit', [MobileCbtController::class, 'submit']);
            Route::get('sessions/{session}/questions/{question}/image', [MobileCbtController::class, 'image']);
        });

        // Staff CBT management is intentionally separate from the student
        // attempt contract above. Every action is server-authorized and
        // teacher accounts remain scoped to subjects/classes they teach.
        Route::prefix('staff/cbt')->group(function () {
            Route::get('exams', [StaffCbtApiController::class, 'index']);
            Route::get('exams/{exam}', [StaffCbtApiController::class, 'show']);
            Route::post('exams/{exam}/publish', [StaffCbtApiController::class, 'publish']);
            Route::post('exams/{exam}/close', [StaffCbtApiController::class, 'close']);
            Route::patch('exams/{exam}/schedule', [StaffCbtApiController::class, 'reschedule']);
        });

        Route::get('me', [TeacherController::class, 'me']);
        Route::get('portal/modules', [MobilePortalController::class, 'modules']);
        Route::post('portal/session', [MobilePortalController::class, 'createSession']);
        Route::get('classes', [MobileClassController::class, 'index']);
        Route::get('classes/{classArm}', [MobileClassController::class, 'show']);
        Route::get('classes/{classArm}/students', [MobileClassController::class, 'students']);
        Route::get('classes/{classArm}/students/{student}', [MobileClassController::class, 'student']);
        Route::get('classes/{classArm}/students/{student}/results', [MobileClassController::class, 'results']);
        Route::get('announcements', [TeacherController::class, 'announcements']);
        Route::get('notifications', [MobileCommunicationController::class, 'notifications']);
        Route::post('notifications/read-all', [MobileCommunicationController::class, 'readAll']);
        Route::post('notifications/{announcement}/read', [MobileCommunicationController::class, 'read']);
        Route::get('calendar/events', [MobileCommunicationController::class, 'events']);

        Route::get('classes/{classArm}/attendance', [AttendanceController::class, 'index']);
        Route::post('classes/{classArm}/attendance', [AttendanceController::class, 'store']);

        // Staff self-attendance — clock-in/out reuse the proven web JSON
        // endpoints (QR verification + geo-fence live in that controller)
        Route::get('staff-attendance', [StaffAttendanceApiController::class, 'me']);
        Route::post('staff-attendance/clock-in', [StaffAttendanceApiController::class, 'clockIn']);
        Route::post('staff-attendance/clock-out', [StaffAttendanceController::class, 'clockOut']);

        // Clock in for a colleague (PIN-verified, single step)
        Route::get('staff-attendance/colleagues', [StaffAttendanceController::class, 'staffSearch']);
        Route::post('staff-attendance/proxy-clock-in', [StaffAttendanceApiController::class, 'proxyClockIn']);

        // Score entry (subject teachers)
        Route::get('scores/teaching', [ScoreController::class, 'teaching']);
        Route::get('scores/sheet', [ScoreController::class, 'sheet']);
        Route::post('scores/save', [ScoreController::class, 'save']);

        // Timetables
        Route::get('timetable/mine', [TimetableController::class, 'mine']);
        Route::get('timetable/form-class', [TimetableController::class, 'formClass']);

        // Staff self-service: ID card + payslips
        Route::get('id-card', [StaffCardController::class, 'idCard']);
        Route::get('id-card/photo-file', [StaffCardController::class, 'photoFile']);
        Route::get('id-card/signature-file', [StaffCardController::class, 'signatureFile']);
        Route::post('id-card/photo', [StaffCardController::class, 'uploadPhoto']);
        Route::get('payslips', [StaffCardController::class, 'payslips']);
        Route::get('payslips/{item}', [StaffCardController::class, 'payslip']);
        Route::get('payslips/{item}/pdf', [StaffCardController::class, 'payslipPdf']);

        // Exam supervision duties (personal, published only)
        Route::get('exam-duties', [ExamDutyController::class, 'index']);

        // Push notification device registration (FCM)
        Route::post('push/register', [PushController::class, 'registerToken']);
        Route::post('push/unregister', [PushController::class, 'unregisterToken']);
        // Compatibility for app builds released before the canonical push
        // endpoint was aligned. Keep these while older installations update.
        Route::post('devices/register', [PushController::class, 'registerToken']);
        Route::post('devices/unregister', [PushController::class, 'unregisterToken']);

        // Messages (student-linked / internal threads this staff member is party to)
        Route::get('messages', [MessageController::class, 'index']);
        Route::get('messages/recipients', [MessageController::class, 'recipients']);
        Route::post('messages', [MessageController::class, 'store']);
        Route::get('messages/replies/{reply}/attachment', [MessageController::class, 'attachment']);
        Route::get('messages/{thread}', [MessageController::class, 'show']);
        Route::post('messages/{thread}/reply', [MessageController::class, 'reply']);

        // Student self-service. Every query resolves the student from the
        // authenticated user; no client-supplied student id is accepted.
        Route::prefix('student')->group(function () {
            Route::get('dashboard', [StudentController::class, 'dashboard']);
            Route::get('timetable', [StudentController::class, 'timetable']);
            Route::get('results', [StudentController::class, 'results']);
            Route::get('attendance', [StudentController::class, 'attendance']);
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
            Route::get('staff', MobileStaffDirectoryController::class);
            Route::get('academics', [AdminController::class, 'academics']);
            Route::get('finance', [AdminController::class, 'finance']);
            Route::patch('students/{student}', [AdminController::class, 'updateStudent']);
            Route::patch('staff/{member}', [AdminController::class, 'updateStaff']);
            Route::get('management', [AdminController::class, 'management']);
            Route::post('students', [AdminController::class, 'storeStudent']);
            Route::post('staff', [AdminController::class, 'storeStaff']);
            Route::post('classes', [AdminController::class, 'storeClass']);
            Route::patch('classes/{classArm}', [AdminController::class, 'updateClass']);
            Route::post('subjects', [AdminController::class, 'storeSubject']);
            Route::patch('subjects/{subject}', [AdminController::class, 'updateSubject']);
            Route::get('staff-attendance', [AdminStaffAttendanceController::class, 'index']);
            Route::get('staff-attendance/report', [AdminStaffAttendanceController::class, 'report']);
            Route::put('staff-attendance/settings', [AdminStaffAttendanceController::class, 'updateSettings']);
        });

        Route::prefix('accountant')->group(function () {
            Route::get('dashboard', [AccountantController::class, 'dashboard']);
            Route::get('payroll', [AccountantController::class, 'payroll']);
            Route::get('preparation-options', [AccountantController::class, 'preparationOptions']);
            Route::post('fees/generate', [AccountantController::class, 'generateFees']);
            Route::post('payroll/generate', [AccountantController::class, 'generatePayroll']);
        });

        Route::middleware('super.admin')->prefix('platform')->group(function () {
            Route::get('dashboard', [PlatformController::class, 'dashboard']);
            Route::get('tenants', [PlatformController::class, 'tenants']);
            Route::get('billing', [PlatformController::class, 'billing']);
            Route::get('plans', [PlatformController::class, 'plans']);
            Route::patch('tenants/{tenant}', [PlatformController::class, 'updateTenant']);
            Route::delete('tenants/{tenant}', [PlatformController::class, 'destroyTenant']);
            Route::post('tenants', [PlatformController::class, 'storeTenant']);
            Route::get('agents', [PlatformController::class, 'agents']);
            Route::post('agents', [PlatformController::class, 'storeAgent']);
            Route::patch('agents/{agent}', [PlatformController::class, 'updateAgent']);
        });

        Route::prefix('admissions')->group(function () {
            Route::get('/', [AdmissionOfficerController::class, 'index']);
            Route::post('/', [AdmissionOfficerController::class, 'store']);
            Route::patch('{admission}/status', [AdmissionOfficerController::class, 'updateStatus']);
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
Route::post('lan/sync', [CbtLanController::class, 'apiSync'])
    ->middleware('throttle:30,1');
