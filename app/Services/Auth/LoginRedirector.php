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
            $user->isStaff() => 'staff.portal.dashboard',
            default => 'dashboard',
        };

        // Generate a relative URL so tenant users remain on the hostname that
        // issued their session cookie. An absolute route URL would use APP_URL,
        // move the browser to the central domain, and make a host-only tenant
        // session cookie unavailable immediately after login.
        return redirect()->to(route($route, [], false));
    }
}
