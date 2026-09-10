<?php

namespace Tests\Feature;

use Tests\TestCase;

class TimetableWebRenderingContractTest extends TestCase
{
    public function test_timetable_controller_and_views_keep_safe_rendering_contracts(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/TimetableController.php'));
        $index = file_get_contents(resource_path('views/timetable/index.blade.php'));
        $view = file_get_contents(resource_path('views/timetable/view.blade.php'));
        $teacher = file_get_contents(resource_path('views/timetable/teacher.blade.php'));

        $this->assertStringContainsString("where('tenant_id', $this->tenantId())", $controller);
        $this->assertStringContainsString('normalizeTime', $controller);
        $this->assertStringContainsString('optional($arm->classLevel)->name', $index);
        $this->assertStringContainsString('optional($classArm->classLevel)->name', $view);
        $this->assertStringContainsString('optional(optional($match->classArm)->classLevel)->name', $teacher);
    }
}
