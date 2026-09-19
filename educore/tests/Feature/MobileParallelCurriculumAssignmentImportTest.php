<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\AssessmentTemplate;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileParallelCurriculumAssignmentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_validate_and_import_parallel_student_assignments_from_csv(): void
    {
        $tenant = Tenant::create([
            'name' => 'Parallel Import School',
            'slug' => 'parallel-import-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $session = AcademicSession::create([
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
        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $tenant->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
            'default_assessment_template_id' => $template->id,
            'is_active' => true,
        ]);
        $class = ParallelCurriculumClass::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Mutawassitah 1',
            'code' => 'M1',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $arm = ParallelCurriculumClassArm::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $class->id,
            'name' => 'A',
            'code' => 'A',
            'sort_order' => 1,
            'capacity' => 20,
            'is_active' => true,
        ]);
        $student = Student::create([
            'tenant_id' => $tenant->id,
            'admission_number' => 'PC-100',
            'first_name' => 'Maryam',
            'last_name' => 'Sani',
            'status' => Student::STATUS_ACTIVE,
        ]);
        $apiToken = ApiToken::issue($admin, 'parallel-import-test');
        $csv = "admission_number,parallel_class,parallel_arm\nPC-100,M1,A\n";

        $templateResponse = $this->withToken($apiToken)
            ->get('/api/v1/parallel-curriculum/lifecycle/assignments/template');
        $templateResponse->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString(
            'admission_number,parallel_class,parallel_arm',
            $templateResponse->streamedContent()
        );

        $this->withToken($apiToken)
            ->post('/api/v1/parallel-curriculum/lifecycle/assignments/import', [
                'parallel_curriculum_id' => $curriculum->id,
                'session_id' => $session->id,
                'dry_run' => '1',
                'assignment_file' => UploadedFile::fake()->createWithContent('assignments.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('validated', 1)
            ->assertJsonPath('imported', 0);

        $this->assertDatabaseMissing('parallel_curriculum_enrolments', [
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'session_id' => $session->id,
        ]);

        $this->withToken($apiToken)
            ->post('/api/v1/parallel-curriculum/lifecycle/assignments/import', [
                'parallel_curriculum_id' => $curriculum->id,
                'session_id' => $session->id,
                'assignment_file' => UploadedFile::fake()->createWithContent('assignments.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('dry_run', false)
            ->assertJsonPath('validated', 1)
            ->assertJsonPath('imported', 1);

        $this->assertDatabaseHas('parallel_curriculum_enrolments', [
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $class->id,
            'parallel_curriculum_class_arm_id' => $arm->id,
            'student_id' => $student->id,
            'session_id' => $session->id,
            'is_active' => true,
        ]);
    }
}
