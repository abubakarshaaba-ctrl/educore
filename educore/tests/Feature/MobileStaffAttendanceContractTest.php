<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AdminStaffAttendanceController;
use App\Http\Controllers\Api\AdminStaffAttendanceOfflineController;
use App\Http\Controllers\Api\StaffAttendanceApiController;
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
            'POST api/v1/staff-attendance/offline/sync',
        ];

        foreach ($expected as $signature) {
            $this->assertContains($signature, $registered, "Missing mobile attendance route: {$signature}");
            $this->assertSame(
                1,
                count(array_keys($registered, $signature, true)),
                "Mobile attendance route is registered more than once: {$signature}",
            );
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

        $this->assertTrue(method_exists(StaffAttendanceApiController::class, 'syncOffline'));
        $this->assertTrue(method_exists(AdminStaffAttendanceOfflineController::class, '__invoke'));
    }

    public function test_self_offline_sync_route_is_not_inside_admin_namespace(): void
    {
        $route = collect(Route::getRoutes())->first(fn ($candidate) =>
            in_array('POST', $candidate->methods(), true)
            && $candidate->uri() === 'api/v1/staff-attendance/offline/sync'
        );

        $this->assertNotNull($route, 'The ordinary-staff offline replay route is missing.');
        $this->assertStringNotContainsString('/admin/', '/'.$route->uri());
    }

    public function test_admin_offline_review_route_uses_durable_review_feed(): void
    {
        $route = collect(Route::getRoutes())->first(fn ($candidate) =>
            in_array('GET', $candidate->methods(), true)
            && $candidate->uri() === 'api/v1/admin/staff-attendance/offline'
        );

        $this->assertNotNull($route);
        $this->assertStringContainsString(AdminStaffAttendanceOfflineController::class, $route->getActionName());
    }

    public function test_offline_sync_service_contract_uses_separate_event_ledger(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/StaffAttendanceApiController.php'));
        $this->assertStringContainsString('staff_attendance_sync_events', $source);
        $this->assertStringContainsString("status' => 'rejected'", $source);
        $this->assertStringContainsString('verifyStaticQrToken', $source);
        $this->assertStringContainsString('distanceTo', $source);
    }
}
