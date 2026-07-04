<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create the super-admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@sms.ng'],
            [
                'name'           => 'Super Administrator',
                'password'       => Hash::make('SuperAdmin@2025!'),
                'is_super_admin' => true,
                'is_active'      => true,
                'tenant_id'      => null,
                'role'           => null,
            ]
        );

        // Assign the super admin role used by RolesAndPermissionsSeeder.
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->assignRole('super_admin');

        $this->command->info('✅ Super Admin created.');
        $this->command->line('   Email   : superadmin@sms.ng');
        $this->command->line('   Password: SuperAdmin@2025!');
        $this->command->warn('   ⚠️  Change this password immediately after first login!');
    }
}
