<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class LoginRedirector
{
    public function redirectFor(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('super.dashboard');
        }

        if ($user->isStudent()) {
            return redirect()->route('student.portal.dashboard');
        }

        if ($user->isParent()) {
            return redirect()->route('parent.dashboard');
        }

        if ($user->isStaff()) {
            return redirect()->route('staff.portal.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
