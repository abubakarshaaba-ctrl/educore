<?php

namespace Tests\Feature;

use App\Http\Controllers\PlatformBillingMutationController;
use App\Http\Controllers\PlatformTenantRemovalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PlatformRouteConvergenceTest extends TestCase
{
    public function test_web_mutation_routes_resolve_to_shared_service_bridges(): void
    {
        $this->assertSame(
            PlatformBillingMutationController::class.'@generate',
            Route::getRoutes()->getByName('super.billing.generate')?->getActionName()
        );
        $this->assertSame(
            PlatformBillingMutationController::class.'@paid',
            Route::getRoutes()->getByName('super.billing.paid')?->getActionName()
        );
        $this->assertSame(
            PlatformTenantRemovalController::class.'@destroy',
            Route::getRoutes()->getByName('super.tenant.destroy')?->getActionName()
        );
    }

    public function test_mobile_tenant_delete_resolves_to_hardened_removal_bridge(): void
    {
        $request = Request::create('/api/v1/platform/tenants/123', 'DELETE');
        $route = Route::getRoutes()->match($request);

        $this->assertSame(PlatformTenantRemovalController::class.'@destroy', $route->getActionName());
    }
}
