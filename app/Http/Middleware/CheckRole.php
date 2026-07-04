<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CheckRole Middleware
 *
 * Restricts access to routes based on the authenticated user's role.
 * Super admins and school admins bypass all role checks.
 *
 * Usage in routes:
 *   ->middleware('role:accountant')
 *   ->middleware('role:accountant,admin,principal')  // any of these roles
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admins and school admins always pass
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return $next($request);
        }

        // Check if user has one of the required roles
        if (!empty($roles) && !$user->hasAnyRole($roles)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
