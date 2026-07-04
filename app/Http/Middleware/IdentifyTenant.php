<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthAuditLogger;
use App\Services\TenantHostResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's tenant for tenant-aware routes.
 *
 * Availability and subscription policy are enforced by the separate
 * EnsureTenantHasApplicationAccess middleware where operational access is
 * required.
 */
class IdentifyTenant
{
    public function __construct(
        private readonly TenantHostResolver $hosts,
        private readonly AuthAuditLogger $audit
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->is_super_admin) {
            return $next($request);
        }

        if (!$user || !$user->tenant_id) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is not linked to any school.']);
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['email' => 'School account not found.']);
        }

        $hostResolution = $this->hosts->resolve($request);
        if ($hostResolution->isTenant()
            && (int) $hostResolution->tenant->id !== (int) $tenant->id
            && !$this->isVerifiedImpersonation($request, $hostResolution->tenant->id)) {
            $this->audit->recordForTenant($hostResolution->tenant, 'tenant.host.auth_mismatch', [
                'host' => $hostResolution->host,
                'expected_tenant_id' => $hostResolution->tenant->id,
                'authenticated_tenant_id' => $tenant->id,
            ], $request, 'authenticated_user_tenant_mismatch', $user);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')
                ->withErrors(['login_id' => 'Please sign in to this school portal.']);
        }

        $request->attributes->set('current_tenant', $tenant);
        view()->share('currentTenant', $tenant);

        return $next($request);
    }

    private function isVerifiedImpersonation(Request $request, int $tenantId): bool
    {
        return $request->session()->has('super_admin_id')
            && (int) $request->session()->get('impersonating_tenant_id') === $tenantId;
    }
}
