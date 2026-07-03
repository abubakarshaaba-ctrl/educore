<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Allow camera on pages that use it; never restrict microphone/geolocation by default
        // camera=* (allow all origins) is intentionally broad so LiteSpeed/host headers don't override us
        $needsCamera = $request->routeIs('staff-attendance.my') || $request->routeIs('staff-attendance.qr');
        $permissionsPolicy = $needsCamera
            ? 'camera=*, microphone=()'
            : 'microphone=()';
        $response->headers->set('Permissions-Policy', $permissionsPolicy);
        // Feature-Policy for older Chromium / Safari fallback
        $featurePolicy = $needsCamera ? "camera 'self'" : "camera 'none'";
        $response->headers->set('Feature-Policy', $featurePolicy);

        // Only send HSTS over HTTPS so we don't break local dev
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Prevent LiteSpeed / proxy caches from caching auth pages — if they do,
        // Set-Cookie headers get stripped and the browser never gets a session cookie,
        // causing every login POST to fail with 419 CSRF token mismatch.
        $isAuthRoute = $request->routeIs(
            'login', 'admin.login', 'staff.login', 'student.login',
            'parent.login', 'portal.parent.login', 'school.register'
        );
        if ($isAuthRoute) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('X-Accel-Buffering', 'no');
        }

        return $response;
    }
}