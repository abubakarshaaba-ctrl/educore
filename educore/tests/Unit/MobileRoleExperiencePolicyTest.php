<?php

namespace Tests\Unit;

use App\Services\Mobile\MobileRoleExperiencePolicy;
use PHPUnit\Framework\TestCase;

class MobileRoleExperiencePolicyTest extends TestCase
{
    private MobileRoleExperiencePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MobileRoleExperiencePolicy();
    }

    public function test_core_experiences_have_task_first_priorities(): void
    {
        $this->assertSame('platform.schools', $this->policy->prioritizedModuleKeys('platform', 'super_admin')[0]);
        $this->assertSame('attendance', $this->policy->prioritizedModuleKeys('admin', 'admin')[0]);
        $this->assertSame('attendance', $this->policy->prioritizedModuleKeys('staff', 'subject_teacher')[0]);
        $this->assertSame('student.timetable', $this->policy->prioritizedModuleKeys('student', 'student')[0]);
        $this->assertSame('parent.attendance', $this->policy->prioritizedModuleKeys('parent', 'parent')[0]);
    }

    public function test_teacher_action_titles_are_task_oriented(): void
    {
        $this->assertSame(
            'Mark Attendance',
            $this->policy->actionTitle('staff', 'subject_teacher', 'attendance', 'Student Attendance')
        );
        $this->assertSame(
            'My Classes',
            $this->policy->actionTitle('staff', 'form_subject_teacher', 'classes', 'Classes')
        );
    }

    public function test_unknown_role_keeps_safe_fallbacks_and_never_creates_permissions(): void
    {
        $keys = $this->policy->prioritizedModuleKeys('staff', 'laboratory_assistant');

        $this->assertContains('staff-attendance.self', $keys);
        $this->assertContains('messages', $keys);
        $this->assertSame(
            'Inventory',
            $this->policy->actionTitle('staff', 'laboratory_assistant', 'inventory', 'Inventory')
        );
    }
}
