<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UnifiedResetPasswordNotification;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Unified "forgot password" flow for the central /login page — covers every
 * account type (tenant admin, staff, student, parent) EXCEPT super admins,
 * who change their password from inside the Super Admin panel instead
 * (Super Admin → Settings → Change Password) since they aren't tied to a
 * school and self-service reset-by-email doesn't fit their access model.
 */
class PasswordResetController extends Controller
{
    private const GENERIC_RESET_STATUS = 'If the account is eligible, a password reset link has been sent.';
    private const GENERIC_RESET_ERROR = 'The password reset link is invalid or has expired.';

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request, AuthAuditLogger $audit)
    {
        $validated = $request->validate([
            'login_id' => ['required', 'string', 'max:180'],
        ]);

        $user = $this->eligibleUser(trim($validated['login_id']));

        if ($user) {
            $token = Password::broker()->createToken($user);
            $user->notify(new UnifiedResetPasswordNotification($token));
            $audit->recordForUser($user, 'auth.password_reset.requested', ['login_surface' => 'unified'], $request);
        }

        return back()->with('status', self::GENERIC_RESET_STATUS);
    }

    public function showReset(Request $request)
    {
        return view('auth.reset-password', [
            'token' => (string) $request->route('token'),
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request, AuthAuditLogger $audit)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $email = Str::lower(trim($validated['email']));
        $user = $this->eligibleUser($email);
        $broker = Password::broker();

        if (!$user || !$broker->tokenExists($user, $validated['token'])) {
            return back()
                ->withErrors(['email' => self::GENERIC_RESET_ERROR])
                ->withInput(['email' => $email]);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        $broker->deleteToken($user);

        $audit->recordForUser($user, 'auth.password_reset.completed', ['login_surface' => 'unified'], $request);

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now sign in.');
    }

    /** Any active, non-super-admin account — matched by email, staff ID, or student ID. */
    private function eligibleUser(string $loginId): ?User
    {
        if ($loginId === '') {
            return null;
        }

        $user = filter_var($loginId, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $loginId)->first()
            : User::where('staff_id', $loginId)->orWhere('student_id', $loginId)->first();

        if (!$user || $user->is_super_admin || !$user->is_active) {
            return null;
        }

        return $user;
    }
}
