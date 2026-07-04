<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Support\LoginFormToken;
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
 * The login POST is deliberately excluded from Laravel's CSRF middleware
 * (see bootstrap/app.php) and instead protected by a stateless HMAC-signed
 * token (LoginFormToken). This makes authentication immune to Cloudflare
 * caching the login page and stripping Set-Cookie / invalidating sessions.
 */
class LoginController extends Controller
{
    public function showLogin(LoginRedirector $redirector)
    {
        if (Auth::check()) {
            return $redirector->redirectFor(Auth::user());
        }

        return view('auth.login', [
            'loginToken' => LoginFormToken::generate(),
        ]);
    }

    public function login(
        Request $request,
        LoginUserResolver $users,
        LoginRedirector $redirector,
        AuthAuditLogger $audit,
        TenantAccessService $tenantAccess
    ) {
        // Verify our stateless signed token (replaces CSRF for the login form).
        if (!LoginFormToken::verify((string) $request->input('_login_token', ''))) {
            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'The form expired. Please try again.'])
                ->withInput(['login_id' => $request->login_id]);
        }

        $request->validate([
            'login_id' => ['required', 'string', 'max:180'],
            'password'  => ['required', 'string'],
        ]);

        $loginId = trim($request->login_id);
        $user    = $users->resolveGlobal($loginId);

        if (!$user || !Hash::check($request->password, $user->password)) {
            if ($user) {
                $audit->recordForUser($user, 'auth.login.denied', [
                    'login_surface' => 'unified',
                ], $request, 'invalid_credentials');
            }

            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'Credentials not found. Check your ID or email and password.'])
                ->withInput(['login_id' => $loginId]);
        }

        if (!$user->is_active || ($user->isTenantStaff() && !$user->isEmploymentActive())) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => 'unified',
            ], $request, 'inactive_or_ineligible_account');

            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'Your account has been deactivated. Contact the school.']);
        }

        // ── Super admin ────────────────────────────────────────────────────
        if ($user->is_super_admin) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->save();
            $audit->recordForUser($user, 'auth.login.success', [
                'login_surface' => 'unified',
            ], $request);

            // Persist authentication before the browser is sent to the dashboard.
            // This prevents redirect loops when a reverse proxy/CDN delays or drops
            // the session write until after the transition response is returned.
            $request->session()->save();

            return $this->loginResponse(route('super.dashboard'));
        }

        // ── School user (admin / staff / teacher / student / parent) ───────
        $tenant = $user->tenant;

        if (!$tenant) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => 'unified',
            ], $request, 'missing_tenant');

            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'Account not linked to any school.']);
        }

        $decision = $tenantAccess->applicationAccess($tenant);
        if ($decision->isDenied()) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => 'unified',
                'state'         => $decision->state,
            ], $request, 'tenant_' . $decision->state);

            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'This school account is currently unavailable. Contact support.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id',   $tenant->id);
        $request->session()->put('tenant_slug',  $tenant->slug);
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->recordForUser($user, 'auth.login.success', [
            'login_surface' => 'unified',
        ], $request);

        // Force the regenerated session ID and tenant context to storage before
        // rendering the navigation page. Without this, the next request can arrive
        // before the database session has been committed and auth redirects to login.
        $request->session()->save();

        return $this->loginResponse($redirector->redirectFor($user)->getTargetUrl());
    }

    /**
     * Return a 200 HTML response that navigates to $url via JS + meta-refresh.
     *
     * Why: Cloudflare strips Set-Cookie headers from 302 redirect responses
     * (even with Cache-Control: no-store). Returning a 200 body ensures the
     * browser always receives and stores the session cookie before navigating.
     */
    private function loginResponse(string $url): \Illuminate\Http\Response
    {
        return response()
            ->view('auth.redirecting', ['url' => $url])
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
                'Pragma'        => 'no-cache',
                'Expires'       => 'Thu, 01 Jan 1970 00:00:00 GMT',
                'Vary'          => 'Cookie',
            ]);
    }

    public function logout(Request $request, AuthAuditLogger $audit)
    {
        if ($user = Auth::user()) {
            $audit->recordForUser($user, 'auth.logout', [
                'tenant_slug' => $user->tenant?->slug,
            ], $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
