<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ParentPortalAccess
 *
 * Ensures only users with role='parent' can access the parent portal.
 */
class ParentPortalAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->isParent()) {
            if ($user->isStudent()) {
                return redirect()->route('student.portal.dashboard');
            }
            if ($user->isStaff() || $user->isSuperAdmin()) {
                return redirect()->route('dashboard');
            }
            abort(403, 'Parent portal access only.');
        }

        return $next($request);
    }
}
