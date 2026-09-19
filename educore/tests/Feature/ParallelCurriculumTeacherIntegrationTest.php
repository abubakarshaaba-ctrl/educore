<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumSubject;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use App\Services\ParallelCurriculumOperationsService;
use App\Services\ParallelCurriculumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParallelCurriculumTeacherIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_teacher_can_mark_parallel_attendance_even_before_subject_rows_exist(): void
    {
        $context = $this->context(false);

        $this->assertTrue(
            app(ParallelCurriculumOperationsService::class)
                ->canMarkAttendance($context['teacher'], $context['arm'])
        );
    }

    public function test_class_teacher_gets_parallel_score_workspace_for_every_active_class_subject(): void
    {
        $context = $this->context(true);

        $workspaces = app(ParallelCurriculumService::class)
            ->scoreWorkspacesForUser($context['teacher'], $context['term']);

        $this->assertCount(1, $workspaces);
        $this->assertSame($context['arm']->id, $workspaces->first()['arm']->id);
        $this->assertSame(
            $context['subject']->id,
            $workspaces->first()['assignment']->parallel_curriculum_subject_id
        );
        $this->assertTrue($workspaces->first()['is_form_teacher']);
        $this->assertTrue(
            $context['teacher']->canAccessRoute('parallel-curriculum.form-teacher-comments.save')
        );
        $this->assertStringContainsString(
            '/parallel-curriculum/score-sheet',
            $workspaces->first()['score_sheet_url']
        );
        $this->assertStringContainsString(
            '/parallel-curriculum/operations',
            $workspaces->first()['attendance_url']
        );
        $this->assertStringContainsString(
            '/parallel-curriculum/form-teacher-comments',
            $workspaces->first()['form_teacher_comments_url']
        );
    }

    public function test_parallel_class_teacher_can_save_term_specific_form_teacher_comment(): void
    {
        $context = $this->context(true);

        $student = Student::create([
            'tenant_id' => $context['tenant']->id,
            'admission_number' => 'PC-COMMENT-001',
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'status' => Student::STATUS_ACTIVE,
        ]);

        $enrolment = ParallelCurriculumEnrolment::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $context['curriculum']->id,
            'parallel_curriculum_class_id' => $context['class']->id,
            'parallel_curriculum_class_arm_id' => $context['arm']->id,
            'student_id' => $student->id,
            'session_id' => $context['session']->id,
            'is_active' => true,
        ]);

        $this->actingAs($context['teacher'])
            ->post(route('parallel-curriculum.form-teacher-comments.save'), [
                'arm_id' => $context['arm']->id,
                'term_id' => $context['term']->id,
                'comments' => [
                    $enrolment->id => 'Amina participates actively and should maintain the effort.',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('parallel_curriculum_result_comments', [
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_enrolment_id' => $enrolment->id,
            'term_id' => $context['term']->id,
            'form_teacher_id' => $context['teacher']->id,
            'form_teacher_comment' => 'Amina participates actively and should maintain the effort.',
        ]);
    }

    private function context(bool $withSubject): array
    {
        $tenant = Tenant::create([
            'name' => 'Parallel Teacher Integration School',
            'slug' => 'parallel-teacher-integration-'.uniqid(),
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
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_current' => true,
        ]);

        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Parallel Class Teacher',
            'role' => 'subject_teacher',
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

        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $tenant->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
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
            'teaching_assignment_mode' => 'class_teacher',
            'class_teacher_id' => $teacher->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $subject = null;
        if ($withSubject) {
            $subject = ParallelCurriculumSubject::create([
                'tenant_id' => $tenant->id,
                'parallel_curriculum_id' => $curriculum->id,
                'name' => 'Qur\'an',
                'code' => 'QRN',
                'is_active' => true,
            ]);

            ParallelCurriculumClassSubject::create([
                'tenant_id' => $tenant->id,
                'parallel_curriculum_class_id' => $class->id,
                'parallel_curriculum_subject_id' => $subject->id,
                'teacher_id' => null,
                'is_active' => true,
            ]);
        }

        return compact(
            'tenant',
            'session',
            'term',
            'teacher',
            'curriculum',
            'class',
            'arm',
            'subject',
        );
    }
}
