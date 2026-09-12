<?php
namespace App\Http\Controllers;

use App\Models\StaffPermission;
use App\Models\User;
use Illuminate\Http\Request;

class StaffPermissionController extends Controller
{
    public const GRANTABLE_MODULES = [
        'students'            => 'Students',
        'staff'               => 'Staff',
        'classes'             => 'Classes',
        'academic-cycle'      => 'Academic Sessions',
        'subjects'            => 'Subjects',
        'curriculum'          => 'Curriculum',
        'academic-repository' => 'Academic Repository',
        'lesson-planner'      => 'Lesson Planner',
        'scores'              => 'Scores & Grades',
        'reports'             => 'Report Cards',
        'transcript'          => 'Transcripts',
        'attendance'          => 'Student Attendance',
        'staff-attendance'    => 'Staff Attendance Management',
        'timetable'           => 'Timetable',
        'exam-timetable'      => 'Exam Timetable & Personal Supervision',
        'skills'              => 'Skill Ratings',
        'cbt'                 => 'CBT Exams',
        'admissions'          => 'Admissions',
        'fees'                => 'Fees',
        'expenses'            => 'Expenses',
        'payroll'             => 'Payroll',
        'health'              => 'Health Records',
        'library'             => 'Library',
        'transport'           => 'Transport',
        'hostels'             => 'Hostels',
        'inventory'           => 'Inventory',
        'announcements'       => 'Announcements',
        'calendar'            => 'Calendar',
        'messages'            => 'Messages',
        'notifications'       => 'Notifications',
        'sms'                 => 'SMS Campaigns',
        'analytics'           => 'Analytics',
        'risk'                => 'Risk Flags',
        'exports'             => 'Data Exports',
        'asc'                 => 'Annual School Census',
        'settings'            => 'Settings',
        'transfers'           => 'Student Transfers',
        'gradebook'           => 'Gradebook',
        'portal-accounts'     => 'Portal Accounts',
    ];

    public function show(User $staff)
    {
        $this->adminOnly();
        $staff = $this->activeTenantStaff($staff);
        $permissions = StaffPermission::where('user_id', $staff->id)->get()->keyBy('module');
        $modules = self::GRANTABLE_MODULES;
        return view('staff.permissions', compact('staff', 'permissions', 'modules'));
    }

    public function update(Request $request, User $staff)
    {
        $this->adminOnly();
        $staff = $this->activeTenantStaff($staff);
        $data = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['in:grant,deny,inherit'],
        ]);
        $tid = auth()->user()->tenant_id;

        foreach (self::GRANTABLE_MODULES as $module => $label) {
            $val = $data['permissions'][$module] ?? 'inherit';
            if ($val === 'inherit') {
                StaffPermission::where('user_id', $staff->id)->where('module', $module)->delete();
            } else {
                StaffPermission::updateOrCreate(
                    ['user_id' => $staff->id, 'module' => $module],
                    ['tenant_id' => $tid, 'type' => $val, 'granted_by' => auth()->id()]
                );
            }
        }
        return back()->with('success', "Permissions updated for {$staff->name}.");
    }

    private function adminOnly(): void
    {
        if (!auth()->user()->canManage('staff')) abort(403);
    }

    private function activeTenantStaff(User $staff): User
    {
        return User::activeStaff((int) auth()->user()->tenant_id)->whereKey($staff->id)->firstOrFail();
    }
}
