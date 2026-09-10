<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AdminStaffAttendanceController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MobileStaffAttendanceContractTest extends TestCase
{
    public function test_canonical_staff_attendance_api_routes_are_registered(): void
    {
        $registered = collect(Route::getRoutes())->flatMap(function ($route) {
            return collect($route->methods())->map(fn (string $method): string => $method.' '.$route->uri());
        })->all();

        $expected = [
            'GET api/v1/admin/staff-attendance/daily',
            'GET api/v1/admin/staff-attendance/monthly',
            'GET api/v1/admin/staff-attendance/settings',
            'PUT api/v1/admin/staff-attendance/settings',
            'GET api/v1/admin/staff-attendance/offline',
            'POST api/v1/admin/staff-attendance/offline/sync',
            'GET api/v1/admin/staff-attendance/proxy-reviews',
            'POST api/v1/admin/staff-attendance/proxy-clock',
            'GET api/v1/admin/staff-attendance/qr',
            'POST api/v1/admin/staff-attendance/qr/reset',
        ];

        foreach ($expected as $signature) {
            $this->assertContains($signature, $registered, "Missing mobile attendance route: {$signature}");
        }
    }

    public function test_canonical_staff_attendance_controller_actions_exist(): void
    {
        foreach ([
            'daily',
            'monthly',
            'settings',
            'updateSettings',
            'offline',
            'syncOffline',
            'proxyReviews',
            'proxyClock',
            'qr',
            'resetQr',
        ] as $method) {
            $this->assertTrue(
                method_exists(AdminStaffAttendanceController::class, $method),
                "Missing AdminStaffAttendanceController::{$method}",
            );
        }
    }
}
