<?php

namespace App\Services\Mobile;

use App\Models\ClassArm;
use App\Models\User;
use App\Services\ParallelCurriculumService;

class MobileModuleService
{
    public function __construct(private readonly ParallelCurriculumService $parallel) {}

    private const STAFF_MODULES = [
        'dashboard' => ['Dashboard', '/dashboard', 'dashboard'],
        'students' => ['Students', '/students', 'students'],
        'staff' => ['Staff', '/staff', 'staff'],
        'classes' => ['Classes', '/classes', 'classes'],
        'subjects' => ['Subjects', '/subjects', 'subjects'],
        'curriculum' => ['Curriculum', '/curriculum', 'curriculum'],
        'parallel-curriculum' => ['Parallel Curriculum', '/parallel-curriculum', 'curriculum'],
        'parallel-timetable' => ['Parallel Timetable & Attendance', '/parallel-curriculum/operations', 'timetable'],
        'academic-cycle' => ['Academic Sessions', '/academic-session', 'academic-cycle'],
        'attendance' => ['Student Attendance', '/attendance', 'attendance'],
        'staff-attendance.admin' => ['Staff Attendance', '/staff-attendance', 'staff-attendance'],
        'staff-attendance.self' => ['My Attendance', '/staff-attendance/my', 'staff-attendance'],
        'skills' => ['Skill Ratings', '/skills', 'skills'],
        'scores' => ['Score Entry', '/scores', 'scores'],
        'reports' => ['Report Cards', '/reports', 'reports'],
        'timetable' => ['Timetable', '/timetable', 'timetable'],
        'fees' => ['Fees & Invoices', '/fees/invoices', 'fees'],
        'subscription' => ['Subscription & Billing', '/billing/subscription', 'fees'],
        'expenses' => ['Expenses', '/expenses', 'expenses'],
        'payroll' => ['Payroll', '/payroll', 'payroll'],
        'admissions' => ['Admissions', '/admissions', 'admissions'],
        'transfers' => ['Student Transfers', '/students/transfers', 'transfers'],
        'portal-accounts' => ['Portal Accounts', '/portal-accounts', 'profile'],
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
        'lesson-planner' => ['Lesson Planner', '/lesson-planner', 'lesson-planner'],
        'academic-repository' => ['Academic Repository', '/academic-repository', 'repository'],
        'profile' => ['My Profile', '/profile', 'profile'],
    ];

    private const ACCOUNTANT_MODULES = [
        'dashboard', 'staff-attendance.self', 'fees', 'expenses', 'payroll',
        'analytics', 'exports', 'messages', 'notifications.view', 'calendar.view', 'profile',
    ];

    private const ACADEMIC_MODULES = [
        'classes', 'subjects', 'curriculum', 'parallel-curriculum', 'parallel-timetable', 'academic-cycle', 'attendance',
        'skills', 'scores', 'reports', 'timetable', 'lesson-planner', 'academic-repository',
    ];

    private const ACADEMIC_ROLE_KEYS = [
        'admin', 'principal', 'head', 'head_teacher', 'head_of_school', 'head_of_schools',
        'vice_principal', 'vice_principal_academics', 'vice_principal_administration',
        'assistant_principal', 'assistant_head', 'academic_head', 'academic_administrator',
        'director_of_studies', 'hod', 'head_of_department', 'teacher', 'subject_teacher',
        'class_teacher', 'form_teacher', 'asst_form_teacher', 'form_subject_teacher',
    ];

    private const EXPLICIT_ACADEMIC_PERMISSION_KEYS = [
        'classes' => ['classes', 'classes.view'],
        'subjects' => ['subjects', 'subjects.view'],
        'curriculum' => ['curriculum', 'curriculum.view'],
        'parallel-curriculum' => ['scores'],
        'parallel-timetable' => ['scores', 'scores.entry', 'timetable', 'timetable.view', 'attendance'],
        'academic-cycle' => ['academic-cycle', 'academic-session'],
        'attendance' => ['attendance', 'attendance.mark', 'student-attendance'],
        'skills' => ['skills', 'skills.rate'],
        'scores' => ['scores', 'scores.entry', 'scores.view'],
        'reports' => ['reports', 'report-cards', 'results'],
        'timetable' => ['timetable', 'timetable.view'],
        'lesson-planner' => ['lesson-planner'],
        'academic-repository' => ['academic-repository'],
    ];

    private const STAFF_ATTENDANCE_MANAGEMENT_ROLES = [
        'admin', 'principal', 'head', 'head_teacher', 'head_of_school', 'school_head',
        'vice_principal', 'vice_principal_academics', 'vice_principal_administration',
        'assistant_principal', 'assistant_head', 'academic_head', 'academic_administrator',
    ];

