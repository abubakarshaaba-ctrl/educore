<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StudentLifecyclePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'student.status.view',
            'student.status.change',
            'student.status.approve',
            'student.archive.view',
            'student.archive.export',
            'student.reactivate',
            'student.readmit',
            'student.status.correct-graduation',
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
                'student.status.view',
                'student.status.change',
                'student.archive.view',
            ],
            'academic_administrator' => [
                'student.status.view',
                'student.status.change',
                'student.archive.view',
            ],
            'admission_officer' => [
                'student.status.view',
                'student.archive.view',
            ],
            'form_teacher' => [
                'student.status.view',
            ],
        ];

        foreach ($assignments as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->givePermissionTo($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
