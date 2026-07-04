<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Support\LoginFormToken;
use App\Models\Tenant;
use App\Services\Auth\AuthAuditLogger;
use App\Services\Auth\LoginRedirector;
use App\Services\Auth\LoginUserResolver;
use App\Services\TenantAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Single authentication controller for the EduCore platform domain and all
 * tenant school hosts. Host middleware resolves the tenant before this
 * controller runs; the same Blade page and session pipeline are used everywhere.
 */
class LoginController extends Controller
{
    public function showLogin(Request $request, LoginRedirector $redirector)
    {
        if (Auth::check()) {
            return $redirector->redirectFor(Auth::user());
        }

        return view('auth.login', [
            'loginToken' => LoginFormToken::generate(),
            'tenant' => $this->resolvedTenant($request),
            'loginAction' => url('/login'),
        ]);
    }

    public function login(
        Request $request,
        LoginUserResolver $users,
        LoginRedirector $redirector,
        AuthAuditLogger $audit,
        TenantAccessService $tenantAccess
    ) {
        if (!LoginFormToken::verify((string) $request->input('_login_token', ''))) {
            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'The form expired. Please try again.'])
                ->withInput(['login_id' => $request->login_id]);
        }

        $request->validate([
            'login_id' => ['required', 'string', 'max:180'],
            'password' => ['required', 'string'],
        ]);

        $loginId = trim((string) $request->login_id);
        $tenant = $this->resolvedTenant($request);
        $surface = $tenant ? 'tenant_host' : 'unified';
        $user = $tenant
            ? $users->resolveTenantUser($tenant, $loginId)
            : $users->resolveGlobal($loginId);

        if (!$user || !Hash::check((string) $request->password, $user->password)) {
            if ($user) {
                $audit->recordForUser($user, 'auth.login.denied', [
                    'host' => $request->getHost(),
                    'login_surface' => $surface,
                ], $request, 'invalid_credentials');
            } elseif ($tenant) {
                $audit->recordForTenant($tenant, 'auth.login.denied', [
                    'login_id_hash' => hash('sha256', mb_strtolower($loginId)),
                    'host' => $request->getHost(),
                    'login_surface' => $surface,
                ], $request, 'invalid_credentials_or_tenant_mismatch');
            }

            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'Credentials not found. Check your ID or email and password.'])
                ->withInput(['login_id' => $loginId]);
        }

        if ($tenant && ($user->isSuperAdmin() || (int) $user->tenant_id !== (int) $tenant->id)) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'host' => $request->getHost(),
                'login_surface' => $surface,
            ], $request, 'tenant_mismatch');

            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'This account cannot access this school portal.'])
                ->withInput(['login_id' => $loginId]);
        }

        if (!$user->is_active || ($user->isTenantStaff() && !$user->isEmploymentActive())) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'host' => $request->getHost(),
                'login_surface' => $surface,
            ], $request, 'inactive_or_ineligible_account');

            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'Your account has been deactivated. Contact the school.'])
                ->withInput(['login_id' => $loginId]);
        }

        if ($user->isSuperAdmin()) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->save();
            $audit->recordForUser($user, 'auth.login.success', [
                'host' => $request->getHost(),
                'login_surface' => $surface,
            ], $request);
            $request->session()->save();

            return $this->loginResponse(route('super.dashboard'));
        }

        $userTenant = $tenant ?: $user->tenant;
        if (!$userTenant) {
            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'Account not linked to any school.']);
        }

        $decision = $tenantAccess->applicationAccess($userTenant);
        if ($decision->isDenied()) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'host' => $request->getHost(),
                'login_surface' => $surface,
                'state' => $decision->state,
            ], $request, 'tenant_' . $decision->state);

            return back()
                ->with('loginToken', LoginFormToken::generate())
                ->withErrors(['login_id' => 'This school account is currently unavailable. Contact support.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $userTenant->id);
        $request->session()->put('tenant_slug', $userTenant->slug);
        $request->session()->put('tenant_host', $request->getHost());
        $user->forceFill(['last_login_at' => now()])->save();

        $audit->recordForUser($user, 'auth.login.success', [
            'host' => $request->getHost(),
            'login_surface' => $surface,
        ], $request);

        $request->session()->save();

        return $this->loginResponse($redirector->redirectFor($user)->getTargetUrl());
    }

    private function resolvedTenant(Request $request): ?Tenant
    {
        $tenant = $request->attributes->get('resolved_tenant');
        return $tenant instanceof Tenant ? $tenant : null;
    }

    private function loginResponse(string $url): \Illuminate\Http\Response
    {
        return response()
            ->view('auth.redirecting', ['url' => $url])
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
                'Pragma' => 'no-cache',
                'Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT',
                'Vary' => 'Cookie',
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

        return redirect('/login');
    }
}
