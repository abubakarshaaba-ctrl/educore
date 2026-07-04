<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Auth\AuthAuditLogger;
use App\Services\TenantAccessDecision;
use App\Services\TenantAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasApplicationAccess
{
    public function __construct(
        private readonly TenantAccessService $access,
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

        $decision = $this->access->applicationAccess($tenant);
        $this->shareDecision($decision);

        if ($this->isExempt($request)) {
            return $next($request);
        }

        if ($decision->isDenied() && $this->isVerifiedImpersonation($request, $tenant)) {
            $this->audit->recordForTenant($tenant, 'tenant.access.impersonation_bypass', [
                'state' => $decision->state,
                'route' => $request->route()?->getName(),
            ], $request, 'verified_super_admin_impersonation', $user);

            return $next($request);
        }

        if ($decision->isDenied()) {
            $this->auditBlockedAccess($tenant, $decision, $request);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $this->access->genericUnavailableMessage(),
                ], 403);
            }

            return redirect()->route('tenant.account-status');
        }

        return $next($request);
    }

    private function shareDecision(TenantAccessDecision $decision): void
    {
        view()->share('tenantAccessDecision', $decision);

        if ($decision->isWarning()) {
            view()->share('tenantAccessBanner', [
                'title' => $decision->title(),
                'message' => $decision->message,
                'state' => $decision->state,
                'expires_at' => $decision->expiresAt,
            ]);
        }
    }

    private function isExempt(Request $request): bool
    {
        $routeName = (string) $request->route()?->getName();

        if ($routeName === '') {
            return false;
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

    private function auditBlockedAccess(Tenant $tenant, TenantAccessDecision $decision, Request $request): void
    {
        $payload = [
            'state' => $decision->state,
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
        ];

        $this->audit->recordForTenant($tenant, 'tenant.access.blocked', $payload, $request, $decision->state, Auth::user());

        $stateAction = match ($decision->state) {
            TenantAccessDecision::STATE_EXPIRED => 'tenant.access.expired',
            TenantAccessDecision::STATE_SUSPENDED => 'tenant.access.suspended',
            TenantAccessDecision::STATE_INACTIVE, TenantAccessDecision::STATE_MISSING => 'tenant.access.inactive',
            default => null,
        };

        if ($stateAction) {
            $this->audit->recordForTenant($tenant, $stateAction, $payload, $request, $decision->state, Auth::user());
        }
    }
}
