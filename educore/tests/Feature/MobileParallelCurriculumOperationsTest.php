<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\TimetablePeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileParallelCurriculumOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_parallel_timetable_blocks_teacher_clash_with_conventional_timetable(): void
    {
        $context = $this->context();
        $teacher = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Parallel Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $context['classSubject']->update(['teacher_id' => $teacher->id]);

        $level = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'JSS 1',
            'section' => 'junior_secondary',
            'order_index' => 1,
        ]);
        $conventionalArm = ClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);
        $subject = Subject::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Basic Science',
            'code' => 'BSC',
            'is_active' => true,
        ]);

        TimetablePeriod::create([
            'tenant_id' => $context['tenant']->id,
            'class_arm_id' => $conventionalArm->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'session_id' => $context['session']->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '09:40',
            'venue' => 'Room 1',
        ]);

        $token = ApiToken::issue($context['admin'], 'parallel-operations-clash');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'monday',
                'start_time' => '09:20',
                'end_time' => '10:00',
                'venue' => 'Parallel Room',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_time');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'monday',
                'start_time' => '10:00',
                'end_time' => '10:40',
                'venue' => 'Parallel Room',
            ])
            ->assertCreated()
            ->assertJsonPath('period.teacher_id', $teacher->id)
            ->assertJsonPath('period.subject', 'Qur\'an');

        $this->assertDatabaseHas('parallel_curriculum_timetable_periods', [
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_arm_id' => $context['arm']->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 'monday',
        ]);
    }

    public function test_parallel_attendance_is_saved_separately_from_conventional_attendance(): void
    {
        $context = $this->context();
        $term = Term::create([
            'tenant_id' => $context['tenant']->id,
            'session_id' => $context['session']->id,
            'name' => 'First Term',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_current' => true,
        ]);

        $student = Student::create([
            'tenant_id' => $context['tenant']->id,
            'admission_number' => 'PC-ATT-001',
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

        $level = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'JSS 1',
            'section' => 'junior_secondary',
            'order_index' => 1,
        ]);
        $conventionalArm = ClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);

        AttendanceRecord::create([
            'tenant_id' => $context['tenant']->id,
            'student_id' => $student->id,
            'class_arm_id' => $conventionalArm->id,
            'term_id' => $term->id,
            'marked_by' => $context['admin']->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $token = ApiToken::issue($context['admin'], 'parallel-operations-attendance');

        $sheet = $this->withToken($token)
            ->getJson(
                '/api/v1/parallel-curriculum/operations?'.
                'parallel_curriculum_id='.$context['curriculum']->id.
                '&session_id='.$context['session']->id.
                '&term_id='.$term->id.
                '&class_id='.$context['class']->id.
                '&arm_id='.$context['arm']->id.
                '&date='.now()->toDateString()
            )
            ->assertOk()
            ->assertJsonPath('contract_version', 1)
            ->assertJsonPath('capabilities.save_attendance', true)
            ->assertJsonPath('attendance.students.0.enrolment_id', $enrolment->id)
            ->assertJsonPath('attendance.students.0.name', 'Amina Bello');

        $version = $sheet->json('attendance.version');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/attendance', [
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'term_id' => $term->id,
                'attendance_date' => now()->toDateString(),
                'version' => $version,
                'records' => [[
                    'enrolment_id' => $enrolment->id,
                    'status' => 'late',
                    'remark' => 'Arrived after opening exercise',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('saved', 1)
            ->assertJsonPath('summary.late', 1);

        $this->assertDatabaseHas('parallel_curriculum_attendance_records', [
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_enrolment_id' => $enrolment->id,
            'student_id' => $student->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'late',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'tenant_id' => $context['tenant']->id,
            'student_id' => $student->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);
    }

    private function context(): array
    {
        $tenant = Tenant::create([
            'name' => 'Parallel Operations School',
            'slug' => 'parallel-operations-'.uniqid(),
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
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $subject = ParallelCurriculumSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Qur\'an',
            'code' => 'QRN',
            'is_active' => true,
        ]);

        $classSubject = ParallelCurriculumClassSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $class->id,
            'parallel_curriculum_subject_id' => $subject->id,
            'teacher_id' => null,
            'is_active' => true,
        ]);

        return compact(
            'tenant',
            'session',
            'admin',
            'curriculum',
            'class',
            'arm',
            'subject',
            'classSubject',
        );
    }
}
