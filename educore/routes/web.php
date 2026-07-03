<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CbtController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\PublicMarketingController;
use App\Http\Controllers\SkillRatingController;
use App\Http\Controllers\StaffArchiveController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffLifecycleController;
use App\Http\Controllers\StaffWorkHistoryController;
use App\Http\Controllers\StudentClassTransferController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentArchiveController;
use App\Http\Controllers\StudentLifecycleController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TenantAccountStatusController;
use App\Http\Controllers\TenantHostController;
use App\Http\Controllers\TenantOnboardingController;
use Illuminate\Support\Facades\Route;

Route::domain('{tenantSubdomain}.' . config('tenancy.local_base_domain', 'educore.test'))
    ->where(['tenantSubdomain' => '[A-Za-z0-9][A-Za-z0-9-]*'])
    ->name('tenant.host.')
    ->middleware('tenant.host')
    ->group(function () {
        Route::get('/', [TenantHostController::class, 'landing'])->name('landing');
        Route::get('login', [TenantHostController::class, 'showLogin'])->name('login');
        Route::post('login', [TenantHostController::class, 'login'])
            ->middleware('throttle:tenant-login')
            ->name('login.submit');
        Route::get('forgot-password', [TenantHostController::class, 'showForgot'])->name('password.request');
        Route::post('forgot-password', [TenantHostController::class, 'sendResetLink'])
            ->middleware('throttle:tenant-password')
            ->name('password.email');
        Route::get('reset-password/{token}', [TenantHostController::class, 'showReset'])->name('password.reset');
        Route::post('reset-password', [TenantHostController::class, 'reset'])
            ->middleware('throttle:tenant-password')
            ->name('password.update');

        Route::get('apply', [TenantHostController::class, 'applyLanding'])->name('apply');
        Route::get('apply/form', [TenantHostController::class, 'applyForm'])->name('apply.form');
        Route::post('apply/submit', [TenantHostController::class, 'applySubmit'])->name('apply.submit');
        Route::get('apply/status', [TenantHostController::class, 'applyStatusForm'])->name('apply.status.form');
        Route::post('apply/status', [TenantHostController::class, 'applyStatus'])->name('apply.status');
        Route::get('apply/success/{app}', [TenantHostController::class, 'applySuccess'])->name('apply.success');

        Route::get('account-status', [TenantAccountStatusController::class, 'show'])
            ->middleware(['auth', 'active.account'])
            ->name('account-status');
    });

Route::domain('{customSubdomain}.{customDomain}.{customTld}')
    ->where([
        'customSubdomain' => '[A-Za-z0-9][A-Za-z0-9-]*',
        'customDomain' => '[A-Za-z0-9][A-Za-z0-9-]*',
        'customTld' => '[A-Za-z][A-Za-z0-9-]*',
    ])
    ->name('tenant.host.custom.')
    ->middleware('tenant.host')
    ->group(function () {
        Route::get('/', [TenantHostController::class, 'landing'])->name('landing');
        Route::get('login', [TenantHostController::class, 'showLogin'])->name('login');
        Route::post('login', [TenantHostController::class, 'login'])
            ->middleware('throttle:tenant-login')
            ->name('login.submit');
        Route::get('forgot-password', [TenantHostController::class, 'showForgot'])->name('password.request');
        Route::post('forgot-password', [TenantHostController::class, 'sendResetLink'])
            ->middleware('throttle:tenant-password')
            ->name('password.email');
        Route::get('reset-password/{token}', [TenantHostController::class, 'showReset'])->name('password.reset');
        Route::post('reset-password', [TenantHostController::class, 'reset'])
            ->middleware('throttle:tenant-password')
            ->name('password.update');

        Route::get('apply', [TenantHostController::class, 'applyLanding'])->name('apply');
        Route::get('apply/form', [TenantHostController::class, 'applyForm'])->name('apply.form');
        Route::post('apply/submit', [TenantHostController::class, 'applySubmit'])->name('apply.submit');
        Route::get('apply/status', [TenantHostController::class, 'applyStatusForm'])->name('apply.status.form');
        Route::post('apply/status', [TenantHostController::class, 'applyStatus'])->name('apply.status');
        Route::get('apply/success/{app}', [TenantHostController::class, 'applySuccess'])->name('apply.success');

        Route::get('account-status', [TenantAccountStatusController::class, 'show'])
            ->middleware(['auth', 'active.account'])
            ->name('account-status');
    });

// â”€â”€ Authentication â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::get('/', [PublicMarketingController::class, 'index'])->name('home');
Route::post('/contact', [PublicMarketingController::class, 'sendContact'])->middleware('throttle:public-form')->name('contact.submit');
Route::post('/school-onboarding', [PublicMarketingController::class, 'sendSchoolOnboarding'])->middleware('throttle:public-form')->name('school-onboarding.submit');
Route::get('/get-started',  [\App\Http\Controllers\SchoolRegistrationController::class, 'show'])->name('school.register');
Route::post('/get-started', [\App\Http\Controllers\SchoolRegistrationController::class, 'store'])->middleware('throttle:6,1')->name('school.register.post');

Route::middleware(\App\Http\Middleware\PortalGuard::class)->group(function () {
    // Platform gateway â€” super administration only.
    Route::get(config('portal.super_admin_login_path', 'platform/login'),  [LoginController::class, 'showLogin'])->name('login');
    Route::post(config('portal.super_admin_login_path', 'platform/login'), [LoginController::class, 'login'])->middleware('throttle:global-login');
    // School Administration login
    Route::get('/admin/login',    [\App\Http\Controllers\Auth\RoleLoginController::class, 'show'])
        ->defaults('surface', 'admin')->name('admin.login');
    Route::post('/admin/login',   [\App\Http\Controllers\Auth\RoleLoginController::class, 'login'])
        ->defaults('surface', 'admin')->middleware('throttle:tenant-login');

    // School Staff login
    Route::get('/staff/login',    [\App\Http\Controllers\Auth\RoleLoginController::class, 'show'])
        ->defaults('surface', 'staff')->name('staff.login');
    Route::post('/staff/login',   [\App\Http\Controllers\Auth\RoleLoginController::class, 'login'])
        ->defaults('surface', 'staff')->middleware('throttle:tenant-login');

    // Student login
    Route::get('/student/login',  [\App\Http\Controllers\Auth\RoleLoginController::class, 'show'])
        ->defaults('surface', 'student')->name('student.login');
    Route::post('/student/login', [\App\Http\Controllers\Auth\RoleLoginController::class, 'login'])
        ->defaults('surface', 'student')->middleware('throttle:tenant-login');

    // Parent login
    Route::get('/parent/login',  [\App\Http\Controllers\Auth\RoleLoginController::class, 'show'])
        ->defaults('surface', 'parent')->name('parent.login');
    Route::post('/parent/login', [\App\Http\Controllers\Auth\RoleLoginController::class, 'login'])
        ->defaults('surface', 'parent')->middleware('throttle:tenant-login');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Named stubs so internal route() helpers still resolve â€” they build subdomain URLs directly.
// The old /school/{slug} path is gone; these generate the correct subdomain URL.
Route::name('tenant.')->group(function () {
    $base = fn (string $slug, string $path = '') =>
        config('tenancy.scheme') . '://' . $slug . '.' . config('tenancy.base_domain') . ($path ? '/' . ltrim($path, '/') : '');

    Route::get('_tenant_stub/{slug}',                  fn (string $slug) => redirect()->away($base($slug)))->name('portal.landing');
    Route::get('_tenant_stub/{slug}/login',             fn (string $slug) => redirect()->away($base($slug, 'login')))->name('login');
    Route::post('_tenant_stub/{slug}/login',            fn (string $slug) => redirect()->away($base($slug, 'login')))->name('login.submit');
    Route::get('_tenant_stub/{slug}/forgot-password',   fn (string $slug) => redirect()->away($base($slug, 'forgot-password')))->name('password.request');
    Route::post('_tenant_stub/{slug}/forgot-password',  fn (string $slug) => redirect()->away($base($slug, 'forgot-password')))->name('password.email');
    Route::get('_tenant_stub/{slug}/reset-password/{token}', fn (string $slug, string $token) => redirect()->away($base($slug, 'reset-password/' . $token)))->name('password.reset');
    Route::post('_tenant_stub/{slug}/reset-password',   fn (string $slug) => redirect()->away($base($slug, 'reset-password')))->name('password.update');
});

// â”€â”€ Authenticated Tenant Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::middleware(['auth', 'active.account', 'tenant'])->group(function () {
    Route::get('account-status', [TenantAccountStatusController::class, 'show'])->name('tenant.account-status');
});

// â”€â”€ Onboarding wizard removed â€” each step now lives in its own module â”€â”€â”€â”€â”€â”€
// These routes are kept for backward compatibility (e.g. old bookmarks, the
// middleware that redirects incomplete tenants). Each one redirects to the
// correct existing page. POST save routes now complete onboarding automatically
// and redirect to the relevant destination.
Route::middleware(['auth', 'active.account', 'tenant', 'tenant.access', \App\Http\Middleware\StaffOnly::class])
    ->prefix('onboarding')
    ->name('tenant.onboarding.')
    ->group(function () {
        // All GET pages â†’ redirect to the proper module
        Route::get('/', fn () => redirect()->route('dashboard'))->name('index');
        Route::get('profile',           fn () => redirect()->route('settings.school-profile'))->name('profile');
        Route::get('branding',          fn () => redirect()->route('settings.branding'))->name('branding');
        Route::get('academic-session',  fn () => redirect()->route('academic-cycle.index'))->name('session');
        Route::get('classes',           fn () => redirect()->route('classes.index'))->name('classes');
        Route::get('subjects',          fn () => redirect()->route('subjects.index'))->name('subjects');
        Route::get('settings',          fn () => redirect()->route('settings.school-profile'))->name('settings');
        Route::get('portals',           fn () => redirect()->route('portal-accounts.index'))->name('portals');
        Route::get('review',            fn () => redirect()->route('dashboard'))->name('review');

        // POST save routes â€” keep TenantOnboardingController for the actual save logic
        // (settings, classes, subjects etc. are still saved via these endpoints by the middleware)
        Route::post('profile',           [TenantOnboardingController::class, 'saveProfile'])->name('profile.save');
        Route::post('branding',          [TenantOnboardingController::class, 'saveBranding'])->name('branding.save');
        Route::post('academic-session',  [TenantOnboardingController::class, 'saveAcademicSession'])->name('session.save');
        Route::post('classes',           [TenantOnboardingController::class, 'saveClasses'])->name('classes.save');
        Route::post('subjects',          [TenantOnboardingController::class, 'saveSubjects'])->name('subjects.save');
        Route::post('settings',          [TenantOnboardingController::class, 'saveSettings'])->name('settings.save');
        Route::post('complete',          [TenantOnboardingController::class, 'complete'])->name('complete');
    });

