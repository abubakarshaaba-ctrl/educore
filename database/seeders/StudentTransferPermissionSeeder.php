<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StudentTransferPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'student.transfer.request',
            'student.transfer.approve',
            'student.transfer.reject',
            'student.transfer.cancel',
            'student.transfer.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $assignments = [
            'admin' => $permissions,
            'administrator' => $permissions,
            'principal' => $permissions,
            'vice_principal' => [
                'student.transfer.request',
                'student.transfer.view',
            ],
            'admission_officer' => [
                'student.transfer.request',
                'student.transfer.view',
            ],
        ];

        foreach ($assignments as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->givePermissionTo($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
