<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $freshUser = $user->fresh();
        $inactiveAccount = !$freshUser || !$freshUser->is_active;
        $inactiveTenantStaff = $freshUser
            && $freshUser->isTenantStaff()
            && !$freshUser->isEmploymentActive();

        if ($inactiveAccount || $inactiveTenantStaff) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account is inactive. Please sign in again or contact the school.',
                ], 403);
            }

            return redirect()
                ->route('login')
                ->withErrors(['login_id' => 'Your account is inactive. Contact the school.']);
        }

        Auth::setUser($freshUser);

        return $next($request);
    }
}
