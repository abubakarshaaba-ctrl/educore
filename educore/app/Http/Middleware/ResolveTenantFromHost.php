<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Auth\AuthAuditLogger;
use App\Services\TenantAccessService;
use App\Services\TenantHostResolution;
use App\Services\TenantHostResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromHost
{
    public function __construct(
        private readonly TenantHostResolver $hosts,
        private readonly TenantAccessService $access,
        private readonly AuthAuditLogger $audit
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $resolution = $this->hosts->resolve($request);

        if (!$resolution->isTenant()) {
            return $this->unavailable();
        }

        $tenant = $resolution->tenant;
        $request->attributes->set('tenant_host_resolution', $resolution);
        $request->attributes->set('resolved_tenant', $tenant);
        $request->attributes->set('resolved_tenant_slug', $tenant->slug);
        $request->attributes->set('current_tenant', $tenant);
        view()->share('currentTenant', $tenant);

        if (!$this->isAuthenticatedTenantMatch($request, $tenant)) {
            $this->audit->recordForTenant($tenant, 'tenant.host.auth_mismatch', [
                'host' => $resolution->host,
                'type' => $resolution->type,
                'actor_user_id' => Auth::id(),
            ], $request, 'authenticated_user_tenant_mismatch', Auth::user());

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')
                ->withErrors(['login_id' => 'Please sign in to this school portal.']);
        }

        if (!$this->routeAllowsUnavailableTenant($request) && !$this->routeAllowsPublicAdmission($request, $tenant)) {
            $decision = $this->access->applicationAccess($tenant);
            if ($decision->isDenied()) {
                $this->audit->recordForTenant($tenant, 'tenant.host.denied', [
                    'host' => $resolution->host,
                    'type' => $resolution->type,
                    'state' => $decision->state,
                    'route' => $request->route()?->getName(),
                ], $request, $decision->state, Auth::user());

                return $this->unavailable();
            }
        }

        $this->audit->recordForTenant($tenant, 'tenant.host.resolved', [
            'host' => $resolution->host,
            'type' => $resolution->type,
            'route' => $request->route()?->getName(),
        ], $request, null, Auth::user());

        return $next($request);
    }

    private function routeAllowsUnavailableTenant(Request $request): bool
    {
        return (string) $request->route()?->getName() === 'tenant.host.account-status';
    }

    private function routeAllowsPublicAdmission(Request $request, Tenant $tenant): bool
    {
        $routeName = (string) $request->route()?->getName();

        if (!str_starts_with($routeName, 'tenant.host.apply')) {
            return false;
        }

        return $tenant->isPublicPortalAvailable();
    }

    private function isAuthenticatedTenantMatch(Request $request, Tenant $tenant): bool
    {
        $user = Auth::user();

        if (!$user || $user->isSuperAdmin()) {
            return true;
        }

        if ($request->session()->has('super_admin_id')
            && (int) $request->session()->get('impersonating_tenant_id') === (int) $tenant->id) {
            return true;
        }

        return !($user->isTenantStaff() && (int) $user->tenant_id !== (int) $tenant->id);
    }

    private function unavailable(): Response
    {
        return response()->view('tenant.unavailable', [
            'message' => 'This school portal is currently unavailable. Please contact the school administration.',
        ], 404);
    }
}