    private const ADMISSION_OFFICER_BLOCKED_MODULES = [
        'students', 'transfers',
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
                ['key' => 'student.timetable', 'title' => 'Timetable', 'path' => '/student/timetable', 'icon' => 'timetable'],
                ['key' => 'student.attendance', 'title' => 'Attendance', 'path' => '/student/attendance', 'icon' => 'attendance'],
                ['key' => 'student.results', 'title' => 'Results', 'path' => '/student/results', 'icon' => 'reports'],
                ['key' => 'student.subjects', 'title' => 'Subjects', 'path' => '/student/subjects', 'icon' => 'subjects'],
                ['key' => 'student.messages', 'title' => 'Messages', 'path' => '/student/messages', 'icon' => 'messages'],
                ['key' => 'student.notifications', 'title' => 'Notifications', 'path' => '/student/notifications', 'icon' => 'notifications'],
                ['key' => 'student.calendar', 'title' => 'Calendar', 'path' => '/student/calendar', 'icon' => 'calendar'],
            ];
        }

        $roleKey = strtolower((string) $user->roleKey());
        $isAccountant = $user->isAccountant() || in_array($roleKey, ['accountant', 'bursar', 'finance_officer'], true);
        $isSchoolAdmin = $user->isAdmin() || in_array($roleKey, self::STAFF_ATTENDANCE_MANAGEMENT_ROLES, true);
        $isAcademicStaff = $this->isAcademicStaff($user);

        return collect(self::STAFF_MODULES)
            ->filter(function (array $definition, string $key) use ($user, $isAccountant, $isSchoolAdmin, $isAcademicStaff, $roleKey): bool {
                if ($isAccountant && ! in_array($key, self::ACCOUNTANT_MODULES, true)) {
                    return false;
                }

                if ($key === 'subscription') {
                    return $roleKey === 'admin';
                }

                if ($key === 'parallel-curriculum') {
                    return $isSchoolAdmin
                        && (bool) $user->tenant_id
                        && $this->parallel->enabledForTenant((int) $user->tenant_id);
                }

                if ($key === 'parallel-timetable') {
                    return (bool) $user->tenant_id
                        && $this->parallel->enabledForTenant((int) $user->tenant_id)
                        && (
                            $isSchoolAdmin
                            || $user->canAccessExactModule('scores')
                            || $user->canAccessExactModule('scores.entry')
                            || $user->canAccessExactModule('timetable')
                            || $user->canAccessExactModule('timetable.view')
                            || $user->canAccessExactModule('attendance')
                            || $this->hasExplicitAcademicGrant($user, $key)
                        );
                }


                if (
                    $user->roleKey() === 'admission_officer'
                    && in_array($key, self::ADMISSION_OFFICER_BLOCKED_MODULES, true)
                    && ! $user->hasGrantedPermission($key)
                ) {
                    return false;
                }

                if (! $isAcademicStaff && in_array($key, self::ACADEMIC_MODULES, true)
                    && ! $this->hasExplicitAcademicGrant($user, $key)) {
                    return false;
                }

                if ($key === 'staff-attendance.admin') {
                    return $this->canManageStaffAttendance($user);
                }

                if ($key === 'staff-attendance.self') {
                    return $user->isTenantStaff() && $user->canAccessModule('staff-attendance.self');
                }

                // School leadership must always receive the core native academic
                // workspaces. Endpoint authorization remains tenant scoped.
                if ($isSchoolAdmin && in_array($key, ['staff', 'students', 'scores', 'reports'], true)) {
                    return true;
                }

                if ($key === 'academic-repository') {
                    return $user->isAdmin() || $user->isTeacher() || $this->hasExplicitAcademicGrant($user, $key);
                }

                if ($key === 'skills') {
                    if (! $user->canAccessModule('skills')) return false;
                    if ($user->canAccessExactModule('students')) return true;

                    return ClassArm::where('tenant_id', $user->tenant_id)
                        ->where('form_tutor_id', $user->id)
                        ->exists();
                }

                if ($key === 'scores') {
                    // Score Entry is a core academic workspace. Academic staff
                    // (including subject teachers) must be able to open it even when
                    // their role permissions do not redundantly include a scores alias.
                    // The score endpoints themselves still scope teachers to their
                    // assigned classes/subjects, so module visibility does not broaden
                    // the records a teacher may edit.
                    if ($isAcademicStaff) return true;

                    return $user->canAccessExactModule('scores')
                        || $user->canAccessExactModule('scores.entry')
                        || $user->canAccessExactModule('scores.view')
                        || $this->hasExplicitAcademicGrant($user, $key);
                }

                if ($key === 'reports') {
                    return $user->canAccessModule('reports')
                        || $user->canAccessExactModule('report-cards')
                        || $user->canAccessExactModule('results')
                        || $this->hasExplicitAcademicGrant($user, $key);
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

    private function isAcademicStaff(User $user): bool
    {
        $roles = collect($user->getRoleNames())
            ->map(fn ($role): string => strtolower((string) $role))
            ->push(strtolower((string) $user->roleKey()))
            ->unique();

        return $roles->contains(fn (string $role): bool => in_array($role, self::ACADEMIC_ROLE_KEYS, true));
    }

    private function hasExplicitAcademicGrant(User $user, string $moduleKey): bool
    {
        foreach (self::EXPLICIT_ACADEMIC_PERMISSION_KEYS[$moduleKey] ?? [$moduleKey] as $permission) {
            if ($user->hasGrantedPermission($permission)) return true;
        }
        return false;
    }

    private function canManageStaffAttendance(User $user): bool
    {
        return (bool) $user->tenant_id
            && $user->isTenantStaff()
            && (
                in_array($user->roleKey(), self::STAFF_ATTENDANCE_MANAGEMENT_ROLES, true)
                || $user->canManage('staff-attendance')
            );
    }
}
