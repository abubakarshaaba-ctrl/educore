<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MobileLiveBackendContractTest extends TestCase
{
    public function test_required_mobile_backend_routes_are_registered(): void
    {
        Artisan::call('route:list', ['--path' => 'api/v1']);
        $routes = Artisan::output();

        foreach ([
            'api/v1/admin/staff-attendance/qr',
            'api/v1/admin/staff-attendance/reset-qr',
            'api/v1/staff/cbt/options',
            'api/v1/staff/cbt/exams',
            'api/v1/school-messages',
            'api/v1/school-messages/recipients',
            'api/v1/calendar/events',
        ] as $uri) {
            $this->assertStringContainsString($uri, $routes, "Missing required mobile route: {$uri}");
        }
    }

    public function test_school_communication_controller_keeps_broadcast_privacy_contract(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Api/SchoolCommunicationApiController.php'));

        $this->assertStringContainsString("'all_staff'", $controller);
        $this->assertStringContainsString("'all_parents'", $controller);
        $this->assertStringContainsString("'school_admin'", $controller);
        $this->assertStringContainsString('redirected_from_broadcast', $controller);
        $this->assertStringContainsString('message_thread_reads', $controller);
    }
}
