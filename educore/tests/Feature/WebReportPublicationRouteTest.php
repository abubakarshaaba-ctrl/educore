<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportCardPublicationController;
use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\StaffOnly;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebReportPublicationRouteTest extends TestCase
{
    public function test_report_publication_routes_use_shared_lifecycle_controller_and_preserve_security_boundary(): void
    {
        foreach ([
            'reports.publish' => 'publish',
            'reports.unpublish' => 'unpublish',
        ] as $routeName => $method) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertSame('reports/'.$method, $route->uri());
            $this->assertSame(['POST'], $route->methods());
            $this->assertSame(
                ReportCardPublicationController::class.'@'.$method,
                $route->getActionName(),
            );
            $this->assertStringNotContainsString(
                ReportCardController::class.'@'.$method,
                $route->getActionName(),
            );

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
}
