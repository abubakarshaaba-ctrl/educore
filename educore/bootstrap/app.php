<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Deployment/validation endpoints authenticate with the deploy token
            // and must remain callable even when the normal web application is in
            // maintenance mode. Their individual routes apply throttling.
            Route::group(base_path('routes/deploy.php'));

            // Replace only the legacy web report-card computation handler after
            // routes/web.php has loaded. The focused route file preserves the same
            // authenticated tenant/staff/module middleware boundary while routing
            // computation through the shared service used by the native app.
            Route::group(base_path('routes/web-report-compute.php'));

            // Focused native route files share the same bearer-authenticated
            // /api/v1 contract without making routes/api.php a monolith.
            Route::prefix('api/v1/staff/cbt')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-staff-cbt.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-skills.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-transfers.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-gradebook.php'));

            Route::prefix('api/v1')
                ->middleware(\App\Http\Middleware\AuthenticateApiToken::class)
                ->group(base_path('routes/mobile-reports.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust Cloudflare (and any other reverse proxy) so X-Forwarded-Proto/Host/IP are read correctly
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // Run on every HTTP request (outermost layer)
        $middleware->prepend([
            \App\Http\Middleware\ForceHttps::class,
        ]);

        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Runs inside the routed "web" group so route()/routeIs() are available.
        $middleware->web(append: [
            \App\Http\Middleware\BlockPublicDiagnostics::class,
            \App\Http\Middleware\MaintenanceMode::class,
            \App\Http\Middleware\RestrictCbtLanSession::class,
            \App\Http\Middleware\ProtectPublicTenantPortal::class,
            \App\Http\Middleware\EnforceTenantFormReferences::class,
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
        // CSRF token mismatch (419) — redirect to the unified login with a user-friendly message.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has expired. Please refresh and try again.'], 419);
            }

            return redirect()->route('login')
                ->withErrors(['login_id' => 'Your session expired. Please sign in again.']);
        });
    })->create();
