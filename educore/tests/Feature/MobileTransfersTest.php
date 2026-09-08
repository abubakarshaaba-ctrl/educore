<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\PushSubscription;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileTransfersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile transfer tests require sqlite :memory:.');
        }

        foreach ([
            'audit_logs',
            'push_subscriptions',
            'student_status_histories',
            'transport_assignments',
            'student_subject_selections',
            'student_enrollments',
            'student_transfers',
            'api_tokens',
            'students',
            'users',
            'tenants',
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
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_migration_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
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
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('ended_by')->nullable();
            $table->text('ended_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('student_subject_selections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('selection_type')->nullable();
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

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function test_approval_preserves_source_history_and_creates_fresh_destination_student(): void
    {
        [$source, $sourceAdmin] = $this->school('Source School');
        [$receiver, $receiverAdmin] = $this->school('Receiver School');

        Student::withoutTenantScope()->create([
            'tenant_id' => $receiver->id,
            'admission_number' => 'SRC-001',
            'first_name' => 'Existing',
            'last_name' => 'Learner',
            'status' => Student::STATUS_ACTIVE,
        ]);

        $portal = User::create([
            'tenant_id' => $source->id,
            'name' => 'Amina Portal',
            'email' => 'amina.portal@example.test',
            'role' => 'student',
            'is_active' => true,
        ]);
        $student = Student::withoutTenantScope()->create([
            'tenant_id' => $source->id,
            'user_id' => $portal->id,
            'admission_number' => 'SRC-001',
            'first_name' => 'Amina',
            'middle_name' => 'Zainab',
            'last_name' => 'Musa',
            'gender' => 'female',
            'date_of_birth' => '2012-04-03',
            'passport_photo_path' => 'students/source/amina.jpg',
            'current_class_arm_id' => 45,
            'status' => Student::STATUS_ACTIVE,
            'admission_date' => '2024-09-02',
        ]);
        $portal->update(['student_id' => $student->id]);
        ApiToken::issue($portal, 'student-phone');
        PushSubscription::create([
            'tenant_id' => $source->id,
            'user_id' => $portal->id,
            'endpoint' => 'https://push.example.test/amina',
            'p256dh_key' => 'p256dh',
            'auth_key' => 'auth',
            'is_active' => true,
        ]);

        DB::table('student_enrollments')->insert([
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'class_arm_id' => 45,
            'session_id' => 5,
            'term_id' => 8,
            'start_date' => '2026-09-01',
            'is_current' => true,
            'status' => 'active',
            'created_by' => $sourceAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('student_subject_selections')->insert([
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'subject_id' => 11,
            'selection_type' => 'compulsory',
            'session_id' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transport_assignments')->insert([
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'route_id' => 9,
            'pickup_stop' => 'Gate',
            'direction' => 'both',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sourceToken = ApiToken::issue($sourceAdmin, 'source-admin');
        $transferId = (int) $this->withToken($sourceToken)
            ->postJson('/api/v1/transfers/cross-school', [
                'student_id' => $student->id,
                'to_tenant_id' => $receiver->id,
                'reason' => 'Family relocation',
            ])
            ->assertCreated()
            ->json('transfer_id');

        $receiverToken = ApiToken::issue($receiverAdmin, 'receiver-admin');
        $response = $this->withToken($receiverToken)
            ->postJson('/api/v1/transfers/cross-school/'.$transferId.'/approve')
            ->assertOk()
            ->assertJsonPath('message', 'Incoming transfer approved. A fresh receiving-school student record has been created while source-school history remains archived.');

        $destinationStudentId = (int) $response->json('destination_student_id');
        $this->assertGreaterThan(0, $destinationStudentId);

        $this->assertDatabaseHas('student_transfers', [
            'id' => $transferId,
            'student_id' => $student->id,
            'destination_student_id' => $destinationStudentId,
            'from_tenant_id' => $source->id,
            'to_tenant_id' => $receiver->id,
            'status' => StudentTransfer::STATUS_COMPLETED,
            'approved_by' => $receiverAdmin->id,
        ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'tenant_id' => $source->id,
            'status' => Student::STATUS_TRANSFERRED_OUT,
            'current_class_arm_id' => null,
            'admission_number' => 'SRC-001',
        ]);

        $destination = Student::withoutTenantScope()->findOrFail($destinationStudentId);
        $this->assertSame((int) $receiver->id, (int) $destination->tenant_id);
        $this->assertSame(Student::STATUS_ACTIVE, $destination->status);
        $this->assertNull($destination->user_id);
        $this->assertNull($destination->current_class_arm_id);
        $this->assertNull($destination->passport_photo_path);
        $this->assertStringStartsWith('TRF-', (string) $destination->admission_number);
        $this->assertSame('Amina', $destination->first_name);
        $this->assertSame('Musa', $destination->last_name);

        $this->assertDatabaseHas('student_enrollments', [
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'is_current' => false,
            'status' => 'transferred_out',
        ]);
        $this->assertDatabaseHas('student_subject_selections', [
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('transport_assignments', [
            'tenant_id' => $source->id,
            'student_id' => $student->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $portal->id,
            'tenant_id' => $source->id,
            'role' => 'student',
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $portal->id]);
        $this->assertDatabaseHas('push_subscriptions', [
            'tenant_id' => $source->id,
            'user_id' => $portal->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('student_status_histories', [
            'tenant_id' => $source->id,
            'student_id' => $student->id,
            'new_status' => Student::STATUS_TRANSFERRED_OUT,
            'destination_school' => $receiver->name,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $receiver->id,
            'actor_user_id' => $receiverAdmin->id,
            'auditable_id' => $transferId,
            'action' => 'student.transfer.completed',
        ]);
    }

    public function test_foreign_school_cannot_approve_or_reject_an_incoming_transfer(): void
    {
        [$source, $sourceAdmin] = $this->school('Foreign Boundary Source');
        [$receiver] = $this->school('Foreign Boundary Receiver');
        [$third, $thirdAdmin] = $this->school('Foreign Boundary Third');
        $student = $this->student($source, 'FBS-001');

        $transfer = StudentTransfer::create([
            'from_tenant_id' => $source->id,
            'to_tenant_id' => $receiver->id,
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'admission_number' => $student->admission_number,
            'status' => StudentTransfer::STATUS_PENDING,
            'requested_by' => $sourceAdmin->id,
        ]);

        $token = ApiToken::issue($thirdAdmin, 'third-school');
        $this->withToken($token)
            ->postJson('/api/v1/transfers/cross-school/'.$transfer->id.'/approve')
            ->assertForbidden();
        $this->withToken($token)
            ->postJson('/api/v1/transfers/cross-school/'.$transfer->id.'/reject')
            ->assertForbidden();

        $this->assertDatabaseHas('student_transfers', [
            'id' => $transfer->id,
            'status' => StudentTransfer::STATUS_PENDING,
            'destination_student_id' => null,
        ]);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'tenant_id' => $source->id,
            'status' => Student::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseMissing('students', [
            'tenant_id' => $third->id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
        ]);
    }

    public function test_stale_pending_transfer_cannot_create_a_destination_student(): void
    {
        [$source, $sourceAdmin] = $this->school('Stale Source');
        [$receiver, $receiverAdmin] = $this->school('Stale Receiver');
        $student = $this->student($source, 'STALE-001');
        $transfer = StudentTransfer::create([
            'from_tenant_id' => $source->id,
            'to_tenant_id' => $receiver->id,
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'admission_number' => $student->admission_number,
            'status' => StudentTransfer::STATUS_PENDING,
            'requested_by' => $sourceAdmin->id,
        ]);

        Student::withoutTenantScope()->whereKey($student->id)->update(['status' => Student::STATUS_LEFT]);
        $receiverCount = Student::withoutTenantScope()->where('tenant_id', $receiver->id)->count();

        $token = ApiToken::issue($receiverAdmin, 'stale-receiver');
        $this->withToken($token)
            ->postJson('/api/v1/transfers/cross-school/'.$transfer->id.'/approve')
            ->assertUnprocessable();

        $this->assertSame(
            $receiverCount,
            Student::withoutTenantScope()->where('tenant_id', $receiver->id)->count(),
        );
        $this->assertDatabaseHas('student_transfers', [
            'id' => $transfer->id,
            'status' => StudentTransfer::STATUS_PENDING,
            'destination_student_id' => null,
        ]);
    }

    public function test_duplicate_pending_request_is_rejected(): void
    {
        [$source, $sourceAdmin] = $this->school('Duplicate Source');
        [$receiver] = $this->school('Duplicate Receiver');
        $student = $this->student($source, 'DUP-001');
        $token = ApiToken::issue($sourceAdmin, 'duplicate-request');

        $payload = [
            'student_id' => $student->id,
            'to_tenant_id' => $receiver->id,
            'reason' => 'Relocation',
        ];

        $this->withToken($token)
            ->postJson('/api/v1/transfers/cross-school', $payload)
            ->assertCreated();
        $this->withToken($token)
            ->postJson('/api/v1/transfers/cross-school', $payload)
            ->assertUnprocessable();

        $this->assertSame(1, StudentTransfer::where('student_id', $student->id)
            ->where('status', StudentTransfer::STATUS_PENDING)->count());
    }

    public function test_rejection_does_not_mutate_source_student(): void
    {
        [$source, $sourceAdmin] = $this->school('Reject Source');
        [$receiver, $receiverAdmin] = $this->school('Reject Receiver');
        $student = $this->student($source, 'REJ-001');
        $transfer = StudentTransfer::create([
            'from_tenant_id' => $source->id,
            'to_tenant_id' => $receiver->id,
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'admission_number' => $student->admission_number,
            'status' => StudentTransfer::STATUS_PENDING,
            'requested_by' => $sourceAdmin->id,
        ]);

        $token = ApiToken::issue($receiverAdmin, 'reject-receiver');
        $this->withToken($token)
            ->postJson('/api/v1/transfers/cross-school/'.$transfer->id.'/reject')
            ->assertOk();

        $this->assertDatabaseHas('student_transfers', [
            'id' => $transfer->id,
            'status' => StudentTransfer::STATUS_REJECTED,
            'rejected_by' => $receiverAdmin->id,
        ]);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'tenant_id' => $source->id,
            'status' => Student::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseMissing('students', [
            'tenant_id' => $receiver->id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
        ]);
    }

    public function test_role_without_transfer_module_is_forbidden(): void
    {
        [$source] = $this->school('Denied Source');
        [$receiver] = $this->school('Denied Receiver');
        $teacher = User::create([
            'tenant_id' => $source->id,
            'name' => 'Subject Teacher',
            'email' => 'denied.teacher@example.test',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $student = $this->student($source, 'DEN-001');
        $token = ApiToken::issue($teacher, 'denied-transfer');

        $this->withToken($token)
            ->postJson('/api/v1/transfers/cross-school', [
                'student_id' => $student->id,
                'to_tenant_id' => $receiver->id,
            ])
            ->assertForbidden();
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' Admin',
            'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $admin];
    }

    private function student(Tenant $tenant, string $admissionNumber): Student
    {
        return Student::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'admission_number' => $admissionNumber,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'current_class_arm_id' => 10,
            'status' => Student::STATUS_ACTIVE,
            'admission_date' => '2025-09-01',
        ]);
    }
}
