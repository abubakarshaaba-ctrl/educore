<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CheckModuleAccess
 *
 * Applied to ALL authenticated staff routes.
 * Uses User::canAccessRoute() to check if the current route
 * is allowed for the user's role.
 *
 * If blocked → 403 with role label in message.
 */
class CheckModuleAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if (!$user) return redirect()->route('login');

        // Portal users should never reach staff routes
        if ($user->isStudent() || $user->isParent()) {
            return redirect()->route(
                $user->isStudent() ? 'student.portal.dashboard' : 'parent.dashboard'
            );
        }

        // Super admin bypasses everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        // Always allow dashboard and logout
        if (!$routeName || str_starts_with($routeName, 'dashboard') || $routeName === 'logout') {
            return $next($request);
        }

        if (!$user->canAccessRoute($routeName)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'Access denied.',
                    'role'    => $user->roleLabel(),
                    'route'   => $routeName,
                ], 403);
            }

            abort(403, "Your role ({$user->roleLabel()}) does not have access to this section.");
        }

        return $next($request);
    }
}
