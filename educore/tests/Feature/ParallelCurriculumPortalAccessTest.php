<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\AssessmentTemplateComponent;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumAttendanceRecord;
use App\Models\ParallelCurriculumGrade;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumScore;
use App\Models\ParallelCurriculumSubject;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Services\ParallelCurriculumResultService;
use App\Services\ParallelCurriculumPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_parallel_student_report_exposes_conventional_report_card_content(): void
    {
        $fixture = $this->publishedFixture();

        ParallelCurriculumAttendanceRecord::create([
            'tenant_id' => $fixture['tenant']->id,
            'parallel_curriculum_id' => $fixture['curriculum']->id,
            'parallel_curriculum_class_id' => $fixture['parallelClass']->id,
            'parallel_curriculum_class_arm_id' => $fixture['parallelArm']->id,
            'parallel_curriculum_enrolment_id' => $fixture['parallelEnrolment']->id,
            'student_id' => $fixture['student']->id,
            'term_id' => $fixture['term']->id,
            'marked_by' => null,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
            'remark' => null,
        ]);

        $presentation = app(ParallelCurriculumResultService::class)
            ->conventionalStyleStudentReport(
                $fixture['parallelClass'],
                $fixture['term']->load('session'),
                $fixture['student']->id
            );

        $this->assertNotNull($presentation);
        $this->assertSame(
            'Mutawassitah 1 A',
            $presentation['parallelClassName']
        );
        $this->assertCount(2, $presentation['assessmentTypes']);
        $this->assertCount(1, $presentation['subjectRows']);
        $this->assertArrayHasKey(
            'class_position',
            $presentation['subjectRows'][0]
        );
        $this->assertArrayHasKey(
            'class_lowest',
            $presentation['subjectRows'][0]
        );
        $this->assertArrayHasKey(
            'class_highest',
            $presentation['subjectRows'][0]
        );
        $this->assertSame(1, $presentation['attendanceSummary']['days_open']);
        $this->assertSame(1, $presentation['attendanceSummary']['days_present']);
        $this->assertSame(100, $presentation['attendanceSummary']['rate']);
        $this->assertNotEmpty($presentation['principalRemark']);
    }

    public function test_parallel_pdf_reuses_conventional_report_template(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ParallelCurriculumResultController.php')
        );
        $parallelStudentView = file_get_contents(
            resource_path('views/parallel-curriculum/results/student.blade.php')
        );
        $conventionalPdf = file_get_contents(
            resource_path('views/reports/pdf.blade.php')
        );

        $this->assertStringContainsString(
            "Pdf::loadView('reports.pdf', \\$presentation)",
            $controller
        );
        $this->assertStringContainsString(
            'conventionalStyleStudentReport',
            $controller
        );
        $this->assertStringContainsString(
            'Behavioural Development and Attendance',
            $parallelStudentView
        );
        $this->assertStringContainsString(
            "Form Teacher's Comment",
            $parallelStudentView
        );
        $this->assertStringContainsString(
            "Principal's Comment",
            $parallelStudentView
        );
        $this->assertStringContainsString(
            'Next Term Begins',
            $parallelStudentView
        );
        $this->assertStringContainsString(
            '$reportDocumentTitle ??',
            $conventionalPdf
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

    public function test_learner_portal_service_returns_only_the_students_parallel_schedule_and_attendance(): void
    {
        $fixture = $this->publishedFixture();

        ParallelCurriculumTimetablePeriod::create([
            'tenant_id' => $fixture['tenant']->id,
            'parallel_curriculum_id' => $fixture['curriculum']->id,
            'parallel_curriculum_class_id' => $fixture['parallelClass']->id,
            'parallel_curriculum_class_arm_id' => $fixture['parallelArm']->id,
            'parallel_curriculum_subject_id' => $fixture['subject']->id,
            'teacher_id' => null,
            'session_id' => $fixture['session']->id,
            'day_of_week' => 'monday',
            'start_time' => '08:00',
            'end_time' => '08:40',
            'venue' => 'Islamiyyah Hall',
        ]);

        ParallelCurriculumAttendanceRecord::create([
            'tenant_id' => $fixture['tenant']->id,
            'parallel_curriculum_id' => $fixture['curriculum']->id,
            'parallel_curriculum_class_id' => $fixture['parallelClass']->id,
            'parallel_curriculum_class_arm_id' => $fixture['parallelArm']->id,
            'parallel_curriculum_enrolment_id' => $fixture['parallelEnrolment']->id,
            'student_id' => $fixture['student']->id,
            'term_id' => $fixture['term']->id,
            'marked_by' => null,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
            'remark' => 'On time',
        ]);

        $service = app(ParallelCurriculumPortalService::class);
        $timetables = $service->timetableForStudent(
            $fixture['student'],
            $fixture['session']->id,
        );
        $attendance = $service->attendanceForStudent(
            $fixture['student'],
            $fixture['term']->id,
        );

        $this->assertCount(1, $timetables);
        $this->assertSame('Islamiyyah', $timetables->first()['curriculum_name']);
        $this->assertSame('Mutawassitah 1', $timetables->first()['class_name']);
        $this->assertSame('A', $timetables->first()['arm_name']);
        $this->assertSame('Qur\'an', $timetables->first()['periods']->first()->subject->name);

        $this->assertCount(1, $attendance);
        $this->assertSame(1, $attendance->first()['stats']['present']);
        $this->assertSame(100.0, (float) $attendance->first()['stats']['rate']);

        $this->assertTrue(
            $service->timetableForStudent($fixture['otherStudent'], $fixture['session']->id)->isEmpty()
        );
        $this->assertTrue(
            $service->attendanceForStudent($fixture['otherStudent'], $fixture['term']->id)->isEmpty()
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

        DB::table('school_settings')->insert([
            'tenant_id' => $tenant->id,
            'key' => 'parallel_curriculum_enabled',
            'value' => '1',
            'group' => 'academic',
            'created_at' => now(),
            'updated_at' => now(),
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

        $parallelArm = ParallelCurriculumClassArm::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'name' => 'A',
            'code' => 'A',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ParallelCurriculumClassSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_subject_id' => $subject->id,
            'is_active' => true,
        ]);

        $parallelEnrolment = ParallelCurriculumEnrolment::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArm->id,
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
            'parallelArm',
            'parallelEnrolment',
            'publication'
        );
    }
}
