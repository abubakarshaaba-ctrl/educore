<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\AssessmentTemplate;
use App\Models\AssessmentTemplateComponent;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumGrade;
use App\Models\ParallelCurriculumScore;
use App\Models\ParallelCurriculumSubject;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileParallelCurriculumResultsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_publish_and_unpublish_parallel_results_on_mobile_api(): void
    {
        $tenant = Tenant::create([
            'name' => 'Parallel Results School',
            'slug' => 'parallel-results-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $session = AcademicSession::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
        ]);

        $term = Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
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

        $ca = AssessmentTemplateComponent::create([
            'tenant_id' => $tenant->id,
            'assessment_template_id' => $template->id,
            'name' => 'CA',
            'weight_percentage' => 40,
            'component_type' => 'continuous_assessment',
            'entry_mode' => 'manual',
            'sort_order' => 1,
        ]);

        $exam = AssessmentTemplateComponent::create([
            'tenant_id' => $tenant->id,
            'assessment_template_id' => $template->id,
            'name' => 'Exam',
            'weight_percentage' => 60,
            'component_type' => 'exam',
            'entry_mode' => 'manual',
            'sort_order' => 2,
        ]);

        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $tenant->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
            'default_assessment_template_id' => $template->id,
            'is_active' => true,
        ]);

        $subject = ParallelCurriculumSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Qur\'an',
            'code' => 'QRN',
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
            'is_active' => true,
        ]);

        ParallelCurriculumClassSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $class->id,
            'parallel_curriculum_subject_id' => $subject->id,
            'is_active' => true,
        ]);

        $student = Student::create([
            'tenant_id' => $tenant->id,
            'admission_number' => 'PC-001',
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'status' => Student::STATUS_ACTIVE,
        ]);

        ParallelCurriculumEnrolment::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $class->id,
            'parallel_curriculum_class_arm_id' => $arm->id,
            'student_id' => $student->id,
            'session_id' => $session->id,
            'is_active' => true,
        ]);

        foreach ([[$ca, 35], [$exam, 48]] as [$component, $score]) {
            ParallelCurriculumScore::create([
                'tenant_id' => $tenant->id,
                'parallel_curriculum_id' => $curriculum->id,
                'parallel_curriculum_class_id' => $class->id,
                'student_id' => $student->id,
                'parallel_curriculum_subject_id' => $subject->id,
                'assessment_template_component_id' => $component->id,
                'term_id' => $term->id,
                'session_id' => $session->id,
                'entered_by' => $admin->id,
                'score' => $score,
                'entered_at' => now(),
            ]);
        }

        ParallelCurriculumGrade::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'grade_letter' => 'A',
            'min_score' => 70,
            'max_score' => 100,
            'remark' => 'Excellent',
            'is_pass_grade' => true,
            'sort_order' => 1,
        ]);

        ParallelCurriculumGrade::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'grade_letter' => 'F',
            'min_score' => 0,
            'max_score' => 69.99,
            'remark' => 'Needs improvement',
            'is_pass_grade' => false,
            'sort_order' => 2,
        ]);

        $token = ApiToken::issue($admin, 'parallel-results-admin');

        $this->withToken($token)
            ->getJson(
                '/api/v1/parallel-curriculum/results?class_id='.
                $class->id.'&term_id='.$term->id
            )
            ->assertOk()
            ->assertJsonPath('contract_version', 1)
            ->assertJsonPath('report.curriculum_name', 'Islamiyyah')
            ->assertJsonPath('report.class_name', 'Mutawassitah 1')
            ->assertJsonPath('report.is_published', false)
            ->assertJsonPath('report.can_publish', true)
            ->assertJsonPath('report.rows.0.student_name', 'Amina Bello')
            ->assertJsonPath('report.rows.0.arm_name', 'A')
            ->assertJsonPath('report.rows.0.average', 83);

        $this->withToken($token)
            ->getJson(
                '/api/v1/parallel-curriculum/results/classes/'.
                $class->id.'/students/'.$student->id.'?term_id='.$term->id
            )
            ->assertOk()
            ->assertJsonPath('student.name', 'Amina Bello')
            ->assertJsonPath('student.subjects.0.subject', 'Qur\'an')
            ->assertJsonPath('student.subjects.0.grade', 'A')
            ->assertJsonPath('student.subjects.0.components.0.name', 'CA');

        $csv = $this->withToken($token)
            ->get(
                '/api/v1/parallel-curriculum/results/export?class_id='.
                $class->id.'&term_id='.$term->id.'&format=csv'
            );

        $csv->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('PC-001', $csv->streamedContent());
        $this->assertStringContainsString('Amina Bello', $csv->streamedContent());

        $this->withToken($token)
            ->get(
                '/api/v1/parallel-curriculum/results/export?class_id='.
                $class->id.'&term_id='.$term->id.'&format=pdf'
            )
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->withToken($token)
            ->get(
                '/api/v1/parallel-curriculum/results/classes/'.
                $class->id.'/students/'.$student->id.'/pdf?term_id='.$term->id
            )
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/results/publish', [
                'class_id' => $class->id,
                'term_id' => $term->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'published');

        $this->assertDatabaseHas('parallel_curriculum_report_publications', [
            'parallel_curriculum_class_id' => $class->id,
            'term_id' => $term->id,
            'status' => 'published',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/results/unpublish', [
                'class_id' => $class->id,
                'term_id' => $term->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'draft');

        $this->assertDatabaseHas('parallel_curriculum_report_publications', [
            'parallel_curriculum_class_id' => $class->id,
            'term_id' => $term->id,
            'status' => 'draft',
        ]);
    }
}
