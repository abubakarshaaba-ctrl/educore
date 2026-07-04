<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthAuditLogger;
use App\Services\Auth\LoginRedirector;
use App\Services\Auth\LoginUserResolver;
use App\Services\TenantAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Unified login for ALL user types:
 *   super admin · tenant admin · staff · teacher · student · parent
 *
 * Uses standard Laravel session CSRF. After successful authentication the
 * response is a 200 HTML handoff page (auth.redirecting) instead of a 302 —
 * the proxy in front of the app has been observed dropping Set-Cookie from
 * 302 responses, which loses the freshly regenerated session.
 */
class LoginController extends Controller
{
    public function showLogin(LoginRedirector $redirector)
    {
        if (Auth::check()) {
            return $redirector->redirectFor(Auth::user());
        }

        return view('auth.login');
    }

    public function login(
        Request $request,
        LoginUserResolver $users,
        LoginRedirector $redirector,
        AuthAuditLogger $audit,
        TenantAccessService $tenantAccess
    ) {
        $request->validate([
            'login_id' => ['required', 'string', 'max:180'],
            'password' => ['required', 'string'],
        ]);

        $loginId = trim($request->login_id);
        $user    = $users->resolveGlobal($loginId);

        if (!$user || !Hash::check($request->password, $user->password)) {
            if ($user) {
                $audit->recordForUser($user, 'auth.login.denied', ['login_surface' => 'unified'], $request, 'invalid_credentials');
            }

            return back()
                ->withErrors(['login_id' => 'These credentials do not match our records.'])
                ->withInput(['login_id' => $loginId]);
        }

        if (!$user->is_active) {
            $audit->recordForUser($user, 'auth.login.denied', ['login_surface' => 'unified'], $request, 'inactive_account');

            return back()->withErrors(['login_id' => 'Your account has been deactivated. Contact the school.']);
        }

        if ($user->isTenantStaff() && !$user->isEmploymentActive()) {
            $audit->recordForUser($user, 'auth.login.denied', ['login_surface' => 'unified'], $request, 'inactive_employment');

            return back()->withErrors(['login_id' => 'Your employment is no longer active. Contact the school.']);
        }

        // ── Super admin ────────────────────────────────────────────────────
        if ($user->is_super_admin) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $request->session()->save();

            $user->forceFill(['last_login_at' => now()])->save();
            $audit->recordForUser($user, 'auth.login.success', ['login_surface' => 'unified'], $request);

            return $this->handoff(route('super.dashboard'));
        }

        // ── School user (admin / staff / teacher / student / parent) ───────
        $tenant = $user->tenant;

        if (!$tenant) {
            $audit->recordForUser($user, 'auth.login.denied', ['login_surface' => 'unified'], $request, 'no_tenant');

            return back()->withErrors(['login_id' => 'Account is not linked to any school. Contact support.']);
        }

        $decision = $tenantAccess->applicationAccess($tenant);

        if ($decision->isDenied()) {
            $audit->recordForUser($user, 'auth.login.denied', ['login_surface' => 'unified', 'state' => $decision->state], $request, 'tenant_' . $decision->state);

            return back()->withErrors(['login_id' => 'This school account is currently unavailable. Contact support.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id',   $tenant->id);
        $request->session()->put('tenant_slug',  $tenant->slug);
        $request->session()->save();

        $user->forceFill(['last_login_at' => now()])->save();
        $audit->recordForUser($user, 'auth.login.success', ['login_surface' => 'unified'], $request);

        return $this->handoff($redirector->redirectFor($user)->getTargetUrl());
    }

    /**
     * 200 HTML handoff instead of a 302 redirect — the proxy drops Set-Cookie
     * on 302 responses, which would lose the regenerated session cookie.
     */
    private function handoff(string $url): \Illuminate\Http\Response
    {
        return response()->view('auth.redirecting', ['url' => $url]);
    }

    public function logout(Request $request, AuthAuditLogger $audit)
    {
        if ($user = Auth::user()) {
            $audit->recordForUser($user, 'auth.logout', ['tenant_slug' => $user->tenant?->slug], $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
