<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportCardPdfController;
use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\StaffOnly;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebReportPdfRouteTest extends TestCase
{
    public function test_staff_report_pdf_route_uses_shared_read_only_renderer_and_preserves_security_boundary(): void
    {
        $route = Route::getRoutes()->getByName('reports.pdf');

        $this->assertNotNull($route);
        $this->assertSame('reports/pdf/{student}', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertContains('POST', $route->methods());
        $this->assertStringContainsString(ReportCardPdfController::class, $route->getActionName());
        $this->assertStringNotContainsString(ReportCardController::class.'@pdf', $route->getActionName());

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
