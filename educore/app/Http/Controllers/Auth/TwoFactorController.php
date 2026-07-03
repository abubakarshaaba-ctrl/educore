<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFactorController extends Controller
{
    // ── Setup: show QR code ────────────────────────────────────────────
    public function setup(Request $request)
    {
        $user   = Auth::user();
        $google = app('pragmarx.google2fa');

        // Generate a new secret only if not already set
        if (!$user->two_factor_secret) {
            $secret = $google->generateSecretKey();
            $user->forceFill(['two_factor_secret' => $secret])->save();
        }

        $qrUrl = $google->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret
        );

        return view('auth.two-factor.setup', compact('qrUrl'));
    }

    // ── Setup: confirm the first code ────────────────────────────────
    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user   = Auth::user();
        $google = app('pragmarx.google2fa');

        if (!$google->verifyKey($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'The code is incorrect. Try again.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return redirect()->route('profile.show')->with('success', 'Two-factor authentication enabled.');
    }

    // ── Disable 2FA ───────────────────────────────────────────────────
    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        Auth::user()->forceFill([
            'two_factor_secret'       => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    // ── Challenge: show form ───────────────────────────────────────────
    public function challenge()
    {
        return view('auth.two-factor.challenge');
    }

    // ── Challenge: verify code ────────────────────────────────────────
    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user   = Auth::user();
        $google = app('pragmarx.google2fa');

        if (!$google->verifyKey($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'Incorrect code. Please try again.']);
        }

        // Mark the challenge as passed for this session
        $request->session()->put('2fa_verified', true);

        return redirect()->intended(route('super.dashboard'));
    }
}
