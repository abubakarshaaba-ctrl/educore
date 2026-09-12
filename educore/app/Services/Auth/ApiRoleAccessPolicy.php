<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Authorizes mobile API requests against the same explicit module contract used
 * by the web application. The API fails closed for tenant staff: a route family
 * must map to an allowed module or an explicit per-staff grant.
 */
class ApiRoleAccessPolicy
{
    private const ADMISSION_OFFICER_DEFAULT_DENIES = [
        'students', 'transfers', 'classes', 'subjects', 'curriculum',
        'academic-cycle', 'scores', 'scores.entry', 'scores.view',
        'reports', 'reports.view', 'reports.remarks', 'attendance',
        'timetable', 'timetable.view', 'skills', 'cbt', 'gradebook',
        'academic-repository', 'lesson-planner',
    ];

    public function allows(User $user, Request $request): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->isStudent() || $user->isParent()) return true;
        if (! $user->isTenantStaff() || ! $user->isEmploymentActive()) return false;

        $path = trim($request->path(), '/');
        $path = preg_replace('#^api/v1/#', '', $path) ?? $path;
        $method = strtoupper($request->method());

        if ($path === 'auth/logout' || $path === 'bootstrap' || $path === 'me') return true;
        if ($path === 'dashboard' || $path === 'admin/dashboard') return $this->allowsAny($user, ['dashboard']);
        if ($path === 'portal/modules' || $path === 'portal/session') return true;
        if (preg_match('#^(push|devices)/(register|unregister)$#', $path)) return true;
        if (str_starts_with($path, 'id-card') || str_starts_with($path, 'payslips')) return $this->allowsAny($user, ['profile']);
        if (str_starts_with($path, 'exam-duties')) return $this->allowsAny($user, ['timetable', 'timetable.view', 'dashboard']);
        if ($path === 'schedule') return $this->allowsAny($user, ['timetable', 'timetable.view', 'calendar', 'calendar.view']);

        if (preg_match('#^operations/([^/]+)#', $path, $match)) {
            $module = $match[1] === 'finance' ? 'fees' : $match[1];
            return $this->allowsModule($user, $module);
        }

        $rules = [
            'academic-repository' => ['academic-repository'],
            'lesson-plans' => ['lesson-planner'],
            'cbt' => ['cbt'],
            'classes' => ['classes', 'attendance', 'scores', 'scores.entry', 'scores.view'],
            'announcements' => ['announcements', 'notifications.view'],
            'staff-attendance' => ['staff-attendance.self'],
            'scores' => ['scores', 'scores.entry', 'scores.view'],
            'timetable' => ['timetable', 'timetable.view'],
            'messages' => ['messages'],
            'admissions' => ['admissions'],
            'transport-officer' => ['transport'],
            'health-officer' => ['health'],
            'library' => ['library'],
            'inventory' => ['inventory'],
            'hostels' => ['hostels'],
            'subjects' => ['subjects'],
            'curriculum' => ['curriculum'],
            'academic-cycle' => ['academic-cycle'],
            'skills' => ['skills'],
            'gradebook' => ['gradebook'],
            'reports' => ['reports', 'reports.view', 'reports.remarks'],
            'portal-accounts' => ['portal-accounts'],
            'school-settings' => ['settings'],
            'transfers' => ['transfers'],
            'fees' => ['fees'],
            'expenses' => ['expenses'],
            'payroll' => ['payroll'],
            'analytics' => ['analytics'],
            'risk' => ['risk'],
            'exports' => ['exports'],
        ];

        if (str_starts_with($path, 'notifications')) return $this->allowsAny($user, ['notifications', 'notifications.send', 'notifications.view']);
        if (str_starts_with($path, 'calendar/events')) {
            return $method === 'GET'
                ? $this->allowsAny($user, ['calendar', 'calendar.view'])
                : $this->allowsAny($user, ['calendar']);
        }
        if (str_starts_with($path, 'admin/staff-attendance')) return $this->allowsAny($user, ['staff-attendance']);
        if (str_starts_with($path, 'admin/students')) return $this->allowsAny($user, ['students']);
        if (str_starts_with($path, 'admin/staff')) return $this->allowsAny($user, ['staff']);
        if (str_starts_with($path, 'admin/academics')) return $this->allowsAny($user, ['classes', 'subjects', 'curriculum', 'academic-cycle']);
        if (str_starts_with($path, 'admin/finance')) return $this->allowsAny($user, ['fees', 'expenses', 'payroll']);
        if (str_starts_with($path, 'admin/management')) return $this->allowsAny($user, ['settings', 'subjects', 'classes']);
        if (str_starts_with($path, 'accountant')) return $this->allowsAny($user, ['fees', 'expenses', 'payroll']);

        foreach ($rules as $prefix => $modules) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $this->allowsAny($user, $modules);
            }
        }

        return false;
    }

    private function allowsAny(User $user, array $modules): bool
    {
        foreach ($modules as $module) {
            if ($this->allowsModule($user, $module)) return true;
        }
        return false;
    }

    private function allowsModule(User $user, string $module): bool
    {
        if ($user->hasDeniedPermission($module)) return false;
        if ($user->hasGrantedPermission($module)) return true;
        if ($user->roleKey() === 'admission_officer' && in_array($module, self::ADMISSION_OFFICER_DEFAULT_DENIES, true)) return false;
        if ($user->canAccessExactModule($module)) return true;
        foreach ($user->effectivePermissionKeys() as $permission) {
            if (str_starts_with($permission, $module.'.')) return true;
        }
        if ($module === 'academic-repository') return $user->isAdmin() || $user->isTeacher();
        return false;
    }
}
