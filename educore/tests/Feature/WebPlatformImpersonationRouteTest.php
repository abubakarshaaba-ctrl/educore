<?php

namespace Tests\Feature;

use App\Http\Controllers\PlatformImpersonationController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebPlatformImpersonationRouteTest extends TestCase
{
    public function test_platform_impersonation_routes_use_hardened_controller(): void
    {
        $start = Route::getRoutes()->getByName('super.impersonate');
        $stop = Route::getRoutes()->getByName('super.stop-impersonating');

        $this->assertNotNull($start);
        $this->assertNotNull($stop);
        $this->assertSame(PlatformImpersonationController::class.'@start', $start->getActionName());
        $this->assertSame(PlatformImpersonationController::class.'@stop', $stop->getActionName());

        foreach ([$start, $stop] as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('web', $middleware);
            $this->assertContains('auth', $middleware);
            $this->assertContains('active.account', $middleware);
        }
    }
}
