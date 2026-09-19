<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Guardian;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumSubject;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileParallelSchedulePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_switch_children_without_parallel_schedule_leakage(): void
    {
        $tenant = Tenant::create([
            'name' => 'Parent Parallel Schedule School',
            'slug' => 'parent-parallel-schedule-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        DB::table('school_settings')->insert([
            'tenant_id' => $tenant->id,
            'key' => 'parallel_curriculum_enabled',
            'value' => '1',
            'group' => 'academic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = AcademicSession::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
        ]);

        Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'is_current' => true,
        ]);

        $level = ClassLevel::create([
            'tenant_id' => $tenant->id,
            'name' => 'JSS 1',
            'section' => 'junior_secondary',
            'order_index' => 1,
        ]);

        $classA = ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);
        $classB = ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'name' => 'B',
        ]);

        $parentUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Parent User',
            'role' => 'parent',
            'is_active' => true,
        ]);

        $guardian = Guardian::create([
            'tenant_id' => $tenant->id,
            'user_id' => $parentUser->id,
            'first_name' => 'Maryam',
            'last_name' => 'Parent',
            'relationship' => 'mother',
        ]);

        $childA = Student::create([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $classA->id,
            'admission_number' => 'CHILD-A',
            'first_name' => 'Amina',
            'last_name' => 'Parent',
            'status' => Student::STATUS_ACTIVE,
        ]);
        $childB = Student::create([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $classB->id,
            'admission_number' => 'CHILD-B',
            'first_name' => 'Bilal',
            'last_name' => 'Parent',
            'status' => Student::STATUS_ACTIVE,
        ]);

        foreach ([$childA, $childB] as $index => $child) {
            $guardian->students()->attach($child->id, [
                'tenant_id' => $tenant->id,
                'is_primary_contact' => $index === 0,
            ]);
        }

        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $tenant->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
            'is_active' => true,
        ]);

        $parallelClass = ParallelCurriculumClass::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Mutawassitah 1',
            'code' => 'M1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $parallelArmA = ParallelCurriculumClassArm::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'name' => 'A',
            'code' => 'A',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $parallelArmB = ParallelCurriculumClassArm::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'name' => 'B',
            'code' => 'B',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $quran = ParallelCurriculumSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Qur\'an',
            'code' => 'QRN',
            'is_active' => true,
        ]);
        $fiqh = ParallelCurriculumSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Fiqh',
            'code' => 'FQH',
            'is_active' => true,
        ]);

        ParallelCurriculumEnrolment::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArmA->id,
            'student_id' => $childA->id,
            'session_id' => $session->id,
            'is_active' => true,
        ]);
        ParallelCurriculumEnrolment::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArmB->id,
            'student_id' => $childB->id,
            'session_id' => $session->id,
            'is_active' => true,
        ]);

        ParallelCurriculumTimetablePeriod::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArmA->id,
            'parallel_curriculum_subject_id' => $quran->id,
            'teacher_id' => null,
            'session_id' => $session->id,
            'day_of_week' => 'monday',
            'start_time' => '08:00',
            'end_time' => '08:40',
            'venue' => 'Room A',
        ]);
        ParallelCurriculumTimetablePeriod::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArmB->id,
            'parallel_curriculum_subject_id' => $fiqh->id,
            'teacher_id' => null,
            'session_id' => $session->id,
            'day_of_week' => 'tuesday',
            'start_time' => '09:00',
            'end_time' => '09:40',
            'venue' => 'Room B',
        ]);

        $token = ApiToken::issue($parentUser, 'parent-parallel-schedule');

        $response = $this->withToken($token)
            ->getJson('/api/v1/schedule?child_id='.$childB->id)
            ->assertOk()
            ->assertJsonPath('scope.type', 'parent_child')
            ->assertJsonPath('scope.title', 'Bilal Parent')
            ->assertJsonPath('scope.class.id', $classB->id)
            ->assertJsonPath('selected_child_id', $childB->id)
            ->assertJsonCount(2, 'children')
            ->assertJsonCount(1, 'parallel_programmes')
            ->assertJsonPath('parallel_programmes.0.curriculum_name', 'Islamiyyah')
            ->assertJsonPath('parallel_programmes.0.arm_name', 'B');

        $this->assertSame(
            'Fiqh',
            $response->json('parallel_programmes.0.week.1.periods.0.subject')
        );
        $this->assertSame(
            [],
            $response->json('parallel_programmes.0.week.0.periods')
        );

        $this->withToken($token)
            ->getJson('/api/v1/schedule?child_id=999999')
            ->assertForbidden();
    }
}
