<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DashboardRoleExperienceViewTest extends TestCase
{
    public function test_canonical_teacher_dashboard_promotes_authorized_teaching_tasks(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/portal/staff/dashboard.blade.php');

        foreach (['attendance', 'scores.entry', 'lesson-planner', 'classes', 'messages'] as $module) {
            $this->assertStringContainsString("canAccessModule('{$module}')", $view);
        }

        $this->assertStringContainsString("route('attendance.index')", $view);
        $this->assertStringContainsString("route('scores.index')", $view);
        $this->assertStringContainsString("route('lesson-planner.index')", $view);
        $this->assertStringContainsString("route('classes.index')", $view);
        $this->assertStringContainsString("route('timetable.index')", $view);
        $this->assertStringContainsString("route('staff.portal.messages')", $view);
        $this->assertStringContainsString('Mark Attendance', $view);
        $this->assertStringContainsString('Enter Scores', $view);
        $this->assertStringContainsString('My Classes', $view);
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

    public function test_student_dashboard_exposes_task_first_academic_shortcuts(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/portal/student/dashboard.blade.php');

        foreach (['timetable', 'attendance', 'results', 'subjects'] as $route) {
            $this->assertStringContainsString("route('student.portal.{$route}')", $view);
        }

        $this->assertStringContainsString('My Timetable', $view);
        $this->assertStringContainsString('My Attendance', $view);
        $this->assertStringContainsString('My Results', $view);
        $this->assertStringContainsString('My Subjects', $view);
    }

    public function test_parent_dashboard_prioritizes_child_monitoring_tasks(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/portal/parent/dashboard.blade.php');

        $attendance = strpos($view, "['parent.attendance','✅','Child Attendance'");
        $results = strpos($view, "['parent.results','📊','Child Results'");
        $fees = strpos($view, "['parent.fees','💳','Fees & Payments'");
        $timetable = strpos($view, "['parent.timetable','📅','Child Timetable'");

        $this->assertNotFalse($attendance);
        $this->assertNotFalse($results);
        $this->assertNotFalse($fees);
        $this->assertNotFalse($timetable);
        $this->assertTrue($attendance < $results && $results < $fees && $fees < $timetable);
    }

    public function test_platform_dashboard_promotes_platform_operations(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/super/dashboard.blade.php');

        foreach (['super.tenants', 'super.broadcasts', 'super.support', 'super.analytics'] as $route) {
            $this->assertStringContainsString("route('{$route}')", $view);
        }

        $this->assertStringContainsString('Manage Schools', $view);
        $this->assertStringContainsString('Send Broadcast', $view);
        $this->assertStringContainsString('Support Inbox', $view);
        $this->assertStringContainsString('Platform Analytics', $view);
    }
}
