<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileAttendanceNotificationContractTest extends TestCase
{
    public function test_native_conventional_attendance_dispatches_activity_notifications(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/AttendanceController.php'));

        $this->assertStringContainsString('ActivityEmailService', $source);
        $this->assertStringContainsString('notifyAttendanceStatus(', $source);
        $this->assertStringContainsString("['absent', 'late']", $source);
    }

    public function test_native_parallel_attendance_dispatches_activity_notifications(): void
    {
        $source = file_get_contents(app_path('Services/ParallelCurriculumOperationsService.php'));

        $this->assertStringContainsString('ActivityEmailService', $source);
        $this->assertStringContainsString('notifyAttendanceStatus(', $source);
        $this->assertStringContainsString("['absent', 'late']", $source);
    }

    public function test_android_attendance_push_deep_link_contract_is_present(): void
    {
        $root = dirname(base_path());
        $parser = file_get_contents(
            $root.'/mobile-native/app/src/main/java/online/educoreng/educore/notification/NotificationDeepLink.kt'
        );
        $shell = file_get_contents(
            $root.'/mobile-native/app/src/main/java/online/educoreng/educore/presentation/AuthorizedShell.kt'
        );

        $this->assertStringContainsString('"attendance"', $parser);
        $this->assertStringContainsString('"attendance" ->', $shell);
        $this->assertStringContainsString('portalAttendanceViewModel.load(childId = studentId)', $shell);
    }
}
