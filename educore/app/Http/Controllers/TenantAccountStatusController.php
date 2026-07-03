<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantAccessService;
use App\Services\TenantPortal\TenantBrandingService;
use Illuminate\Http\Request;

class TenantAccountStatusController extends Controller
{
    public function show(
        Request $request,
        TenantAccessService $access,
        TenantBrandingService $branding
    ) {
        $tenant = $request->attributes->get('resolved_tenant')
            ?: $request->attributes->get('current_tenant')
            ?: $request->user()?->tenant;
        abort_unless($tenant instanceof Tenant, 404);

        $decision = $access->applicationAccess($tenant);

        return view('tenant.account-status', [
            'tenant' => $tenant,
            'branding' => $branding->forTenant($tenant),
            'decision' => $decision,
        ]);
    }
}
