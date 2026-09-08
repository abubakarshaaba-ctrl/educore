<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportCardComputeController;
use App\Http\Controllers\ReportCardController;
use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\StaffOnly;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebReportComputeRouteTest extends TestCase
{
    public function test_reports_compute_route_uses_shared_computation_controller_and_preserves_security_boundary(): void
    {
        $route = Route::getRoutes()->getByName('reports.compute');

        $this->assertNotNull($route);
        $this->assertSame('reports/compute', $route->uri());
        $this->assertSame(['POST'], $route->methods());
        $this->assertStringContainsString(ReportCardComputeController::class, $route->getActionName());
        $this->assertStringNotContainsString(ReportCardController::class.'@compute', $route->getActionName());

        $middleware = $route->middleware();
        foreach ([
            'web',
            'auth',
            'active.account',
            'tenant',
            'tenant.access',
            'tenant.onboarding.complete',
            StaffOnly::class,
            CheckModuleAccess::class,
        ] as $required) {
            $this->assertContains($required, $middleware);
        }
    }
}
