<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MobileApiRouteContractTest extends TestCase
{
    public function test_native_android_api_routes_are_registered(): void
    {
        $registered = collect(Route::getRoutes())->flatMap(function ($route) {
            return collect($route->methods())->map(fn (string $method): string => $method.' '.$route->uri());
        })->all();

        $expected = [
            'GET api/v1/bootstrap',
            'GET api/v1/dashboard',
            'GET api/v1/operations/{module}',
            'GET api/v1/academic-cycle',
            'GET api/v1/fees',
            'GET api/v1/expenses',
            'GET api/v1/hostels',
            'GET api/v1/inventory',
            'GET api/v1/subjects',
            'GET api/v1/curriculum',
            'GET api/v1/admissions',
            'GET api/v1/transport-officer/dashboard',
            'GET api/v1/health-officer/dashboard',
            'GET api/v1/platform/dashboard',
            'GET api/v1/platform/plans',
            'GET api/v1/platform/analytics',
            'GET api/v1/platform/support',
            'GET api/v1/platform/broadcasts',
            'GET api/v1/platform/settings',
            'GET api/v1/platform/gateways',
            'GET api/v1/reports',
            'GET api/v1/portal-accounts',
            'GET api/v1/staff/cbt/options',
        ];

        foreach ($expected as $signature) {
            $this->assertContains($signature, $registered, "Missing native API route: {$signature}");
        }
    }
}