Route::middleware(['auth', 'active.account', 'tenant', 'tenant.access', 'tenant.onboarding.complete', \App\Http\Middleware\StaffOnly::class, \App\Http\Middleware\CheckModuleAccess::class])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // â”€â”€ Student Bulk Upload â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('students/bulk')->name('students.bulk-upload.')->group(function () {
        Route::get('/',          [\App\Http\Controllers\StudentBulkUploadController::class, 'index'])->name('index');
        Route::get('/template',  [\App\Http\Controllers\StudentBulkUploadController::class, 'template'])->name('template');
        Route::post('/import',   [\App\Http\Controllers\StudentBulkUploadController::class, 'import'])->name('import');
    });

    // â”€â”€ Student Transfers (must be before students resource) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('students/transfers')->name('students.transfers.')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\StudentTransferController::class, 'index'])->name('index');
        Route::post('request',              [\App\Http\Controllers\StudentTransferController::class, 'request'])->name('request');
        Route::post('{transfer}/approve',   [\App\Http\Controllers\StudentTransferController::class, 'approve'])->name('approve');
        Route::post('{transfer}/reject',    [\App\Http\Controllers\StudentTransferController::class, 'reject'])->name('reject');
    });

    // â”€â”€ Students â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Interclass transfers stay separate from cross-school transfers.
    Route::prefix('students/class-transfers')
        ->name('students.class-transfers.')
        ->middleware('can:student.transfer.view')
        ->group(function () {
        Route::get('/', [StudentClassTransferController::class, 'index'])->name('index');
        Route::get('create', [StudentClassTransferController::class, 'create'])->middleware('can:student.transfer.request')->name('create');
        Route::post('/', [StudentClassTransferController::class, 'store'])->middleware('can:student.transfer.request')->name('store');
        Route::get('{classTransfer}', [StudentClassTransferController::class, 'show'])->name('show');
        Route::get('{classTransfer}/document', [StudentClassTransferController::class, 'downloadDocument'])->name('document');
        Route::post('{classTransfer}/approve', [StudentClassTransferController::class, 'approve'])->middleware('can:student.transfer.approve')->name('approve');
        Route::post('{classTransfer}/reject', [StudentClassTransferController::class, 'reject'])->middleware('can:student.transfer.reject')->name('reject');
        Route::post('{classTransfer}/cancel', [StudentClassTransferController::class, 'cancel'])->middleware('can:student.transfer.cancel')->name('cancel');
    });

    Route::prefix('students/archive')
        ->name('students.archive.')
        ->middleware('can:student.archive.view')
        ->group(function () {
            Route::get('/', [StudentArchiveController::class, 'index'])->name('index');
            Route::get('export', [StudentArchiveController::class, 'export'])->middleware('can:student.archive.export')->name('export');
            Route::get('{student}', [StudentArchiveController::class, 'show'])->name('show');
            Route::post('{student}/reactivate', [StudentLifecycleController::class, 'reactivate'])->middleware('can:student.reactivate')->name('reactivate');
            Route::post('{student}/readmit', [StudentLifecycleController::class, 'readmit'])->middleware('can:student.readmit')->name('readmit');
        });

    Route::get('students/status-history/{history}/document', [StudentLifecycleController::class, 'downloadDocument'])
        ->middleware('can:student.status.view')
        ->name('students.status-history.document');

    Route::get('students/{student}/status', [StudentLifecycleController::class, 'showStatus'])
        ->middleware('can:student.status.view')
        ->name('students.status.show');
    Route::post('students/{student}/status', [StudentLifecycleController::class, 'updateStatus'])
        ->middleware(['can:student.status.change', 'can:student.status.approve'])
        ->name('students.status.update');
    Route::get('students/{student}/reactivate', [StudentLifecycleController::class, 'reactivateForm'])
        ->middleware('can:student.reactivate')
        ->name('students.reactivate.form');
    Route::post('students/{student}/reactivate', [StudentLifecycleController::class, 'reactivate'])
        ->middleware('can:student.reactivate')
        ->name('students.reactivate');
    Route::get('students/{student}/readmit', [StudentLifecycleController::class, 'readmitForm'])
        ->middleware('can:student.readmit')
        ->name('students.readmit.form');
    Route::post('students/{student}/readmit', [StudentLifecycleController::class, 'readmit'])
        ->middleware('can:student.readmit')
        ->name('students.readmit');
    Route::get('students/{student}/graduation-correction', [StudentLifecycleController::class, 'graduationCorrectionForm'])
        ->middleware('can:student.status.correct-graduation')
        ->name('students.graduation-correction.form');
    Route::post('students/{student}/graduation-correction', [StudentLifecycleController::class, 'correctGraduation'])
        ->middleware('can:student.status.correct-graduation')
        ->name('students.graduation-correction');

    Route::resource('students', StudentController::class)
         ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    // ── Guardian management ───────────────────────────────────────────
    Route::get('guardians/{guardian}/edit',                  [\App\Http\Controllers\GuardianController::class, 'edit'])->name('guardians.edit');
    Route::put('guardians/{guardian}',                       [\App\Http\Controllers\GuardianController::class, 'update'])->name('guardians.update');
    Route::post('students/{student}/guardians',              [\App\Http\Controllers\GuardianController::class, 'store'])->name('guardians.store');
    Route::post('students/{student}/guardians/{guardian}/primary', [\App\Http\Controllers\GuardianController::class, 'setPrimary'])->name('guardians.set-primary');
    Route::delete('students/{student}/guardians/{guardian}', [\App\Http\Controllers\GuardianController::class, 'detach'])->name('guardians.detach');

    Route::prefix('academic-session')->name('academic-cycle.')->group(function () {
        Route::get('/',                                  [AcademicSessionController::class, 'index'])->name('index');

        // Sessions (static before wildcard)
        Route::post('sessions',                          [AcademicSessionController::class, 'storeSession'])->name('sessions.store');
        Route::get('sessions',                           [AcademicSessionController::class, 'sessions'])->name('sessions');
        Route::patch('sessions/{session}',               [AcademicSessionController::class, 'updateSession'])->name('sessions.update');
        Route::delete('sessions/{session}',              [AcademicSessionController::class, 'destroySession'])->name('sessions.destroy');
        Route::post('sessions/{session}/activate',       [AcademicSessionController::class, 'activateSession'])->name('sessions.activate');
        Route::post('sessions/{session}/close',          [AcademicSessionController::class, 'closeSession'])->name('sessions.close');

        // Terms (static before wildcard)
        Route::post('terms',                             [AcademicSessionController::class, 'storeTerm'])->name('terms.store');
        Route::get('terms',                              [AcademicSessionController::class, 'terms'])->name('terms');
        Route::patch('terms/{term}',                     [AcademicSessionController::class, 'updateTerm'])->name('terms.update');
        Route::delete('terms/{term}',                    [AcademicSessionController::class, 'destroyTerm'])->name('terms.destroy');
        Route::post('terms/{term}/activate',             [AcademicSessionController::class, 'activateTerm'])->name('terms.activate');
        Route::post('terms/{term}/close',                [AcademicSessionController::class, 'closeTerm'])->name('terms.close');

        Route::get('readiness',                          [AcademicSessionController::class, 'readiness'])->name('readiness');
        Route::get('repair',                             [AcademicSessionController::class, 'repair'])->name('repair.index');
    });

    // â”€â”€ Staff Bulk Upload â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('staff/bulk')->name('staff.bulk-upload.')->group(function () {
        Route::get('/',         [\App\Http\Controllers\StaffBulkUploadController::class, 'index'])->name('index');
        Route::get('/template', [\App\Http\Controllers\StaffBulkUploadController::class, 'template'])->name('template');
        Route::post('/import',  [\App\Http\Controllers\StaffBulkUploadController::class, 'import'])->name('import');
    });

    // â”€â”€ Classes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('classes')->name('classes.')->group(function () {
        // â”€â”€ Static routes FIRST â€” must be before any {wildcard} routes â”€â”€
        Route::get('levels',                     [ClassController::class, 'levels'])->name('levels');
        Route::post('levels',                    [ClassController::class, 'storeLevel'])->name('levels.store');
        Route::patch('levels/{level}',           [ClassController::class, 'updateLevel'])->name('levels.update');
        Route::delete('levels/{level}',          [ClassController::class, 'destroyLevel'])->name('levels.destroy');
        Route::get('arms',                       [ClassController::class, 'arms'])->name('arms');
        Route::post('arms',                      [ClassController::class, 'storeArm'])->name('arms.store');
        Route::patch('arms/{arm}',               [ClassController::class, 'updateArm'])->name('arms.update');
        Route::delete('arms/{arm}',              [ClassController::class, 'destroyArm'])->name('arms.destroy');
        Route::post('assign-subject',            [ClassController::class, 'assignSubject'])->name('assign-subject');
        Route::match(['patch','delete'], 'subjects/{cas}/toggle', [ClassController::class, 'toggleSubject'])->name('subjects.toggle');
        Route::get('grading',                    [ClassController::class, 'grading'])->name('grading');
        Route::post('grading',                   [ClassController::class, 'storeGrade'])->name('grading.store');
        Route::delete('grading/{grade}',         [ClassController::class, 'destroyGrade'])->name('grading.destroy');
        Route::get('promotion',                  [ClassController::class, 'promotion'])->name('promotion');
        Route::post('promotion',                 [ClassController::class, 'savePromotion'])->name('promotion.save');
        Route::get('promotion/preview',          [ClassController::class, 'promotionPreview'])->name('promotion.preview');
        Route::post('promotion/run',             [ClassController::class, 'runPromotion'])->name('promotion.run');
        Route::get('promotion/history',          [ClassController::class, 'promotionHistory'])->name('promotion.history');
        Route::get('bulk-promote',               [ClassController::class, 'bulkPromotePage'])->name('bulk-promote.page');
        Route::post('bulk-promote',              [ClassController::class, 'bulkPromote'])->name('bulk-promote');
        // â”€â”€ Wildcard routes LAST â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::get('{classArm}',                 [ClassController::class, 'show'])->name('show');
        Route::get('{classArm}/subjects',        [ClassController::class, 'subjects'])->name('subjects');
        Route::post('{classArm}/subjects',       [ClassController::class, 'storeSubject'])->name('subjects.store');
    });

    // â”€â”€ Subjects â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::resource('subjects', \App\Http\Controllers\SubjectController::class)
         ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('subjects/{subject}/toggle', [\App\Http\Controllers\SubjectController::class, 'toggle'])
         ->name('subjects.toggle');

    // â”€â”€ Staff â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('staff')->name('staff.')->group(function () {
    Route::get('/',             [StaffController::class, 'index'])->name('index');
    Route::get('create',        [StaffController::class, 'create'])->name('create');
    Route::post('/',            [StaffController::class, 'store'])->name('store');
    Route::get('archive',       [StaffArchiveController::class, 'index'])->middleware('can:staff.archive.view')->name('archive.index');
    Route::get('archive/export', [StaffArchiveController::class, 'export'])->middleware('can:staff.archive.export')->name('archive.export');
    Route::get('archive/{staff}', [StaffArchiveController::class, 'show'])->middleware('can:staff.archive.view')->name('archive.show');
    Route::get('status-history/{history}/document', [StaffLifecycleController::class, 'downloadStatusDocument'])->name('status.document');
    Route::get('work-history/{history}', [StaffWorkHistoryController::class, 'show'])->middleware('can:staff.work-history.view')->name('work-history.show');
    Route::get('work-history/{history}/document', [StaffWorkHistoryController::class, 'downloadDocument'])->middleware('can:staff.work-history.view')->name('work-history.document');
    Route::get('{staff}/status', [StaffLifecycleController::class, 'showStatus'])->middleware('can:staff.status.view')->name('status.show');
    Route::post('{staff}/status', [StaffLifecycleController::class, 'updateStatus'])->middleware(['can:staff.status.change', 'can:staff.status.approve'])->name('status.update');
    Route::get('{staff}/reinstate', [StaffLifecycleController::class, 'reinstateForm'])->middleware('can:staff.reinstate')->name('reinstate.form');
    Route::post('{staff}/reinstate', [StaffLifecycleController::class, 'reinstate'])->middleware('can:staff.reinstate')->name('reinstate');
    Route::get('{staff}/work-history', [StaffWorkHistoryController::class, 'index'])->middleware('can:staff.work-history.view')->name('work-history.index');
    Route::post('{staff}/work-history', [StaffWorkHistoryController::class, 'store'])->middleware(['can:staff.work-history.manage', 'can:staff.work-history.approve'])->name('work-history.store');
    Route::get('{staff}/edit',  [StaffController::class, 'edit'])->name('edit');
    Route::get('{staff}',       [StaffController::class, 'show'])->name('show');
    Route::put('{staff}',       [StaffController::class, 'update'])->name('update');
    Route::patch('{staff}/toggle',         [StaffController::class, 'toggle'])->name('toggle');
        Route::match(['post', 'patch'], '{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('reset-password');
    });

    // â”€â”€ Scores â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('scores')->name('scores.')->group(function () {
        Route::get('assessment-types',              [ScoreController::class, 'assessmentTypes'])->name('assessment-types');
        Route::post('assessment-types',             [ScoreController::class, 'storeAssessmentType'])->name('assessment-types.store');
        Route::put('assessment-types/{at}',         [ScoreController::class, 'updateAssessmentType'])->name('assessment-types.update');
        Route::patch('assessment-types/{at}/migrate', [ScoreController::class, 'migrateAssessmentType'])->name('assessment-types.migrate');
        Route::delete('assessment-types/{at}',      [ScoreController::class, 'destroyAssessmentType'])->name('assessment-types.destroy');
        Route::get('/',                 [ScoreController::class, 'index'])->name('index');
        Route::get('entry',             [ScoreController::class, 'entry'])->name('entry');
        Route::post('save',             [ScoreController::class, 'save'])->name('save');
        Route::get('broadsheet',        [ScoreController::class, 'broadsheet'])->name('broadsheet');
        Route::get('broadsheet/pdf',    [ScoreController::class, 'broadsheetPdf'])->name('broadsheet.pdf');
    });

    // â”€â”€ Score Import â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('scores/import')->name('scores.import.')->group(function () {
        Route::get('/',          [\App\Http\Controllers\ScoreImportController::class, 'index'])->name('index');
        Route::get('template',   [\App\Http\Controllers\ScoreImportController::class, 'download'])->name('template');
        Route::post('upload',    [\App\Http\Controllers\ScoreImportController::class, 'upload'])->name('upload');
    });

    // â”€â”€ Annual School Census (ASC) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('asc')->name('asc.')->group(function () {
        Route::get('infrastructure',       [\App\Http\Controllers\AscController::class, 'infrastructure'])->name('infrastructure');
        Route::post('infrastructure',      [\App\Http\Controllers\AscController::class, 'saveInfrastructure'])->name('infrastructure.save');
        Route::get('report',               [\App\Http\Controllers\AscController::class, 'report'])->name('report');
    });

    // â”€â”€ Reports â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/',                [ReportCardController::class, 'index'])->name('index');
        Route::post('compute',         [ReportCardController::class, 'compute'])->name('compute');
        Route::get('preview',          [ReportCardController::class, 'preview'])->name('preview');
        Route::match(['get','post'], 'pdf/{student}', [ReportCardController::class, 'pdf'])->name('pdf');
        Route::match(['get','post'], 'pdf-class',      [ReportCardController::class, 'pdfClass'])->name('pdf-class');
        Route::get('remarks',                [ReportCardController::class, 'remarks'])->name('remarks');
        Route::get('remarks/page',           [ReportCardController::class, 'remarks'])->name('remarks.page.view');
        Route::post('remarks/bulk',          [ReportCardController::class, 'bulkRemarks'])->name('remarks.bulk');
        Route::post('remarks/{summary}',     [ReportCardController::class, 'saveRemark'])->name('remarks.save');
        // Publication
        Route::get('publications',     [ReportCardController::class, 'publicationStatus'])->name('publications');
        Route::post('publish',         [ReportCardController::class, 'publish'])->name('publish');
        Route::post('unpublish',       [ReportCardController::class, 'unpublish'])->name('unpublish');
    });

    // â”€â”€ Skills â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('skills')->name('skills.')->group(function () {
        Route::get('/',     [SkillRatingController::class, 'index'])->name('index');
        Route::get('sheet', [SkillRatingController::class, 'sheet'])->name('sheet');
        Route::post('save', [SkillRatingController::class, 'save'])->name('save');
    });

    // â”€â”€ Student Attendance â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/',               [AttendanceController::class, 'index'])->name('index');
        Route::get('sheet',           [AttendanceController::class, 'sheet'])->name('sheet');
        Route::post('save',           [AttendanceController::class, 'save'])->name('save');
        Route::get('report',          [AttendanceController::class, 'report'])->name('report');
        Route::get('student/{student}',[AttendanceController::class, 'studentHistory'])->name('student');
    });

    // â”€â”€ Staff Attendance â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('staff-attendance')->name('staff-attendance.')
         ->group(function () {
        Route::get('/',                    [\App\Http\Controllers\StaffAttendanceController::class, 'index'])->name('index');
        Route::get('settings',             [\App\Http\Controllers\StaffAttendanceController::class, 'settings'])->name('settings');
        Route::post('settings',            [\App\Http\Controllers\StaffAttendanceController::class, 'saveSettings'])->name('settings.save');
        Route::get('qr',                   [\App\Http\Controllers\StaffAttendanceController::class, 'qrDisplay'])->name('qr');
        Route::get('report',               [\App\Http\Controllers\StaffAttendanceController::class, 'monthlyReport'])->name('report');
        Route::get('my',                   [\App\Http\Controllers\StaffAttendanceController::class, 'myAttendance'])->name('my');
        Route::post('manual',              [\App\Http\Controllers\StaffAttendanceController::class, 'manualOverride'])->name('manual');
        Route::get('offline-queue',        [\App\Http\Controllers\StaffAttendanceController::class, 'offlineQueue'])->name('offline-queue');
        Route::post('offline-queue/{record}/process', [\App\Http\Controllers\StaffAttendanceController::class, 'processOffline'])->name('offline.process');
        Route::post('proxy',               [\App\Http\Controllers\StaffAttendanceController::class, 'proxyClock'])->name('proxy');
        // API endpoints (for QR scanner app / JS)
        Route::post('api/clockin',          [\App\Http\Controllers\StaffAttendanceController::class, 'clockInQr'])->name('api.clockin');
        Route::post('api/clockout',         [\App\Http\Controllers\StaffAttendanceController::class, 'clockOut'])->name('api.clockout');
        Route::post('api/offline-upload',   [\App\Http\Controllers\StaffAttendanceController::class, 'uploadOffline'])->name('api.offline');
        // Proxy verification
        Route::get('api/staff-search',      [\App\Http\Controllers\StaffAttendanceController::class, 'staffSearch'])->name('api.staff-search');
        Route::post('api/proxy/initiate',   [\App\Http\Controllers\StaffAttendanceController::class, 'initiateProxy'])->name('api.proxy.initiate');
        Route::post('api/proxy/verify',     [\App\Http\Controllers\StaffAttendanceController::class, 'verifyProxy'])->name('api.proxy.verify');
        Route::post('api/proxy/verify-face',[\App\Http\Controllers\StaffAttendanceController::class, 'verifyProxyFace'])->name('api.proxy.verify-face');
        // Staff PIN management
        Route::post('set-pin',              [\App\Http\Controllers\StaffAttendanceController::class, 'setPin'])->name('set-pin');
        Route::get('id-card/{staff}',       [\App\Http\Controllers\StaffAttendanceController::class, 'idCard'])->name('id-card');
        Route::post('reset-qr',             [\App\Http\Controllers\StaffAttendanceController::class, 'resetStaticQr'])->name('reset-qr');
    });

    // â”€â”€ CBT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('cbt')->name('cbt.')->group(function () {
        // â”€â”€ Question Banks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::get('banks',                          [CbtController::class, 'banks'])->name('banks');
        Route::post('banks',                         [CbtController::class, 'storeBank'])->name('banks.store');
        Route::get('banks/{bank}',                   [CbtController::class, 'showBank'])->name('banks.show');
        Route::get('banks/{bank}/edit',              [CbtController::class, 'editBank'])->name('banks.edit');
        Route::put('banks/{bank}',                   [CbtController::class, 'updateBank'])->name('banks.update');
        Route::delete('banks/{bank}',                [CbtController::class, 'destroyBank'])->name('banks.destroy');
        // â”€â”€ Questions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::get('banks/{bank}/questions',         [CbtController::class, 'questions'])->name('questions');
        Route::post('banks/{bank}/questions',        [CbtController::class, 'storeQuestion'])->name('questions.store');
        Route::get('questions/{q}/edit',             [CbtController::class, 'editQuestion'])->name('questions.edit');
        Route::put('questions/{q}',                  [CbtController::class, 'updateQuestion'])->name('questions.update');
        Route::delete('questions/{q}',               [CbtController::class, 'destroyQuestion'])->name('questions.destroy');
        Route::post('banks/{bank}/reshuffle',        [CbtController::class, 'reshuffleBank'])->name('banks.reshuffle');
        // â”€â”€ Bulk Import â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::get('bulk-template',                  [CbtController::class, 'bulkUploadTemplate'])->name('bulk-template');
        Route::get('banks/{bank}/bulk-upload',       [CbtController::class, 'bulkUploadPage'])->name('bulk-upload');
        Route::post('banks/{bank}/bulk-import',      [CbtController::class, 'bulkImport'])->name('bulk-import');
        // â”€â”€ Exams â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::get('exams',                          [CbtController::class, 'exams'])->name('exams');
        Route::post('exams',                         [CbtController::class, 'storeExam'])->name('exams.store');
        Route::post('exams/{exam}/close',            [CbtController::class, 'closeExam'])->name('close');
        Route::post('exams/{exam}/publish',          [CbtController::class, 'publishExam'])->name('publish');
        Route::get('results/{exam?}',                [CbtController::class, 'results'])->name('results');
        Route::post('session/{session}/grade-essay', [CbtController::class, 'gradeEssay'])->name('grade-essay');
    });

    // â”€â”€ Fees â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('fees')->name('fees.')->group(function () {
        Route::get('subaccounts',                [FeeController::class, 'subaccounts'])->name('subaccounts');
        Route::post('subaccounts',               [FeeController::class, 'storeSubaccount'])->name('subaccounts.store');
        Route::get('categories',                 [FeeController::class, 'categories'])->name('categories');
        Route::post('categories',                [FeeController::class, 'storeCategory'])->name('categories.store');
        Route::get('structures',                 [FeeController::class, 'structures'])->name('structures');
        Route::post('structures',                [FeeController::class, 'storeStructure'])->name('structures.store');
        Route::get('invoices',                   [FeeController::class, 'invoices'])->name('invoices');
        Route::post('invoices/generate',         [FeeController::class, 'generateInvoices'])->name('invoices.generate');
        Route::get('invoices/{invoice}',         [FeeController::class, 'showInvoice'])->name('invoices.show');
        Route::post('payment/{invoice}',         [FeeController::class, 'recordPayment'])->name('payment.record');
    });

    // â”€â”€ Fee Reminders â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('fees/reminders')->name('fees.reminders.')->group(function () {
        Route::get('/',       [\App\Http\Controllers\FeeReminderController::class, 'index'])->name('index');
        Route::post('send',   [\App\Http\Controllers\FeeReminderController::class, 'sendReminder'])->name('send');
        Route::post('bulk',   [\App\Http\Controllers\FeeReminderController::class, 'bulkSend'])->name('bulk');
    });

    // â”€â”€ Payment Gateway â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('fees/gateway')->name('fees.gateway.')->group(function () {
        Route::get('settings',             [\App\Http\Controllers\PaymentGatewayController::class, 'settings'])->name('settings');
        Route::post('settings',            [\App\Http\Controllers\PaymentGatewayController::class, 'saveSettings'])->name('settings.save');
        Route::get('pay/{invoice}',        [\App\Http\Controllers\PaymentGatewayController::class, 'initiate'])->name('initiate');
        Route::get('paystack/callback',    [\App\Http\Controllers\PaymentGatewayController::class, 'paystackCallback'])->name('paystack.callback');
        Route::get('flutterwave/callback', [\App\Http\Controllers\PaymentGatewayController::class, 'flutterwaveCallback'])->name('flutterwave.callback');
        Route::get('monnify/callback',     [\App\Http\Controllers\PaymentGatewayController::class, 'monnifyCallback'])->name('monnify.callback');
    });

    // â”€â”€ Notifications â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',         [NotificationController::class, 'index'])->name('index');
        Route::post('send',     [NotificationController::class, 'send'])->name('send');
        Route::get('logs',      [NotificationController::class, 'logs'])->name('logs');
        Route::get('templates', [NotificationController::class, 'templates'])->name('templates');
        Route::get('settings',  [NotificationController::class, 'notificationSettings'])->name('settings');
        Route::get('triggers',  [\App\Http\Controllers\NotificationTriggerController::class, 'index'])->name('triggers');
        Route::post('triggers', [\App\Http\Controllers\NotificationTriggerController::class, 'save'])->name('triggers.save');
        Route::post('triggers/test', [\App\Http\Controllers\NotificationTriggerController::class, 'test'])->name('triggers.test');
    });

    // â”€â”€ Messages â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/',                [\App\Http\Controllers\MessagingController::class, 'inbox'])->name('inbox');
        Route::get('compose',          [\App\Http\Controllers\MessagingController::class, 'compose'])->name('compose');
        Route::post('/',               [\App\Http\Controllers\MessagingController::class, 'store'])->name('store');
        Route::get('{thread}',         [\App\Http\Controllers\MessagingController::class, 'thread'])->name('thread');
        Route::post('{thread}/reply',  [\App\Http\Controllers\MessagingController::class, 'reply'])->name('reply');
        Route::patch('{thread}/close', [\App\Http\Controllers\MessagingController::class, 'close'])->name('close');
    });

    // â”€â”€ Lesson Planner â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('lesson-planner')->name('lesson-planner.')->group(function () {
        Route::get('/',                                [\App\Http\Controllers\LessonPlannerController::class, 'index'])->name('index');
        Route::get('/create',                          [\App\Http\Controllers\LessonPlannerController::class, 'create'])->name('create');
        Route::post('/',                               [\App\Http\Controllers\LessonPlannerController::class, 'store'])->name('store');
        Route::post('/generate',                       [\App\Http\Controllers\LessonPlannerController::class, 'generate'])->name('generate');
        Route::get('/{lessonPlan}',                    [\App\Http\Controllers\LessonPlannerController::class, 'show'])->name('show');
        Route::get('/{lessonPlan}/edit',               [\App\Http\Controllers\LessonPlannerController::class, 'edit'])->name('edit');
        Route::put('/{lessonPlan}',                    [\App\Http\Controllers\LessonPlannerController::class, 'update'])->name('update');
        Route::delete('/{lessonPlan}',                 [\App\Http\Controllers\LessonPlannerController::class, 'destroy'])->name('destroy');
        Route::get('/{lessonPlan}/print',              [\App\Http\Controllers\LessonPlannerController::class, 'print'])->name('print');
        Route::post('/{lessonPlan}/generate-notes',    [\App\Http\Controllers\LessonPlannerController::class, 'generateNotes'])->name('generate-notes');
        Route::get('/{lessonPlan}/notes',              [\App\Http\Controllers\LessonPlannerController::class, 'notes'])->name('notes');
        Route::get('/{lessonPlan}/notes/print',        [\App\Http\Controllers\LessonPlannerController::class, 'printNotes'])->name('print-notes');
    });

    // â”€â”€ Timetable â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('timetable')->name('timetable.')->group(function () {
        Route::get('/',               [\App\Http\Controllers\TimetableController::class, 'index'])->name('index');
        Route::get('view',            [\App\Http\Controllers\TimetableController::class, 'view'])->name('view');
        Route::post('store',          [\App\Http\Controllers\TimetableController::class, 'store'])->name('store');
        Route::delete('{period}',     [\App\Http\Controllers\TimetableController::class, 'destroy'])->name('destroy');
        Route::get('configure',       [\App\Http\Controllers\TimetableController::class, 'configure'])->name('configure');
        Route::post('config',         [\App\Http\Controllers\TimetableController::class, 'saveConfig'])->name('config.save');
        Route::get('frequency',       [\App\Http\Controllers\TimetableController::class, 'frequency'])->name('frequency');
        Route::match(['get','post'], 'frequency/load', [\App\Http\Controllers\TimetableController::class, 'loadFrequency'])->name('frequency.load');
        Route::post('frequency/save', [\App\Http\Controllers\TimetableController::class, 'saveFrequency'])->name('frequency.save');
        Route::post('generate',       [\App\Http\Controllers\TimetableController::class, 'generate'])->name('generate');
        Route::get('teacher',         [\App\Http\Controllers\TimetableController::class, 'teacher'])->name('teacher');
        Route::get('conflicts',       [\App\Http\Controllers\TimetableController::class, 'conflicts'])->name('conflicts');
    });

    // â”€â”€ School Settings â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/',             [\App\Http\Controllers\SchoolSettingController::class, 'index'])->name('index');
        Route::post('/',            [\App\Http\Controllers\SchoolSettingController::class, 'update'])->name('update');
        Route::get('grading',       fn () => redirect()->route('classes.grading'))->name('grading');
        Route::get('promotion',     fn () => redirect()->route('classes.promotion'))->name('promotion');
    });

    // â”€â”€ Academic Calendar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/',          [\App\Http\Controllers\CalendarController::class, 'index'])->name('index');
        Route::post('/',         [\App\Http\Controllers\CalendarController::class, 'store'])->name('store');
        Route::patch('{event}',  [\App\Http\Controllers\CalendarController::class, 'update'])->name('update');
        Route::delete('{event}', [\App\Http\Controllers\CalendarController::class, 'destroy'])->name('destroy');
        Route::get('api/events', [\App\Http\Controllers\CalendarController::class, 'apiEvents'])->name('api');
    });

    // â”€â”€ Admissions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('admissions')->name('admissions.')->group(function () {
        Route::get('/',                    [\App\Http\Controllers\AdmissionController::class, 'index'])->name('index');
        Route::get('create',               [\App\Http\Controllers\AdmissionController::class, 'create'])->name('create');
        Route::post('/',                   [\App\Http\Controllers\AdmissionController::class, 'store'])->name('store');
        Route::get('export',               [\App\Http\Controllers\AdmissionController::class, 'exportCsv'])->name('export');
        Route::post('bulk-status',         [\App\Http\Controllers\AdmissionController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('portal',               [\App\Http\Controllers\PublicAdmissionController::class, 'portalApplications'])->name('portal');
        Route::get('portal/settings',      [\App\Http\Controllers\PublicAdmissionController::class, 'portalSettings'])->name('portal.settings');
        Route::post('portal/settings',     [\App\Http\Controllers\PublicAdmissionController::class, 'savePortalSettings'])->name('portal.settings.save');
        Route::get('{admission}',          [\App\Http\Controllers\AdmissionController::class, 'show'])->name('show');
        Route::patch('{admission}/status', [\App\Http\Controllers\AdmissionController::class, 'updateStatus'])->name('status');
        Route::delete('{admission}',       [\App\Http\Controllers\AdmissionController::class, 'destroy'])->name('destroy');
        Route::get('{admission}/documents',      [\App\Http\Controllers\AdmissionController::class, 'documents'])->name('documents');
        Route::get('{admission}/documents/{doc}/download', [\App\Http\Controllers\AdmissionController::class, 'downloadDocument'])->name('documents.download');
        Route::post('{admission}/documents/{doc}/verify',  [\App\Http\Controllers\AdmissionController::class, 'verifyDocument'])->name('documents.verify');
        Route::post('{admission}/interview',     [\App\Http\Controllers\AdmissionController::class, 'scheduleInterview'])->name('interview');
        Route::post('{admission}/score',         [\App\Http\Controllers\AdmissionController::class, 'recordInterview'])->name('score');
        Route::post('{admission}/offer',         [\App\Http\Controllers\AdmissionController::class, 'sendOffer'])->name('offer');
    });

    // â”€â”€ Health Records â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('health')->name('health.')->group(function () {
        Route::get('/',         [\App\Http\Controllers\HealthRecordController::class, 'index'])->name('index');
        Route::get('{student}', [\App\Http\Controllers\HealthRecordController::class, 'show'])->name('show');
        Route::post('{student}',[\App\Http\Controllers\HealthRecordController::class, 'upsert'])->name('upsert');
    });

    // â”€â”€ Expenses â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/',            [\App\Http\Controllers\ExpenseController::class, 'index'])->name('index');
        Route::post('/',           [\App\Http\Controllers\ExpenseController::class, 'store'])->name('store');
        Route::delete('{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('destroy');
    });

    // â”€â”€ Payroll â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/',                  [\App\Http\Controllers\PayrollController::class, 'index'])->name('index');
        Route::get('create',             [\App\Http\Controllers\PayrollController::class, 'create'])->name('create');
        Route::post('generate',          [\App\Http\Controllers\PayrollController::class, 'generatePeriod'])->name('generate');
        Route::get('salary/settings',    [\App\Http\Controllers\PayrollController::class, 'salarySettings'])->name('salary');
        Route::post('salary/settings',   [\App\Http\Controllers\PayrollController::class, 'saveSalarySetting'])->name('salary.save');
        Route::get('staff-deductions',   [\App\Http\Controllers\PayrollController::class, 'staffDeductions'])->name('staff-deductions');
        Route::post('staff-deductions',  [\App\Http\Controllers\PayrollController::class, 'storeStaffDeduction'])->name('staff-deductions.store');
        Route::delete('staff-deductions/{deduction}', [\App\Http\Controllers\PayrollController::class, 'destroyStaffDeduction'])->name('staff-deductions.destroy');
        Route::get('tax-bands',          [\App\Http\Controllers\PayrollController::class, 'taxBands'])->name('tax-bands');
        Route::post('tax-bands',         [\App\Http\Controllers\PayrollController::class, 'saveTaxBands'])->name('tax-bands.save');
        Route::get('templates',          [\App\Http\Controllers\PayrollController::class, 'templates'])->name('templates');
        Route::post('templates/deductions',               [\App\Http\Controllers\PayrollController::class, 'storeDeduction'])->name('templates.deduction.store');
        Route::delete('templates/deductions/{deduction}', [\App\Http\Controllers\PayrollController::class, 'destroyDeduction'])->name('templates.deduction.destroy');
        Route::post('templates/roles',                    [\App\Http\Controllers\PayrollController::class, 'storeRoleTemplate'])->name('templates.role.store');
        Route::get('{period}/payslip',   [\App\Http\Controllers\PayrollController::class, 'payslip'])->name('payslip');
        Route::get('{period}/payslip/{item}/pdf', [\App\Http\Controllers\PayrollController::class, 'payslipPdf'])->name('payslip.pdf');
        Route::get('{period}/download/pdf',   [\App\Http\Controllers\PayrollController::class, 'downloadPdf'])->name('download.pdf');
        Route::get('{period}/download/excel', [\App\Http\Controllers\PayrollController::class, 'downloadExcel'])->name('download.excel');
        Route::get('{period}',           [\App\Http\Controllers\PayrollController::class, 'show'])->name('show');
        Route::post('{period}/approve',  [\App\Http\Controllers\PayrollController::class, 'approve'])->name('approve');
        Route::post('{period}/paid',     [\App\Http\Controllers\PayrollController::class, 'markPaid'])->name('paid');
    });

    // â”€â”€ Library â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('library')->name('library.')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\LibraryController::class, 'index'])->name('index');
        Route::post('books',                [\App\Http\Controllers\LibraryController::class, 'store'])->name('books.store');
        Route::get('loans',                 [\App\Http\Controllers\LibraryController::class, 'loans'])->name('loans');
        Route::post('loans',                [\App\Http\Controllers\LibraryController::class, 'issueBook'])->name('issue');
        Route::patch('loans/{loan}/return', [\App\Http\Controllers\LibraryController::class, 'returnBook'])->name('return');
    });

    // â”€â”€ Announcements â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/',                      [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('index');
        Route::get('manage',                 [\App\Http\Controllers\AnnouncementController::class, 'manage'])->name('manage');
        Route::post('/',                     [\App\Http\Controllers\AnnouncementController::class, 'store'])->name('store');
        Route::delete('{announcement}',      [\App\Http\Controllers\AnnouncementController::class, 'destroy'])->name('destroy');
        Route::patch('{announcement}/toggle',[\App\Http\Controllers\AnnouncementController::class, 'toggle'])->name('toggle');
    });

    // â”€â”€ Analytics â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/',            [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('index');
        Route::get('class-report', [\App\Http\Controllers\AnalyticsController::class, 'classReport'])->name('class-report');
        Route::get('subjects',     [\App\Http\Controllers\AnalyticsController::class, 'subjectAnalysis'])->name('subjects');
        Route::get('teachers',     [\App\Http\Controllers\AnalyticsController::class, 'teacherReport'])->name('teachers');
        Route::get('financial',    [\App\Http\Controllers\AnalyticsController::class, 'financial'])->name('financial');
        Route::get('comparative',  [\App\Http\Controllers\AnalyticsController::class, 'comparative'])->name('comparative');
        Route::get('outcomes',     [\App\Http\Controllers\AnalyticsController::class, 'outcomes'])->name('outcomes');
    });

    // â”€â”€ Grade Book â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('gradebook')->name('gradebook.')->group(function () {
        Route::get('/', [\App\Http\Controllers\GradeBookController::class, 'index'])->name('index');
    });

    // â”€â”€ Exports â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('/',          [\App\Http\Controllers\ExportController::class, 'index'])->name('index');
        Route::get('broadsheet', [\App\Http\Controllers\ExportController::class, 'broadsheetCsv'])->name('broadsheet');
        Route::get('students',   [\App\Http\Controllers\ExportController::class, 'studentsCsv'])->name('students');
        Route::get('fees',       [\App\Http\Controllers\ExportController::class, 'feesCsv'])->name('fees');
    });

    // â”€â”€ Push Notifications â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('push')->name('push.')->group(function () {
        Route::post('subscribe',   [\App\Http\Controllers\PushNotificationController::class, 'subscribe'])->name('subscribe');
        Route::post('unsubscribe', [\App\Http\Controllers\PushNotificationController::class, 'unsubscribe'])->name('unsubscribe');
        Route::post('test',        [\App\Http\Controllers\PushNotificationController::class, 'sendTest'])->name('test');
        Route::post('broadcast',   [\App\Http\Controllers\PushNotificationController::class, 'broadcast'])->name('broadcast');
        Route::get('vapid-key',    [\App\Http\Controllers\PushNotificationController::class, 'vapidKey'])->name('vapid-key');
    });

    // â”€â”€ Academic Risk Flagging â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('risk')->name('risk.')->group(function () {
        Route::get('/',                  [\App\Http\Controllers\RiskFlagController::class, 'index'])->name('index');
        Route::get('/{flag}',            [\App\Http\Controllers\RiskFlagController::class, 'show'])->name('show');
        Route::post('/compute',          [\App\Http\Controllers\RiskFlagController::class, 'compute'])->name('compute');
        Route::post('/{flag}/acknowledge',[\App\Http\Controllers\RiskFlagController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{flag}/resolve',   [\App\Http\Controllers\RiskFlagController::class, 'resolve'])->name('resolve');
        Route::post('/config/save',      [\App\Http\Controllers\RiskFlagController::class, 'saveConfig'])->name('config.save');
    });

    // â”€â”€ Fee Invoice Generation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('fees/generate')->name('fees.generate.')->group(function () {
        Route::get('/',                  [\App\Http\Controllers\InvoiceGenerationController::class, 'index'])->name('index');
        Route::post('/preview',          [\App\Http\Controllers\InvoiceGenerationController::class, 'preview'])->name('preview');
        Route::post('/',                 [\App\Http\Controllers\InvoiceGenerationController::class, 'generate'])->name('store');
        Route::get('/batches',           [\App\Http\Controllers\InvoiceGenerationController::class, 'batches'])->name('batches');
        Route::delete('/batch/{batch}/void',   [\App\Http\Controllers\InvoiceGenerationController::class, 'voidBatch'])->name('batch.void');
        Route::post('/discounts',              [\App\Http\Controllers\InvoiceGenerationController::class, 'storeDiscount'])->name('discount.store');
        Route::delete('/discounts/{discount}', [\App\Http\Controllers\InvoiceGenerationController::class, 'destroyDiscount'])->name('discount.destroy');
    });

    // â”€â”€ Fee Payment Plans â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get('fees/plans',                           [\App\Http\Controllers\FeePaymentPlanController::class, 'index'])->name('fees.plans.index');
    Route::post('fees/plans',                          [\App\Http\Controllers\FeePaymentPlanController::class, 'store'])->name('fees.plans.store');
    Route::patch('fees/plans/{plan}/toggle',           [\App\Http\Controllers\FeePaymentPlanController::class, 'toggle'])->name('fees.plans.toggle');
    Route::delete('fees/plans/{plan}',                 [\App\Http\Controllers\FeePaymentPlanController::class, 'destroy'])->name('fees.plans.destroy');
    Route::get('fees/plans/overdue',                   [\App\Http\Controllers\FeePaymentPlanController::class, 'overdue'])->name('fees.plans.overdue');
    Route::post('fees/plans/reminders',                [\App\Http\Controllers\FeePaymentPlanController::class, 'sendReminders'])->name('fees.plans.reminders');
    Route::post('fees/plans/invoice/{invoice}/assign', [\App\Http\Controllers\FeePaymentPlanController::class, 'assignToInvoice'])->name('fees.plans.assign');
    Route::post('fees/plans/installment/{inst}/pay',   [\App\Http\Controllers\FeePaymentPlanController::class, 'payInstallment'])->name('fees.plans.installment.pay');


    // â”€â”€ School Admin Self-Service Billing â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get('billing/subscription', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.subscription');
    Route::post('billing/subscription/select', [\App\Http\Controllers\BillingController::class, 'selectPlan'])->name('billing.select-plan');

    // â”€â”€ Support Tickets & Platform Notices â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get('support',          [\App\Http\Controllers\SupportController::class, 'index'])->name('support.index');
    Route::post('support',         [\App\Http\Controllers\SupportController::class, 'store'])->name('support.store');
    Route::get('platform-notices', [\App\Http\Controllers\SupportController::class, 'platformNotices'])->name('platform.notices');

    // â”€â”€ Dismiss platform broadcast â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::post('broadcasts/{id}/dismiss', [\App\Http\Controllers\SupportController::class, 'dismissBroadcast'])->name('broadcast.dismiss');

    // â”€â”€ Internal Messaging (staff â†” admin, no student required) â”€â”€â”€â”€â”€
    Route::get('messages/internal/compose', [\App\Http\Controllers\MessagingController::class, 'composeInternal'])->name('messages.internal.compose');
    Route::post('messages/internal',        [\App\Http\Controllers\MessagingController::class, 'storeInternal'])->name('messages.internal.store');

    // â”€â”€ Parent/Student Online Payment â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get('fees/pay/{invoice}', [\App\Http\Controllers\PaymentGatewayController::class, 'initiateFromPortal'])->name('fees.gateway.pay');

    // â”€â”€ Staff Permissions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get('staff/{staff}/permissions',   [\App\Http\Controllers\StaffPermissionController::class, 'show'])->name('staff.permissions');
    Route::put('staff/{staff}/permissions',   [\App\Http\Controllers\StaffPermissionController::class, 'update'])->name('staff.permissions.update');

    // â”€â”€ Student Transcript (admin, principal, vice principal only) â”€â”€â”€
    Route::get('transcripts',              [\App\Http\Controllers\StudentController::class, 'transcriptIndex'])->name('students.transcript.index');
    Route::get('transcripts/search',       [\App\Http\Controllers\StudentController::class, 'transcriptIndex'])->name('students.transcript.search');
    Route::get('students/{student}/transcript',     [\App\Http\Controllers\StudentController::class, 'transcript'])->name('students.transcript');
    Route::get('students/{student}/transcript/pdf', [\App\Http\Controllers\StudentController::class, 'transcriptPdf'])->name('students.transcript.pdf');

    // â”€â”€ Transport Management â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('transport')->name('transport.')->group(function () {
        Route::get('routes',                   [\App\Http\Controllers\TransportController::class, 'routes'])->name('routes');
        Route::post('routes',                  [\App\Http\Controllers\TransportController::class, 'storeRoute'])->name('routes.store');
        Route::patch('routes/{route}',         [\App\Http\Controllers\TransportController::class, 'updateRoute'])->name('routes.update');
        Route::delete('routes/{route}',        [\App\Http\Controllers\TransportController::class, 'destroyRoute'])->name('routes.destroy');
        Route::get('buses',                    [\App\Http\Controllers\TransportController::class, 'buses'])->name('buses');
        Route::post('buses',                   [\App\Http\Controllers\TransportController::class, 'storeBus'])->name('buses.store');
        Route::delete('buses/{bus}',           [\App\Http\Controllers\TransportController::class, 'destroyBus'])->name('buses.destroy');
        Route::get('assignments',              [\App\Http\Controllers\TransportController::class, 'assignments'])->name('assignments');
        Route::post('assign',                  [\App\Http\Controllers\TransportController::class, 'assign'])->name('assign');
        Route::delete('unassign/{student}',    [\App\Http\Controllers\TransportController::class, 'unassign'])->name('unassign');
        Route::get('routes/{route}/manifest',  [\App\Http\Controllers\TransportController::class, 'manifest'])->name('manifest');
    });

    // â”€â”€ SMS Campaigns â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('sms')->name('sms.')->group(function () {
        Route::get('/',                        [\App\Http\Controllers\SmsCampaignController::class, 'index'])->name('index');
        Route::get('create',                   [\App\Http\Controllers\SmsCampaignController::class, 'create'])->name('create');
        Route::post('/',                       [\App\Http\Controllers\SmsCampaignController::class, 'store'])->name('store');
        Route::get('{campaign}',               [\App\Http\Controllers\SmsCampaignController::class, 'show'])->name('show');
        Route::post('{campaign}/send',         [\App\Http\Controllers\SmsCampaignController::class, 'send'])->name('send');
        Route::delete('{campaign}',            [\App\Http\Controllers\SmsCampaignController::class, 'destroy'])->name('destroy');
    });

    // â”€â”€ Parent Portal Admin (setup accounts) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::post('parent/setup-account',        [\App\Http\Controllers\ParentPortalController::class, 'setupAccount'])->name('parent.setup');


    // â”€â”€ Curriculum Management â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('curriculum')->name('curriculum.')->group(function () {

        // Academic Tracks
        Route::get('tracks',              [\App\Http\Controllers\CurriculumController::class, 'tracks'])->name('tracks');
        Route::post('tracks',             [\App\Http\Controllers\CurriculumController::class, 'storeTracks'])->name('tracks.store');
        Route::patch('tracks/{track}/toggle', [\App\Http\Controllers\CurriculumController::class, 'toggleTrack'])->name('tracks.toggle');
        Route::delete('tracks/{track}',   [\App\Http\Controllers\CurriculumController::class, 'destroyTrack'])->name('tracks.destroy');

        // Class Level Subject Rules
        Route::get('levels/{level}/subjects',         [\App\Http\Controllers\CurriculumController::class, 'levelSubjects'])->name('level-subjects');
        Route::post('levels/{level}/subjects',        [\App\Http\Controllers\CurriculumController::class, 'storeLevelSubject'])->name('level-subjects.store');
        Route::patch('level-subjects/{rule}',         [\App\Http\Controllers\CurriculumController::class, 'updateLevelSubject'])->name('level-subjects.update');
        Route::delete('level-subjects/{rule}',        [\App\Http\Controllers\CurriculumController::class, 'destroyLevelSubject'])->name('level-subjects.destroy');
        Route::post('levels/{level}/subjects/bulk',   [\App\Http\Controllers\CurriculumController::class, 'bulkSetLevelSubjects'])->name('level-subjects.bulk');

        // Class Arm Track Assignment
        Route::get('arm-tracks',                  [\App\Http\Controllers\CurriculumController::class, 'armTrackAssignment'])->name('arm-tracks');
        Route::patch('arm-tracks/{arm}',          [\App\Http\Controllers\CurriculumController::class, 'setArmTrack'])->name('arm-tracks.set');

        // Student Subject Selection
        Route::get('students/{student}/subjects',      [\App\Http\Controllers\CurriculumController::class, 'studentSubjects'])->name('student-subjects');
        Route::post('students/{student}/sync',         [\App\Http\Controllers\CurriculumController::class, 'syncCompulsoryForStudent'])->name('student-subjects.sync');
        Route::post('students/{student}/elective',     [\App\Http\Controllers\CurriculumController::class, 'addStudentElective'])->name('student-subjects.add');
        Route::delete('students/{student}/elective',   [\App\Http\Controllers\CurriculumController::class, 'removeStudentElective'])->name('student-subjects.remove');

        // Teacher Subject Allocation per Class Arm
        Route::get('arms/{arm}/teachers',         [\App\Http\Controllers\CurriculumController::class, 'armTeacherSubjects'])->name('arm-teachers');
        Route::post('arms/{arm}/teachers',        [\App\Http\Controllers\CurriculumController::class, 'setArmTeacher'])->name('arm-teachers.set');
        Route::delete('arms/{arm}/teachers',      [\App\Http\Controllers\CurriculumController::class, 'removeArmTeacher'])->name('arm-teachers.remove');

        // Migration utility
        Route::post('backfill',                   [\App\Http\Controllers\CurriculumController::class, 'backfillFromClassArmSubjects'])->name('backfill');
    });


    // â”€â”€ Portal Account Management (Admin only) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('portal-accounts')->name('portal-accounts.')->group(function () {
        Route::get('/',                    [\App\Http\Controllers\PortalAccountController::class, 'index'])->name('index');
        Route::post('students/{student}',  [\App\Http\Controllers\PortalAccountController::class, 'createStudentAccount'])->name('students.create');
        Route::post('guardians/{guardian}',[\App\Http\Controllers\PortalAccountController::class, 'createGuardianAccount'])->name('guardians.create');
        Route::post('reset/{user}',        [\App\Http\Controllers\PortalAccountController::class, 'resetPassword'])->name('reset');
        Route::patch('toggle/{user}',      [\App\Http\Controllers\PortalAccountController::class, 'toggleAccess'])->name('toggle');
        Route::post('bulk-students',       [\App\Http\Controllers\PortalAccountController::class, 'bulkCreateStudents'])->name('bulk-students');
    });

    // â”€â”€ User Profile (ALL authenticated users) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\ProfileController::class, 'edit'])->name('edit');
        Route::post('/',          [\App\Http\Controllers\ProfileController::class, 'update'])->name('update');
        Route::post('password',   [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password');
        Route::post('photo',      [\App\Http\Controllers\ProfileController::class, 'updatePhoto'])->name('photo');
        Route::post('bank-details', [\App\Http\Controllers\ProfileController::class, 'updateBankDetails'])->name('bank-details');
        Route::post('email',      [\App\Http\Controllers\ProfileController::class, 'updateEmail'])->name('email');
    });

}); // End auth+tenant

