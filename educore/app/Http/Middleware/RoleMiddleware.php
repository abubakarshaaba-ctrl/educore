<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RoleMiddleware
 *
 * Blocks access if user's role is not in the allowed list.
 * Usage: ->middleware('role:admin,principal,teacher')
 *
 * Register in bootstrap/app.php:
 *   $middleware->alias(['role' => \App\Http\Middleware\RoleMiddleware::class]);
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admins and school admins bypass all role restrictions
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return $next($request);
        }

        if (!empty($roles) && !$user->hasAnyRole($roles)) {
            abort(403, "Access denied. Your role ({$user->roleLabel()}) cannot access this page.");
        }

        return $next($request);
    }
}
