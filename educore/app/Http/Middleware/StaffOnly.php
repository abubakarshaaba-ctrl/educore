<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * StaffOnly Middleware
 *
 * Blocks the main staff dashboard from student and parent accounts.
 * Students → redirected to student portal
 * Parents  → redirected to parent portal
 */
class StaffOnly
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isStudent()) {
            return redirect()->route('student.portal.dashboard');
        }

        if ($user->isParent()) {
            return redirect()->route('parent.dashboard');
        }

        return $next($request);
    }
}
