<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AcademicCyclePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'academic-cycle.view',
            'academic-session.manage',
            'academic-term.manage',
            'student-promotion.view',
            'student-promotion.manage',
            'academic-rollover.preview',
            'academic-rollover.execute',
            'academic-cycle.repair',
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
                'academic-cycle.view',
                'student-promotion.view',
                'student-promotion.manage',
                'academic-rollover.preview',
            ],
            'academic_administrator' => [
                'academic-cycle.view',
                'academic-session.manage',
                'academic-term.manage',
                'student-promotion.view',
                'student-promotion.manage',
                'academic-rollover.preview',
                'academic-cycle.repair',
            ],
        ];

        foreach ($assignments as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->givePermissionTo($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
