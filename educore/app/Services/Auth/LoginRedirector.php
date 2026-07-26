<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class LoginRedirector
{
    public function redirectFor(User $user): RedirectResponse
    {
        $route = match (true) {
            $user->isSuperAdmin() => 'super.dashboard',
            $user->isStudent() => 'student.portal.dashboard',
            $user->isParent() => 'parent.dashboard',
            // Admin-tier staff go straight to the full school dashboard —
            // only teachers/other limited-access staff land on the
            // self-service portal dashboard (matches DashboardController's
            // own canAccessExactModule('students') gate, so this never
            // sends someone to a page that would just redirect them away).
            $user->isStaff() && !$user->canAccessExactModule('students') => 'staff.portal.dashboard',
            default => 'dashboard',
        };

        // Generate a relative URL so tenant users remain on the hostname that
        // issued their session cookie. An absolute route URL would use APP_URL,
        // move the browser to the central domain, and make a host-only tenant
        // session cookie unavailable immediately after login.
        return redirect()->to(route($route, [], false));
    }
}
