<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentArchiveController;
use App\Http\Controllers\CbtController;
use App\Models\AuditLog;
use App\Models\CbtExam;
use App\Models\ClassArm;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentStatusHistory;
use App\Models\User;
use App\Services\StudentLifecycle\ChangeStudentStatus;
use App\Services\StudentLifecycle\CorrectGraduatedStudentStatus;
use App\Services\StudentLifecycle\ReactivateStudent;
use App\Services\StudentLifecycle\ReadmitStudent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentLifecycleTest extends TestCase
{
    private const ALL_PERMISSIONS = [
        'student.status.view',
        'student.status.change',
        'student.status.approve',
        'student.archive.view',
        'student.archive.export',
        'student.reactivate',
        'student.readmit',
        'student.status.correct-graduation',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Student lifecycle tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_active_student_can_be_marked_left(): void
    {
        [$actor, $student, $enrollment] = $this->activeStudentFixture();

        $result = $this->changeStatus($actor, $student, Student::STATUS_LEFT);

        $this->assertSame(Student::STATUS_LEFT, $result->status);
        $this->assertSame($student->current_class_arm_id, $result->current_class_arm_id);
        $this->assertFalse($enrollment->fresh()->is_current);
        $this->assertSame(StudentEnrollment::STATUS_LEFT, $enrollment->fresh()->status);
        $this->assertSame('2026-06-19', $enrollment->fresh()->end_date->toDateString());
        $this->assertSame($actor->id, $enrollment->fresh()->ended_by);
        $this->assertSame(1, StudentStatusHistory::where('new_status', Student::STATUS_LEFT)->count());
        $this->assertSame(1, AuditLog::where('action', 'student.left')->count());
    }

    public function test_active_student_can_be_withdrawn(): void
    {
        [$actor, $student, $enrollment] = $this->activeStudentFixture();

        $this->changeStatus($actor, $student, Student::STATUS_WITHDRAWN);

        $this->assertSame(Student::STATUS_WITHDRAWN, $student->fresh()->status);
        $this->assertFalse($enrollment->fresh()->is_current);
        $this->assertSame(StudentEnrollment::STATUS_WITHDRAWN, $enrollment->fresh()->status);
    }

    public function test_active_student_can_be_transferred_out_with_destination_metadata(): void
    {
        [$actor, $student, $enrollment] = $this->activeStudentFixture();

        $this->changeStatus($actor, $student, Student::STATUS_TRANSFERRED_OUT, [
            'destination_school' => 'New School',
            'transfer_certificate_number' => 'TC-001',
        ]);

        $history = StudentStatusHistory::firstOrFail();
        $this->assertSame(Student::STATUS_TRANSFERRED_OUT, $student->fresh()->status);
        $this->assertSame('New School', $history->destination_school);
        $this->assertSame('TC-001', $history->transfer_certificate_number);
        $this->assertSame(StudentEnrollment::STATUS_TRANSFERRED_OUT, $enrollment->fresh()->status);
    }

    public function test_active_student_can_be_graduated(): void
    {
        [$actor, $student, $enrollment] = $this->activeStudentFixture();

        $this->changeStatus($actor, $student, Student::STATUS_GRADUATED);

        $this->assertSame(Student::STATUS_GRADUATED, $student->fresh()->status);
        $this->assertSame('2026-06-19', $student->fresh()->graduation_date->toDateString());
        $this->assertSame(StudentEnrollment::STATUS_GRADUATED, $enrollment->fresh()->status);
    }

    public function test_active_student_can_be_suspended_without_closing_enrolment(): void
    {
        [$actor, $student, $enrollment] = $this->activeStudentFixture();

        $this->changeStatus($actor, $student, Student::STATUS_SUSPENDED);

        $this->assertSame(Student::STATUS_SUSPENDED, $student->fresh()->status);
        $this->assertTrue($enrollment->fresh()->is_current);
        $this->assertNull($enrollment->fresh()->end_date);
    }

    public function test_suspended_student_can_be_reactivated_without_duplicate_enrolment(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $this->changeStatus($actor, $student, Student::STATUS_SUSPENDED);

        app(ReactivateStudent::class)->execute($actor, $student, $this->lifecyclePayload());

        $this->assertSame(Student::STATUS_ACTIVE, $student->fresh()->status);
        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->where('is_current', true)->count());
        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->count());
    }

    public function test_left_student_reactivation_creates_one_new_current_enrolment(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $newArm = $this->classArm('B');
        $this->changeStatus($actor, $student, Student::STATUS_LEFT);

        app(ReactivateStudent::class)->execute($actor, $student, $this->lifecyclePayload(['class_arm_id' => $newArm->id]));

        $this->assertSame(Student::STATUS_ACTIVE, $student->fresh()->status);
        $this->assertSame($newArm->id, $student->fresh()->current_class_arm_id);
        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->where('is_current', true)->count());
        $this->assertSame(2, StudentEnrollment::where('student_id', $student->id)->count());
    }

    public function test_withdrawn_student_reactivation_creates_one_new_current_enrolment(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $newArm = $this->classArm('B');
        $this->changeStatus($actor, $student, Student::STATUS_WITHDRAWN);

        app(ReactivateStudent::class)->execute($actor, $student, $this->lifecyclePayload(['class_arm_id' => $newArm->id]));

        $this->assertSame(Student::STATUS_ACTIVE, $student->fresh()->status);
        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->where('is_current', true)->count());
        $this->assertSame(2, StudentEnrollment::where('student_id', $student->id)->count());
    }

    public function test_transferred_out_student_requires_readmission_action(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $this->changeStatus($actor, $student, Student::STATUS_TRANSFERRED_OUT, ['destination_school' => 'New School']);

        $this->expectException(ValidationException::class);
        app(ReactivateStudent::class)->execute($actor, $student, $this->lifecyclePayload(['class_arm_id' => $this->classArm('B')->id]));
    }

    public function test_readmission_creates_one_current_enrolment(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $newArm = $this->classArm('B');
        $this->changeStatus($actor, $student, Student::STATUS_TRANSFERRED_OUT, ['destination_school' => 'New School']);

        app(ReadmitStudent::class)->execute($actor, $student, $this->lifecyclePayload(['class_arm_id' => $newArm->id]));

        $this->assertSame(Student::STATUS_ACTIVE, $student->fresh()->status);
        $this->assertSame($newArm->id, $student->fresh()->current_class_arm_id);
        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->where('is_current', true)->count());
    }

    public function test_graduation_correction_requires_restricted_permission(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $this->changeStatus($actor, $student, Student::STATUS_GRADUATED);
        $limitedActor = $this->userWithPermissions(['student.status.view'], 1);
        $this->actingAs($limitedActor);

        $this->expectException(ValidationException::class);
        app(CorrectGraduatedStudentStatus::class)->execute($limitedActor, $student, $this->lifecyclePayload(['class_arm_id' => $this->classArm('B')->id]));
    }

    public function test_graduation_correction_creates_one_current_enrolment(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $newArm = $this->classArm('B');
        $this->changeStatus($actor, $student, Student::STATUS_GRADUATED);

        app(CorrectGraduatedStudentStatus::class)->execute($actor, $student, $this->lifecyclePayload(['class_arm_id' => $newArm->id]));

        $this->assertSame(Student::STATUS_ACTIVE, $student->fresh()->status);
        $this->assertNull($student->fresh()->graduation_date);
        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->where('is_current', true)->count());
    }

    public function test_arbitrary_transition_is_rejected(): void
    {
        [$actor, $student] = $this->activeStudentFixture();

        $this->expectException(ValidationException::class);
        $this->changeStatus($actor, $student, Student::STATUS_APPLICANT);
    }

    public function test_missing_reason_is_rejected(): void
    {
        [$actor, $student] = $this->activeStudentFixture();

        $this->expectException(ValidationException::class);
        app(ChangeStudentStatus::class)->execute($actor, $student, [
            'new_status' => Student::STATUS_LEFT,
            'effective_date' => '2026-06-19',
        ]);
    }

    public function test_ambiguous_active_session_is_rejected(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $this->changeStatus($actor, $student, Student::STATUS_LEFT);
        DB::table('academic_sessions')->insert([
            'tenant_id' => 1,
            'name' => 'Duplicate Current',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        app(ReactivateStudent::class)->execute($actor, $student, $this->lifecyclePayload(['class_arm_id' => $this->classArm('B')->id]));
    }

    public function test_cross_tenant_status_change_is_denied(): void
    {
        [$actor] = $this->activeStudentFixture();
        $otherArm = $this->classArm('Other', 2);
        $otherStudent = $this->studentWithEnrollment($otherArm, Student::STATUS_ACTIVE, 2)[0];

        $this->expectException(ModelNotFoundException::class);
        $this->changeStatus($actor, $otherStudent, Student::STATUS_LEFT);
    }

    public function test_unauthorised_user_is_denied(): void
    {
        [, $student] = $this->activeStudentFixture();
        $actor = $this->userWithPermissions([], 1);
        $this->actingAs($actor);

        $this->expectException(ValidationException::class);
        $this->changeStatus($actor, $student, Student::STATUS_LEFT);
    }

    public function test_archive_excludes_suspended_students(): void
    {
        [$actor, $leftStudent] = $this->activeStudentFixture();
        $suspendedStudent = $this->studentWithEnrollment($this->classArm('B'), Student::STATUS_ACTIVE)[0];
        $this->changeStatus($actor, $leftStudent, Student::STATUS_LEFT);
        $this->changeStatus($actor, $suspendedStudent, Student::STATUS_SUSPENDED);

        $archiveIds = Student::whereIn('status', Student::ARCHIVE_STATUSES)->pluck('id')->all();

        $this->assertContains($leftStudent->id, $archiveIds);
        $this->assertNotContains($suspendedStudent->id, $archiveIds);
    }

    public function test_only_active_and_suspended_students_are_billing_eligible(): void
    {
        $this->activeStudentFixture();
        $arm = $this->classArm('Billing');

        $active = $this->studentWithEnrollment($arm, Student::STATUS_ACTIVE)[0];
        $suspended = $this->studentWithEnrollment($arm, Student::STATUS_SUSPENDED)[0];
        $left = $this->studentWithEnrollment($arm, Student::STATUS_LEFT)[0];
        $withdrawn = $this->studentWithEnrollment($arm, Student::STATUS_WITHDRAWN)[0];
        $transferredOut = $this->studentWithEnrollment($arm, Student::STATUS_TRANSFERRED_OUT)[0];
        $graduated = $this->studentWithEnrollment($arm, Student::STATUS_GRADUATED)[0];

        $billingIds = Student::billingEligible()->pluck('id')->all();

        $this->assertContains($active->id, $billingIds);
        $this->assertContains($suspended->id, $billingIds);
        $this->assertNotContains($left->id, $billingIds);
        $this->assertNotContains($withdrawn->id, $billingIds);
        $this->assertNotContains($transferredOut->id, $billingIds);
        $this->assertNotContains($graduated->id, $billingIds);
    }

    public function test_suspended_student_remains_excluded_from_attendance_entry_scope(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $this->changeStatus($actor, $student, Student::STATUS_SUSPENDED);

        $this->assertFalse(Student::active()->whereKey($student->id)->exists());
        $this->assertTrue(Student::billingEligible()->whereKey($student->id)->exists());
    }

    public function test_suspended_student_remains_excluded_from_cbt_eligibility(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $this->changeStatus($actor, $student, Student::STATUS_SUSPENDED);

        $exam = new CbtExam([
            'tenant_id' => $student->tenant_id,
            'class_arm_id' => $student->current_class_arm_id,
        ]);

        $method = new ReflectionMethod(CbtController::class, 'studentCanTakeExam');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(app(CbtController::class), $student->fresh(), $exam));
    }

    public function test_archive_export_is_tenant_scoped(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $this->changeStatus($actor, $student, Student::STATUS_LEFT);
        $otherArm = $this->classArm('Other', 2);
        $otherStudent = $this->studentWithEnrollment($otherArm, Student::STATUS_ACTIVE, 2)[0];
        $otherActor = $this->userWithPermissions(self::ALL_PERMISSIONS, 2);
        $this->actingAs($otherActor);
        $this->changeStatus($otherActor, $otherStudent, Student::STATUS_LEFT);
        $this->actingAs($actor);

        app(StudentArchiveController::class)->export(Request::create('/students/archive/export', 'GET'));

        $this->assertSame(1, AuditLog::where('action', 'student.archive.exported')->latest()->first()->new_values['count']);
    }

    public function test_historical_invoice_remains_after_exit(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        DB::table('invoices')->insert(['tenant_id' => 1, 'student_id' => $student->id, 'status' => 'unpaid', 'created_at' => now(), 'updated_at' => now()]);

        $this->changeStatus($actor, $student, Student::STATUS_LEFT);

        $this->assertSame(1, DB::table('invoices')->where('student_id', $student->id)->count());
    }

    public function test_historical_payment_remains_after_exit(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $invoiceId = DB::table('invoices')->insertGetId(['tenant_id' => 1, 'student_id' => $student->id, 'status' => 'paid', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('payment_transactions')->insert([
            'tenant_id' => 1,
            'invoice_id' => $invoiceId,
            'student_id' => $student->id,
            'amount_paid' => 5000,
            'status' => 'success',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->changeStatus($actor, $student, Student::STATUS_LEFT);

        $this->assertSame(1, DB::table('payment_transactions')->where('student_id', $student->id)->count());
    }

    public function test_historical_result_remains_after_exit(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        DB::table('scores')->insert(['tenant_id' => 1, 'student_id' => $student->id, 'score' => 75, 'created_at' => now(), 'updated_at' => now()]);

        $this->changeStatus($actor, $student, Student::STATUS_LEFT);

        $this->assertSame(1, DB::table('scores')->where('student_id', $student->id)->count());
    }

    public function test_historical_cbt_session_remains_unchanged(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        DB::table('cbt_student_sessions')->insert(['tenant_id' => 1, 'student_id' => $student->id, 'status' => 'submitted', 'created_at' => now(), 'updated_at' => now()]);

        $this->changeStatus($actor, $student, Student::STATUS_LEFT);

        $this->assertSame('submitted', DB::table('cbt_student_sessions')->where('student_id', $student->id)->value('status'));
    }

    public function test_parent_with_another_active_child_remains_functional(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $otherChild = $this->studentWithEnrollment($this->classArm('B'), Student::STATUS_ACTIVE)[0];
        $guardianId = DB::table('guardians')->insertGetId(['tenant_id' => 1, 'name' => 'Parent', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('guardian_student')->insert([
            ['guardian_id' => $guardianId, 'student_id' => $student->id, 'is_primary_contact' => true, 'created_at' => now(), 'updated_at' => now()],
            ['guardian_id' => $guardianId, 'student_id' => $otherChild->id, 'is_primary_contact' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->changeStatus($actor, $student, Student::STATUS_LEFT);

        $this->assertSame(Student::STATUS_ACTIVE, $otherChild->fresh()->status);
        $this->assertSame(2, DB::table('guardian_student')->where('guardian_id', $guardianId)->count());
    }

    public function test_lifecycle_history_is_written(): void
    {
        [$actor, $student] = $this->activeStudentFixture();

        $this->changeStatus($actor, $student, Student::STATUS_LEFT);

        $this->assertDatabaseHas('student_status_histories', [
            'student_id' => $student->id,
            'old_status' => Student::STATUS_ACTIVE,
            'new_status' => Student::STATUS_LEFT,
            'changed_by' => $actor->id,
            'approved_by' => $actor->id,
        ]);
    }

    public function test_audit_log_is_written(): void
    {
        [$actor, $student] = $this->activeStudentFixture();

        $this->changeStatus($actor, $student, Student::STATUS_LEFT);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => 1,
            'actor_user_id' => $actor->id,
            'auditable_type' => Student::class,
            'auditable_id' => $student->id,
            'action' => 'student.left',
        ]);
    }

    public function test_duplicate_submission_does_not_create_duplicate_current_enrolments(): void
    {
        [$actor, $student] = $this->activeStudentFixture();
        $newArm = $this->classArm('B');
        $this->changeStatus($actor, $student, Student::STATUS_LEFT);
        app(ReactivateStudent::class)->execute($actor, $student, $this->lifecyclePayload(['class_arm_id' => $newArm->id]));

        try {
            app(ReactivateStudent::class)->execute($actor, $student, $this->lifecyclePayload(['class_arm_id' => $newArm->id]));
        } catch (ValidationException) {
            // Expected because the student is already active.
        }

        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->where('is_current', true)->count());
    }

    private function activeStudentFixture(): array
    {
        $actor = $this->userWithPermissions(self::ALL_PERMISSIONS, 1);
        $this->actingAs($actor);
        $this->academicContext(1);
        $arm = $this->classArm('A', 1);

        return [$actor, ...$this->studentWithEnrollment($arm)];
    }

    private function changeStatus(User $actor, Student $student, string $status, array $extra = []): Student
    {
        return app(ChangeStudentStatus::class)->execute($actor, $student, $this->lifecyclePayload(array_merge([
            'new_status' => $status,
        ], $extra)));
    }

    private function lifecyclePayload(array $overrides = []): array
    {
        return array_merge([
            'new_status' => Student::STATUS_LEFT,
            'effective_date' => '2026-06-19',
            'reason' => 'Lifecycle test reason',
        ], $overrides);
    }

    private function studentWithEnrollment(ClassArm $arm, string $status = Student::STATUS_ACTIVE, int $tenantId = 1): array
    {
        $student = Student::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'Student ' . uniqid(),
            'admission_number' => 'ADM' . random_int(1000, 9999),
            'current_class_arm_id' => $arm->id,
            'status' => $status,
            'admission_date' => '2026-01-01',
        ]);

        $enrollment = StudentEnrollment::create([
            'tenant_id' => $tenantId,
            'student_id' => $student->id,
            'class_arm_id' => $arm->id,
            'session_id' => DB::table('academic_sessions')->where('tenant_id', $tenantId)->where('is_current', true)->value('id'),
            'term_id' => DB::table('terms')->where('tenant_id', $tenantId)->where('is_current', true)->value('id'),
            'start_date' => '2026-01-01',
            'is_current' => true,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

        return [$student, $enrollment];
    }

    private function classArm(string $name, int $tenantId = 1): ClassArm
    {
        if (!DB::table('academic_sessions')->where('tenant_id', $tenantId)->where('is_current', true)->exists()) {
            $this->academicContext($tenantId);
        }

        $levelId = DB::table('class_levels')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Level ' . $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ClassArm::create([
            'tenant_id' => $tenantId,
            'class_level_id' => $levelId,
            'name' => $name,
        ]);
    }

    private function academicContext(int $tenantId): void
    {
        if (!DB::table('tenants')->where('id', $tenantId)->exists()) {
            DB::table('tenants')->insert([
                'id' => $tenantId,
                'name' => 'Tenant ' . $tenantId,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('academic_sessions')->where('tenant_id', $tenantId)->exists()) {
            return;
        }

        $sessionId = DB::table('academic_sessions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => '2026/2027',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('terms')->insert([
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
            'name' => 'First Term',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function userWithPermissions(array $permissions, int $tenantId): User
    {
        $this->academicContext($tenantId);

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => 'Lifecycle User ' . uniqid(),
            'email' => uniqid('user') . '@example.test',
            'password' => 'secret',
            'role' => 'admin',
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        foreach (array_unique($permissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        if ($permissions) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'model_has_permissions',
            'role_has_permissions',
            'model_has_roles',
            'permissions',
            'roles',
            'guardian_student',
            'guardians',
            'student_subject_selections',
            'class_level_subjects',
            'cbt_student_sessions',
            'scores',
            'payment_transactions',
            'invoices',
            'audit_logs',
            'student_status_histories',
            'student_enrollments',
            'students',
            'class_arms',
            'class_levels',
            'terms',
            'academic_sessions',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id');
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('class_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('class_arms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('form_tutor_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default(Student::STATUS_ACTIVE);
            $table->date('admission_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('status')->default(StudentEnrollment::STATUS_ACTIVE);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('ended_by')->nullable();
            $table->text('ended_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('student_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->date('effective_date');
            $table->text('reason');
            $table->string('destination_school')->nullable();
            $table->string('transfer_certificate_number')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('changed_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('status')->default('unpaid');
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('success');
            $table->timestamps();
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->integer('score')->nullable();
            $table->timestamps();
        });

        Schema::create('cbt_student_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('class_level_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_status')->default('compulsory');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('student_subject_selections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->string('selection_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guardian_id');
            $table->unsignedBigInteger('student_id');
            $table->boolean('is_primary_contact')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }
}
