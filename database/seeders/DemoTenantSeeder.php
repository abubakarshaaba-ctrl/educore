<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\GradingSystem;
use App\Models\PromotionRule;
use App\Models\Scopes\TenantContext;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // 1. Create Demo School (Tenant)
        // ---------------------------------------------------------------
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'greenfield-academy'],
            [
                'name'                    => 'Greenfield Academy',
                'subdomain'               => 'greenfield',
                'address'                 => '12 School Road, Maitama, Abuja, FCT',
                'phone'                   => '08012345678',
                'email'                   => 'info@greenfieldacademy.ng',
                'status'                  => 'active',
                'subscription_expires_at' => now()->addYear(),
            ]
        );

        $this->command->info("✅ Tenant created: {$tenant->name} (ID: {$tenant->id})");

        // Set tenant context for all subsequent model creates
        TenantContext::set($tenant->id);

        // ---------------------------------------------------------------
        // 2. Create School Admin User
        // ---------------------------------------------------------------
        $admin = User::firstOrCreate(
            ['email' => 'admin@greenfieldacademy.ng'],
            [
                'name'      => 'School Administrator',
                'password'  => Hash::make('Admin@2025!'),
                'tenant_id' => $tenant->id,
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // ---------------------------------------------------------------
        // 3. Create Academic Session & Terms
        // ---------------------------------------------------------------
        $session = AcademicSession::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => '2025/2026'],
            ['is_current' => true]
        );

        $terms = [
            ['name' => '1st Term', 'start_date' => '2025-09-08', 'end_date' => '2025-12-12', 'is_current' => true],
            ['name' => '2nd Term', 'start_date' => '2026-01-12', 'end_date' => '2026-04-03', 'is_current' => false],
            ['name' => '3rd Term', 'start_date' => '2026-04-27', 'end_date' => '2026-07-17', 'is_current' => false],
        ];

        foreach ($terms as $termData) {
            Term::firstOrCreate(
                ['tenant_id' => $tenant->id, 'session_id' => $session->id, 'name' => $termData['name']],
                $termData + ['session_id' => $session->id, 'tenant_id' => $tenant->id]
            );
        }

        // ---------------------------------------------------------------
        // 4. Create Class Levels & Arms
        // ---------------------------------------------------------------
        $classStructure = [
            ['name' => 'JSS 1', 'section' => 'junior_secondary', 'order_index' => 7,  'arms' => ['A', 'B', 'C']],
            ['name' => 'JSS 2', 'section' => 'junior_secondary', 'order_index' => 8,  'arms' => ['A', 'B']],
            ['name' => 'JSS 3', 'section' => 'junior_secondary', 'order_index' => 9,  'arms' => ['A', 'B']],
            ['name' => 'SSS 1', 'section' => 'senior_secondary', 'order_index' => 10, 'arms' => ['Science', 'Arts', 'Commercial']],
            ['name' => 'SSS 2', 'section' => 'senior_secondary', 'order_index' => 11, 'arms' => ['Science', 'Arts', 'Commercial']],
            ['name' => 'SSS 3', 'section' => 'senior_secondary', 'order_index' => 12, 'arms' => ['Science', 'Arts', 'Commercial']],
        ];

        foreach ($classStructure as $levelData) {
            $level = ClassLevel::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $levelData['name']],
                [
                    'section'     => $levelData['section'],
                    'order_index' => $levelData['order_index'],
                ]
            );

            foreach ($levelData['arms'] as $armName) {
                ClassArm::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'name' => $armName]
                );
            }

            // ---------------------------------------------------------------
            // 5. Grading System (Nigerian WAEC scale per class level)
            // ---------------------------------------------------------------
            $grades = [
                ['grade_letter' => 'A1', 'min_score' => 75, 'max_score' => 100, 'remark' => 'Excellent',      'is_pass_grade' => true,  'grade_point' => 1],
                ['grade_letter' => 'B2', 'min_score' => 70, 'max_score' => 74,  'remark' => 'Very Good',      'is_pass_grade' => true,  'grade_point' => 2],
                ['grade_letter' => 'B3', 'min_score' => 65, 'max_score' => 69,  'remark' => 'Good',           'is_pass_grade' => true,  'grade_point' => 3],
                ['grade_letter' => 'C4', 'min_score' => 60, 'max_score' => 64,  'remark' => 'Credit',         'is_pass_grade' => true,  'grade_point' => 4],
                ['grade_letter' => 'C5', 'min_score' => 55, 'max_score' => 59,  'remark' => 'Credit',         'is_pass_grade' => true,  'grade_point' => 5],
                ['grade_letter' => 'C6', 'min_score' => 50, 'max_score' => 54,  'remark' => 'Credit',         'is_pass_grade' => true,  'grade_point' => 6],
                ['grade_letter' => 'D7', 'min_score' => 45, 'max_score' => 49,  'remark' => 'Pass',           'is_pass_grade' => true,  'grade_point' => 7],
                ['grade_letter' => 'E8', 'min_score' => 40, 'max_score' => 44,  'remark' => 'Pass',           'is_pass_grade' => true,  'grade_point' => 8],
                ['grade_letter' => 'F9', 'min_score' => 0,  'max_score' => 39,  'remark' => 'Fail',           'is_pass_grade' => false, 'grade_point' => 9],
            ];

            foreach ($grades as $grade) {
                GradingSystem::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'grade_letter' => $grade['grade_letter']],
                    $grade + ['tenant_id' => $tenant->id, 'class_level_id' => $level->id]
                );
            }

            // ---------------------------------------------------------------
            // 6. Promotion Rules per Class Level
            // ---------------------------------------------------------------
            PromotionRule::firstOrCreate(
                ['tenant_id' => $tenant->id, 'class_level_id' => $level->id],
                [
                    'min_required_average'       => 40,
                    'max_failed_subjects_allowed' => 3,
                    'compulsory_subject_ids'      => null,
                ]
            );
        }

        // Clear tenant context
        TenantContext::clear();

        $this->command->info('✅ Demo tenant seeded successfully.');
        $this->command->line('');
        $this->command->line('   School  : Greenfield Academy');
        $this->command->line('   Admin   : admin@greenfieldacademy.ng');
        $this->command->line('   Password: Admin@2025!');
        $this->command->line('');
        $this->command->line('   Classes : JSS 1–3, SSS 1–3 with arms');
        $this->command->line('   Grading : Nigerian WAEC scale (A1–F9)');
        $this->command->line('   Session : 2025/2026 (1st Term active)');
    }
}
