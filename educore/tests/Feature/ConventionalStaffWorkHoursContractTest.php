<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConventionalStaffWorkHoursContractTest extends TestCase
{
    public function test_conventional_attendance_has_independent_daily_work_hours(): void
    {
        $service = file_get_contents(app_path('Services/StaffAttendanceScheduleService.php'));
        $controller = file_get_contents(app_path('Http/Controllers/StaffAttendanceController.php'));
        $view = file_get_contents(resource_path('views/staff-attendance/settings.blade.php'));

        foreach ([
            'monday', 'tuesday', 'wednesday', 'thursday',
            'friday', 'saturday', 'sunday',
        ] as $day) {
            $this->assertStringContainsString("'{$day}'", $service);
        }

        $this->assertStringContainsString(
            "StaffAttendanceWorkingDay::query()->updateOrCreate(",
            $service,
        );
        $this->assertStringContainsString(
            "classifyArrival(",
            $service,
        );
        $this->assertStringContainsString(
            "workingDatesForMonth(",
            $service,
        );

        $this->assertStringContainsString(
            "Conventional Curriculum Work Hours",
            $view,
        );
        $this->assertStringContainsString(
            'name="days[{{ $key }}][resumption_time]"',
            $view,
        );
        $this->assertStringContainsString(
            'name="days[{{ $key }}][closing_time]"',
            $view,
        );
        $this->assertStringContainsString(
            'name="days[{{ $key }}][grace_minutes]"',
            $view,
        );

        $this->assertStringContainsString(
            "$this->attendanceSchedule()->forDate(",
            $controller,
        );
        $this->assertStringContainsString(
            "'scheduled_workday' => (bool) $schedule->is_working",
            $controller,
        );
    }

    public function test_conventional_attendance_snapshots_expected_hours_per_record(): void
    {
        $migration = file_get_contents(
            database_path('migrations/2026_09_21_140500_add_conventional_staff_working_days.php')
        );
        $record = file_get_contents(app_path('Models/StaffAttendanceRecord.php'));

        foreach ([
            'expected_resumption_time',
            'expected_closing_time',
            'grace_minutes',
            'departure_status',
            'scheduled_workday',
        ] as $column) {
            $this->assertStringContainsString("'{$column}'", $migration);
            $this->assertStringContainsString("'{$column}'", $record);
        }
    }

    public function test_admin_and_self_attendance_apis_expose_daily_schedule(): void
    {
        $admin = file_get_contents(app_path('Http/Controllers/Api/AdminStaffAttendanceController.php'));
        $self = file_get_contents(app_path('Http/Controllers/Api/StaffAttendanceApiController.php'));

        $this->assertStringContainsString("'day_schedule'", $admin);
        $this->assertStringContainsString("'working_days'", $admin);
        $this->assertStringContainsString("'working_days' => ['nullable', 'array']", $admin);

        $this->assertStringContainsString("'today_schedule'", $self);
        $this->assertStringContainsString("'working_days'", $self);
    }

    public function test_legacy_scalar_settings_remain_only_as_compatibility_fallback(): void
    {
        $service = file_get_contents(app_path('Services/StaffAttendanceScheduleService.php'));
        $controller = file_get_contents(app_path('Http/Controllers/StaffAttendanceController.php'));
        $admin = file_get_contents(app_path('Http/Controllers/Api/AdminStaffAttendanceController.php'));

        $this->assertStringContainsString(
            "substr((string) $settings->resumption_time, 0, 8)",
            $service,
        );
        $this->assertStringContainsString(
            'Legacy scalar fields remain accepted',
            $controller,
        );
        $this->assertStringContainsString(
            'Legacy scalar fields remain accepted',
            $admin,
        );
    }

    public function test_shared_qr_reconciliation_is_preserved(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/StaffAttendanceController.php'));

        $this->assertStringContainsString(
            'reconcileSharedStaffClockIn(',
            $controller,
        );
        $this->assertStringContainsString(
            "status === 'not_scheduled'",
            $controller,
        );
        $this->assertStringContainsString(
            'does not count as a conventional working day',
            $controller,
        );
    }
}
