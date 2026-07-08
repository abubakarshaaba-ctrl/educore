<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks tenant-facing access when the super-admin "Maintenance Mode"
 * toggle (Super Admin → Settings) is on. Super admins and a small set
 * of exempt routes (login, deploy, health check) remain reachable so
 * the platform can still be administered/recovered.
 */
class MaintenanceMode
{
    private const EXEMPT_ROUTE_NAMES = [
        'login',
        'login.submit',
        'logout',
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
