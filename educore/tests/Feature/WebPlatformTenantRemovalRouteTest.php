<?php

namespace Tests\Feature;

use App\Http\Controllers\PlatformTenantRemovalController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebPlatformTenantRemovalRouteTest extends TestCase
{
    public function test_web_school_removal_resolves_to_hardened_shared_service_bridge(): void
    {
        $route = Route::getRoutes()->getByName('super.tenant.destroy');

        $this->assertNotNull($route);
        $this->assertSame(PlatformTenantRemovalController::class.'@destroy', $route->getActionName());
        $middleware = $route->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('active.account', $middleware);
        $this->assertContains('super.admin', $middleware);
    }
}
