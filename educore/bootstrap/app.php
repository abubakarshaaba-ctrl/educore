<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

// Shared hosting can retain an older Laravel route cache after files are synced.
// If any route definition is newer than a cached route file, remove only the stale
// route cache so the next bootstrap rebuilds the current route collection.
$routeFiles = glob(__DIR__.'/../routes/*.php') ?: [];
$routeCacheFiles = glob(__DIR__.'/cache/routes-*.php') ?: [];
if ($routeFiles && $routeCacheFiles) {
    $newestRouteMtime = max(array_map(static fn (string $file): int => (int) @filemtime($file), $routeFiles));
    foreach ($routeCacheFiles as $routeCacheFile) {
        if ((int) @filemtime($routeCacheFile) < $newestRouteMtime) {
            @unlink($routeCacheFile);
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::group([], base_path('routes/mail-health.php'));
            Route::group([], base_path('routes/system-health.php'));
            Route::group([], base_path('routes/audit-security.php'));
            Route::group([], base_path('routes/tenant-operations.php'));
            Route::group([], base_path('routes/staff-profile-onboarding.php'));

            Route::middleware(['web', 'auth', 'active.account', 'super.admin'])
                ->post('super/broadcasts', [\App\Http\Controllers\WebPlatformBroadcastController::class, 'store'])
                ->name('super.broadcasts.store');

            Route::middleware([
                'web', 'auth', 'active.account', 'tenant', 'tenant.access',
                'tenant.onboarding.complete', \App\Http\Middleware\StaffOnly::class,
            ])->group(base_path('routes/assessment-types-admin.php'));

            Route::middleware([
                'web', 'auth', 'active.account', 'tenant', 'tenant.access',
                'tenant.onboarding.complete', \App\Http\Middleware\StaffOnly::class,
            ])->group(base_path('routes/asc-official.php'));

            Route::middleware([
                'web', 'auth', 'active.account', 'tenant', 'tenant.access',
                'tenant.onboarding.complete', \App\Http\Middleware\StaffOnly::class,
            ])->group(base_path('routes/web-exam-timetable.php'));

            Route::prefix('api/v1/staff/cbt')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-staff-cbt.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-school-communication.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-finance-results.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-academic-admin.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-native-operations.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-transfers.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-skills.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-gradebook.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-reports.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-portal-accounts.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-school-settings.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-platform-broadcasts.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-admin-staff-attendance.php'));

            Route::prefix('api/v1')
                ->group(base_path('routes/mobile-release.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->prepend([
            \App\Http\Middleware\ForceHttps::class,
        ]);

        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\MaintenanceMode::class,
            \App\Http\Middleware\RestrictCbtLanSession::class,
            \App\Http\Middleware\EnforceStrictWebRbac::class,
            \App\Http\Middleware\EnforceSchoolOpenDay::class,
            \App\Http\Middleware\InjectEduCorePremiumTheme::class,
        ]);

        $middleware->alias([
            'tenant'         => \App\Http\Middleware\IdentifyTenant::class,
            'tenant.host'    => \App\Http\Middleware\ResolveTenantFromHost::class,
            'tenant.slug'    => \App\Http\Middleware\ResolveTenantFromSlug::class,
            'tenant.access'  => \App\Http\Middleware\EnsureTenantHasApplicationAccess::class,
            'tenant.onboarding.complete' => \App\Http\Middleware\EnsureTenantOnboardingComplete::class,
            'active.account' => \App\Http\Middleware\EnsureActiveAccount::class,
            'role'           => \App\Http\Middleware\RoleMiddleware::class,
            'staff.only'     => \App\Http\Middleware\StaffOnly::class,
            'student.portal' => \App\Http\Middleware\StudentPortalAccess::class,
            'parent.portal'  => \App\Http\Middleware\ParentPortalAccess::class,
            '2fa'            => \App\Http\Middleware\RequireTwoFactor::class,
            'super.admin'    => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has expired. Please refresh and try again.'], 419);
            }

            return redirect()->route('login')
                ->withErrors(['login_id' => 'Your session expired. Please sign in again.']);
        });
    })->create();
