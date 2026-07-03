<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Run on every HTTP request (outermost layer)
        $middleware->prepend([
            \App\Http\Middleware\ForceHttps::class,
        ]);

        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // CSRF token mismatch (419) — redirect back to login with a user-friendly message
        // rather than showing the bare Laravel 419 page.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has expired. Please refresh and try again.'], 419);
            }

            // Attempt to redirect back to the originating login surface.
            $previous = url()->previous();
            $fallback = route('login');

            // Map login paths to their named routes so the user lands on the right form.
            $loginMap = [
                '/admin/login'   => route('admin.login'),
                '/staff/login'   => route('staff.login'),
                '/student/login' => route('student.login'),
                '/parent/login'  => route('parent.login'),
                '/portal/login'  => route('portal.parent.login'),
            ];

            $target = $fallback;
            foreach ($loginMap as $path => $namedRoute) {
                if (str_contains($previous, $path)) {
                    $target = $namedRoute;
                    break;
                }
            }

            return redirect($target)
                ->withErrors(['login_id' => 'Your session expired. Please sign in again.']);
        });
    })->create();
