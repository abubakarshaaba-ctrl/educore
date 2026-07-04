<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ---------------------------------------------------------------
        // PERMISSIONS — grouped by module
        // ---------------------------------------------------------------
        $permissions = [
            // Dashboard
            'dashboard.view',

            // Students
            'students.view', 'students.create', 'students.admit', 'students.edit', 'students.delete',
            'student.transfer.request', 'student.transfer.approve', 'student.transfer.reject',
            'student.transfer.cancel', 'student.transfer.view',

            // Staff
            'staff.view', 'staff.create', 'staff.edit',

            // Classes
            'classes.view', 'classes.manage',

            // Subjects
            'subjects.view', 'subjects.manage',

            // Scores
            'scores.view',              // see broadsheet
            'scores.enter.own',         // subject teacher enters scores for their subject
            'scores.enter.all',         // admin/principal can enter any score

            // Report cards
            'reports.view', 'reports.compute', 'reports.pdf',
            'reports.remarks.teacher',  // form teacher enters teacher remark
            'reports.remarks.principal',// principal enters principal remark
            'reports.remarks.bulk',     // bulk auto-generate

            // Skills
            'skills.view', 'skills.enter',

            // Attendance
            'attendance.view', 'attendance.mark',

            // Timetable
            'timetable.view',
            'timetable.view.own',       // subject teacher sees own timetable
            'timetable.manage',         // admin configures timetable

            // CBT
            'cbt.view', 'cbt.manage',

            // Fees
            'fees.view', 'fees.manage',

            // Notifications
            'notifications.send',

            // Super admin
            'super.access',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ---------------------------------------------------------------
        // ROLES + PERMISSION ASSIGNMENTS
        // ---------------------------------------------------------------

        // Super Admin — platform-wide, no tenant scope
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(['super.access', 'dashboard.view']);

        // Admin — full school access
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'dashboard.view',
            'students.view', 'students.create', 'students.admit', 'students.edit', 'students.delete',
            'student.transfer.request', 'student.transfer.approve', 'student.transfer.reject',
            'student.transfer.cancel', 'student.transfer.view',
            'staff.view', 'staff.create', 'staff.edit',
            'classes.view', 'classes.manage',
            'subjects.view', 'subjects.manage',
            'scores.view', 'scores.enter.all',
            'reports.view', 'reports.compute', 'reports.pdf',
            'reports.remarks.teacher', 'reports.remarks.principal', 'reports.remarks.bulk',
            'skills.view', 'skills.enter',
            'attendance.view', 'attendance.mark',
            'timetable.view', 'timetable.manage',
            'cbt.view', 'cbt.manage',
            'fees.view', 'fees.manage',
            'notifications.send',
        ]);

        // Principal — same as admin minus staff management & fee management
        $principal = Role::firstOrCreate(['name' => 'principal', 'guard_name' => 'web']);
        $principal->syncPermissions([
            'dashboard.view',
            'students.view', 'students.admit', 'students.edit',
            'student.transfer.request', 'student.transfer.approve', 'student.transfer.reject',
            'student.transfer.cancel', 'student.transfer.view',
            'staff.view',
            'classes.view', 'classes.manage',
            'subjects.view', 'subjects.manage',
            'scores.view', 'scores.enter.all',
            'reports.view', 'reports.compute', 'reports.pdf',
            'reports.remarks.principal', 'reports.remarks.bulk',
            'skills.view', 'skills.enter',
            'attendance.view', 'attendance.mark',
            'timetable.view', 'timetable.manage',
            'cbt.view', 'cbt.manage',
            'notifications.send',
        ]);

        // Vice Principal — like principal minus principal remarks
        $vp = Role::firstOrCreate(['name' => 'vice_principal', 'guard_name' => 'web']);
        $vp->syncPermissions([
            'dashboard.view',
            'students.view', 'students.admit', 'students.edit',
            'student.transfer.request', 'student.transfer.view',
            'staff.view',
            'classes.view',
            'subjects.view',
            'scores.view', 'scores.enter.all',
            'reports.view', 'reports.pdf',
            'reports.remarks.teacher',
            'skills.view', 'skills.enter',
            'attendance.view', 'attendance.mark',
            'timetable.view',
            'cbt.view',
        ]);

        // Form Teacher — teacher remark, class timetable, attendance for own class
        $formTeacher = Role::firstOrCreate(['name' => 'form_teacher', 'guard_name' => 'web']);
        $formTeacher->syncPermissions([
            'dashboard.view',
            'students.view',
            'scores.view',
            'reports.view', 'reports.pdf',
            'reports.remarks.teacher',
            'skills.view', 'skills.enter',
            'attendance.view', 'attendance.mark',
            'timetable.view',
        ]);

        // Subject Teacher — enter own subject scores, view own timetable
        $subjectTeacher = Role::firstOrCreate(['name' => 'subject_teacher', 'guard_name' => 'web']);
        $subjectTeacher->syncPermissions([
            'dashboard.view',
            'students.view',
            'scores.view', 'scores.enter.own',
            'reports.view',
            'attendance.view', 'attendance.mark',
            'timetable.view.own',
            'cbt.view', 'cbt.manage',
        ]);

        // Teacher (legacy - full teacher access)
        $teacher = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $teacher->syncPermissions([
            'dashboard.view',
            'students.view',
            'scores.view', 'scores.enter.own',
            'reports.view', 'reports.pdf',
            'attendance.view', 'attendance.mark',
            'timetable.view.own',
            'cbt.view', 'cbt.manage',
        ]);

        // Accountant — fees only
        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->syncPermissions([
            'dashboard.view',
            'students.view',
            'fees.view', 'fees.manage',
        ]);

        $adminPermissions = $admin->permissions()->pluck('name')->all();
        $formTeacherPermissions = $formTeacher->permissions()->pluck('name')->all();
        $subjectTeacherPermissions = $subjectTeacher->permissions()->pluck('name')->all();
        $supportPermissions = ['dashboard.view', 'students.view'];

        $rolePermissions = [
            'super-admin'            => ['super.access', 'dashboard.view'],
            'administrator'          => $adminPermissions,
            'admission_officer'      => ['dashboard.view', 'students.view', 'students.create', 'students.admit',
                                         'student.transfer.request', 'student.transfer.view'],
            'asst_form_teacher'      => $formTeacherPermissions,
            'assistant_form_teacher' => $formTeacherPermissions,
            'form_subject_teacher'   => array_values(array_unique(array_merge($formTeacherPermissions, $subjectTeacherPermissions))),
            'health_officer'         => $supportPermissions,
            'librarian'              => $supportPermissions,
            'transport_officer'      => $supportPermissions,
            'communication_officer'  => ['dashboard.view', 'notifications.send'],
            'driver'                 => ['dashboard.view'],
            'bus_assistant'          => ['dashboard.view'],
            'student'                => ['dashboard.view'],
            'parent'                 => ['dashboard.view'],
        ];

        foreach ($rolePermissions as $role => $rolePerms) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->syncPermissions($rolePerms);
        }

        $this->command->info('✅ Roles and permissions seeded:');
        $this->command->info('   super_admin, admin, principal, vice_principal,');
        $this->command->info('   staff roles, portal roles, and legacy role aliases');
        $this->command->info('   Total permissions: ' . count($permissions));
    }
}
