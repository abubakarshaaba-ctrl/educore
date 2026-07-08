<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks TENANT (school) access when the super-admin "Maintenance Mode"
 * toggle (Super Admin → Settings) is on — school admins/staff/students/
 * parents are locked out with a maintenance page while updates happen.
 *
 * It deliberately does NOT touch the public marketing site (landing page,
 * legal pages, school registration) or the super admin account/panel —
 * maintenance mode is scoped to "tenant application access", not "the
 * entire platform is unreachable".
 */
class MaintenanceMode
{
    // Matched against the request URI (Request::is), NOT route names — some
    // of these routes (e.g. the actual POST /login credential submission)
    // are intentionally unnamed, so name-based matching silently misses
    // them and blocks login itself during maintenance. Path matching is
    // robust to that regardless of whether a route happens to have a name.
    private const EXEMPT_PATHS = [
        '/',
        'login',
        'platform/login',
        'admin/login',
        'staff/login',
        'student/login',
        'parent/login',
        'logout',
        'forgot-password',
        'reset-password*',
        'get-started*',
        'privacy',
        'terms',
        'deploy/pull*',
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!PlatformSetting::valueFor('maintenance_mode', false)) {
            return $next($request);
        }

        if (Auth::check() && Auth::user()->isSuperAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('super.*') || $request->is(self::EXEMPT_PATHS)) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }
}
