<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Run order matters — roles must exist before users are assigned them.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,  // 1. Create all roles & permissions
            StudentLifecyclePermissionSeeder::class, // 1b. Student archive/status permissions
            StaffLifecyclePermissionSeeder::class,   // 1c. Staff archive/status permissions
            SuperAdminSeeder::class,            // 2. Create the platform super-admin
            DemoTenantSeeder::class,            // 3. Create demo school + admin + classes
        ]);
    }
}
