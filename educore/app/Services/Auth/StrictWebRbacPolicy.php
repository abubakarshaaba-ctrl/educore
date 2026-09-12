<?php

namespace App\Services\Auth;

use App\Models\StaffPermission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Web-shell RBAC policy that keeps the browser application aligned with the
 * strict role-scoped module policy used by the native app.
 *
 * User::ROLE_ACCESS remains the base role contract. This layer adds the
 * operational restrictions that must be identical across web and mobile,
 * while still allowing an administrator to make an explicit per-staff grant.
 */
class StrictWebRbacPolicy
{
    private const ACCOUNTANT_MODULES = [
        'dashboard', 'staff-attendance.self', 'fees', 'expenses', 'payroll',
        'messages', 'notifications.view', 'calendar.view', 'profile',
    ];

    private const ACADEMIC_MODULES = [
        'classes', 'subjects', 'curriculum', 'academic-cycle', 'attendance',
        'skills', 'scores', 'reports', 'timetable', 'lesson-planner',
        'academic-repository', 'gradebook',
    ];

    private const ACADEMIC_ROLE_KEYS = [
        'admin', 'principal', 'head', 'head_teacher', 'head_of_school', 'head_of_schools',
        'vice_principal', 'academic_administrator', 'director_of_studies',
        'hod', 'head_of_department', 'teacher', 'subject_teacher', 'class_teacher',
        'form_teacher', 'asst_form_teacher', 'form_subject_teacher',
    ];

    private const STAFF_ATTENDANCE_MANAGEMENT_ROLES = [
        'admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator',
    ];

    private const ADMISSION_OFFICER_BLOCKED = ['students', 'transfers'];

    /** @var array<int, Collection<string, string>> */
    private array $overrides = [];

    public function moduleAllowed(User $user, string $module): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $module = $this->normalizeModule($module);
        $override = $this->override($user, $module);
        if ($override === 'deny') {
            return false;
        }
        if ($override === 'grant') {
            return true;
        }

        $role = strtolower((string) $user->roleKey());

        if (in_array($role, ['accountant', 'bursar', 'finance_officer'], true)
            && ! in_array($module, self::ACCOUNTANT_MODULES, true)) {
            return false;
        }

        if ($role === 'admission_officer' && in_array($module, self::ADMISSION_OFFICER_BLOCKED, true)) {
            return false;
        }

        if (in_array($module, self::ACADEMIC_MODULES, true)
            && ! in_array($role, self::ACADEMIC_ROLE_KEYS, true)) {
            return false;
        }

        if ($module === 'staff-attendance.admin') {
            return in_array($role, self::STAFF_ATTENDANCE_MANAGEMENT_ROLES, true)
                || $this->override($user, 'staff-attendance') === 'grant';
        }

        if ($module === 'staff-attendance.self') {
            return $user->isTenantStaff() && $this->baseAllows($user, 'staff-attendance.self');
        }

        if ($module === 'academic-repository') {
            return in_array($role, self::ACADEMIC_ROLE_KEYS, true);
        }

