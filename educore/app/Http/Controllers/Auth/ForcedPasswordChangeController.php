<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AccountSessionRevoker;
use App\Services\Auth\AuthAuditLogger;
use App\Services\Auth\LoginRedirector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ForcedPasswordChangeController extends Controller
{
    public function edit(Request $request, LoginRedirector $redirector)
    {
        $user = $request->user();
        if (! $user->must_change_password) {
            return $redirector->redirectFor($user);
        }

        return view('auth.force-password-change', compact('user'));
    }

    public function update(
        Request $request,
        AccountSessionRevoker $sessions,
        AuthAuditLogger $audit,
        LoginRedirector $redirector
    ) {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                Password::min(10)->letters()->mixedCase()->numbers(),
                'max:128',
            ],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The temporary/current password is incorrect.',
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        $revoked = $sessions->revoke($user, $request->session()->getId());

        $audit->recordForUser(
            $user,
            'auth.password_change.required_completed',
            [
                'surface' => 'web',
                'api_tokens_revoked' => $revoked['api_tokens_revoked'],
                'other_web_sessions_revoked' => $revoked['sessions_revoked'],
            ],
            $request,
            null,
            $user,
        );

        $request->session()->regenerate();

        return $redirector->redirectFor($user)
            ->with('success', 'Password updated. Your account recovery is complete.');
    }
}
