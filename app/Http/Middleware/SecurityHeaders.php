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

        // Never let any proxy (Cloudflare, LiteSpeed) cache Laravel-generated responses.
        // Caching strips Set-Cookie headers → browser never gets session/CSRF cookie → 419.
        // Static assets bypass this middleware entirely (served directly by web server).
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}