        return $this->baseAllows($user, $module);
    }

    public function moduleForRoute(?string $routeName): ?string
    {
        $routeName = trim((string) $routeName);
        if ($routeName === '') {
            return null;
        }

        // The staff portal is an already-scoped self-service surface. Its route
        // names begin with "staff." and must never be mistaken for Staff Directory.
        if (str_starts_with($routeName, 'staff.portal.')) {
            return null;
        }

        if (str_starts_with($routeName, 'profile')) {
            return 'profile';
        }

        if (str_starts_with($routeName, 'academic-repository')) {
            return 'academic-repository';
        }
        if (str_starts_with($routeName, 'lesson-planner')) {
            return 'lesson-planner';
        }

        if (str_starts_with($routeName, 'staff-attendance.')) {
            $selfRoutes = [
                'staff-attendance.my', 'staff-attendance.set-pin', 'staff-attendance.proxy',
                'staff-attendance.api.clockin', 'staff-attendance.api.clockout',
                'staff-attendance.api.proxy.initiate', 'staff-attendance.api.proxy.verify',
                'staff-attendance.api.offline', 'staff-attendance.id-card',
            ];

            return in_array($routeName, $selfRoutes, true)
                ? 'staff-attendance.self'
                : 'staff-attendance.admin';
        }

        if (str_starts_with($routeName, 'students.transfers') || str_starts_with($routeName, 'students.class-transfers')) {
            return 'transfers';
        }

        $topLevel = [
            'dashboard' => 'dashboard',
            'students' => 'students',
            'staff' => 'staff',
            'classes' => 'classes',
            'academic-cycle' => 'academic-cycle',
            'subjects' => 'subjects',
            'curriculum' => 'curriculum',
            'attendance' => 'attendance',
            'scores' => 'scores',
            'reports' => 'reports',
            'timetable' => 'timetable',
            'skills' => 'skills',
            'cbt' => 'cbt',
            'admissions' => 'admissions',
            'fees' => 'fees',
            'expenses' => 'expenses',
            'payroll' => 'payroll',
            'health' => 'health',
            'library' => 'library',
            'transport' => 'transport',
            'hostels' => 'hostels',
            'inventory' => 'inventory',
            'asc' => 'asc',
            'analytics' => 'analytics',
            'risk' => 'risk',
            'exports' => 'exports',
            'messages' => 'messages',
            'notifications' => 'notifications.view',
            'calendar' => 'calendar.view',
            'settings' => 'settings',
            'portal-accounts' => 'portal-accounts',
            'gradebook' => 'gradebook',
        ];

        foreach ($topLevel as $prefix => $module) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                return $module;
            }
        }

        return null;
    }

    public function navigationPaths(): array
    {
        return [
            'students' => '/students',
            'staff' => '/staff',
            'classes' => '/classes',
            'academic-cycle' => '/academic-session',
            'subjects' => '/subjects',
            'curriculum' => '/curriculum',
            'academic-repository' => '/academic-repository',
            'attendance' => '/attendance',
            'scores' => '/scores',
            'reports' => '/reports',
            'timetable' => '/timetable',
            'skills' => '/skills',
            'lesson-planner' => '/lesson-planner',
            'cbt' => '/cbt',
            'admissions' => '/admissions',
            'fees' => '/fees',
            'expenses' => '/expenses',
            'payroll' => '/payroll',
            'health' => '/health',
            'library' => '/library',
            'transport' => '/transport',
            'hostels' => '/hostels',
            'inventory' => '/inventory',
            'asc' => '/asc',
            'analytics' => '/analytics',
            'risk' => '/risk',
            'exports' => '/exports',
            'settings' => '/settings',
            'portal-accounts' => '/portal-accounts',
            'gradebook' => '/gradebook',
        ];
    }

    private function normalizeModule(string $module): string
    {
        return match ($module) {
            'staff-attendance' => 'staff-attendance.admin',
            default => $module,
        };
    }

    private function baseAllows(User $user, string $module): bool
    {
        $roleAllowed = User::ROLE_ACCESS[$user->roleKey()] ?? [];
        if (in_array('*', $roleAllowed, true) || in_array($module, $roleAllowed, true)) {
            return true;
        }

        foreach ($roleAllowed as $permission) {
            if (str_starts_with((string) $permission, $module.'.')) {
                return true;
            }
        }

        return false;
    }

    private function override(User $user, string $module): ?string
    {
        if (! Schema::hasTable('staff_permissions')) {
            return null;
        }

        if (! array_key_exists($user->id, $this->overrides)) {
            $this->overrides[$user->id] = StaffPermission::query()
                ->where('user_id', $user->id)
                ->pluck('type', 'module');
        }

        $overrides = $this->overrides[$user->id];
        if ($overrides->has($module)) {
            return (string) $overrides->get($module);
        }

        if ($module === 'staff-attendance.admin' && $overrides->has('staff-attendance')) {
            return (string) $overrides->get('staff-attendance');
        }

        return null;
    }
}
