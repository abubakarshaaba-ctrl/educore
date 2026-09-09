<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformBillingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform billing tests require sqlite :memory:.');
        }

        foreach (['audit_logs', 'platform_payments', 'platform_invoices', 'students', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('status')->default(Tenant::STATUS_ACTIVE);
            $table->timestamp('subscription_expires_at')->nullable(); $table->unsignedInteger('students_capacity')->nullable();
            $table->unsignedBigInteger('referred_by_agent_id')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->string('name'); $table->string('email')->nullable()->unique();
            $table->string('password')->nullable(); $table->string('role')->nullable(); $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true); $table->string('employment_status')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name'); $table->string('token', 64)->unique();
            $table->string('device')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->string('admission_number')->nullable();
            $table->string('first_name'); $table->string('last_name'); $table->string('status')->default(Student::STATUS_ACTIVE);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('platform_invoices', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('plan_id')->nullable(); $table->string('invoice_number')->unique();
            $table->decimal('amount', 12, 2); $table->unsignedInteger('student_count')->nullable(); $table->string('billing_cycle');
            $table->string('status')->default('pending'); $table->date('due_date'); $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable(); $table->string('payment_method')->nullable(); $table->string('payment_ref')->nullable();
            $table->string('payment_reference')->nullable(); $table->timestamps();
        });
        Schema::create('platform_payments', function (Blueprint $table): void {
            $table->id(); $table->string('reference')->unique(); $table->unsignedBigInteger('tenant_id'); $table->decimal('amount', 12, 2);
            $table->string('currency')->default('NGN'); $table->string('status'); $table->string('payment_method')->nullable();
            $table->text('description')->nullable(); $table->unsignedBigInteger('confirmed_by')->nullable(); $table->timestamp('paid_at')->nullable(); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->string('action');
            $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->text('reason')->nullable();
            $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->timestamps();
        });
    }

    public function test_non_super_admin_cannot_manage_platform_invoices(): void
    {
        $tenant = $this->tenant('Restricted Billing School');
        $admin = $this->user('Restricted Billing Admin', false, $tenant->id);
        $this->withToken(ApiToken::issue($admin, 'billing-deny'))->getJson('/api/v1/platform/billing/invoices')->assertForbidden();
    }

    public function test_paid_plan_invoice_can_be_generated_and_is_audited(): void
    {
        $tenant = $this->tenant('Invoice School');
        $this->students($tenant->id, 60);
        $super = $this->user('Billing Operator', true, null);

        $response = $this->withToken(ApiToken::issue($super, 'billing-generate'))->postJson('/api/v1/platform/billing/invoices', [
            'tenant_id' => $tenant->id,
            'billing_cycle' => 'termly',
            'capacity' => 80,
            'due_date' => now()->addDays(14)->toDateString(),
            'notes' => 'Approved term invoice',
        ])->assertCreated()->assertJsonPath('invoice.student_count', 80)->assertJsonPath('invoice.status', 'pending');

        $this->assertGreaterThan(0, (float) $response->json('invoice.amount'));
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'actor_user_id' => $super->id, 'action' => 'platform.invoice.generated']);
    }

    public function test_free_plan_invoice_generation_is_rejected(): void
    {
        $tenant = $this->tenant('Free Invoice School');
        $this->students($tenant->id, 10);
        $super = $this->user('Free Billing Operator', true, null);

        $this->withToken(ApiToken::issue($super, 'billing-free'))->postJson('/api/v1/platform/billing/invoices', [
            'tenant_id' => $tenant->id,
            'billing_cycle' => 'termly',
            'capacity' => 20,
            'due_date' => now()->addDays(14)->toDateString(),
        ])->assertUnprocessable();
        $this->assertDatabaseCount('platform_invoices', 0);
    }

    public function test_invoice_settlement_is_idempotent_and_extends_subscription_and_capacity(): void
    {
        $tenant = $this->tenant('Settlement School', Tenant::STATUS_SUSPENDED, now()->addDays(10));
        $tenant->update(['students_capacity' => 60]);
        $super = $this->user('Settlement Operator', true, null);
        $invoiceId = DB::table('platform_invoices')->insertGetId([
            'tenant_id' => $tenant->id, 'invoice_number' => 'INV-SETTLE01', 'amount' => 50000,
            'student_count' => 100, 'billing_cycle' => 'annual', 'status' => 'pending', 'due_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $token = ApiToken::issue($super, 'billing-settle');
        $before = $tenant->fresh()->subscription_expires_at;

        $this->withToken($token)->postJson("/api/v1/platform/billing/invoices/{$invoiceId}/settle", [
            'payment_method' => 'bank_transfer', 'payment_ref' => 'BANK-SETTLE-001',
        ])->assertOk()->assertJsonPath('processed', true)->assertJsonPath('tenant.status', Tenant::STATUS_ACTIVE);

        $tenant->refresh();
        $this->assertSame(100, $tenant->students_capacity);
        $this->assertTrue($tenant->subscription_expires_at->greaterThan($before));
        $this->assertDatabaseCount('platform_payments', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'platform.invoice.settled', 'tenant_id' => $tenant->id]);

        $this->withToken($token)->postJson("/api/v1/platform/billing/invoices/{$invoiceId}/settle", [
            'payment_method' => 'bank_transfer', 'payment_ref' => 'BANK-SETTLE-002',
        ])->assertOk()->assertJsonPath('processed', false);
        $this->assertDatabaseCount('platform_payments', 1);
    }

    public function test_payment_reference_cannot_be_reused_for_another_invoice(): void
    {
        $tenant = $this->tenant('Reference School');
        $super = $this->user('Reference Operator', true, null);
        DB::table('platform_payments')->insert([
            'reference' => 'USED-REFERENCE', 'tenant_id' => $tenant->id, 'amount' => 1000, 'status' => 'confirmed',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $invoiceId = DB::table('platform_invoices')->insertGetId([
            'tenant_id' => $tenant->id, 'invoice_number' => 'INV-REF01', 'amount' => 5000, 'student_count' => 70,
            'billing_cycle' => 'termly', 'status' => 'pending', 'due_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($super, 'billing-reference'))->postJson("/api/v1/platform/billing/invoices/{$invoiceId}/settle", [
            'payment_method' => 'cash', 'payment_ref' => 'USED-REFERENCE',
        ])->assertUnprocessable();
        $this->assertDatabaseHas('platform_invoices', ['id' => $invoiceId, 'status' => 'pending']);
    }

    private function tenant(string $name, string $status = Tenant::STATUS_ACTIVE, $expiry = null): Tenant
    {
        return Tenant::create(['name' => $name, 'slug' => str($name)->slug().'-'.uniqid(), 'status' => $status, 'subscription_expires_at' => $expiry]);
    }

    private function user(string $name, bool $super, ?int $tenantId): User
    {
        return User::create([
            'tenant_id' => $tenantId, 'name' => $name, 'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'role' => $super ? 'super_admin' : 'admin', 'is_super_admin' => $super, 'is_active' => true,
            'employment_status' => $super ? null : User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function students(int $tenantId, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Student::create([
                'tenant_id' => $tenantId, 'admission_number' => 'PB'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'first_name' => 'Student', 'last_name' => (string) $i, 'status' => Student::STATUS_ACTIVE,
            ]);
        }
    }
}
