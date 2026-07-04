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
        // Trust Cloudflare (and any other reverse proxy) so X-Forwarded-Proto/Host/IP are read correctly
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // The login POST uses a stateless HMAC-signed token (LoginFormToken) instead of
        // the standard session-backed CSRF token. This makes it immune to Cloudflare
        // caching the login page and stripping Set-Cookie on cached responses.
        $middleware->validateCsrfTokens(except: ['login']);

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
        // CSRF token mismatch (419) — redirect to the unified login with a user-friendly message.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has expired. Please refresh and try again.'], 419);
            }

            return redirect()->route('login')
                ->withErrors(['login_id' => 'Your session expired. Please sign in again.']);
        });
    })->create();
