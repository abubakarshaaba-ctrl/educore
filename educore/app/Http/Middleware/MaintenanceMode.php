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
    private const EXEMPT_ROUTE_NAMES = [
        'home',
        'legal.privacy',
        'legal.terms',
        'login',
        'login.submit',
        'logout',
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
        'school.register',
        'school.register.post',
        'deploy.pull',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!PlatformSetting::valueFor('maintenance_mode', false)) {
            return $next($request);
        }

        if (Auth::check() && Auth::user()->isSuperAdmin()) {
            return $next($request);
        }

        if ($request->is('up') || $request->routeIs('super.*') || $request->routeIs(self::EXEMPT_ROUTE_NAMES)) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }
}
