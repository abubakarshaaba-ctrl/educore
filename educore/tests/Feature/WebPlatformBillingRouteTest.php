<?php

namespace Tests\Feature;

use App\Http\Controllers\PlatformBillingMutationController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebPlatformBillingRouteTest extends TestCase
{
    public function test_web_platform_billing_mutations_resolve_to_shared_service_bridge(): void
    {
        $generate = Route::getRoutes()->getByName('super.billing.generate');
        $paid = Route::getRoutes()->getByName('super.billing.paid');

        $this->assertNotNull($generate);
        $this->assertNotNull($paid);
        $this->assertSame(PlatformBillingMutationController::class.'@generate', $generate->getActionName());
        $this->assertSame(PlatformBillingMutationController::class.'@paid', $paid->getActionName());

        foreach ([$generate, $paid] as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware);
            $this->assertContains('active.account', $middleware);
            $this->assertContains('super.admin', $middleware);
        }
    }
}
