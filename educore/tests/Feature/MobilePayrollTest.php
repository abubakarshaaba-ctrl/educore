<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePayrollTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile payroll tests require sqlite :memory:.');
        }

        foreach (['payroll_items', 'payroll_periods', 'staff_permissions', 'api_tokens', 'users', 'tenants'] as $table) {
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
            $table->string('staff_id')->nullable();
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
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft');
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
        Schema::create('payroll_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('staff_id');
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('housing_allowance', 10, 2)->default(0);
            $table->decimal('transport_allowance', 10, 2)->default(0);
            $table->decimal('other_allowances', 10, 2)->default(0);
            $table->decimal('gross_pay', 10, 2)->default(0);
            $table->decimal('tax_deduction', 10, 2)->default(0);
            $table->decimal('pension_deduction', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->text('deduction_breakdown')->nullable();
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('payment_status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function test_payroll_index_is_tenant_scoped_searchable_and_permission_aware(): void
    {
        [$tenant, $admin] = $this->school('Payroll School');
        [$foreignTenant] = $this->school('Foreign Payroll School');
        $this->period($tenant->id, 'September 2026 Salary', 'draft', 1000000, 100000, 900000);
        $this->period($foreignTenant->id, 'Foreign Salary', 'paid', 9999999, 0, 9999999);

        $this->withToken(ApiToken::issue($admin, 'payroll-index'))
            ->getJson('/api/v1/payroll?q=September')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonPath('metrics.periods', 1)
            ->assertJsonPath('metrics.net_total', 900000)
            ->assertJsonCount(1, 'periods')
            ->assertJsonPath('periods.0.title', 'September 2026 Salary');
    }

    public function test_period_detail_is_tenant_scoped_and_exposes_staff_lines(): void
    {
        [$tenant, $admin] = $this->school('Detail Payroll School');
        [$foreignTenant] = $this->school('Foreign Detail School');
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Staff Member',
            'staff_id' => 'STF-001',
            'role' => 'teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $periodId = $this->period($tenant->id, 'September Payroll', 'draft', 100000, 10000, 90000);
        $foreignPeriodId = $this->period($foreignTenant->id, 'Foreign Payroll', 'draft', 100000, 0, 100000);
        $this->item($tenant->id, $periodId, $staff->id, 100000, 10000, 90000);

        $token = ApiToken::issue($admin, 'payroll-detail');
        $this->withToken($token)
            ->getJson("/api/v1/payroll/{$periodId}")
            ->assertOk()
            ->assertJsonPath('period.id', $periodId)
            ->assertJsonPath('items.0.staff_name', 'Staff Member')
            ->assertJsonPath('items.0.net', 90000);

        $this->withToken($token)->getJson("/api/v1/payroll/{$foreignPeriodId}")->assertNotFound();
    }

    public function test_draft_can_be_approved_but_non_draft_cannot_be_reapproved(): void
    {
        [$tenant, $admin] = $this->school('Approval Payroll School');
        $periodId = $this->period($tenant->id, 'Approval Payroll', 'draft', 100000, 10000, 90000);
        $token = ApiToken::issue($admin, 'payroll-approve');

        $this->withToken($token)
            ->postJson("/api/v1/payroll/{$periodId}/approve")
            ->assertOk()
            ->assertJsonPath('period.status', 'approved');

        $this->assertDatabaseHas('payroll_periods', [
            'id' => $periodId,
            'tenant_id' => $tenant->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $this->withToken($token)
            ->postJson("/api/v1/payroll/{$periodId}/approve")
            ->assertUnprocessable();
    }

    public function test_mark_paid_requires_approved_status_and_updates_every_item(): void
    {
        [$tenant, $admin] = $this->school('Paid Payroll School');
        $staffA = $this->staff($tenant->id, 'Staff A', 'PAY-001');
        $staffB = $this->staff($tenant->id, 'Staff B', 'PAY-002');
        $draftId = $this->period($tenant->id, 'Draft Payroll', 'draft', 100000, 10000, 90000);
        $approvedId = $this->period($tenant->id, 'Approved Payroll', 'approved', 200000, 20000, 180000);
        $this->item($tenant->id, $approvedId, $staffA, 100000, 10000, 90000);
        $this->item($tenant->id, $approvedId, $staffB, 100000, 10000, 90000);
        $token = ApiToken::issue($admin, 'payroll-paid');

        $this->withToken($token)->postJson("/api/v1/payroll/{$draftId}/paid")->assertUnprocessable();
        $this->withToken($token)
            ->postJson("/api/v1/payroll/{$approvedId}/paid")
            ->assertOk()
            ->assertJsonPath('period.status', 'paid');

        $this->assertSame(2, DB::table('payroll_items')
            ->where('tenant_id', $tenant->id)
            ->where('payroll_period_id', $approvedId)
            ->where('payment_status', 'paid')
            ->count());
    }

    public function test_foreign_period_mutations_are_rejected(): void
    {
        [, $admin] = $this->school('Boundary Payroll School');
        [$foreignTenant] = $this->school('Foreign Boundary Payroll School');
        $foreignPeriod = $this->period($foreignTenant->id, 'Foreign Payroll', 'draft', 100000, 0, 100000);
        $token = ApiToken::issue($admin, 'payroll-boundary');

        $this->withToken($token)->postJson("/api/v1/payroll/{$foreignPeriod}/approve")->assertNotFound();
        DB::table('payroll_periods')->where('id', $foreignPeriod)->update(['status' => 'approved']);
        $this->withToken($token)->postJson("/api/v1/payroll/{$foreignPeriod}/paid")->assertNotFound();
    }

    public function test_custom_deny_blocks_payroll_access(): void
    {
        [$tenant, $admin] = $this->school('Denied Payroll School');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'module' => 'payroll',
            'type' => 'deny',
            'granted_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($admin, 'payroll-denied'))
            ->getJson('/api/v1/payroll')
            ->assertForbidden();
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => 'active',
        ]);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' Admin',
            'staff_id' => strtoupper(substr(md5($name), 0, 8)),
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $admin];
    }

    private function staff(int $tenantId, string $name, string $staffId): int
    {
        return User::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'staff_id' => $staffId,
            'role' => 'teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ])->id;
    }

    private function period(int $tenantId, string $title, string $status, float $gross, float $deductions, float $net): int
    {
        return DB::table('payroll_periods')->insertGetId([
            'tenant_id' => $tenantId,
            'title' => $title,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'status' => $status,
            'total_gross' => $gross,
            'total_deductions' => $deductions,
            'total_net' => $net,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function item(int $tenantId, int $periodId, int $staffId, float $gross, float $deductions, float $net): void
    {
        DB::table('payroll_items')->insert([
            'tenant_id' => $tenantId,
            'payroll_period_id' => $periodId,
            'staff_id' => $staffId,
            'basic_salary' => $gross,
            'gross_pay' => $gross,
            'tax_deduction' => $deductions / 2,
            'pension_deduction' => $deductions / 2,
            'total_deductions' => $deductions,
            'net_pay' => $net,
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
