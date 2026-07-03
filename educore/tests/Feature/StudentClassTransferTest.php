<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Models\ClassArm;
use App\Models\Student;
use App\Models\StudentClassTransfer;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use App\Services\AcademicCycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class StudentClassTransferTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    private const TRANSFER_PERMISSIONS = [
        'student.transfer.view',
        'student.transfer.request',
        'student.transfer.approve',
        'student.transfer.reject',
        'student.transfer.cancel',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();
    }

    public function test_authorised_user_can_request_transfer_without_changing_enrolment(): void
    {
        $world = $this->transferWorld();

        $response = $this->actingAs($world['actor'])->post(route('students.class-transfers.store'), [
            'student_id' => $world['student']->id,
            'to_class_arm_id' => $world['to']->id,
            'effective_date' => '2026-02-01',
            'reason' => 'Move to another arm.',
        ]);

        $transfer = StudentClassTransfer::withoutTenantScope()->firstOrFail();

        $response->assertRedirect(route('students.class-transfers.show', $transfer));
        $this->assertSame(StudentClassTransfer::STATUS_PENDING, $transfer->status);
        $this->assertSame($world['from']->id, $transfer->from_class_arm_id);
        $this->assertSame($world['to']->id, $transfer->to_class_arm_id);
        $this->assertSame($world['from']->id, $world['student']->fresh()->current_class_arm_id);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $world['student']->id)->where('is_current', true)->count());
        $this->assertSame(1, AuditLog::where('action', 'student_class_transfer.requested')->count());
    }

    public function test_request_is_tenant_scoped_and_cross_tenant_destination_is_rejected(): void
    {
        $world = $this->transferWorld();
        $otherTenant = $this->tenantFixture(['slug' => 'other-school']);
        $foreignArm = $this->classArmFixture($otherTenant, 'Primary 2', 2);

        $this->actingAs($world['actor'])->post(route('students.class-transfers.store'), [
            'student_id' => $world['student']->id,
            'to_class_arm_id' => $foreignArm->id,
            'effective_date' => '2026-02-01',
            'reason' => 'Invalid destination.',
        ])->assertSessionHasErrors('to_class_arm_id');

        $this->assertSame(0, StudentClassTransfer::withoutTenantScope()->count());
        $this->assertSame($world['from']->id, $world['student']->fresh()->current_class_arm_id);
    }

    public function test_approval_completes_transfer_and_preserves_historical_records(): void
    {
        $world = $this->transferWorld();
        $transfer = $this->pendingTransfer($world);
        $this->createHistoricalRecords($world);

        $this->actingAs($world['actor'])->post(route('students.class-transfers.approve', $transfer))
            ->assertRedirect(route('students.class-transfers.show', $transfer));

        $transfer->refresh();
        $student = $world['student']->fresh();

        $this->assertSame(StudentClassTransfer::STATUS_COMPLETED, $transfer->status);
        $this->assertSame($world['to']->id, $student->current_class_arm_id);

        $source = $world['enrollment']->fresh();
        $this->assertFalse((bool) $source->is_current);
        $this->assertSame(StudentEnrollment::STATUS_TRANSFERRED, $source->status);
        $this->assertSame('Interclass transfer #' . $transfer->id, $source->ended_reason);

        $this->assertSame(1, StudentEnrollment::withoutTenantScope()
            ->where('student_id', $student->id)
            ->where('class_arm_id', $world['to']->id)
            ->where('is_current', true)
            ->count());

        $this->assertSame(2, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->count());
        $this->assertSame(1, DB::table('scores')->where('student_id', $student->id)->where('class_arm_id', $world['from']->id)->count());
        $this->assertSame(1, DB::table('attendance_records')->where('student_id', $student->id)->where('class_arm_id', $world['from']->id)->count());
        $this->assertSame(1, DB::table('invoices')->where('student_id', $student->id)->count());
        $this->assertSame(1, DB::table('payment_transactions')->where('student_id', $student->id)->count());
        $this->assertSame(1, DB::table('cbt_student_sessions')->where('student_id', $student->id)->count());
        $this->assertSame(1, AuditLog::where('action', 'student_class_transfer.completed')->count());
    }

    public function test_duplicate_current_enrolment_blocks_completion(): void
    {
        $world = $this->transferWorld();
        $transfer = $this->pendingTransfer($world);
        $this->enrollmentFixture($world['tenant'], $world['student'], $world['from'], $world['session'], $world['term'], true);

        $this->actingAs($world['actor'])->post(route('students.class-transfers.approve', $transfer))
            ->assertSessionHasErrors('student_id');

        $this->assertSame(StudentClassTransfer::STATUS_PENDING, $transfer->fresh()->status);
        $this->assertSame(2, StudentEnrollment::withoutTenantScope()->where('student_id', $world['student']->id)->where('is_current', true)->count());
    }

    public function test_rejection_and_cancellation_do_not_change_enrolment(): void
    {
        $world = $this->transferWorld();
        $rejected = $this->pendingTransfer($world);

        $this->actingAs($world['actor'])->post(route('students.class-transfers.reject', $rejected), [
            'rejection_reason' => 'Not approved.',
        ])->assertRedirect(route('students.class-transfers.show', $rejected));

        $this->assertSame(StudentClassTransfer::STATUS_REJECTED, $rejected->fresh()->status);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $world['student']->id)->where('is_current', true)->count());
        $this->assertSame($world['from']->id, $world['student']->fresh()->current_class_arm_id);

        $cancelled = $this->pendingTransfer($world, ['reason' => 'Second request.']);

        $this->actingAs($world['actor'])->post(route('students.class-transfers.cancel', $cancelled), [
            'cancellation_reason' => 'No longer needed.',
        ])->assertRedirect(route('students.class-transfers.show', $cancelled));

        $this->assertSame(StudentClassTransfer::STATUS_CANCELLED, $cancelled->fresh()->status);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $world['student']->id)->where('is_current', true)->count());
        $this->assertSame(1, AuditLog::where('action', 'student_class_transfer.rejected')->count());
        $this->assertSame(1, AuditLog::where('action', 'student_class_transfer.cancelled')->count());
    }

    public function test_duplicate_completion_and_unauthorised_approval_are_prevented(): void
    {
        $world = $this->transferWorld();
        $transfer = $this->pendingTransfer($world);
        $unauthorised = $this->staff($world['tenant'], []);

        $this->actingAs($unauthorised)->post(route('students.class-transfers.approve', $transfer))
            ->assertForbidden();

        $this->actingAs($world['actor'])->post(route('students.class-transfers.approve', $transfer))
            ->assertRedirect(route('students.class-transfers.show', $transfer));
        $this->actingAs($world['actor'])->post(route('students.class-transfers.approve', $transfer))
            ->assertSessionHasErrors('transfer');

        $this->assertSame(StudentClassTransfer::STATUS_COMPLETED, $transfer->fresh()->status);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()
            ->where('student_id', $world['student']->id)
            ->where('class_arm_id', $world['to']->id)
            ->where('is_current', true)
            ->count());
    }

    public function test_rollover_does_not_duplicate_completed_transfer_history(): void
    {
        $world = $this->transferWorld();
        $transfer = $this->pendingTransfer($world);

        $this->actingAs($world['actor'])->post(route('students.class-transfers.approve', $transfer));

        $targetSession = $this->sessionFixture($world['tenant'], '2026/2027');
        $this->termFixture($world['tenant'], $targetSession, 'First Term');
        $nextArm = $this->classArmFixture($world['tenant'], 'Primary 3', 3, 'B');
        $this->summaryFixture($world['tenant'], $world['student'], $world['to'], $world['term'], 'promoted');

        $result = app(AcademicCycleService::class)->commitRollover(
            $world['tenant']->id,
            $world['session']->id,
            $targetSession->id,
            $world['actor']
        );

        $this->assertSame(1, StudentClassTransfer::withoutTenantScope()->whereKey($transfer->id)->where('status', StudentClassTransfer::STATUS_COMPLETED)->count());
        $this->assertSame($nextArm->id, $world['student']->fresh()->current_class_arm_id);
        $this->assertGreaterThanOrEqual(1, $result->counts['promoted']);
    }

    private function transferWorld(): array
    {
        $tenant = $this->tenantFixture();
        $actor = $this->staff($tenant, self::TRANSFER_PERMISSIONS);
        $session = $this->sessionFixture($tenant, '2025/2026', true);
        $term = $this->termFixture($tenant, $session, 'Second Term', true);
        $from = $this->classArmFixture($tenant, 'Primary 1', 1, 'A');
        $to = $this->classArmFixture($tenant, 'Primary 2', 2, 'B');
        $student = $this->studentFixture($tenant, $from);
        $enrollment = $this->enrollmentFixture($tenant, $student, $from, $session, $term, true);

        Subject::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Mathematics',
            'code' => 'MTH',
            'is_active' => true,
        ]);

        return compact('tenant', 'actor', 'session', 'term', 'from', 'to', 'student', 'enrollment');
    }

    private function pendingTransfer(array $world, array $overrides = []): StudentClassTransfer
    {
        return StudentClassTransfer::withoutTenantScope()->create(array_merge([
            'tenant_id' => $world['tenant']->id,
            'student_id' => $world['student']->id,
            'academic_session_id' => $world['session']->id,
            'term_id' => $world['term']->id,
            'from_class_arm_id' => $world['from']->id,
            'to_class_arm_id' => $world['to']->id,
            'effective_date' => '2026-02-01',
            'reason' => 'Move to another arm.',
            'status' => StudentClassTransfer::STATUS_PENDING,
            'requested_by' => $world['actor']->id,
        ], $overrides));
    }

    private function createHistoricalRecords(array $world): void
    {
        DB::table('scores')->insert([
            'tenant_id' => $world['tenant']->id,
            'student_id' => $world['student']->id,
            'subject_id' => null,
            'assessment_type_id' => null,
            'term_id' => $world['term']->id,
            'session_id' => $world['session']->id,
            'class_arm_id' => $world['from']->id,
            'score' => 82,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attendance_records')->insert([
            'tenant_id' => $world['tenant']->id,
            'student_id' => $world['student']->id,
            'class_arm_id' => $world['from']->id,
            'term_id' => $world['term']->id,
            'attendance_date' => '2026-01-15',
            'status' => 'present',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'tenant_id' => $world['tenant']->id,
            'student_id' => $world['student']->id,
            'academic_session_id' => $world['session']->id,
            'term_id' => $world['term']->id,
            'invoice_number' => 'INV-001',
            'amount' => 1000,
            'amount_paid' => 500,
            'status' => 'partially_paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payment_transactions')->insert([
            'tenant_id' => $world['tenant']->id,
            'invoice_id' => $invoiceId,
            'student_id' => $world['student']->id,
            'amount_paid' => 500,
            'status' => 'successful',
            'transaction_reference' => 'PAY-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cbt_exams')->insert([
            'id' => 1,
            'tenant_id' => $world['tenant']->id,
            'term_id' => $world['term']->id,
            'class_arm_id' => $world['from']->id,
            'title' => 'Mid Term CBT',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cbt_student_sessions')->insert([
            'tenant_id' => $world['tenant']->id,
            'cbt_exam_id' => 1,
            'student_id' => $world['student']->id,
            'question_order' => json_encode([1]),
            'answers' => json_encode(['1' => 'A']),
            'status' => 'submitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function staff(Tenant $tenant, array $permissions): User
    {
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'School Admin',
            'email' => 'staff' . uniqid() . '@school.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'is_super_admin' => false,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        foreach (array_unique($permissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
