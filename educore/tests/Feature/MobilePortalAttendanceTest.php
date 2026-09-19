<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Guardian;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumAttendanceRecord;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobilePortalAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_attendance_is_child_and_term_scoped_and_includes_parallel_attendance(): void
    {
        $tenant = Tenant::create([
            'name' => 'Portal Attendance School',
            'slug' => 'portal-attendance-'.uniqid(),
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

        $firstTerm = Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'is_current' => true,
        ]);
        $secondTerm = Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'Second Term',
            'is_current' => false,
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

        AttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'student_id' => $childA->id,
            'class_arm_id' => $classA->id,
            'term_id' => $firstTerm->id,
            'marked_by' => null,
            'attendance_date' => '2026-09-14',
            'status' => 'present',
            'remark' => null,
        ]);
        AttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'student_id' => $childB->id,
            'class_arm_id' => $classB->id,
            'term_id' => $firstTerm->id,
            'marked_by' => null,
            'attendance_date' => '2026-09-14',
            'status' => 'present',
            'remark' => 'On time',
        ]);
        AttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'student_id' => $childB->id,
            'class_arm_id' => $classB->id,
            'term_id' => $firstTerm->id,
            'marked_by' => null,
            'attendance_date' => '2026-09-15',
            'status' => 'absent',
            'remark' => 'Absent',
        ]);
        AttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'student_id' => $childB->id,
            'class_arm_id' => $classB->id,
            'term_id' => $secondTerm->id,
            'marked_by' => null,
            'attendance_date' => '2027-01-12',
            'status' => 'late',
            'remark' => 'Late arrival',
        ]);

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

        $enrolmentA = ParallelCurriculumEnrolment::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArmA->id,
            'student_id' => $childA->id,
            'session_id' => $session->id,
            'is_active' => true,
        ]);
        $enrolmentB = ParallelCurriculumEnrolment::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArmB->id,
            'student_id' => $childB->id,
            'session_id' => $session->id,
            'is_active' => true,
        ]);

        ParallelCurriculumAttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArmA->id,
            'parallel_curriculum_enrolment_id' => $enrolmentA->id,
            'student_id' => $childA->id,
            'term_id' => $firstTerm->id,
            'marked_by' => null,
            'attendance_date' => '2026-09-14',
            'status' => 'absent',
            'remark' => null,
        ]);
        ParallelCurriculumAttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArmB->id,
            'parallel_curriculum_enrolment_id' => $enrolmentB->id,
            'student_id' => $childB->id,
            'term_id' => $firstTerm->id,
            'marked_by' => null,
            'attendance_date' => '2026-09-14',
            'status' => 'present',
            'remark' => 'Present',
        ]);
        ParallelCurriculumAttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'parallel_curriculum_class_arm_id' => $parallelArmB->id,
            'parallel_curriculum_enrolment_id' => $enrolmentB->id,
            'student_id' => $childB->id,
            'term_id' => $secondTerm->id,
            'marked_by' => null,
            'attendance_date' => '2027-01-12',
            'status' => 'absent',
            'remark' => null,
        ]);

        $token = ApiToken::issue($parentUser, 'parent-portal-attendance');

        $this->withToken($token)
            ->getJson('/api/v1/portal-attendance?child_id='.$childB->id.'&term_id='.$firstTerm->id)
            ->assertOk()
            ->assertJsonPath('student.id', $childB->id)
            ->assertJsonPath('student.admission_number', 'CHILD-B')
            ->assertJsonCount(2, 'children')
            ->assertJsonPath('selected_term_id', $firstTerm->id)
            ->assertJsonPath('conventional.stats.total', 2)
            ->assertJsonPath('conventional.stats.present', 1)
            ->assertJsonPath('conventional.stats.absent', 1)
            ->assertJsonPath('conventional.stats.late', 0)
            ->assertJsonPath('conventional.stats.rate', 50)
            ->assertJsonCount(2, 'conventional.records')
            ->assertJsonCount(1, 'parallel_programmes')
            ->assertJsonPath('parallel_programmes.0.curriculum_name', 'Islamiyyah')
            ->assertJsonPath('parallel_programmes.0.class_name', 'Mutawassitah 1')
            ->assertJsonPath('parallel_programmes.0.arm_name', 'B')
            ->assertJsonPath('parallel_programmes.0.stats.total', 1)
            ->assertJsonPath('parallel_programmes.0.stats.present', 1)
            ->assertJsonPath('parallel_programmes.0.stats.rate', 100)
            ->assertJsonCount(1, 'parallel_programmes.0.records');

        $this->withToken($token)
            ->getJson('/api/v1/portal-attendance?child_id=999999&term_id='.$firstTerm->id)
            ->assertForbidden();
    }

    public function test_student_sees_only_own_attendance_even_when_child_id_is_supplied(): void
    {
        $tenant = Tenant::create([
            'name' => 'Student Attendance School',
            'slug' => 'student-attendance-'.uniqid(),
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
            'name' => 'JSS 2',
            'section' => 'junior_secondary',
            'order_index' => 2,
        ]);
        $class = ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);

        $studentUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Student User',
            'role' => 'student',
            'is_active' => true,
        ]);
        $student = Student::create([
            'tenant_id' => $tenant->id,
            'user_id' => $studentUser->id,
            'current_class_arm_id' => $class->id,
            'admission_number' => 'STD-SELF',
            'first_name' => 'Self',
            'last_name' => 'Student',
            'status' => Student::STATUS_ACTIVE,
        ]);
        $otherStudent = Student::create([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $class->id,
            'admission_number' => 'STD-OTHER',
            'first_name' => 'Other',
            'last_name' => 'Student',
            'status' => Student::STATUS_ACTIVE,
        ]);

        AttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'class_arm_id' => $class->id,
            'term_id' => $term->id,
            'marked_by' => null,
            'attendance_date' => '2026-09-16',
            'status' => 'present',
            'remark' => null,
        ]);
        AttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'student_id' => $otherStudent->id,
            'class_arm_id' => $class->id,
            'term_id' => $term->id,
            'marked_by' => null,
            'attendance_date' => '2026-09-16',
            'status' => 'absent',
            'remark' => null,
        ]);

        $token = ApiToken::issue($studentUser, 'student-portal-attendance');

        $this->withToken($token)
            ->getJson('/api/v1/portal-attendance?child_id='.$otherStudent->id.'&term_id='.$term->id)
            ->assertOk()
            ->assertJsonPath('student.id', $student->id)
            ->assertJsonPath('student.admission_number', 'STD-SELF')
            ->assertJsonCount(0, 'children')
            ->assertJsonPath('conventional.stats.total', 1)
            ->assertJsonPath('conventional.stats.present', 1)
            ->assertJsonPath('conventional.stats.absent', 0);
    }

    public function test_staff_cannot_access_portal_attendance_endpoint(): void
    {
        $tenant = Tenant::create([
            'name' => 'Portal Attendance Guard School',
            'slug' => 'portal-attendance-guard-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Teacher User',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $token = ApiToken::issue($staff, 'staff-portal-attendance');

        $this->withToken($token)
            ->getJson('/api/v1/portal-attendance')
            ->assertForbidden();
    }
}
