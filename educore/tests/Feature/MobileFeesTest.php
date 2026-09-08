<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileFeesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile fees tests require sqlite :memory:.');
        }

        foreach ([
            'payment_transactions', 'invoice_items', 'invoices', 'fee_structures', 'fee_categories',
            'students', 'class_arms', 'class_levels', 'terms', 'academic_sessions',
            'staff_permissions', 'api_tokens', 'users', 'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('role')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
        Schema::create('staff_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('module', 60);
            $table->string('type')->default('grant');
            $table->unsignedBigInteger('granted_by');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id', 'module']);
        });
        Schema::create('academic_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('terms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id');
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('class_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('section')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('form_tutor_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('fee_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('fee_category_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('term_id');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'student_id', 'term_id', 'session_id']);
        });
        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('fee_category_id');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_id');
            $table->string('gateway_reference')->unique();
            $table->string('gateway')->default('cash');
            $table->decimal('amount_paid', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('pending');
            $table->text('gateway_response')->nullable();
            $table->text('split_breakdown')->nullable();
            $table->string('paid_by_name')->nullable();
            $table->string('paid_by_phone')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_finance_register_is_tenant_scoped_and_searchable(): void
    {
        [$tenant, $admin, $termId] = $this->school('Finance School');
        [$otherTenant, , $otherTermId] = $this->school('Other Finance School');
        $student = $this->student($tenant->id, 'FIN-001', 'Amina');
        $foreignStudent = $this->student($otherTenant->id, 'FOREIGN-001', 'Foreign');
        $this->invoice($tenant->id, $student, $termId, 'INV-FIN-001', 100000, 20000);
        $this->invoice($otherTenant->id, $foreignStudent, $otherTermId, 'INV-FOREIGN', 500000, 0);

        $this->withToken(ApiToken::issue($admin, 'fees-index'))
            ->getJson('/api/v1/fees?q=FIN-001')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonPath('metrics.billed', 100000)
            ->assertJsonCount(1, 'invoices')
            ->assertJsonPath('invoices.0.number', 'INV-FIN-001');
    }

    public function test_manual_payment_updates_invoice_and_ledger_atomically(): void
    {
        [$tenant, $admin, $termId] = $this->school('Payment School');
        $student = $this->student($tenant->id, 'PAY-001', 'Maryam');
        $invoiceId = $this->invoice($tenant->id, $student, $termId, 'INV-PAY-001', 100000, 20000);

        $this->withToken(ApiToken::issue($admin, 'fees-payment'))
            ->postJson("/api/v1/fees/invoices/{$invoiceId}/payments", [
                'amount' => 30000,
                'paid_by_name' => 'Guardian Name',
                'paid_by_phone' => '08000000000',
                'gateway' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('invoice.paid', 50000)
            ->assertJsonPath('invoice.balance', 50000)
            ->assertJsonPath('invoice.status', 'partially_paid');

        $this->assertDatabaseHas('payment_transactions', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoiceId,
            'amount_paid' => 30000,
            'status' => 'success',
        ]);
    }

    public function test_payment_rejects_overpayment_and_foreign_invoice(): void
    {
        [$tenant, $admin, $termId] = $this->school('Boundary Fee School');
        [$otherTenant, , $otherTermId] = $this->school('Foreign Fee School');
        $student = $this->student($tenant->id, 'LOCAL-PAY', 'Local');
        $foreignStudent = $this->student($otherTenant->id, 'FOREIGN-PAY', 'Foreign');
        $invoiceId = $this->invoice($tenant->id, $student, $termId, 'INV-LOCAL', 50000, 40000);
        $foreignInvoiceId = $this->invoice($otherTenant->id, $foreignStudent, $otherTermId, 'INV-FOREIGN-PAY', 50000, 0);
        $token = ApiToken::issue($admin, 'fees-boundary');

        $payload = ['amount' => 20000, 'paid_by_name' => 'Payer', 'gateway' => 'cash'];
        $this->withToken($token)->postJson("/api/v1/fees/invoices/{$invoiceId}/payments", $payload)->assertUnprocessable();
        $this->withToken($token)->postJson("/api/v1/fees/invoices/{$foreignInvoiceId}/payments", $payload)->assertNotFound();

        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_bill_generation_is_permission_aware_and_skips_existing_invoice(): void
    {
        [$tenant, $admin, $termId, $sessionId, $levelId, $armId] = $this->school('Billing School', withClass: true);
        $studentA = $this->student($tenant->id, 'BILL-001', 'Aisha', $armId);
        $this->student($tenant->id, 'BILL-002', 'Zainab', $armId);
        $categoryId = DB::table('fee_categories')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Tuition',
            'is_mandatory' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('fee_structures')->insert([
            'tenant_id' => $tenant->id,
            'fee_category_id' => $categoryId,
            'class_level_id' => $levelId,
            'term_id' => $termId,
            'amount' => 75000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->invoice($tenant->id, $studentA, $termId, 'INV-EXISTING', 75000, 0, $sessionId);

        $this->withToken(ApiToken::issue($admin, 'fees-generate'))
            ->postJson('/api/v1/fees/generate', ['term_id' => $termId, 'class_level_id' => $levelId])
            ->assertCreated()
            ->assertJsonPath('generated', 1)
            ->assertJsonPath('skipped', 1);

        $this->assertSame(2, DB::table('invoices')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, DB::table('invoice_items')->where('tenant_id', $tenant->id)->count());
    }

    public function test_custom_deny_blocks_fee_access_and_mutations(): void
    {
        [$tenant, $admin] = $this->school('Denied Fee School');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'module' => 'fees',
            'type' => 'deny',
            'granted_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($admin, 'fees-denied'))
            ->getJson('/api/v1/fees')
            ->assertForbidden();
    }

    private function school(string $name, bool $withClass = false): array
    {
        $tenant = Tenant::create(['name' => $name, 'slug' => str($name)->slug().'-'.uniqid(), 'status' => 'active']);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' Admin',
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $sessionId = DB::table('academic_sessions')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $termId = DB::table('terms')->insertGetId([
            'tenant_id' => $tenant->id,
            'session_id' => $sessionId,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-15',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $levelId = null;
        $armId = null;
        if ($withClass) {
            $levelId = DB::table('class_levels')->insertGetId([
                'tenant_id' => $tenant->id,
                'name' => 'Year 10',
                'section' => 'senior',
                'order_index' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $armId = DB::table('class_arms')->insertGetId([
                'tenant_id' => $tenant->id,
                'class_level_id' => $levelId,
                'name' => 'A',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$tenant, $admin, $termId, $sessionId, $levelId, $armId];
    }

    private function student(int $tenantId, string $number, string $firstName, ?int $armId = null): int
    {
        return DB::table('students')->insertGetId([
            'tenant_id' => $tenantId,
            'admission_number' => $number,
            'first_name' => $firstName,
            'last_name' => 'Student',
            'current_class_arm_id' => $armId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invoice(int $tenantId, int $studentId, int $termId, string $number, float $total, float $paid, ?int $sessionId = null): int
    {
        $sessionId ??= (int) DB::table('terms')->where('id', $termId)->value('session_id');
        return DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'student_id' => $studentId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'invoice_number' => $number,
            'total_amount' => $total,
            'amount_paid' => $paid,
            'status' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partially_paid' : 'unpaid'),
            'due_date' => '2026-12-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
