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
        $this->assertStringContainsString("substr((string) $p->start_time, 0, 5)", $teacher);
    }
}