// â”€â”€ 2FA Challenge & Setup (auth required, but before 2fa gate) â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::middleware('auth')->group(function () {
    Route::get('two-factor/setup',    [\App\Http\Controllers\Auth\TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('two-factor/confirm', [\App\Http\Controllers\Auth\TwoFactorController::class, 'confirm'])->name('2fa.confirm');
    Route::post('two-factor/disable', [\App\Http\Controllers\Auth\TwoFactorController::class, 'disable'])->name('2fa.disable');
    Route::get('two-factor/challenge',  [\App\Http\Controllers\Auth\TwoFactorController::class, 'challenge'])->name('2fa.challenge');
    Route::post('two-factor/challenge', [\App\Http\Controllers\Auth\TwoFactorController::class, 'verify'])->name('2fa.verify');
});

// â”€â”€ Super Admin Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::middleware(['auth', 'active.account', '2fa'])->prefix('super')->name('super.')->group(function () {
    Route::get('/',              [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('tenants',        [SuperAdminController::class, 'tenants'])->name('tenants');
    Route::get('tenants/create', [SuperAdminController::class, 'createTenant'])->name('tenants.create');
    Route::post('tenants',       [SuperAdminController::class, 'storeTenant'])->name('tenants.store');
    Route::get('tenants/{tenant}/edit', [SuperAdminController::class, 'editTenant'])->name('tenant.edit');
    Route::patch('tenants/{tenant}',    [SuperAdminController::class, 'updateTenant'])->name('tenant.update');
    Route::get('tenants/{tenant}',      [SuperAdminController::class, 'showTenant'])->name('tenant.show');
    Route::patch('tenants/{tenant}/toggle',  [SuperAdminController::class, 'toggleTenant'])->name('tenant.toggle');
    Route::post('tenants/{tenant}/extend',   [SuperAdminController::class, 'extendTenant'])->name('tenant.extend');
    Route::post('tenants/{tenant}/renew',    [SuperAdminController::class, 'renewTenant'])->name('tenant.renew');
    Route::delete('tenants/{tenant}',        [SuperAdminController::class, 'destroyTenant'])->name('tenant.destroy');
    Route::post('impersonate/{tenant}',      [SuperAdminController::class, 'impersonate'])->name('impersonate');
    Route::post('stop-impersonating',        [SuperAdminController::class, 'stopImpersonating'])->name('stop-impersonating');
    Route::get('subscriptions',  [SuperAdminController::class, 'subscriptions'])->name('subscriptions');
    Route::get('plans',          [SuperAdminController::class, 'plans'])->name('plans');
    Route::post('plans',         [SuperAdminController::class, 'storePlan'])->name('plans.store');
    Route::patch('plans/{plan}', [SuperAdminController::class, 'updatePlan'])->name('plans.update');
    Route::get('payments',       [SuperAdminController::class, 'payments'])->name('payments');
    Route::get('settings',               [SuperAdminController::class, 'settings'])->name('settings');
    Route::post('settings',              [SuperAdminController::class, 'saveSettings'])->name('settings.save');
    Route::get('payment-gateways',       [SuperAdminController::class, 'paymentGateways'])->name('payment-gateways');
    Route::post('payment-gateways',      [SuperAdminController::class, 'savePaymentGateways'])->name('payment-gateways.save');
    Route::get('analytics',      [SuperAdminController::class, 'analytics'])->name('analytics');
    Route::post('send-renewals', [SuperAdminController::class, 'sendRenewalReminders'])->name('send-renewals');

    // â”€â”€ Tenant Self-Service Subscription Payment â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Schools can pay their own subscription online (no super admin needed)
    Route::get('billing/{invoice}/pay',      [SuperAdminController::class, 'tenantPayInitiate'])->name('billing.pay');
    Route::get('billing/pay/callback',       [SuperAdminController::class, 'tenantPayCallback'])->name('billing.pay.callback');
    Route::get('billing/pay/monnify/callback', [SuperAdminController::class, 'monnifyPayCallback'])->name('billing.pay.monnify.callback');
    Route::get('billing/{invoice}/pay/monnify', [SuperAdminController::class, 'monnifyPayInitiate'])->name('billing.pay.monnify');

    // â”€â”€ Billing & Invoicing â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get('billing',                  [SuperAdminController::class, 'billingInvoices'])->name('billing');
    Route::post('billing/generate',        [SuperAdminController::class, 'generateInvoice'])->name('billing.generate');
    Route::post('billing/{invoice}/paid',  [SuperAdminController::class, 'markInvoicePaid'])->name('billing.paid');
    Route::get('billing/{invoice}/pdf',    [SuperAdminController::class, 'invoicePdf'])->name('billing.pdf');

    // â”€â”€ School Groups / Chains â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\SchoolGroupController::class, 'index'])->name('index');
        Route::post('/',                  [\App\Http\Controllers\SchoolGroupController::class, 'store'])->name('store');
        Route::get('{group}',             [\App\Http\Controllers\SchoolGroupController::class, 'show'])->name('show');
        Route::post('{group}/members',    [\App\Http\Controllers\SchoolGroupController::class, 'addMember'])->name('members.add');
        Route::delete('{group}/members/{tenant}', [\App\Http\Controllers\SchoolGroupController::class, 'removeMember'])->name('members.remove');
        Route::get('{group}/report',      [\App\Http\Controllers\SchoolGroupController::class, 'report'])->name('report');
        Route::delete('{group}',          [\App\Http\Controllers\SchoolGroupController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/',                              [\App\Http\Controllers\AgentController::class, 'index'])->name('index');
        Route::post('/',                             [\App\Http\Controllers\AgentController::class, 'store'])->name('store');
        Route::get('settings',                       [\App\Http\Controllers\AgentController::class, 'settings'])->name('settings');
        Route::post('settings',                      [\App\Http\Controllers\AgentController::class, 'saveSettings'])->name('settings.save');
        Route::get('{agent}',                        [\App\Http\Controllers\AgentController::class, 'show'])->name('show');
        Route::patch('{agent}/toggle',               [\App\Http\Controllers\AgentController::class, 'toggle'])->name('toggle');
        Route::patch('{agent}/password',             [\App\Http\Controllers\AgentController::class, 'updatePassword'])->name('password.update');
        Route::post('{agent}/pay',                   [\App\Http\Controllers\AgentController::class, 'recordPayment'])->name('pay');
        Route::post('referrals/{referral}/approve',  [\App\Http\Controllers\AgentController::class, 'approveCommission'])->name('referrals.approve');
        Route::post('messages',                      [\App\Http\Controllers\AgentController::class, 'sendMessage'])->name('messages.send');
        Route::post('{agent}/activate',              [\App\Http\Controllers\AgentController::class, 'activate'])->name('activate');
    });

    Route::get('tenants/{tenant}/white-label',    [\App\Http\Controllers\WhiteLabelController::class, 'settings'])->name('white-label');
    Route::post('tenants/{tenant}/white-label',   [\App\Http\Controllers\WhiteLabelController::class, 'save'])->name('white-label.save');
    Route::post('tenants/{tenant}/verify-domain', [\App\Http\Controllers\WhiteLabelController::class, 'verifyDomain'])->name('verify-domain');

    // â”€â”€ Support Inbox â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get('support',                [SuperAdminController::class, 'supportInbox'])->name('support');
    Route::post('support/{ticket}/reply',[SuperAdminController::class, 'replyTicket'])->name('support.reply');
    Route::post('support/{ticket}/close',[SuperAdminController::class, 'closeTicket'])->name('support.close');

    // â”€â”€ Broadcasts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get('broadcasts',             [SuperAdminController::class, 'broadcasts'])->name('broadcasts');
    Route::post('broadcasts',            [SuperAdminController::class, 'storeBroadcast'])->name('broadcasts.store');
    Route::delete('broadcasts/{id}',     [SuperAdminController::class, 'deleteBroadcast'])->name('broadcasts.delete');
}); // End super admin


