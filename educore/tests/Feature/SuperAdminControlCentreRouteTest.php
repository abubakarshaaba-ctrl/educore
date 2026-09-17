<?php

namespace Tests\Feature;

use App\Http\Controllers\AuditSecurityController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\TenantOperationsController;
use Illuminate\Http\Request;
use Tests\TestCase;

class SuperAdminControlCentreRouteTest extends TestCase
{
    public function test_system_health_route_is_registered_and_super_admin_protected(): void
    {
        $this->assertProtectedControlRoute(
            '/super/system-health',
            'super.system-health',
            SystemHealthController::class.'@index'
        );
    }

    public function test_audit_security_route_is_registered_and_super_admin_protected(): void
    {
        $this->assertProtectedControlRoute(
            '/super/audit-security',
            'super.audit-security',
            AuditSecurityController::class.'@index'
        );
    }

    public function test_tenant_operations_route_is_registered_and_super_admin_protected(): void
    {
        $this->assertProtectedControlRoute(
            '/super/tenant-operations',
            'super.tenant-operations',
            TenantOperationsController::class.'@index'
        );
    }

    private function assertProtectedControlRoute(string $uri, string $name, string $action): void
    {
        $route = app('router')->getRoutes()->match(Request::create($uri, 'GET'));

        $this->assertSame($name, $route->getName());
        $this->assertSame($action, $route->getActionName());

        $middleware = $route->gatherMiddleware();
        $this->assertContains('web', $middleware);
        $this->assertContains('auth', $middleware);
        $this->assertContains('active.account', $middleware);
        $this->assertContains('super.admin', $middleware);

        $matches = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($candidate) => $candidate->uri() === ltrim($uri, '/') && in_array('GET', $candidate->methods(), true));

        $this->assertCount(1, $matches);
    }
}
