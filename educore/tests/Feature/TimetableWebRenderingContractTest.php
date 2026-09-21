<?php

namespace Tests\Feature;

use Tests\TestCase;

class TimetableWebRenderingContractTest extends TestCase
{
    public function test_timetable_views_use_null_safe_relationship_rendering(): void
    {
        $index = file_get_contents(resource_path('views/timetable/index.blade.php'));
        $teacher = file_get_contents(resource_path('views/timetable/teacher.blade.php'));

        $this->assertStringContainsString('optional($arm->classLevel)->name', $index);
        $this->assertStringContainsString('optional($match->subject)->name', $teacher);
        $this->assertStringContainsString('optional(optional($match->classArm)->classLevel)->name', $teacher);
        $this->assertStringContainsString("where('tenant_id', auth()->user()->tenant_id)", $teacher);
        $this->assertStringContainsString('substr((string) $p->start_time, 0, 5)', $teacher);
    }
    public function test_timetable_configuration_supports_weekday_start_and_end_overrides(): void
    {
        $configView = file_get_contents(resource_path('views/timetable/configure.blade.php'));
        $classView = file_get_contents(resource_path('views/timetable/view.blade.php'));
        $teacherView = file_get_contents(resource_path('views/timetable/teacher.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/TimetableController.php'));
        $model = file_get_contents(app_path('Models/TimetableConfig.php'));

        $this->assertStringContainsString(
            'name="day_start_times[{{ $day }}]"',
            $configView
        );
        $this->assertStringContainsString(
            'name="day_end_times[{{ $day }}]"',
            $configView
        );
        $this->assertStringContainsString(
            'School Hours by Day',
            $configView
        );
        $this->assertStringContainsString(
            "'day_start_times' => $dayStartTimes",
            $controller
        );
        $this->assertStringContainsString(
            'startingTimeFor(',
            $model
        );
        $this->assertStringContainsString(
            'startingTimeFor($day)',
            $classView
        );
        $this->assertStringContainsString(
            'startingTimeFor($day)',
            $teacherView
        );
    }

}
