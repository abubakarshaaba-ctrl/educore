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
            'api/v1/classes/{classArm}/students/{student}/results',
            'api/v1/fees',
            'api/v1/fees/generate',
            'api/v1/expenses',
            'api/v1/payroll',
        ] as $uri) {
            $this->assertStringContainsString($uri, $routes, "Missing required mobile route: {$uri}");
        }
    }

    public function test_school_communication_controller_keeps_privacy_and_staff_messaging_contract(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Api/SchoolCommunicationApiController.php'));

        $this->assertStringContainsString("'all_staff'", $controller);
        $this->assertStringContainsString("'all_parents'", $controller);
        $this->assertStringContainsString("'school_admin'", $controller);
        $this->assertStringContainsString("'staff:'", $controller);
        $this->assertStringContainsString('redirected_from_broadcast', $controller);
        $this->assertStringContainsString('message_thread_reads', $controller);
        $this->assertStringContainsString('notifyMessageThread', $controller);
    }

    public function test_push_service_handles_school_broadcast_audiences(): void
    {
        $service = file_get_contents(app_path('Services/Notifications/PushNotificationService.php'));

        $this->assertStringContainsString("$thread->audience === 'all_staff'", $service);
        $this->assertStringContainsString("$thread->audience === 'all_parents'", $service);
        $this->assertStringContainsString("'destination_type' => 'message_thread'", $service);
    }
}
