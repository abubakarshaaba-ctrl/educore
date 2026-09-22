<?php

namespace App\Services\Mobile;

/**
 * Presentation policy for the task-first mobile experience.
 *
 * This class never grants access. It only orders modules that have already
 * survived MobileModuleService authorization, so presentation cannot widen a
 * user's permission surface.
 */
class MobileRoleExperiencePolicy
{
    private const TEACHING_ROLES = [
        'teacher',
        'subject_teacher',
        'class_teacher',
        'form_teacher',
        'asst_form_teacher',
        'form_subject_teacher',
        'hod',
        'head_of_department',
        'director_of_studies',
    ];

    public function prioritizedModuleKeys(string $scope, string $roleKey): array
    {
        $roleKey = strtolower($roleKey);

        return match ($scope) {
            'platform' => [
                'platform.schools',
                'platform.broadcasts',
                'platform.support',
                'platform.analytics',
                'platform.billing',
                'platform.settings',
            ],
            'admin' => [
                'attendance',
                'scores',
                'staff-attendance.admin',
                'students',
                'classes',
                'messages',
            ],
            'student' => [
                'student.timetable',
                'student.attendance',
                'student.results',
                'student.subjects',
                'student.messages',
                'student.calendar',
            ],
            'parent' => [
                'parent.attendance',
                'parent.results',
                'parent.fees',
                'parent.messages',
                'parent.timetable',
                'parent.calendar',
            ],
            'accountant' => [
                'fees',
                'expenses',
                'payroll',
                'analytics',
                'exports',
                'messages',
            ],
            'transport' => [
                'transport',
                'staff-attendance.self',
                'messages',
                'calendar.view',
                'profile',
            ],
            'health' => [
                'health',
                'staff-attendance.self',
                'messages',
                'calendar.view',
                'profile',
            ],
            default => $this->staffPriorities($roleKey),
        };
    }

    public function actionTitle(string $scope, string $roleKey, string $moduleKey, string $fallback): string
    {
        $roleKey = strtolower($roleKey);

        $aliases = match ($scope) {
            'platform' => [
                'platform.schools' => 'Manage Schools',
                'platform.broadcasts' => 'Send Broadcast',
                'platform.support' => 'Support Inbox',
                'platform.analytics' => 'Platform Analytics',
            ],
            'admin' => [
                'attendance' => 'Student Attendance',
                'scores' => 'Enter Scores',
                'reports' => 'Report Cards',
                'staff-attendance.admin' => 'Staff Attendance',
                'students' => 'Students',
                'messages' => 'Messages',
            ],
            'student' => [
                'student.timetable' => 'My Timetable',
                'student.attendance' => 'My Attendance',
                'student.results' => 'My Results',
                'student.subjects' => 'My Subjects',
            ],
            'parent' => [
                'parent.attendance' => 'Child Attendance',
                'parent.results' => 'Child Results',
                'parent.fees' => 'Fees & Payments',
            ],
            'accountant' => [
                'fees' => 'Fees & Invoices',
                'expenses' => 'Expenses',
                'payroll' => 'Payroll',
            ],
            default => $this->isTeachingRole($roleKey) ? [
                'attendance' => 'Mark Attendance',
                'scores' => 'Enter Scores',
                'lesson-planner' => 'Lesson Planner',
                'classes' => 'My Classes',
                'timetable' => 'My Timetable',
                'staff-attendance.self' => 'My Attendance',
            ] : [],
        };

        return $aliases[$moduleKey] ?? $fallback;
    }

    private function staffPriorities(string $roleKey): array
    {
        if ($this->isTeachingRole($roleKey)) {
            return [
                'attendance',
                'scores',
                'lesson-planner',
                'classes',
                'timetable',
                'messages',
                'staff-attendance.self',
                'skills',
            ];
        }

        return [
            'staff-attendance.self',
            'messages',
            'timetable',
            'calendar.view',
            'profile',
        ];
    }

    private function isTeachingRole(string $roleKey): bool
    {
        return in_array($roleKey, self::TEACHING_ROLES, true)
            || str_contains($roleKey, 'teacher');
    }
}
