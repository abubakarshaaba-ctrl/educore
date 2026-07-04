<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'greenfield-academy')->first();

        if (!$tenant) {
            $this->command->error('Greenfield Academy tenant not found.');
            return;
        }

        // Set tenant context for scoped models
        \App\Models\TenantContext::set($tenant->id);

        $session = AcademicSession::where('tenant_id', $tenant->id)
                                  ->where('is_current', true)
                                  ->first();

        if (!$session) {
            $this->command->error('No current session found.');
            return;
        }

        // ---------------------------------------------------------------
        // Create subjects
        // ---------------------------------------------------------------
        $subjectData = [
            // Core
            ['name' => 'Mathematics',              'code' => 'MTH'],
            ['name' => 'English Language',         'code' => 'ENG'],
            ['name' => 'Basic Science',            'code' => 'BSC'],
            ['name' => 'Social Studies',           'code' => 'SST'],
            ['name' => 'Christian Religious Studies', 'code' => 'CRS'],
            ['name' => 'Islamic Religious Studies',   'code' => 'IRS'],
            // JSS
            ['name' => 'Agricultural Science',    'code' => 'AGR'],
            ['name' => 'Business Studies',        'code' => 'BST'],
            ['name' => 'Computer Studies',        'code' => 'CMP'],
            ['name' => 'Home Economics',          'code' => 'HEC'],
            ['name' => 'Physical and Health Education', 'code' => 'PHE'],
            ['name' => 'Fine Art',                'code' => 'ART'],
            ['name' => 'Yoruba',                  'code' => 'YOR'],
            ['name' => 'Hausa',                   'code' => 'HAU'],
            ['name' => 'Igbo',                    'code' => 'IGB'],
            // SSS
            ['name' => 'Physics',                 'code' => 'PHY'],
            ['name' => 'Chemistry',               'code' => 'CHM'],
            ['name' => 'Biology',                 'code' => 'BIO'],
            ['name' => 'Further Mathematics',     'code' => 'FMT'],
            ['name' => 'Economics',               'code' => 'ECO'],
            ['name' => 'Government',              'code' => 'GOV'],
            ['name' => 'Literature in English',   'code' => 'LIT'],
            ['name' => 'Accounting',              'code' => 'ACC'],
            ['name' => 'Commerce',                'code' => 'COM'],
            ['name' => 'Geography',               'code' => 'GEO'],
        ];

        $subjects = [];
        foreach ($subjectData as $data) {
            $subject = Subject::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $data['name']],
                ['code' => $data['code'], 'is_active' => true]
            );
            $subjects[$data['name']] = $subject;
        }

        $this->command->info('✅ ' . count($subjects) . ' subjects created.');

        // ---------------------------------------------------------------
        // Assign subjects to class arms
        // ---------------------------------------------------------------
        $classArms = ClassArm::where('tenant_id', $tenant->id)
                             ->with('classLevel')
                             ->get();

        // Load teachers
        $teachers = User::where('tenant_id', $tenant->id)
                        ->whereIn('role', User::teachingRoleNames())
                        ->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found — subjects assigned without teacher. Run StaffSeeder first.');
        }

        // Map teacher index for round-robin assignment
        $teacherCount = $teachers->count();
        $teacherIndex = 0;

        // JSS core subjects
        $jssCore = [
            'Mathematics', 'English Language', 'Basic Science', 'Social Studies',
            'Agricultural Science', 'Business Studies', 'Computer Studies',
            'Physical and Health Education', 'Christian Religious Studies', 'Fine Art',
        ];

        // SSS core subjects
        $sssCore = [
            'Mathematics', 'English Language', 'Physics', 'Chemistry', 'Biology',
            'Economics', 'Government', 'Literature in English', 'Geography',
            'Physical and Health Education', 'Christian Religious Studies',
        ];

        $assigned = 0;
        foreach ($classArms as $arm) {
            $section     = $arm->classLevel->section ?? '';
            $subjectList = str_contains($section, 'secondary') && str_contains($section, 'senior')
                ? $sssCore : $jssCore;

            foreach ($subjectList as $subjectName) {
                if (!isset($subjects[$subjectName])) continue;

                $subject   = $subjects[$subjectName];
                $teacher   = $teacherCount > 0 ? $teachers[$teacherIndex % $teacherCount] : null;
                $teacherIndex++;

                ClassArmSubject::firstOrCreate(
                    [
                        'tenant_id'    => $tenant->id,
                        'class_arm_id' => $arm->id,
                        'subject_id'   => $subject->id,
                        'session_id'   => $session->id,
                    ],
                    ['teacher_id' => $teacher?->id]
                );
                $assigned++;
            }
        }

        $this->command->info("✅ {$assigned} subject-class assignments created.");
        $this->command->info("   Session: {$session->name}");
        $this->command->info("   Classes: {$classArms->count()} · Subjects assigned per class: ~" . count($jssCore));
    }
}
