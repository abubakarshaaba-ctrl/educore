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
}
