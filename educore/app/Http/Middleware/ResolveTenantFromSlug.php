<?php

namespace App\Http\Middleware;

use App\Services\TenantPortal\TenantAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromSlug
{
    public function __construct(private readonly TenantAccessService $tenants)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('slug');
        $tenant = $this->tenants->resolveBySlug($slug);

        if (!$this->tenants->isPubliclyAccessible($tenant)) {
            return response()
                ->view('tenant.unavailable', [
                    'message' => $this->tenants->unavailableMessage(),
                ], 404);
        }

        $request->attributes->set('resolved_tenant', $tenant);
        $request->attributes->set('resolved_tenant_slug', $tenant->slug);

        return $next($request);
    }
}
