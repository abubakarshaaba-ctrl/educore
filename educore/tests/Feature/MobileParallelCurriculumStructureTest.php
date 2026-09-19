<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\AssessmentTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileParallelCurriculumStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_build_parallel_programme_structure_through_mobile_api(): void
    {
        $tenant = Tenant::create([
            'name' => 'Parallel Mobile School',
            'slug' => 'parallel-mobile-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        AcademicSession::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Academic Admin',
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        DB::table('school_settings')->insert([
            'tenant_id' => $tenant->id,
            'key' => 'parallel_curriculum_enabled',
            'value' => '1',
            'group' => 'academic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $template = AssessmentTemplate::create([
            'tenant_id' => $tenant->id,
            'name' => 'Parallel 40/60',
            'status' => AssessmentTemplate::STATUS_ACTIVE,
        ]);

        $token = ApiToken::issue($admin, 'parallel-structure-test');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/programmes', [
                'name' => 'Islamiyyah',
                'code' => 'ISL',
                'default_assessment_template_id' => $template->id,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Parallel curriculum programme created.');

        $curriculumId = (int) DB::table('parallel_curricula')
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Islamiyyah')
            ->value('id');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/classes', [
                'parallel_curriculum_id' => $curriculumId,
                'name' => 'Mutawassitah 1',
                'code' => 'M1',
            ])
            ->assertCreated();

        $classId = (int) DB::table('parallel_curriculum_classes')
            ->where('parallel_curriculum_id', $curriculumId)
            ->where('name', 'Mutawassitah 1')
            ->value('id');

        $this->assertDatabaseHas('parallel_curriculum_class_arms', [
            'parallel_curriculum_class_id' => $classId,
            'name' => 'A',
            'is_active' => true,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/subjects', [
                'parallel_curriculum_id' => $curriculumId,
                'name' => 'Qur\'an',
                'code' => 'QRN',
            ])
            ->assertOk();

        $subjectId = (int) DB::table('parallel_curriculum_subjects')
            ->where('parallel_curriculum_id', $curriculumId)
            ->where('name', 'Qur\'an')
            ->value('id');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/class-subjects', [
                'parallel_curriculum_class_id' => $classId,
                'parallel_curriculum_subject_id' => $subjectId,
                'teacher_id' => null,
            ])
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/programme-grades', [
                'parallel_curriculum_id' => $curriculumId,
                'grade_letter' => 'A',
                'min_score' => 70,
                'max_score' => 100,
                'remark' => 'Excellent',
                'is_pass_grade' => true,
            ])
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/parallel-curriculum/lifecycle?parallel_curriculum_id='.$curriculumId)
            ->assertOk()
            ->assertJsonPath('contract_version', 3)
            ->assertJsonPath('assessment_templates.0.name', 'Parallel 40/60')
            ->assertJsonPath('curricula.0.name', 'Islamiyyah')
            ->assertJsonPath('curricula.0.default_assessment_template_id', $template->id)
            ->assertJsonPath('curricula.0.subjects.0.name', 'Qur\'an')
            ->assertJsonPath('curricula.0.grades.0.grade_letter', 'A')
            ->assertJsonPath('curricula.0.classes.0.name', 'Mutawassitah 1')
            ->assertJsonPath('curricula.0.classes.0.arms.0.name', 'A')
            ->assertJsonPath('curricula.0.classes.0.subjects.0.subject_name', 'Qur\'an');
    }
}
