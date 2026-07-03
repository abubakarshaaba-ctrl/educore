<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // Find Greenfield Academy tenant
        $tenant = \App\Models\Tenant::where('slug', 'greenfield-academy')->first();

        if (!$tenant) {
            $this->command->error('Greenfield Academy tenant not found. Run DemoTenantSeeder first.');
            return;
        }

        $staff = [
            // Teachers
            [
                'name'       => 'Aminu Bello',
                'email'      => 'aminu.bello@greenfieldacademy.ng',
                'role'       => 'subject_teacher',
                'password'   => 'Teacher@2025!',
            ],
            [
                'name'       => 'Fatima Usman',
                'email'      => 'fatima.usman@greenfieldacademy.ng',
                'role'       => 'subject_teacher',
                'password'   => 'Teacher@2025!',
            ],
            [
                'name'       => 'Emeka Okafor',
                'email'      => 'emeka.okafor@greenfieldacademy.ng',
                'role'       => 'subject_teacher',
                'password'   => 'Teacher@2025!',
            ],
            [
                'name'       => 'Ngozi Adeyemi',
                'email'      => 'ngozi.adeyemi@greenfieldacademy.ng',
                'role'       => 'subject_teacher',
                'password'   => 'Teacher@2025!',
            ],
            [
                'name'       => 'Ibrahim Musa',
                'email'      => 'ibrahim.musa@greenfieldacademy.ng',
                'role'       => 'subject_teacher',
                'password'   => 'Teacher@2025!',
            ],
            [
                'name'       => 'Chioma Nwosu',
                'email'      => 'chioma.nwosu@greenfieldacademy.ng',
                'role'       => 'subject_teacher',
                'password'   => 'Teacher@2025!',
            ],
            // Accountant
            [
                'name'       => 'Yusuf Abdullahi',
                'email'      => 'yusuf.abdullahi@greenfieldacademy.ng',
                'role'       => 'accountant',
                'password'   => 'Staff@2025!',
            ],
        ];

        foreach ($staff as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'tenant_id'  => $tenant->id,
                    'name'       => $data['name'],
                    'role'       => $data['role'],
                    'password'   => Hash::make($data['password']),
                    'is_active'  => true,
                ]
            );
            $user->assignRole($data['role']);
        }

        $this->command->info('✅ Staff seeded for: ' . $tenant->name);
        $this->command->info('   Teachers: 6 · Accountant: 1');
        $this->command->info('   Password: Teacher@2025! / Staff@2025!');
    }
}
