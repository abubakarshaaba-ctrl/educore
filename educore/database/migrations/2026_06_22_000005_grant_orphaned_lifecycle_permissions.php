<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        // Force Spatie's permission cache onto the in-memory array driver for the
        // duration of this migration. This matters when CACHE_STORE=database is set
        // (e.g. Railway) but the `cache` table doesn't exist yet on a fresh install.
        // Changing config() alone is not enough — the PermissionRegistrar singleton
        // was already instantiated with the previous store. Forgetting the instance
        // forces re-instantiation on the next app() call, picking up the new config.
        config(['permission.cache.store' => 'array']);
        app()->forgetInstance(PermissionRegistrar::class);

        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Swallow any residual cache errors on the fresh instance.
        }

        $studentPermissions = [
            'student.status.view', 'student.status.change', 'student.status.approve',
            'student.archive.view', 'student.archive.export',
            'student.reactivate', 'student.readmit', 'student.status.correct-graduation',
        ];
        $staffPermissions = [
            'staff.status.view', 'staff.status.change', 'staff.status.approve',
            'staff.archive.view', 'staff.archive.export',
            'staff.reinstate', 'staff.reinstate-terminated',
            'staff.work-history.view', 'staff.work-history.manage', 'staff.work-history.approve',
        ];

        foreach (array_merge($studentPermissions, $staffPermissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $assignments = [
            'admin'                   => array_merge($studentPermissions, $staffPermissions),
            'administrator'           => array_merge($studentPermissions, $staffPermissions),
            'principal'               => array_merge($studentPermissions, $staffPermissions),
            'head'                    => array_merge($studentPermissions, $staffPermissions),
            'head_teacher'            => array_merge($studentPermissions, $staffPermissions),
            'vice_principal'          => [
                'student.status.view', 'student.status.change', 'student.archive.view',
                'staff.status.view', 'staff.archive.view', 'staff.work-history.view',
            ],
            'academic_administrator'  => [
                'student.status.view', 'student.status.change', 'student.archive.view',
                'staff.status.view', 'staff.archive.view', 'staff.work-history.view',
            ],
            'admission_officer'       => ['student.status.view', 'student.archive.view'],
            'form_teacher'            => ['student.status.view'],
            'accountant'              => ['staff.archive.view', 'staff.work-history.view'],
        ];

        foreach ($assignments as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            // givePermissionTo is additive — safe to re-run on existing roles.
            $role->givePermissionTo($rolePermissions);
        }

        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Swallow — cache table may not exist yet on fresh installs.
        }
    }

    public function down(): void
    {
        // Non-destructive: leave permissions in place on rollback.
    }
};
