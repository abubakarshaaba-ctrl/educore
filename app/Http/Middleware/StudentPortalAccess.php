<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * StudentPortalAccess
 *
 * Ensures only users with role='student' can access the student portal.
 */
class StudentPortalAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->isStudent()) {
            // Staff trying to access student portal → back to dashboard
            if ($user->isStaff() || $user->isSuperAdmin()) {
                return redirect()->route('dashboard');
            }
            // Parents → parent portal
            if ($user->isParent()) {
                return redirect()->route('parent.dashboard');
            }
            abort(403, 'Student portal access only.');
        }

        return $next($request);
    }
}
