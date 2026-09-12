<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MobileApiRouteContractTest extends TestCase
{
    public function test_active_native_mobile_routes_are_registered(): void
    {
        $registered = collect(Route::getRoutes())->flatMap(function ($route) {
            return collect($route->methods())->map(fn (string $method): string => $method.' '.$route->uri());
        })->all();

        $expected = [
            'GET api/v1/bootstrap',
            'GET api/v1/dashboard',
            'GET api/v1/operations/{module}',
            'GET api/v1/academic-cycle',
            'GET api/v1/subjects',
            'GET api/v1/curriculum',
            'GET api/v1/skills',
            'GET api/v1/skills/sheet',
            'PUT api/v1/skills/sheet',
            'GET api/v1/gradebook',
            'GET api/v1/reports',
            'GET api/v1/portal-accounts',
            'GET api/v1/school-settings',
            'GET api/v1/risk',
            'GET api/v1/library/options',
            'GET api/v1/inventory',
            'GET api/v1/hostels',
            'GET api/v1/transfers',
            'GET api/v1/admin/staff-attendance',
            'GET api/v1/admin/staff-attendance/report',
            'POST api/v1/admin/staff-attendance/manual',
            'GET api/v1/admin/staff-attendance/offline',
            'POST api/v1/admin/staff-attendance/offline/{record}',
            'GET api/v1/admin/staff-attendance/proxy-reviews',
            'POST api/v1/admin/staff-attendance/proxy-reviews/{record}',
            'GET api/v1/admin/staff-attendance/qr',
            'POST api/v1/admin/staff-attendance/reset-qr',
            'PUT api/v1/admin/staff-attendance/settings',
            'GET api/v1/staff-attendance',
            'POST api/v1/staff-attendance/clock-in',
            'POST api/v1/staff-attendance/clock-out',
        ];

        foreach ($expected as $signature) {
            $this->assertContains($signature, $registered, "Missing native API route: {$signature}");
        }
    }
}
