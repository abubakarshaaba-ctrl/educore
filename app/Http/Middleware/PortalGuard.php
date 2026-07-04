<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PortalGuard — ensures the login page detects returning portal users
 * and sends them to the right dashboard without re-entering the staff area.
 *
 * This middleware is applied to the login route.
 */
class PortalGuard
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::check()) {
            $user = Auth::user();
            return match (true) {
                $user->isStudent()  => redirect()->route('student.portal.dashboard'),
                $user->isParent()   => redirect()->route('parent.dashboard'),
                $user->isSuperAdmin() => redirect()->route('super.dashboard'),
                default             => redirect()->route('dashboard'),
            };
        }
        return $next($request);
    }
}
