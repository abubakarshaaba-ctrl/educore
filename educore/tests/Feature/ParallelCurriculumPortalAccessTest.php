<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\AssessmentTemplateComponent;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumGrade;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumScore;
use App\Models\ParallelCurriculumSubject;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Services\ParallelCurriculumResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParallelCurriculumPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_parallel_report_is_available_only_to_the_enrolled_student(): void
    {
        $fixture = $this->publishedFixture();
        $service = app(ParallelCurriculumResultService::class);

        $report = $service->publishedReportForStudent(
            $fixture['student'],
            $fixture['publication']->id
        );

        $this->assertNotNull($report);
        $this->assertTrue($report['is_published']);
        $this->assertSame(
            $fixture['student']->id,
            $report['student_result']['student']->id
        );
        $this->assertSame('A', $report['student_result']['subjects']->first()['grade']);

        $this->assertNull(
            $service->publishedReportForStudent(
                $fixture['otherStudent'],
                $fixture['publication']->id
            )
        );
    }

    public function test_draft_parallel_report_is_not_available_through_portal_download(): void
    {
        $fixture = $this->publishedFixture();
        $fixture['publication']->update([
            'status' => ParallelCurriculumReportPublication::STATUS_DRAFT,
            'unpublished_at' => now(),
        ]);

        $this->assertNull(
            app(ParallelCurriculumResultService::class)
                ->publishedReportForStudent(
                    $fixture['student'],
                    $fixture['publication']->id
                )
        );
    }

    public function test_parallel_result_pdf_routes_exist_on_all_portal_surfaces(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            'student.portal.parallel-results.pdf',
            'parent.parallel-results.pdf',
            'portal.parent.parallel-results.pdf',
        ] as $name) {
            $route = $routes->getByName($name);

            $this->assertNotNull($route, "Missing route {$name}");
            $this->assertContains('GET', $route->methods());
        }
    }

    public function test_portal_result_views_render_shared_parallel_result_component(): void
    {
        foreach ([
            resource_path('views/portal/student/results.blade.php'),
            resource_path('views/portal/parent/results.blade.php'),
            resource_path('views/parent/results.blade.php'),
        ] as $path) {
            $view = file_get_contents($path);

            $this->assertStringContainsString(
                "portal.partials.parallel-results",
                $view
            );
        }

        $partial = file_get_contents(
            resource_path('views/portal/partials/parallel-results.blade.php')
        );

        $this->assertStringContainsString('Other Curriculum Results', $partial);
        $this->assertStringContainsString('Download PDF', $partial);
        $this->assertStringContainsString('parallel-result-mobile', $partial);
    }

    private function publishedFixture(): array
    {
        $tenant = Tenant::create([
            'name' => 'Portal Parallel Test School',
            'slug' => 'portal-parallel-'.uniqid(),
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

        $level = ClassLevel::create([
            'tenant_id' => $tenant->id,
            'name' => 'JSS 1',
            'section' => 'junior',
            'order_index' => 1,
        ]);

        $arm = ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);

        $student = Student::create([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $arm->id,
            'admission_number' => 'STU-PC-101',
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'status' => Student::STATUS_ACTIVE,
        ]);

        $otherStudent = Student::create([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $arm->id,
            'admission_number' => 'STU-PC-102',
            'first_name' => 'Musa',
            'last_name' => 'Aliyu',
            'status' => Student::STATUS_ACTIVE,
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

        $parallelClass = ParallelCurriculumClass::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Mutawassitah 1',
            'code' => 'M1',
            'is_active' => true,
        ]);

        ParallelCurriculumClassSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_subject_id' => $subject->id,
            'is_active' => true,
        ]);

        ParallelCurriculumEnrolment::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'student_id' => $student->id,
            'session_id' => $session->id,
            'is_active' => true,
        ]);

        ParallelCurriculumScore::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'student_id' => $student->id,
            'parallel_curriculum_subject_id' => $subject->id,
            'assessment_template_component_id' => $ca->id,
            'term_id' => $term->id,
            'session_id' => $session->id,
            'score' => 35,
            'entered_at' => now(),
        ]);

        ParallelCurriculumScore::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'student_id' => $student->id,
            'parallel_curriculum_subject_id' => $subject->id,
            'assessment_template_component_id' => $exam->id,
            'term_id' => $term->id,
            'session_id' => $session->id,
            'score' => 50,
            'entered_at' => now(),
        ]);

        ParallelCurriculumGrade::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'grade_letter' => 'A',
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'Pass',
            'is_pass_grade' => true,
            'sort_order' => 1,
        ]);

        $publication = ParallelCurriculumReportPublication::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'term_id' => $term->id,
            'status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return compact(
            'tenant',
            'session',
            'term',
            'level',
            'arm',
            'student',
            'otherStudent',
            'template',
            'ca',
            'exam',
            'curriculum',
            'subject',
            'parallelClass',
            'publication'
        );
    }
}
