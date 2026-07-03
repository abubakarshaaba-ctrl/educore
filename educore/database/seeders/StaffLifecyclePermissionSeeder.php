<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StaffLifecyclePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'staff.status.view',
            'staff.status.change',
            'staff.status.approve',
            'staff.archive.view',
            'staff.archive.export',
            'staff.reinstate',
            'staff.reinstate-terminated',
            'staff.work-history.view',
            'staff.work-history.manage',
            'staff.work-history.approve',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $all = $permissions;

        $assignments = [
            'admin' => $all,
            'administrator' => $all,
            'principal' => $all,
            'head' => $all,
            'head_teacher' => $all,
            'vice_principal' => [
                'staff.status.view',
                'staff.archive.view',
                'staff.work-history.view',
            ],
            'academic_administrator' => [
                'staff.status.view',
                'staff.archive.view',
                'staff.work-history.view',
            ],
            'accountant' => [
                'staff.archive.view',
                'staff.work-history.view',
            ],
        ];

        foreach ($assignments as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->givePermissionTo($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