// â”€â”€ Legacy Parent Portal (session-based at /portal) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::prefix('portal')->name('portal.parent.')->group(function () {
    Route::get('login',    [\App\Http\Controllers\ParentPortalController::class, 'loginForm'])->name('login');
    Route::post('login',   [\App\Http\Controllers\ParentPortalController::class, 'login'])->name('login.post');
    Route::post('logout',  [\App\Http\Controllers\ParentPortalController::class, 'logout'])->name('logout');
    Route::get('/',        [\App\Http\Controllers\ParentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('results',  [\App\Http\Controllers\ParentPortalController::class, 'results'])->name('results');
    Route::get('fees',     [\App\Http\Controllers\ParentPortalController::class, 'fees'])->name('fees');
    Route::get('messages',                        [\App\Http\Controllers\ParentPortalController::class, 'messages'])->name('messages');
    Route::post('messages/{thread}/reply',        [\App\Http\Controllers\ParentPortalController::class, 'replyToThread'])->name('messages.reply');
});


// â”€â”€ Student Portal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::middleware(['auth', 'active.account', \App\Http\Middleware\IdentifyTenant::class, \App\Http\Middleware\StudentPortalAccess::class])
     ->prefix('student')->name('student.portal.')->group(function () {
    Route::get('dashboard',   [\App\Http\Controllers\Portal\StudentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('results',     [\App\Http\Controllers\Portal\StudentPortalController::class, 'results'])->name('results');
    Route::get('timetable',   [\App\Http\Controllers\Portal\StudentPortalController::class, 'timetable'])->name('timetable');
    Route::get('attendance',  [\App\Http\Controllers\Portal\StudentPortalController::class, 'attendance'])->name('attendance');
    Route::get('exams',       [\App\Http\Controllers\Portal\StudentPortalController::class, 'exams'])->name('exams');
    Route::get('subjects',    [\App\Http\Controllers\Portal\StudentPortalController::class, 'subjects'])->name('subjects');
    Route::get('report-card/pdf', [\App\Http\Controllers\ReportCardController::class, 'studentPdf'])->name('report-card.pdf');
});

// â”€â”€ Parent Portal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::middleware(['auth', 'active.account', \App\Http\Middleware\IdentifyTenant::class, \App\Http\Middleware\EnsureTenantHasApplicationAccess::class, \App\Http\Middleware\ParentPortalAccess::class])
     ->prefix('parent')->name('parent.')->group(function () {
    Route::get('dashboard',             [\App\Http\Controllers\Portal\ParentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('results',               [\App\Http\Controllers\Portal\ParentPortalController::class, 'results'])->name('results');
    Route::get('results/pdf',           [\App\Http\Controllers\Portal\ParentPortalController::class, 'reportCardPdf'])->name('results.pdf');
    Route::get('fees',                  [\App\Http\Controllers\Portal\ParentPortalController::class, 'fees'])->name('fees');
    Route::get('fees/pay/{invoice}',    [\App\Http\Controllers\Portal\ParentPortalController::class, 'payFee'])->name('fees.pay');
    Route::get('attendance',            [\App\Http\Controllers\Portal\ParentPortalController::class, 'attendance'])->name('attendance');
    Route::get('notifications',         [\App\Http\Controllers\Portal\ParentPortalController::class, 'notifications'])->name('notifications');
    Route::get('calendar',              [\App\Http\Controllers\Portal\ParentPortalController::class, 'calendar'])->name('calendar');
});

// â”€â”€ Staff Self-Service Portal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::middleware(['auth', 'active.account', \App\Http\Middleware\IdentifyTenant::class, \App\Http\Middleware\EnsureTenantHasApplicationAccess::class, \App\Http\Middleware\StaffOnly::class])
     ->prefix('my')->name('staff.portal.')->group(function () {
    Route::get('dashboard',                      [\App\Http\Controllers\Portal\StaffPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('payroll',                        [\App\Http\Controllers\Portal\StaffPortalController::class, 'payroll'])->name('payroll');
    Route::get('payroll/{period}/print',         [\App\Http\Controllers\Portal\StaffPortalController::class, 'payslipPrint'])->name('payslip.print');
    Route::get('messages',                       [\App\Http\Controllers\Portal\StaffPortalController::class, 'messages'])->name('messages');
    Route::get('messages/{thread}',              [\App\Http\Controllers\Portal\StaffPortalController::class, 'messageThread'])->name('messages.thread');
    Route::post('messages/{thread}/reply',       [\App\Http\Controllers\Portal\StaffPortalController::class, 'messageReply'])->name('messages.reply');
});

// â”€â”€ Agent Portal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::prefix('agent/portal')->name('agent.portal.')->group(function () {
    Route::get('login',    [\App\Http\Controllers\AgentPortalController::class, 'loginForm'])->name('login');
    Route::post('login',   [\App\Http\Controllers\AgentPortalController::class, 'login'])->name('login.post');
    Route::post('logout',  [\App\Http\Controllers\AgentPortalController::class, 'logout'])->name('logout');
    Route::get('dashboard',[\App\Http\Controllers\AgentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('schools',  [\App\Http\Controllers\AgentPortalController::class, 'schools'])->name('schools');
    Route::get('earnings', [\App\Http\Controllers\AgentPortalController::class, 'earnings'])->name('earnings');
    Route::get('messages', [\App\Http\Controllers\AgentPortalController::class, 'messages'])->name('messages');
    Route::get('profile',  [\App\Http\Controllers\AgentPortalController::class, 'profile'])->name('profile');
    Route::post('profile', [\App\Http\Controllers\AgentPortalController::class, 'updateProfile'])->name('profile.update');
    Route::post('password',[\App\Http\Controllers\AgentPortalController::class, 'updatePassword'])->name('password');
});

Route::get('agent/login', fn () => redirect()->route('agent.portal.login'))->name('agent.login');

// â”€â”€ Agent Registration (public onboarding) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::get('agent/register',  [\App\Http\Controllers\AgentPortalController::class, 'registerForm'])->name('agent.register');
Route::post('agent/register', [\App\Http\Controllers\AgentPortalController::class, 'register'])->name('agent.register.post');

// â”€â”€ CBT Exam Routes (accessible to students AND staff) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::middleware(['auth', 'active.account', \App\Http\Middleware\IdentifyTenant::class])->group(function () {
    Route::get('cbt/exams/{exam}/start',          [\App\Http\Controllers\CbtController::class, 'startExam'])->name('cbt.exams.start');
    Route::post('cbt/session/{session}/submit',   [\App\Http\Controllers\CbtController::class, 'submitExam'])->name('cbt.session.submit');
});

// â”€â”€ Public Admissions Portal stubs (subdomain handles the real routes) â”€â”€â”€â”€
Route::name('portal.')->group(function () {
    $apply = fn (string $slug, string $path = '') =>
        config('tenancy.scheme') . '://' . $slug . '.' . config('tenancy.base_domain') . '/apply' . ($path ? '/' . ltrim($path, '/') : '');

    Route::get('_apply_stub/{slug}',              fn (string $slug) => redirect()->away($apply($slug)))->name('landing');
    Route::get('_apply_stub/{slug}/form',         fn (string $slug) => redirect()->away($apply($slug, 'form')))->name('form');
    Route::get('_apply_stub/{slug}/success/{app}',fn (string $slug, string $app) => redirect()->away($apply($slug, 'success/' . $app)))->name('success');
    Route::get('_apply_stub/{slug}/status',       fn (string $slug) => redirect()->away($apply($slug, 'status')))->name('status.form');
    Route::post('_apply_stub/{slug}/submit',      fn () => abort(404))->name('submit');
    Route::post('_apply_stub/{slug}/status',      fn () => abort(404))->name('status');
});

// â”€â”€ Webhooks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Admission fee payment callback (public â€” no auth)
Route::get('admissions/fee-callback', [\App\Http\Controllers\PublicAdmissionController::class, 'feeCallback'])->name('portal.fee.callback');

Route::post('webhooks/paystack',
    [\App\Http\Controllers\PaymentGatewayController::class, 'paystackWebhook'])
    ->name('webhooks.paystack')->withoutMiddleware(['web']);

