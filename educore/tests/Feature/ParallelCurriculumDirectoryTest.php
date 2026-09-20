<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumArmSubjectTeacher;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumSubject;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParallelCurriculumDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_generate_arm_specific_and_all_arm_parallel_class_lists(): void
    {
        $context = $this->context();

        $level = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'JSS 1',
            'section' => 'junior',
            'order_index' => 1,
        ]);

        $conventionalArm = ClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);

        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
            'is_active' => true,
        ]);

        $parallelClass = ParallelCurriculumClass::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Mutawassitah 1',
            'code' => 'M1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $armA = ParallelCurriculumClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'name' => 'A',
            'code' => 'A',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $armB = ParallelCurriculumClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'name' => 'B',
            'code' => 'B',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $amina = $this->student(
            $context['tenant']->id,
            $conventionalArm->id,
            'PC-001',
            'Amina',
            'Bello',
            Student::STATUS_ACTIVE
        );
        $zainab = $this->student(
            $context['tenant']->id,
            $conventionalArm->id,
            'PC-002',
            'Zainab',
            'Musa',
            Student::STATUS_ACTIVE
        );
        $left = $this->student(
            $context['tenant']->id,
            $conventionalArm->id,
            'PC-003',
            'Former',
            'Learner',
            Student::STATUS_LEFT
        );

        foreach ([
            [$amina, $armA],
            [$zainab, $armB],
            [$left, $armA],
        ] as [$student, $arm]) {
            ParallelCurriculumEnrolment::create([
                'tenant_id' => $context['tenant']->id,
                'parallel_curriculum_id' => $curriculum->id,
                'parallel_curriculum_class_id' => $parallelClass->id,
                'parallel_curriculum_class_arm_id' => $arm->id,
                'student_id' => $student->id,
                'session_id' => $context['session']->id,
                'is_active' => true,
            ]);
        }

        $armResponse = $this->actingAs($context['admin'])
            ->get(route('parallel-curriculum.class-list', [
                'parallel_curriculum_id' => $curriculum->id,
                'session_id' => $context['session']->id,
                'class_id' => $parallelClass->id,
                'arm_id' => $armA->id,
            ]))
            ->assertOk()
            ->assertViewIs('parallel-curriculum.directories.class-list');

        $armEnrolments = $armResponse->viewData('enrolments');
        $this->assertCount(1, $armEnrolments);
        $this->assertSame($amina->id, $armEnrolments->first()->student_id);

        $allResponse = $this->actingAs($context['admin'])
            ->get(route('parallel-curriculum.class-list', [
                'parallel_curriculum_id' => $curriculum->id,
                'session_id' => $context['session']->id,
                'class_id' => $parallelClass->id,
                'arm_id' => 'all',
            ]))
            ->assertOk();

        $allEnrolments = $allResponse->viewData('enrolments');
        $this->assertCount(2, $allEnrolments);
        $this->assertEqualsCanonicalizing(
            [$amina->id, $zainab->id],
            $allEnrolments->pluck('student_id')->all()
        );
        $this->assertTrue($allResponse->viewData('allArms'));
    }

    public function test_teacher_list_resolves_class_teacher_default_and_arm_override_assignments(): void
    {
        $context = $this->context();

        $classTeacher = $this->teacher($context['tenant']->id, 'Class Teacher', 'T-001');
        $defaultTeacher = $this->teacher($context['tenant']->id, 'Default Teacher', 'T-002');
        $overrideTeacher = $this->teacher($context['tenant']->id, 'Override Teacher', 'T-003');

        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
            'is_active' => true,
        ]);

        $parallelClass = ParallelCurriculumClass::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Mutawassitah 1',
            'code' => 'M1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $armA = ParallelCurriculumClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'name' => 'A',
            'code' => 'A',
            'teaching_assignment_mode' => 'class_teacher',
            'class_teacher_id' => $classTeacher->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $armB = ParallelCurriculumClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'name' => 'B',
            'code' => 'B',
            'teaching_assignment_mode' => 'subject_based',
            'class_teacher_id' => null,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $quran = ParallelCurriculumSubject::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Quran',
            'code' => 'QRN',
            'is_active' => true,
        ]);
        $fiqh = ParallelCurriculumSubject::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Fiqh',
            'code' => 'FQH',
            'is_active' => true,
        ]);

        $quranAssignment = ParallelCurriculumClassSubject::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_subject_id' => $quran->id,
            'teacher_id' => $defaultTeacher->id,
            'is_active' => true,
        ]);
        ParallelCurriculumClassSubject::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_subject_id' => $fiqh->id,
            'teacher_id' => $defaultTeacher->id,
            'is_active' => true,
        ]);

        ParallelCurriculumArmSubjectTeacher::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $armB->id,
            'parallel_curriculum_subject_id' => $quranAssignment->parallel_curriculum_subject_id,
            'teacher_id' => $overrideTeacher->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($context['admin'])
            ->get(route('parallel-curriculum.teacher-list', [
                'parallel_curriculum_id' => $curriculum->id,
            ]))
            ->assertOk()
            ->assertViewIs('parallel-curriculum.directories.teacher-list');

        $rows = $response->viewData('teacherRows');
        $this->assertCount(3, $rows);
        $this->assertSame(0, $response->viewData('unassignedSlots'));

        $classTeacherRow = $rows->first(
            fn (array $row) => $row['teacher']->id === $classTeacher->id
        );
        $this->assertNotNull($classTeacherRow);
        $this->assertTrue(
            $classTeacherRow['assignment_types']->contains('Class/Form Teacher')
        );
        $this->assertTrue($classTeacherRow['class_arms']->contains('Mutawassitah 1 A'));

        $overrideRow = $rows->first(
            fn (array $row) => $row['teacher']->id === $overrideTeacher->id
        );
        $this->assertNotNull($overrideRow);
        $this->assertTrue($overrideRow['subjects']->contains('Quran'));
        $this->assertTrue($overrideRow['class_arms']->contains('Mutawassitah 1 B'));

        $defaultRow = $rows->first(
            fn (array $row) => $row['teacher']->id === $defaultTeacher->id
        );
        $this->assertNotNull($defaultRow);
        $this->assertTrue($defaultRow['subjects']->contains('Fiqh'));
        $this->assertFalse($defaultRow['subjects']->contains('Quran'));
    }

    public function test_parallel_directories_are_manager_only(): void
    {
        $context = $this->context();
        $teacher = $this->teacher($context['tenant']->id, 'Regular Teacher', 'T-010');

        $this->actingAs($teacher)
            ->get(route('parallel-curriculum.class-list'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('parallel-curriculum.teacher-list'))
            ->assertForbidden();
    }

    public function test_parallel_directory_views_are_responsive_and_print_ready(): void
    {
        $classView = file_get_contents(
            resource_path('views/parallel-curriculum/directories/class-list.blade.php')
        );
        $teacherView = file_get_contents(
            resource_path('views/parallel-curriculum/directories/teacher-list.blade.php')
        );

        $this->assertStringContainsString('id="parallel-class-print"', $classView);
        $this->assertStringContainsString('@media print', $classView);
        $this->assertStringContainsString('mobile-list', $classView);
        $this->assertStringContainsString('All arms', $classView);

        $this->assertStringContainsString('id="parallel-teacher-print"', $teacherView);
        $this->assertStringContainsString('@media print', $teacherView);
        $this->assertStringContainsString('mobile-list', $teacherView);
        $this->assertStringContainsString('Unassigned Teaching Slots', $teacherView);
    }

    private function context(): array
    {
        $tenant = Tenant::create([
            'name' => 'Parallel Directory Test School',
            'slug' => 'parallel-directory-'.uniqid(),
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

        return compact('tenant', 'session', 'admin');
    }

    private function teacher(int $tenantId, string $name, string $staffId): User
    {
        return User::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'staff_id' => $staffId,
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function student(
        int $tenantId,
        int $classArmId,
        string $admissionNumber,
        string $firstName,
        string $lastName,
        string $status
    ): Student {
        return Student::create([
            'tenant_id' => $tenantId,
            'current_class_arm_id' => $classArmId,
            'admission_number' => $admissionNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => 'female',
            'status' => $status,
        ]);
    }
}
