<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MobileBackendConvergenceRouteTest extends TestCase
{
    public function test_converged_native_routes_are_registered(): void
    {
        $routes = collect(Route::getRoutes())->flatMap(function ($route) {
            return collect($route->methods())->map(fn (string $method): string => $method.' '.$route->uri());
        });

        foreach ([
            'GET api/v1/operations/{module}',
            'GET api/v1/academic-cycle',
            'POST api/v1/academic-cycle/sessions',
            'POST api/v1/academic-cycle/terms',
            'GET api/v1/classes/{classArm}/students/{student}/results',
            'GET api/v1/staff/cbt/options',
            'GET api/v1/staff/cbt/exams',
            'POST api/v1/staff/cbt/exams',
            'GET api/v1/staff/cbt/exams/{exam}',
            'POST api/v1/staff/cbt/exams/{exam}/publish',
            'POST api/v1/staff/cbt/exams/{exam}/close',
            'PATCH api/v1/staff/cbt/exams/{exam}/schedule',
            'GET api/v1/reports',
            'POST api/v1/reports/compute',
            'POST api/v1/reports/publish',
            'GET api/v1/platform/analytics',
            'GET api/v1/platform/groups',
            'GET api/v1/platform/support',
            'GET api/v1/platform/broadcasts',
            'GET api/v1/platform/settings',
            'GET api/v1/platform/gateways',
            'POST api/v1/platform/tenants',
            'GET api/v1/platform/tenants/{tenant}',
            'GET api/v1/platform/billing/invoices',
        ] as $signature) {
            $this->assertTrue($routes->contains($signature), "Missing converged native route: {$signature}");
        }
    }
}
