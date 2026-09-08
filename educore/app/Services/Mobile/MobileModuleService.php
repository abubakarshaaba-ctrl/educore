<?php

namespace App\Services\Mobile;

use App\Models\ClassArm;
use App\Models\User;

class MobileModuleService
{
    private const STAFF_MODULES = [
        'dashboard' => ['Dashboard', '/dashboard', 'dashboard'],
        'students' => ['Students', '/students', 'students'],
        'staff' => ['Staff', '/staff', 'staff'],
        'classes' => ['Classes', '/classes', 'classes'],
        'subjects' => ['Subjects', '/subjects', 'subjects'],
        'curriculum' => ['Curriculum', '/curriculum', 'curriculum'],
        'academic-cycle' => ['Academic Sessions', '/academic-session', 'academic-cycle'],
        'attendance' => ['Student Attendance', '/attendance', 'attendance'],
        'staff-attendance' => ['Staff Attendance', '/staff-attendance/my', 'staff-attendance'],
        'staff-attendance.self' => ['My Attendance', '/staff-attendance/my', 'staff-attendance'],
        'skills' => ['Skill Ratings', '/skills', 'skills'],
        'scores' => ['Scores', '/scores', 'scores'],
        'reports' => ['Report Cards', '/reports', 'reports'],
        'timetable' => ['Timetable', '/timetable', 'timetable'],
        'fees' => ['Fees & Invoices', '/fees/invoices', 'fees'],
        'expenses' => ['Expenses', '/expenses', 'expenses'],
        'payroll' => ['Payroll', '/payroll', 'payroll'],
        'admissions' => ['Admissions', '/admissions', 'admissions'],
        'messages' => ['Messages', '/messages', 'messages'],
        'notifications.view' => ['Notifications', '/notifications', 'notifications'],
        'calendar.view' => ['Calendar', '/calendar', 'calendar'],
        'health' => ['Health Records', '/health', 'health'],
        'transport' => ['Transport', '/transport', 'transport'],
        'library' => ['Library', '/library', 'library'],
        'inventory' => ['Inventory', '/inventory', 'inventory'],
        'hostels' => ['Hostels', '/hostels', 'hostels'],
        'analytics' => ['Analytics', '/analytics', 'analytics'],
        'risk' => ['Risk Flags', '/risk', 'risk'],
        'exports' => ['Exports', '/exports', 'exports'],
        'cbt' => ['CBT', '/cbt', 'cbt'],
        'lesson-planner' => ['Lesson Planner', '/lesson-planner', 'lesson-planner'],
        'academic-repository' => ['Academic Repository', '/academic-repository', 'repository'],
        'profile' => ['My Profile', '/profile', 'profile'],
    ];

    public function forUser(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return [
                ['key' => 'platform.dashboard', 'title' => 'Platform Overview', 'path' => '/super', 'icon' => 'dashboard'],
                ['key' => 'platform.schools', 'title' => 'Schools', 'path' => '/super/tenants', 'icon' => 'schools'],
                ['key' => 'platform.plans', 'title' => 'Plans & Pricing', 'path' => '/super/plans', 'icon' => 'plans'],
                ['key' => 'platform.billing', 'title' => 'Billing & Invoices', 'path' => '/super/billing', 'icon' => 'fees'],
                ['key' => 'platform.payments', 'title' => 'Payments', 'path' => '/super/payments', 'icon' => 'fees'],
                ['key' => 'platform.gateways', 'title' => 'Payment Gateways', 'path' => '/super/payment-gateways', 'icon' => 'settings'],
                ['key' => 'platform.analytics', 'title' => 'Platform Analytics', 'path' => '/super/analytics', 'icon' => 'analytics'],
                ['key' => 'platform.groups', 'title' => 'School Groups', 'path' => '/super/groups', 'icon' => 'schools'],
                ['key' => 'platform.agents', 'title' => 'Agents', 'path' => '/super/agents', 'icon' => 'staff'],
                ['key' => 'platform.support', 'title' => 'Support Inbox', 'path' => '/super/support', 'icon' => 'messages'],
                ['key' => 'platform.broadcasts', 'title' => 'Broadcasts', 'path' => '/super/broadcasts', 'icon' => 'notifications'],
                ['key' => 'platform.settings', 'title' => 'Platform Settings', 'path' => '/super/settings', 'icon' => 'settings'],
            ];
        }

        if ($user->isParent()) {
            return [
                ['key' => 'parent.dashboard', 'title' => 'Parent Dashboard', 'path' => '/parent/dashboard', 'icon' => 'dashboard'],
                ['key' => 'parent.fees', 'title' => 'Fees & Payments', 'path' => '/parent/fees', 'icon' => 'fees'],
                ['key' => 'parent.results', 'title' => 'Results', 'path' => '/parent/results', 'icon' => 'reports'],
                ['key' => 'parent.attendance', 'title' => 'Attendance', 'path' => '/parent/attendance', 'icon' => 'attendance'],
                ['key' => 'parent.messages', 'title' => 'Messages', 'path' => '/parent/messages', 'icon' => 'messages'],
                ['key' => 'parent.notifications', 'title' => 'Notifications', 'path' => '/parent/notifications', 'icon' => 'notifications'],
                ['key' => 'parent.calendar', 'title' => 'Calendar', 'path' => '/parent/calendar', 'icon' => 'calendar'],
            ];
        }

        if ($user->isStudent()) {
            return [
                ['key' => 'student.dashboard', 'title' => 'Student Dashboard', 'path' => '/student/dashboard', 'icon' => 'dashboard'],
                ['key' => 'student.results', 'title' => 'Results', 'path' => '/student/results', 'icon' => 'reports'],
                ['key' => 'student.timetable', 'title' => 'Timetable', 'path' => '/student/timetable', 'icon' => 'timetable'],
                ['key' => 'student.attendance', 'title' => 'Attendance', 'path' => '/student/attendance', 'icon' => 'attendance'],
                ['key' => 'student.exams', 'title' => 'Examinations', 'path' => '/student/exams', 'icon' => 'cbt'],
                ['key' => 'student.subjects', 'title' => 'Subjects', 'path' => '/student/subjects', 'icon' => 'subjects'],
                ['key' => 'student.messages', 'title' => 'Messages', 'path' => '/student/messages', 'icon' => 'messages'],
                ['key' => 'student.notifications', 'title' => 'Notifications', 'path' => '/student/notifications', 'icon' => 'notifications'],
                ['key' => 'student.calendar', 'title' => 'Calendar', 'path' => '/student/calendar', 'icon' => 'calendar'],
            ];
        }

        return collect(self::STAFF_MODULES)
            ->filter(function (array $definition, string $key) use ($user): bool {
                if ($key === 'academic-repository') {
                    return $user->isAdmin() || $user->isTeacher();
                }

                if ($key === 'skills') {
                    if (!$user->canAccessModule('skills')) {
                        return false;
                    }
                    if ($user->canAccessExactModule('students')) {
                        return true;
                    }

                    return ClassArm::where('tenant_id', $user->tenant_id)
                        ->where('form_tutor_id', $user->id)
                        ->exists();
                }

                return $user->canAccessModule($key);
            })
            ->map(fn (array $definition, string $key): array => [
                'key' => $key,
                'title' => $definition[0],
                'path' => $definition[1],
                'icon' => $definition[2],
            ])
            ->values()
            ->all();
    }
}
