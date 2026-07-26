<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MobilePortalController extends Controller
{
    private const MODULES = [
        'dashboard' => ['Dashboard', '/dashboard', 'dashboard'],
        'students' => ['Students', '/students', 'students'],
        'staff' => ['Staff', '/staff', 'staff'],
        'classes' => ['Classes', '/classes', 'classes'],
        'subjects' => ['Subjects', '/subjects', 'subjects'],
        'attendance' => ['Student Attendance', '/attendance', 'attendance'],
        'staff-attendance' => ['Staff Attendance', '/staff-attendance/my', 'staff-attendance'],
        'staff-attendance.self' => ['My Attendance', '/staff-attendance/my', 'staff-attendance'],
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
        'analytics' => ['Analytics', '/analytics', 'analytics'],
        'exports' => ['Exports', '/exports', 'exports'],
        'cbt' => ['CBT', '/cbt', 'cbt'],
        'lesson-planner' => ['Lesson Planner', '/lesson-planner', 'lesson-planner'],
        'profile' => ['My Profile', '/profile', 'profile'],
    ];

    public function modules(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->isSuperAdmin()) {
            return response()->json(['modules' => [
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
            ]]);
        }

        if ($user->isParent()) {
            return response()->json(['modules' => [
                ['key' => 'parent.dashboard', 'title' => 'Parent Dashboard', 'path' => '/parent/dashboard', 'icon' => 'dashboard'],
                ['key' => 'parent.fees', 'title' => 'Fees & Payments', 'path' => '/parent/fees', 'icon' => 'fees'],
                ['key' => 'parent.results', 'title' => 'Results', 'path' => '/parent/results', 'icon' => 'reports'],
                ['key' => 'parent.attendance', 'title' => 'Attendance', 'path' => '/parent/attendance', 'icon' => 'attendance'],
                ['key' => 'parent.notifications', 'title' => 'Notifications', 'path' => '/parent/notifications', 'icon' => 'notifications'],
                ['key' => 'parent.calendar', 'title' => 'Calendar', 'path' => '/parent/calendar', 'icon' => 'calendar'],
            ]]);
        }

        if ($user->isStudent()) {
            return response()->json(['modules' => [
                ['key' => 'student.dashboard', 'title' => 'Student Dashboard', 'path' => '/student/dashboard', 'icon' => 'dashboard'],
                ['key' => 'student.results', 'title' => 'Results', 'path' => '/student/results', 'icon' => 'reports'],
                ['key' => 'student.timetable', 'title' => 'Timetable', 'path' => '/student/timetable', 'icon' => 'timetable'],
                ['key' => 'student.attendance', 'title' => 'Attendance', 'path' => '/student/attendance', 'icon' => 'attendance'],
                ['key' => 'student.exams', 'title' => 'Examinations', 'path' => '/student/exams', 'icon' => 'cbt'],
                ['key' => 'student.subjects', 'title' => 'Subjects', 'path' => '/student/subjects', 'icon' => 'subjects'],
            ]]);
        }

        $access = User::ROLE_ACCESS[$user->roleKey()] ?? [];
        $modules = collect(self::MODULES)
            ->filter(function ($definition, $key) use ($access) {
                if (in_array('*', $access, true)) return true;
                return collect($access)->contains(fn ($permission) =>
                    $permission === $key || str_starts_with($permission, $key.'.') || str_starts_with($key, $permission.'.')
                );
            })
            ->map(fn ($definition, $key) => [
                'key' => $key, 'title' => $definition[0], 'path' => $definition[1], 'icon' => $definition[2],
            ])->values();

        return response()->json(['modules' => $modules]);
    }

    public function createSession(Request $request)
    {
        $data = $request->validate(['path' => ['required', 'string', 'max:500', 'regex:/^\/(?!\/)/']]);
        $allowed = collect($this->modules($request)->getData(true)['modules'])->pluck('path');
        $path = $data['path'];
        abort_unless($allowed->contains(fn ($base) => $path === $base || str_starts_with($path, rtrim($base, '/').'/')), 403,
            'This route is not available to your role.');

        $token = Str::random(64);
        Cache::put('mobile-web-session:'.$token, ['user_id' => $request->user()->id, 'path' => $path], now()->addMinutes(2));
        return response()->json(['url' => URL::temporarySignedRoute('mobile.web-session', now()->addMinutes(2), ['token' => $token])]);
    }

    public function consumeSession(Request $request, string $token)
    {
        $session = Cache::pull('mobile-web-session:'.$token);
        abort_unless($session, 410, 'This mobile session link has expired or was already used.');
        $user = User::find($session['user_id']);
        abort_unless($user && $user->is_active, 403);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect($session['path']);
    }
}
