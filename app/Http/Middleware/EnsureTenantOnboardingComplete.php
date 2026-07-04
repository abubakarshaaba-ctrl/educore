<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Auth\AuthAuditLogger;
use App\Services\TenantOnboardingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantOnboardingComplete
{
    public function __construct(
        private readonly TenantOnboardingService $onboarding,
        private readonly AuthAuditLogger $audit
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $tenant = $request->attributes->get('current_tenant') ?: $user->tenant;
        if (!$tenant instanceof Tenant) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $next($request);
        }

        $status = $this->onboarding->status($tenant);
        view()->share('tenantOnboardingStatus', $status);

        // One-time activation gate. A tenant only reaches STATUS_ACTIVE once the platform
        // has activated it, which itself requires onboarding blocking items to be cleared —
        // so an active school has already completed onboarding. Never re-lock an active school
        // out of the whole ERP because a step later reads incomplete (e.g. the current term
        // lapses during a session rollover). The shared status above still lets the UI nudge
        // them to resolve it. Only not-yet-active (pending) tenants are held in onboarding.
        if ($tenant->isActive()) {
            return $next($request);
        }

        if ($status->complete || $this->isVerifiedImpersonation($request, $tenant)) {
            if (!$status->complete && $this->isVerifiedImpersonation($request, $tenant)) {
                $this->audit->recordForTenant($tenant, 'tenant.onboarding.impersonation_bypass', [
                    'route' => $request->route()?->getName(),
                    'blocking_count' => count($status->blocking_items),
                ], $request, 'verified_super_admin_impersonation', $user);
            }

            return $next($request);
        }

        // Onboarding wizard removed — if a tenant is not yet active and not yet complete,
        // send them to the dashboard. The super admin can activate them manually.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'School setup is incomplete.',
                'next_step' => $status->next_step,
                'blocking_items' => $status->blocking_items,
            ], 423);
        }

        // No longer redirect to the onboarding wizard. Send to dashboard; the super admin
        // activates new schools directly via the Super Admin panel.
        return redirect()->route('dashboard');
    }

    private function isExempt(Request $request): bool
    {
        $routeName = (string) $request->route()?->getName();

        if ($routeName === '' || str_starts_with($routeName, 'tenant.onboarding.')) {
            return true;
        }

        if (in_array($routeName, [
            'logout',
            'tenant.account-status',
            'super.stop-impersonating',
        ], true)) {
            return true;
        }

        foreach (['billing', 'renew', 'subscription', 'support'] as $token) {
            if (str_contains($routeName, $token)) {
                return true;
            }
        }

        return false;
    }

    private function isVerifiedImpersonation(Request $request, Tenant $tenant): bool
    {
        return $request->session()->has('super_admin_id')
            && (int) $request->session()->get('impersonating_tenant_id') === (int) $tenant->id;
    }
}
