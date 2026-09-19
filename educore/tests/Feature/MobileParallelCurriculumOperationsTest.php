<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumAttendanceRecord;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Mobile\MobileModuleService;
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

    public function test_teacher_reassignment_cannot_create_timetable_clash_and_syncs_when_safe(): void
    {
        $context = $this->context();
        $teacherA = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Teacher A',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $teacherB = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Teacher B',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $teacherC = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Teacher C',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $context['classSubject']->update(['teacher_id' => $teacherA->id]);

        $level = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'JSS 2',
            'section' => 'junior_secondary',
            'order_index' => 2,
        ]);
        $conventionalArm = ClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);
        $subject = Subject::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Mathematics',
            'code' => 'MTH',
            'is_active' => true,
        ]);

        TimetablePeriod::create([
            'tenant_id' => $context['tenant']->id,
            'class_arm_id' => $conventionalArm->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacherB->id,
            'session_id' => $context['session']->id,
            'day_of_week' => 'tuesday',
            'start_time' => '10:00',
            'end_time' => '10:40',
            'venue' => 'Main Block',
        ]);

        $token = ApiToken::issue($context['admin'], 'parallel-teacher-change');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'tuesday',
                'start_time' => '10:00',
                'end_time' => '10:40',
            ])
            ->assertCreated()
            ->assertJsonPath('period.teacher_id', $teacherA->id);

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/class-subjects', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'teacher_id' => $teacherB->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('teacher_id');

        $this->assertDatabaseHas('parallel_curriculum_timetable_periods', [
            'parallel_curriculum_class_id' => $context['class']->id,
            'teacher_id' => $teacherA->id,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/class-subjects', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'teacher_id' => $teacherC->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('parallel_curriculum_timetable_periods', [
            'parallel_curriculum_class_id' => $context['class']->id,
            'teacher_id' => $teacherC->id,
        ]);
    }

    public function test_assigned_subject_teacher_gets_attendance_workspace_without_timetable_management(): void
    {
        $context = $this->context();
        $teacher = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Assigned Parallel Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $context['classSubject']->update(['teacher_id' => $teacher->id]);

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
            'admission_number' => 'PC-TEACH-001',
            'first_name' => 'Musa',
            'last_name' => 'Ibrahim',
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

        $this->assertTrue($teacher->canAccessRoute('parallel-curriculum.operations.index'));
        $this->assertTrue($teacher->canAccessRoute('parallel-curriculum.operations.attendance.save'));
        $this->assertFalse($teacher->canAccessRoute('parallel-curriculum.operations.periods.store'));

        $mobileModules = app(MobileModuleService::class)->forUser($teacher);
        $moduleKeys = collect($mobileModules)->pluck('key');
        $this->assertTrue($moduleKeys->contains('parallel-timetable'));
        $this->assertFalse($moduleKeys->contains('parallel-curriculum'));

        $token = ApiToken::issue($teacher, 'parallel-teacher-attendance');
        $response = $this->withToken($token)
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
            ->assertJsonPath('capabilities.manage_timetable', false)
            ->assertJsonPath('capabilities.save_attendance', true)
            ->assertJsonPath('capabilities.export_attendance', false)
            ->assertJsonPath('attendance.students.0.enrolment_id', $enrolment->id);

        $version = $response->json('attendance.version');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/attendance', [
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'term_id' => $term->id,
                'attendance_date' => now()->toDateString(),
                'version' => $version,
                'records' => [[
                    'enrolment_id' => $enrolment->id,
                    'status' => 'present',
                    'remark' => null,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('saved', 1);

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'monday',
                'start_time' => '11:00',
                'end_time' => '11:40',
            ])
            ->assertForbidden();
    }

    public function test_parallel_attendance_exports_csv_and_pdf_for_authorized_admin(): void
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
            'admission_number' => 'PC-EXP-001',
            'first_name' => 'Zainab',
            'last_name' => 'Sani',
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

        ParallelCurriculumAttendanceRecord::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $context['curriculum']->id,
            'parallel_curriculum_class_id' => $context['class']->id,
            'parallel_curriculum_class_arm_id' => $context['arm']->id,
            'parallel_curriculum_enrolment_id' => $enrolment->id,
            'student_id' => $student->id,
            'term_id' => $term->id,
            'marked_by' => $context['admin']->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $token = ApiToken::issue($context['admin'], 'parallel-attendance-export');

        $csv = $this->withToken($token)->get(
            '/api/v1/parallel-curriculum/operations/attendance/export?'.
            http_build_query([
                'arm_id' => $context['arm']->id,
                'term_id' => $term->id,
                'format' => 'csv',
            ])
        );

        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));
        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('PC-EXP-001', $csvContent);
        $this->assertStringContainsString('Zainab Sani', $csvContent);

        $pdf = $this->withToken($token)->get(
            '/api/v1/parallel-curriculum/operations/attendance/export?'.
            http_build_query([
                'arm_id' => $context['arm']->id,
                'term_id' => $term->id,
                'format' => 'pdf',
            ])
        );

        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));
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
            'attendance_date' => '2026-09-21',
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
                '&date='.'2026-09-21'
            )
            ->assertOk()
            ->assertJsonPath('contract_version', 2)
            ->assertJsonPath('capabilities.save_attendance', true)
            ->assertJsonPath('attendance.students.0.enrolment_id', $enrolment->id)
            ->assertJsonPath('attendance.students.0.name', 'Amina Bello');

        $version = $sheet->json('attendance.version');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/attendance', [
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'term_id' => $term->id,
                'attendance_date' => '2026-09-21',
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
            'attendance_date' => '2026-09-21',
            'status' => 'late',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'tenant_id' => $context['tenant']->id,
            'student_id' => $student->id,
            'attendance_date' => '2026-09-21',
            'status' => 'present',
        ]);
    }

    public function test_parallel_working_days_support_day_specific_hours(): void
    {
        $context = $this->context();
        $token = ApiToken::issue($context['admin'], 'parallel-working-days');

        $days = [
            ['day_of_week' => 'monday', 'is_working' => true, 'resumption_time' => '15:30', 'closing_time' => '18:00', 'grace_minutes' => 10],
            ['day_of_week' => 'tuesday', 'is_working' => true, 'resumption_time' => '16:00', 'closing_time' => '18:30', 'grace_minutes' => 15],
            ['day_of_week' => 'wednesday', 'is_working' => false, 'resumption_time' => null, 'closing_time' => null, 'grace_minutes' => 0],
            ['day_of_week' => 'thursday', 'is_working' => true, 'resumption_time' => '15:00', 'closing_time' => '17:30', 'grace_minutes' => 5],
            ['day_of_week' => 'friday', 'is_working' => false, 'resumption_time' => null, 'closing_time' => null, 'grace_minutes' => 0],
            ['day_of_week' => 'saturday', 'is_working' => true, 'resumption_time' => '08:00', 'closing_time' => '13:00', 'grace_minutes' => 20],
            ['day_of_week' => 'sunday', 'is_working' => false, 'resumption_time' => null, 'closing_time' => null, 'grace_minutes' => 0],
        ];

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/working-days', [
                'parallel_curriculum_id' => $context['curriculum']->id,
                'days' => $days,
            ])
            ->assertOk()
            ->assertJsonPath('working_days.0.day_of_week', 'monday')
            ->assertJsonPath('working_days.0.resumption_time', '15:30')
            ->assertJsonPath('working_days.5.day_of_week', 'saturday')
            ->assertJsonPath('working_days.5.closing_time', '13:00');

        $this->assertDatabaseHas('parallel_curriculum_working_days', [
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $context['curriculum']->id,
            'day_of_week' => 'tuesday',
            'is_working' => 1,
            'grace_minutes' => 15,
        ]);

        $this->withToken($token)
            ->getJson(
                '/api/v1/parallel-curriculum/operations?'.
                'parallel_curriculum_id='.$context['curriculum']->id.
                '&session_id='.$context['session']->id
            )
            ->assertOk()
            ->assertJsonPath('contract_version', 2)
            ->assertJsonPath('working_days.1.day_of_week', 'tuesday')
            ->assertJsonPath('working_days.1.resumption_time', '16:00')
            ->assertJsonPath('working_days.5.day_of_week', 'saturday');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'tuesday',
                'start_time' => '15:30',
                'end_time' => '16:30',
                'venue' => 'Too Early',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end_time');

        $term = Term::create([
            'tenant_id' => $context['tenant']->id,
            'session_id' => $context['session']->id,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $this->withToken($token)
            ->getJson(
                '/api/v1/parallel-curriculum/operations?'.
                'parallel_curriculum_id='.$context['curriculum']->id.
                '&session_id='.$context['session']->id.
                '&term_id='.$term->id.
                '&class_id='.$context['class']->id.
                '&arm_id='.$context['arm']->id.
                '&date=2026-09-23'
            )
            ->assertOk()
            ->assertJsonPath('capabilities.save_attendance', false)
            ->assertJsonPath('attendance.is_working_day', false);
    }

    public function test_switching_to_subject_based_mode_rejects_existing_teacher_clash(): void
    {
        $context = $this->context();
        $classTeacher = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Class Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $subjectTeacher = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Subject Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $context['classSubject']->update(['teacher_id' => $subjectTeacher->id]);

        $level = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'JSS 2',
            'section' => 'junior_secondary',
            'order_index' => 2,
        ]);
        $conventionalArm = ClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);
        $subject = Subject::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Mathematics',
            'code' => 'MTH',
            'is_active' => true,
        ]);

        TimetablePeriod::create([
            'tenant_id' => $context['tenant']->id,
            'class_arm_id' => $conventionalArm->id,
            'subject_id' => $subject->id,
            'teacher_id' => $subjectTeacher->id,
            'session_id' => $context['session']->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '09:40',
            'venue' => 'Conventional Room',
        ]);

        $token = ApiToken::issue($context['admin'], 'parallel-mode-switch-clash');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/arm-teaching-mode', [
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'teaching_assignment_mode' => 'class_teacher',
                'class_teacher_id' => $classTeacher->id,
            ])
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'monday',
                'start_time' => '09:00',
                'end_time' => '09:40',
                'venue' => 'Parallel Room',
            ])
            ->assertCreated()
            ->assertJsonPath('period.teacher_id', $classTeacher->id);

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/arm-teaching-mode', [
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'teaching_assignment_mode' => 'subject_based',
                'class_teacher_id' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('teacher_id');

        $context['arm']->refresh();
        $this->assertSame('class_teacher', $context['arm']->teaching_assignment_mode);
        $this->assertSame($classTeacher->id, (int) $context['arm']->class_teacher_id);
    }

    public function test_subject_teacher_can_teach_same_subject_across_multiple_parallel_classes(): void
    {
        $context = $this->context();
        $teacher = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Shared Parallel Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $secondClass = ParallelCurriculumClass::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $context['curriculum']->id,
            'name' => 'Mutawassitah 2',
            'code' => 'M2',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $secondArm = ParallelCurriculumClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $secondClass->id,
            'name' => 'A',
            'code' => 'A',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ParallelCurriculumClassSubject::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $secondClass->id,
            'parallel_curriculum_subject_id' => $context['subject']->id,
            'teacher_id' => null,
            'is_active' => true,
        ]);

        $token = ApiToken::issue($context['admin'], 'parallel-shared-subject-teacher');

        foreach ([$context['arm']->id, $secondArm->id] as $armId) {
            $this->withToken($token)
                ->postJson('/api/v1/parallel-curriculum/lifecycle/arm-teachers', [
                    'parallel_curriculum_class_arm_id' => $armId,
                    'parallel_curriculum_subject_id' => $context['subject']->id,
                    'teacher_id' => $teacher->id,
                ])
                ->assertOk()
                ->assertJsonPath('effective_teacher_id', $teacher->id);
        }

        $this->assertDatabaseCount('parallel_curriculum_arm_subject_teachers', 2);

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'monday',
                'start_time' => '10:00',
                'end_time' => '10:40',
                'venue' => 'Room A',
            ])
            ->assertCreated()
            ->assertJsonPath('period.teacher_id', $teacher->id);

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $secondClass->id,
                'parallel_curriculum_class_arm_id' => $secondArm->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'monday',
                'start_time' => '10:40',
                'end_time' => '11:20',
                'venue' => 'Room B',
            ])
            ->assertCreated()
            ->assertJsonPath('period.teacher_id', $teacher->id);

        $this->assertDatabaseHas('parallel_curriculum_timetable_periods', [
            'parallel_curriculum_class_id' => $context['class']->id,
            'parallel_curriculum_class_arm_id' => $context['arm']->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertDatabaseHas('parallel_curriculum_timetable_periods', [
            'parallel_curriculum_class_id' => $secondClass->id,
            'parallel_curriculum_class_arm_id' => $secondArm->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_default_subject_teacher_update_ignores_class_teacher_arm_periods(): void
    {
        $context = $this->context();

        $classTeacher = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Dedicated Class Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $newDefaultTeacher = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'New Default Subject Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $level = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'JSS 3',
            'section' => 'junior_secondary',
            'order_index' => 3,
        ]);
        $conventionalArm = ClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);
        $subject = Subject::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'English Language',
            'code' => 'ENG',
            'is_active' => true,
        ]);

        TimetablePeriod::create([
            'tenant_id' => $context['tenant']->id,
            'class_arm_id' => $conventionalArm->id,
            'subject_id' => $subject->id,
            'teacher_id' => $newDefaultTeacher->id,
            'session_id' => $context['session']->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '09:40',
            'venue' => 'Conventional Room',
        ]);

        $token = ApiToken::issue($context['admin'], 'parallel-default-teacher-class-arm');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/arm-teaching-mode', [
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'teaching_assignment_mode' => 'class_teacher',
                'class_teacher_id' => $classTeacher->id,
            ])
            ->assertOk();

        $period = $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'monday',
                'start_time' => '09:00',
                'end_time' => '09:40',
                'venue' => 'Parallel Room',
            ])
            ->assertCreated()
            ->assertJsonPath('period.teacher_id', $classTeacher->id);

        $periodId = (int) $period->json('period.id');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/class-subjects', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'teacher_id' => $newDefaultTeacher->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('parallel_curriculum_class_subjects', [
            'id' => $context['classSubject']->id,
            'teacher_id' => $newDefaultTeacher->id,
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('parallel_curriculum_timetable_periods', [
            'id' => $periodId,
            'parallel_curriculum_class_arm_id' => $context['arm']->id,
            'teacher_id' => $classTeacher->id,
        ]);
    }

    public function test_class_teacher_mode_grants_arm_scoped_mobile_score_entry(): void
    {
        $context = $this->context();
        $teacher = User::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Parallel Class Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $adminToken = ApiToken::issue($context['admin'], 'parallel-class-teacher-score-setup');

        $this->withToken($adminToken)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/arm-teaching-mode', [
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'teaching_assignment_mode' => 'class_teacher',
                'class_teacher_id' => $teacher->id,
            ])
            ->assertOk();

        $templateId = DB::table('assessment_templates')->insertGetId([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Parallel Score Template',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $componentId = DB::table('assessment_template_components')->insertGetId([
            'tenant_id' => $context['tenant']->id,
            'assessment_template_id' => $templateId,
            'name' => 'Continuous Assessment',
            'weight_percentage' => 100,
            'component_type' => 'continuous_assessment',
            'entry_mode' => 'manual',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $context['curriculum']->update([
            'default_assessment_template_id' => $templateId,
        ]);

        $term = Term::create([
            'tenant_id' => $context['tenant']->id,
            'session_id' => $context['session']->id,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);
        $student = Student::create([
            'tenant_id' => $context['tenant']->id,
            'admission_number' => 'PC-SCORE-001',
            'first_name' => 'Maryam',
            'last_name' => 'Ibrahim',
            'status' => Student::STATUS_ACTIVE,
        ]);
        ParallelCurriculumEnrolment::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $context['curriculum']->id,
            'parallel_curriculum_class_id' => $context['class']->id,
            'parallel_curriculum_class_arm_id' => $context['arm']->id,
            'student_id' => $student->id,
            'session_id' => $context['session']->id,
            'is_active' => true,
        ]);

        $teacherToken = ApiToken::issue($teacher, 'parallel-class-teacher-score');
        $workspaceId = 1000000000 + (int) $context['arm']->id;

        $this->withToken($teacherToken)
            ->getJson('/api/v1/parallel-scores/teaching')
            ->assertOk()
            ->assertJsonCount(1, 'assignments')
            ->assertJsonPath('assignments.0.class_arm_id', $workspaceId)
            ->assertJsonPath('assignments.0.subject_id', $context['subject']->id);

        $sheet = $this->withToken($teacherToken)
            ->getJson(
                '/api/v1/parallel-scores/sheet?'.
                'class_arm_id='.$workspaceId.
                '&subject_id='.$context['subject']->id.
                '&term_id='.$term->id
            )
            ->assertOk()
            ->assertJsonPath('students.0.id', $student->id);

        $this->withToken($teacherToken)
            ->postJson('/api/v1/parallel-scores/save', [
                'class_arm_id' => $workspaceId,
                'subject_id' => $context['subject']->id,
                'term_id' => $term->id,
                'version' => $sheet->json('version'),
                'request_id' => 'e8095080-cae4-4f84-a4de-62f422bd5f96',
                'scores' => [
                    $student->id => [
                        $componentId => 78,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('saved', 1);

        $this->assertDatabaseHas('parallel_curriculum_scores', [
            'parallel_curriculum_class_id' => $context['class']->id,
            'student_id' => $student->id,
            'parallel_curriculum_subject_id' => $context['subject']->id,
            'assessment_template_component_id' => $componentId,
            'term_id' => $term->id,
            'entered_by' => $teacher->id,
            'score' => 78,
        ]);
    }

    public function test_class_teacher_mode_drives_timetable_and_parallel_staff_attendance(): void
    {
        $context = $this->context();
        $token = ApiToken::issue($context['admin'], 'parallel-class-teacher-attendance');

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/arm-teaching-mode', [
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'teaching_assignment_mode' => 'class_teacher',
                'class_teacher_id' => $context['admin']->id,
            ])
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/lifecycle/arm-teachers', [
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'teacher_id' => $context['admin']->id,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('parallel_curriculum_class_arms', [
            'id' => $context['arm']->id,
            'teaching_assignment_mode' => 'class_teacher',
            'class_teacher_id' => $context['admin']->id,
        ]);

        $days = collect([
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ])->map(fn (string $day) => [
            'day_of_week' => $day,
            'is_working' => $day === 'monday',
            'resumption_time' => $day === 'monday' ? '08:00' : null,
            'closing_time' => $day === 'monday' ? '15:00' : null,
            'grace_minutes' => $day === 'monday' ? 15 : 0,
        ])->values()->all();

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/working-days', [
                'parallel_curriculum_id' => $context['curriculum']->id,
                'days' => $days,
            ])
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/parallel-curriculum/operations/periods', [
                'parallel_curriculum_class_id' => $context['class']->id,
                'parallel_curriculum_class_arm_id' => $context['arm']->id,
                'parallel_curriculum_subject_id' => $context['subject']->id,
                'session_id' => $context['session']->id,
                'day_of_week' => 'monday',
                'start_time' => '09:00',
                'end_time' => '09:40',
                'venue' => 'Parallel Room',
            ])
            ->assertCreated()
            ->assertJsonPath('period.teacher_id', $context['admin']->id);

        Carbon::setTestNow(Carbon::parse('2026-09-21 08:10:00'));

        try {
            $this->withToken($token)
                ->postJson('/api/v1/parallel-curriculum/operations/staff-attendance/clock-in', [
                    'parallel_curriculum_id' => $context['curriculum']->id,
                ])
                ->assertOk()
                ->assertJsonPath('status', 'present')
                ->assertJsonPath('clock_in_time', '08:10');

            $this->withToken($token)
                ->getJson(
                    '/api/v1/parallel-curriculum/operations?'.
                    'parallel_curriculum_id='.$context['curriculum']->id.
                    '&session_id='.$context['session']->id.
                    '&class_id='.$context['class']->id.
                    '&arm_id='.$context['arm']->id.
                    '&date=2026-09-21'
                )
                ->assertOk()
                ->assertJsonPath('capabilities.clock_parallel_staff', true)
                ->assertJsonPath('staff_attendance.self_record.status', 'present')
                ->assertJsonPath('staff_attendance.self_record.clock_in_time', '08:10');

            Carbon::setTestNow(Carbon::parse('2026-09-21 14:30:00'));

            $this->withToken($token)
                ->postJson('/api/v1/parallel-curriculum/operations/staff-attendance/clock-out', [
                    'parallel_curriculum_id' => $context['curriculum']->id,
                ])
                ->assertOk()
                ->assertJsonPath('departure_status', 'early')
                ->assertJsonPath('clock_out_time', '14:30');

            $this->assertDatabaseHas('parallel_curriculum_staff_attendance_records', [
                'tenant_id' => $context['tenant']->id,
                'parallel_curriculum_id' => $context['curriculum']->id,
                'user_id' => $context['admin']->id,
                'attendance_date' => '2026-09-21',
                'status' => 'present',
                'departure_status' => 'early',
            ]);
        } finally {
            Carbon::setTestNow();
        }
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
