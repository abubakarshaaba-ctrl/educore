<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantPortal\TenantBrandingService;
use Illuminate\Http\Request;

class TenantPortalController extends Controller
{
    public function landing(Request $request, string $slug, TenantBrandingService $branding)
    {
        $tenant = $this->tenant($request);

        return view('tenant.landing', [
            'tenant' => $tenant,
            'branding' => $branding->forTenant($tenant),
        ]);
    }

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->attributes->get('resolved_tenant');
        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }
}
