<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\PushSubscription;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentSubjectSelection;
use App\Models\StudentTransfer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CrossSchoolStudentTransferService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CrossSchoolStudentTransferServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildSchema();
    }

    public function test_approval_preserves_source_history_and_creates_new_destination_identity(): void
    {
        [$source, $destination, $sourceAdmin, $receiver, $student, $portal] = $this->world();

        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'class_arm_id' => 11,
            'session_id' => 21,
            'term_id' => 31,
            'start_date' => '2026-09-01',
            'is_current' => true,
            'status' => StudentEnrollment::STATUS_ACTIVE,
            'created_by' => $sourceAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $selectionId = DB::table('student_subject_selections')->insertGetId([
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'class_level_id' => 5,
            'subject_id' => 7,
            'selection_type' => 'compulsory',
            'session_id' => 21,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $transportId = DB::table('transport_assignments')->insertGetId([
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'route_id' => 9,
            'pickup_stop' => 'Main Gate',
            'direction' => 'both',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $apiToken = ApiToken::issue($portal, 'test-device');
        PushSubscription::create([
            'tenant_id' => $source->id,
            'user_id' => $portal->id,
            'endpoint' => 'https://push.example.test/source-student',
            'p256dh_key' => 'key',
            'auth_key' => 'auth',
            'is_active' => true,
        ]);

        $service = app(CrossSchoolStudentTransferService::class);
        $this->actingAs($sourceAdmin);
        $transfer = $service->request($sourceAdmin, $student->id, $destination->id, 'Family relocation');

        $this->actingAs($receiver);
        $completed = $service->approve($receiver, $transfer->id);

        $sourceStudent = Student::withoutTenantScope()->findOrFail($student->id);
        $this->assertSame($source->id, (int) $sourceStudent->tenant_id, 'The source student must never be re-tenanted.');
        $this->assertSame(Student::STATUS_TRANSFERRED_OUT, $sourceStudent->status);
        $this->assertNull($sourceStudent->current_class_arm_id);

        $destinationStudent = Student::withoutTenantScope()->findOrFail($completed->destination_student_id);
        $this->assertNotSame($student->id, $destinationStudent->id);
        $this->assertSame($destination->id, (int) $destinationStudent->tenant_id);
        $this->assertSame(Student::STATUS_ACTIVE, $destinationStudent->status);
        $this->assertNull($destinationStudent->user_id);
        $this->assertNull($destinationStudent->current_class_arm_id);
        $this->assertSame('A/001', $destinationStudent->admission_number);

        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollmentId,
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'is_current' => false,
            'status' => StudentEnrollment::STATUS_TRANSFERRED_OUT,
        ]);
        $this->assertDatabaseHas('student_subject_selections', [
            'id' => $selectionId,
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('transport_assignments', ['id' => $transportId]);

        $this->assertDatabaseHas('users', [
            'id' => $portal->id,
            'tenant_id' => $source->id,
            'role' => 'student',
            'is_active' => false,
        ]);
        $this->assertNull(ApiToken::findValid($apiToken));
        $this->assertDatabaseHas('push_subscriptions', [
            'tenant_id' => $source->id,
            'user_id' => $portal->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('student_status_histories', [
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'old_status' => Student::STATUS_ACTIVE,
            'new_status' => Student::STATUS_TRANSFERRED_OUT,
            'destination_school' => $destination->name,
        ]);
        $this->assertDatabaseHas('student_transfers', [
            'id' => $transfer->id,
            'student_id' => $student->id,
            'destination_student_id' => $destinationStudent->id,
            'status' => StudentTransfer::STATUS_COMPLETED,
            'approved_by' => $receiver->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $destination->id,
            'action' => 'student.transfer.completed',
        ]);
    }

    public function test_destination_admission_number_collision_gets_safe_transfer_number(): void
    {
        [, $destination, $sourceAdmin, $receiver, $student] = $this->world();

        Student::withoutTenantScope()->create([
            'tenant_id' => $destination->id,
            'admission_number' => $student->admission_number,
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'status' => Student::STATUS_ACTIVE,
        ]);

        $service = app(CrossSchoolStudentTransferService::class);
        $this->actingAs($sourceAdmin);
        $transfer = $service->request($sourceAdmin, $student->id, $destination->id, null);
        $this->actingAs($receiver);
        $completed = $service->approve($receiver, $transfer->id);

        $destinationStudent = Student::withoutTenantScope()->findOrFail($completed->destination_student_id);
        $this->assertNotSame($student->admission_number, $destinationStudent->admission_number);
        $this->assertStringStartsWith('TRF-', $destinationStudent->admission_number);
    }

    public function test_stale_transfer_cannot_move_student_after_source_lifecycle_changes(): void
    {
        [, $destination, $sourceAdmin, $receiver, $student] = $this->world();
        $service = app(CrossSchoolStudentTransferService::class);

        $this->actingAs($sourceAdmin);
        $transfer = $service->request($sourceAdmin, $student->id, $destination->id, null);
        Student::withoutTenantScope()->whereKey($student->id)->update(['status' => Student::STATUS_WITHDRAWN]);

        $this->actingAs($receiver);
        try {
            $service->approve($receiver, $transfer->id);
            $this->fail('Expected stale transfer approval to be rejected.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('transfer', $error->errors());
        }

        $this->assertDatabaseHas('student_transfers', [
            'id' => $transfer->id,
            'status' => StudentTransfer::STATUS_PENDING,
            'destination_student_id' => null,
        ]);
        $this->assertDatabaseMissing('students', [
            'tenant_id' => $destination->id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
        ]);
    }

    public function test_only_receiving_school_can_approve_pending_transfer(): void
    {
        [, $destination, $sourceAdmin, , $student] = $this->world();
        $third = Tenant::create(['name' => 'Third School', 'slug' => 'third-school', 'status' => Tenant::STATUS_ACTIVE]);
        $thirdAdmin = $this->user($third, 'Third Admin', 'admin');
        $service = app(CrossSchoolStudentTransferService::class);

        $this->actingAs($sourceAdmin);
        $transfer = $service->request($sourceAdmin, $student->id, $destination->id, null);

        $this->actingAs($thirdAdmin);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->approve($thirdAdmin, $transfer->id);
    }

    public function test_duplicate_pending_request_is_rejected(): void
    {
        [, $destination, $sourceAdmin, , $student] = $this->world();
        $service = app(CrossSchoolStudentTransferService::class);
        $this->actingAs($sourceAdmin);

        $service->request($sourceAdmin, $student->id, $destination->id, null);

        $this->expectException(ValidationException::class);
        $service->request($sourceAdmin, $student->id, $destination->id, null);
    }

    private function world(): array
    {
        $source = Tenant::create(['name' => 'Source School', 'slug' => 'source-school', 'status' => Tenant::STATUS_ACTIVE]);
        $destination = Tenant::create(['name' => 'Destination School', 'slug' => 'destination-school', 'status' => Tenant::STATUS_ACTIVE]);
        $sourceAdmin = $this->user($source, 'Source Admin', 'admin');
        $receiver = $this->user($destination, 'Receiving Admin', 'admin');
        $portal = $this->user($source, 'Student Portal', 'student', true);

        $this->actingAs($sourceAdmin);
        $student = Student::create([
            'tenant_id' => $source->id,
            'user_id' => $portal->id,
            'admission_number' => 'A/001',
            'first_name' => 'Amina',
            'middle_name' => 'B.',
            'last_name' => 'Yusuf',
            'gender' => 'female',
            'date_of_birth' => '2010-01-02',
            'current_class_arm_id' => 11,
            'status' => Student::STATUS_ACTIVE,
            'admission_date' => '2024-09-01',
        ]);
        $portal->forceFill(['student_id' => $student->id])->save();

        return [$source, $destination, $sourceAdmin, $receiver, $student, $portal];
    }

    private function user(Tenant $tenant, string $name, string $role, bool $active = true): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => str($name)->slug('.').'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => $active,
            'is_super_admin' => false,
            'employment_status' => $role === 'student' ? null : User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'audit_logs', 'push_subscriptions', 'api_tokens', 'transport_assignments',
            'student_subject_selections', 'student_enrollments', 'student_status_histories',
            'student_transfers', 'students', 'users', 'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default(Tenant::STATUS_ACTIVE);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->string('employment_status')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('state_of_origin')->nullable();
            $table->string('lga_of_origin')->nullable();
            $table->string('religion')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('genotype')->nullable();
            $table->string('passport_photo_path')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default(Student::STATUS_ACTIVE);
            $table->date('admission_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'admission_number']);
        });
        Schema::create('student_transfers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('from_tenant_id');
            $table->unsignedBigInteger('to_tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('destination_student_id')->nullable();
            $table->string('student_name');
            $table->string('admission_number')->nullable();
            $table->string('status')->default(StudentTransfer::STATUS_PENDING);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
        Schema::create('student_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->string('destination_school')->nullable();
            $table->string('transfer_certificate_number')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('student_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id')->nullable();
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
        Schema::create('student_subject_selections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->string('selection_type');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('transport_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('route_id')->nullable();
            $table->string('pickup_stop')->nullable();
            $table->string('direction')->nullable();
            $table->timestamps();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('device')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('endpoint')->unique();
            $table->text('p256dh_key');
            $table->text('auth_key');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
