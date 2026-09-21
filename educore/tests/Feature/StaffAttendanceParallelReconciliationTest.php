<?php

namespace Tests\Feature;

use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumSubject;
use App\Models\ParallelCurriculumWorkingDay;
use App\Models\StaffAttendanceSetting;
use App\Models\StaffAttendanceWorkingDay;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ParallelCurriculumOperationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StaffAttendanceParallelReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_staff_qr_scan_is_evaluated_independently_for_conventional_and_parallel_curricula(): void
    {
        $fixture = $this->dualCurriculumTeacher();

        StaffAttendanceSetting::forTenant($fixture['tenant']->id)->update([
            'resumption_time' => '07:30:00',
            'grace_minutes' => 0,
            'closing_time' => '16:00:00',
        ]);

        ParallelCurriculumWorkingDay::create([
            'tenant_id' => $fixture['tenant']->id,
            'parallel_curriculum_id' => $fixture['curriculum']->id,
            'day_of_week' => 'monday',
            'is_working' => true,
            'resumption_time' => '08:00:00',
            'closing_time' => '17:00:00',
            'grace_minutes' => 0,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-21 07:30:00'));

        try {
            $token = $fixture['teacher']->personalQrPayload();

            $this->withoutMiddleware()
                ->actingAs($fixture['teacher'])
                ->postJson(route('staff-attendance.api.clockin'), [
                    'token' => $token,
                ])
                ->assertOk()
                ->assertJsonPath('status', 'present')
                ->assertJsonPath('attendance_contexts.0.type', 'conventional')
                ->assertJsonPath('attendance_contexts.0.status', 'present')
                ->assertJsonPath(
                    'attendance_contexts.0.expected_resumption_time',
                    '07:30'
                )
                ->assertJsonPath('attendance_contexts.1.type', 'parallel')
                ->assertJsonPath(
                    'attendance_contexts.1.curriculum_id',
                    $fixture['curriculum']->id
                )
                ->assertJsonPath('attendance_contexts.1.status', 'early')
                ->assertJsonPath(
                    'attendance_contexts.1.expected_resumption_time',
                    '08:00'
                )
                ->assertJsonPath(
                    'attendance_contexts.1.minutes_from_resumption',
                    -30
                );

            $this->assertDatabaseHas('staff_attendance_records', [
                'tenant_id' => $fixture['tenant']->id,
                'user_id' => $fixture['teacher']->id,
                'attendance_date' => '2026-09-21',
                'status' => 'present',
                'clock_in_time' => '07:30:00',
            ]);

            $this->assertDatabaseHas(
                'parallel_curriculum_staff_attendance_records',
                [
                    'tenant_id' => $fixture['tenant']->id,
                    'parallel_curriculum_id' => $fixture['curriculum']->id,
                    'user_id' => $fixture['teacher']->id,
                    'attendance_date' => '2026-09-21',
                    'status' => 'early',
                    'clock_in_time' => '07:30:00',
                    'expected_resumption_time' => '08:00:00',
                ]
            );

            Carbon::setTestNow(Carbon::parse('2026-09-21 17:00:00'));

            $this->withoutMiddleware()
                ->actingAs($fixture['teacher'])
                ->postJson(route('staff-attendance.api.clockout'))
                ->assertOk()
                ->assertJsonPath(
                    'attendance_contexts.0.expected_closing_time',
                    '16:00'
                )
                ->assertJsonPath(
                    'attendance_contexts.0.departure_status',
                    'on_time'
                )
                ->assertJsonPath(
                    'attendance_contexts.1.expected_closing_time',
                    '17:00'
                )
                ->assertJsonPath(
                    'attendance_contexts.1.departure_status',
                    'on_time'
                );

            $this->assertDatabaseHas(
                'parallel_curriculum_staff_attendance_records',
                [
                    'parallel_curriculum_id' => $fixture['curriculum']->id,
                    'user_id' => $fixture['teacher']->id,
                    'attendance_date' => '2026-09-21',
                    'clock_out_time' => '17:00:00',
                    'departure_status' => 'on_time',
                ]
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_shared_scan_only_creates_parallel_contexts_for_curricula_the_teacher_is_assigned_to(): void
    {
        $fixture = $this->dualCurriculumTeacher();

        $other = ParallelCurriculum::create([
            'tenant_id' => $fixture['tenant']->id,
            'name' => 'Tahfeez',
            'code' => 'THF',
            'is_active' => true,
        ]);

        ParallelCurriculumWorkingDay::create([
            'tenant_id' => $fixture['tenant']->id,
            'parallel_curriculum_id' => $fixture['curriculum']->id,
            'day_of_week' => 'monday',
            'is_working' => true,
            'resumption_time' => '08:00:00',
            'closing_time' => '17:00:00',
            'grace_minutes' => 0,
        ]);

        ParallelCurriculumWorkingDay::create([
            'tenant_id' => $fixture['tenant']->id,
            'parallel_curriculum_id' => $other->id,
            'day_of_week' => 'monday',
            'is_working' => true,
            'resumption_time' => '09:00:00',
            'closing_time' => '18:00:00',
            'grace_minutes' => 0,
        ]);

        StaffAttendanceSetting::forTenant($fixture['tenant']->id)->update([
            'resumption_time' => '07:30:00',
            'grace_minutes' => 0,
            'closing_time' => '16:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-21 07:30:00'));

        try {
            $response = $this->withoutMiddleware()
                ->actingAs($fixture['teacher'])
                ->postJson(route('staff-attendance.api.clockin'), [
                    'token' => $fixture['teacher']->personalQrPayload(),
                ])
                ->assertOk();

            $this->assertCount(2, $response->json('attendance_contexts'));

            $this->assertDatabaseHas(
                'parallel_curriculum_staff_attendance_records',
                [
                    'parallel_curriculum_id' => $fixture['curriculum']->id,
                    'user_id' => $fixture['teacher']->id,
                ]
            );

            $this->assertDatabaseMissing(
                'parallel_curriculum_staff_attendance_records',
                [
                    'parallel_curriculum_id' => $other->id,
                    'user_id' => $fixture['teacher']->id,
                ]
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_legacy_parallel_clock_in_cannot_create_an_independent_attendance_timestamp(): void
    {
        $fixture = $this->dualCurriculumTeacher();

        Carbon::setTestNow(Carbon::parse('2026-09-21 08:15:00'));

        try {
            try {
                app(ParallelCurriculumOperationsService::class)
                    ->clockInParallelStaff(
                        $fixture['teacher'],
                        $fixture['curriculum']->id
                    );

                $this->fail(
                    'Standalone parallel clock-in should require the shared conventional QR event.'
                );
            } catch (ValidationException $exception) {
                $this->assertStringContainsString(
                    'normal staff attendance QR first',
                    $exception->errors()['attendance'][0] ?? ''
                );
            }

            $this->assertDatabaseMissing(
                'parallel_curriculum_staff_attendance_records',
                [
                    'parallel_curriculum_id' => $fixture['curriculum']->id,
                    'user_id' => $fixture['teacher']->id,
                    'attendance_date' => '2026-09-21',
                ]
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_legacy_parallel_clock_in_reuses_the_shared_qr_timestamp(): void
    {
        $fixture = $this->dualCurriculumTeacher();

        ParallelCurriculumWorkingDay::create([
            'tenant_id' => $fixture['tenant']->id,
            'parallel_curriculum_id' => $fixture['curriculum']->id,
            'day_of_week' => 'monday',
            'is_working' => true,
            'resumption_time' => '08:00:00',
            'closing_time' => '17:00:00',
            'grace_minutes' => 0,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-21 07:30:00'));

        try {
            $this->withoutMiddleware()
                ->actingAs($fixture['teacher'])
                ->postJson(route('staff-attendance.api.clockin'), [
                    'token' => $fixture['teacher']->personalQrPayload(),
                ])
                ->assertOk();

            Carbon::setTestNow(Carbon::parse('2026-09-21 08:15:00'));

            $record = app(ParallelCurriculumOperationsService::class)
                ->clockInParallelStaff(
                    $fixture['teacher'],
                    $fixture['curriculum']->id
                );

            $this->assertSame('07:30:00', (string) $record->clock_in_time);
            $this->assertSame('early', $record->status);

            $this->assertDatabaseHas(
                'parallel_curriculum_staff_attendance_records',
                [
                    'parallel_curriculum_id' => $fixture['curriculum']->id,
                    'user_id' => $fixture['teacher']->id,
                    'attendance_date' => '2026-09-21',
                    'clock_in_time' => '07:30:00',
                ]
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_conventional_work_hours_can_differ_by_day_without_hard_coded_times(): void
    {
        $fixture = $this->dualCurriculumTeacher();

        StaffAttendanceSetting::forTenant($fixture['tenant']->id)->update([
            'resumption_time' => '08:00:00',
            'grace_minutes' => 15,
            'closing_time' => '15:00:00',
        ]);

        StaffAttendanceWorkingDay::create([
            'tenant_id' => $fixture['tenant']->id,
            'day_of_week' => 'monday',
            'is_working' => true,
            'resumption_time' => '07:15:00',
            'closing_time' => '16:10:00',
            'grace_minutes' => 5,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-21 07:21:00'));

        try {
            $this->withoutMiddleware()
                ->actingAs($fixture['teacher'])
                ->postJson(route('staff-attendance.api.clockin'), [
                    'token' => $fixture['teacher']->personalQrPayload(),
                ])
                ->assertOk()
                ->assertJsonPath('status', 'late')
                ->assertJsonPath('attendance_contexts.0.type', 'conventional')
                ->assertJsonPath('attendance_contexts.0.is_working_day', true)
                ->assertJsonPath(
                    'attendance_contexts.0.expected_resumption_time',
                    '07:15'
                )
                ->assertJsonPath('attendance_contexts.0.grace_minutes', 5);

            $this->assertDatabaseHas('staff_attendance_records', [
                'tenant_id' => $fixture['tenant']->id,
                'user_id' => $fixture['teacher']->id,
                'attendance_date' => '2026-09-21',
                'status' => 'late',
                'expected_resumption_time' => '07:15:00',
                'expected_closing_time' => '16:10:00',
                'grace_minutes' => 5,
                'scheduled_workday' => true,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_shared_scan_on_conventional_off_day_still_feeds_working_parallel_curriculum(): void
    {
        $fixture = $this->dualCurriculumTeacher();

        StaffAttendanceWorkingDay::create([
            'tenant_id' => $fixture['tenant']->id,
            'day_of_week' => 'monday',
            'is_working' => false,
            'resumption_time' => null,
            'closing_time' => null,
            'grace_minutes' => 0,
        ]);

        ParallelCurriculumWorkingDay::create([
            'tenant_id' => $fixture['tenant']->id,
            'parallel_curriculum_id' => $fixture['curriculum']->id,
            'day_of_week' => 'monday',
            'is_working' => true,
            'resumption_time' => '08:00:00',
            'closing_time' => '17:00:00',
            'grace_minutes' => 10,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-21 08:05:00'));

        try {
            $response = $this->withoutMiddleware()
                ->actingAs($fixture['teacher'])
                ->postJson(route('staff-attendance.api.clockin'), [
                    'token' => $fixture['teacher']->personalQrPayload(),
                ])
                ->assertOk()
                ->assertJsonPath('status', 'not_scheduled')
                ->assertJsonPath('attendance_contexts.0.is_working_day', false)
                ->assertJsonPath('attendance_contexts.0.expected_resumption_time', null)
                ->assertJsonPath('attendance_contexts.1.type', 'parallel')
                ->assertJsonPath('attendance_contexts.1.status', 'present');

            $this->assertCount(2, $response->json('attendance_contexts'));

            $this->assertDatabaseHas('staff_attendance_records', [
                'tenant_id' => $fixture['tenant']->id,
                'user_id' => $fixture['teacher']->id,
                'attendance_date' => '2026-09-21',
                'status' => 'not_scheduled',
                'scheduled_workday' => false,
            ]);

            $this->assertDatabaseHas(
                'parallel_curriculum_staff_attendance_records',
                [
                    'tenant_id' => $fixture['tenant']->id,
                    'parallel_curriculum_id' => $fixture['curriculum']->id,
                    'user_id' => $fixture['teacher']->id,
                    'attendance_date' => '2026-09-21',
                    'status' => 'present',
                    'clock_in_time' => '08:05:00',
                ]
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    private function dualCurriculumTeacher(): array
    {
        $tenant = Tenant::create([
            'name' => 'Shared Attendance School',
            'slug' => 'shared-attendance-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dual Curriculum Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
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

        ParallelCurriculumClassArm::create([
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

        ParallelCurriculumClassSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $class->id,
            'parallel_curriculum_subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        return compact(
            'tenant',
            'teacher',
            'curriculum',
            'class',
            'subject'
        );
    }
}
