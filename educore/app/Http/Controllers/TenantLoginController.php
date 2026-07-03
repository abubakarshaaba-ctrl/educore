<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\Auth\AuthAuditLogger;
use App\Services\Auth\LoginRedirector;
use App\Services\Auth\LoginUserResolver;
use App\Services\TenantPortal\TenantBrandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TenantLoginController extends Controller
{
    private const GENERIC_LOGIN_ERROR = 'The login details are invalid or this account cannot access this school portal.';

    public function showLogin(
        Request $request,
        string $slug,
        TenantBrandingService $branding,
        LoginRedirector $redirector
    ) {
        $tenant = $this->tenant($request);

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isTenantStaff() && (int) $user->tenant_id !== (int) $tenant->id) {
                app(AuthAuditLogger::class)->recordForTenant($tenant, 'auth.login.denied', [
                    'reason' => 'authenticated_cross_tenant_slug',
                    'actor_user_id' => $user->id,
                ], $request, 'authenticated_cross_tenant_slug', $user);
            }

            return $redirector->redirectFor(Auth::user());
        }

        return view('tenant.login', [
            'tenant' => $tenant,
            'branding' => $branding->forTenant($tenant),
        ]);
    }

    public function login(
        Request $request,
        string $slug,
        LoginUserResolver $users,
        LoginRedirector $redirector,
        AuthAuditLogger $audit
    ) {
        $tenant = $this->tenant($request);

        $validated = $request->validate([
            'login_id' => ['required', 'string', 'max:180'],
            'password' => ['required', 'string'],
            'remember' => ['nullable'],
        ]);

        $loginId = trim($validated['login_id']);
        $user = $users->resolveTenantStaff($tenant, $loginId);

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            $audit->recordForTenant($tenant, 'auth.login.denied', [
                'login_id_hash' => hash('sha256', mb_strtolower($loginId)),
                'tenant_slug' => $tenant->slug,
            ], $request, 'invalid_credentials_or_tenant_mismatch');

            return back()
                ->withErrors(['login_id' => self::GENERIC_LOGIN_ERROR])
                ->withInput(['login_id' => $loginId]);
        }

        if (!$this->canUseTenantLogin($user, $tenant)) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'tenant_slug' => $tenant->slug,
            ], $request, 'inactive_or_ineligible_account');

            return back()
                ->withErrors(['login_id' => self::GENERIC_LOGIN_ERROR])
                ->withInput(['login_id' => $loginId]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->put('tenant_slug', $tenant->slug);
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->recordForUser($user, 'auth.login.success', [
            'tenant_slug' => $tenant->slug,
            'login_surface' => 'tenant_slug',
        ], $request);

        return $redirector->redirectFor($user);
    }

    private function canUseTenantLogin($user, Tenant $tenant): bool
    {
        return (int) $user->tenant_id === (int) $tenant->id
            && !$user->isSuperAdmin()
            && $user->isTenantStaff()
            && (bool) $user->is_active
            && $user->isEmploymentActive();
    }

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->attributes->get('resolved_tenant');
        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }
}
