<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DashboardRoleExperienceViewTest extends TestCase
{
    public function test_teacher_dashboard_promotes_authorized_teaching_tasks(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/dashboard/staff.blade.php');

        $this->assertStringContainsString("canAccessModule('attendance')", $view);
        $this->assertStringContainsString("route('attendance.index')", $view);
        $this->assertStringContainsString('Mark Attendance', $view);

        $this->assertStringContainsString("canAccessModule('scores.entry')", $view);
        $this->assertStringContainsString('Enter Scores', $view);

        $this->assertStringContainsString("canAccessModule('lesson-planner')", $view);
        $this->assertStringContainsString("route('lesson-planner.index')", $view);

        $this->assertStringContainsString("canAccessModule('classes')", $view);
        $this->assertStringContainsString('My Classes', $view);

        $this->assertStringContainsString("canAccessModule('messages')", $view);
        $this->assertStringContainsString("route('messages.inbox')", $view);
    }

    public function test_school_admin_dashboard_promotes_only_permission_checked_priority_actions(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/dashboard/index.blade.php');

        foreach (['attendance', 'scores', 'reports', 'staff-attendance', 'students', 'messages'] as $module) {
            $this->assertStringContainsString("canAccessModule('{$module}')", $view);
        }

        $this->assertStringContainsString("route('reports.index')", $view);
        $this->assertStringContainsString("route('staff-attendance.index')", $view);
        $this->assertStringContainsString("route('students.index')", $view);
        $this->assertStringContainsString("route('messages.inbox')", $view);
        $this->assertStringContainsString('Priority Actions', $view);
    }
}
