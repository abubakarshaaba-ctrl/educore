<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileExpensesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile expenses tests require sqlite :memory:.');
        }

        foreach (['school_expenses', 'terms', 'academic_sessions', 'staff_permissions', 'api_tokens', 'users', 'tenants'] as $table) {
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
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('school_expenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->string('title');
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
        });
    }

    public function test_expense_index_is_tenant_scoped_searchable_and_reports_totals(): void
    {
        [$tenant, $admin, $sessionId, $termId] = $this->school('Expense School');
        [$foreignTenant, , $foreignSession, $foreignTerm] = $this->school('Foreign Expense School');
        $this->expense($tenant->id, $sessionId, $termId, 'Generator Fuel', 'utilities', 50000);
        $this->expense($foreignTenant->id, $foreignSession, $foreignTerm, 'Foreign Expense', 'rent', 900000);

        $this->withToken(ApiToken::issue($admin, 'expenses-index'))
            ->getJson('/api/v1/expenses?q=Generator')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonPath('metrics.total', 50000)
            ->assertJsonCount(1, 'expenses')
            ->assertJsonPath('expenses.0.title', 'Generator Fuel');
    }

    public function test_create_and_update_keep_academic_period_tenant_scoped(): void
    {
        [$tenant, $admin, $sessionId, $termId] = $this->school('CRUD Expense School');
        [$foreignTenant, , $foreignSession, $foreignTerm] = $this->school('Foreign Period School');
        $token = ApiToken::issue($admin, 'expenses-crud');

        $response = $this->withToken($token)->postJson('/api/v1/expenses', [
            'title' => 'Laboratory Supplies',
            'category' => 'supplies',
            'amount' => 125000,
            'expense_date' => '2026-09-08',
            'payment_method' => 'bank transfer',
            'reference' => 'EXP-001',
            'term_id' => $termId,
            'session_id' => $sessionId,
        ])->assertCreated()
            ->assertJsonPath('expense.session_id', $sessionId)
            ->assertJsonPath('expense.term_id', $termId);

        $expenseId = $response->json('expense.id');
        $this->withToken($token)->patchJson("/api/v1/expenses/{$expenseId}", [
            'title' => 'Laboratory Supplies Updated',
            'category' => 'equipment',
            'amount' => 130000,
            'expense_date' => '2026-09-08',
            'term_id' => $termId,
            'session_id' => $sessionId,
        ])->assertOk()->assertJsonPath('expense.amount', 130000);

        $this->withToken($token)->postJson('/api/v1/expenses', [
            'title' => 'Invalid Foreign Period',
            'category' => 'other',
            'amount' => 1000,
            'expense_date' => '2026-09-08',
            'term_id' => $foreignTerm,
            'session_id' => $foreignSession,
        ])->assertUnprocessable();

        $this->assertSame($tenant->id, DB::table('school_expenses')->where('id', $expenseId)->value('tenant_id'));
        $this->assertNotSame($tenant->id, $foreignTenant->id);
    }

    public function test_term_and_session_must_belong_together(): void
    {
        [, $admin, $sessionId] = $this->school('Mismatch Expense School');
        [, , $foreignSession, $foreignTerm] = $this->school('Mismatch Foreign School');

        $this->withToken(ApiToken::issue($admin, 'expenses-mismatch'))
            ->postJson('/api/v1/expenses', [
                'title' => 'Mismatched Period',
                'category' => 'other',
                'amount' => 1000,
                'expense_date' => '2026-09-08',
                'term_id' => $foreignTerm,
                'session_id' => $sessionId,
            ])->assertUnprocessable();

        $this->assertNotSame($sessionId, $foreignSession);
    }

    public function test_delete_is_tenant_safe(): void
    {
        [, $admin] = $this->school('Delete Expense School');
        [$foreignTenant, , $foreignSession, $foreignTerm] = $this->school('Foreign Delete School');
        $foreignExpense = $this->expense($foreignTenant->id, $foreignSession, $foreignTerm, 'Foreign Delete Expense', 'other', 1000);

        $this->withToken(ApiToken::issue($admin, 'expenses-delete'))
            ->deleteJson("/api/v1/expenses/{$foreignExpense}")
            ->assertNotFound();

        $this->assertDatabaseHas('school_expenses', ['id' => $foreignExpense, 'tenant_id' => $foreignTenant->id]);
    }

    public function test_custom_deny_blocks_expense_access(): void
    {
        [$tenant, $admin] = $this->school('Denied Expense School');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'module' => 'expenses',
            'type' => 'deny',
            'granted_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($admin, 'expenses-denied'))
            ->getJson('/api/v1/expenses')
            ->assertForbidden();
    }

    private function school(string $name): array
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
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenant, $admin, $sessionId, $termId];
    }

    private function expense(int $tenantId, int $sessionId, int $termId, string $title, string $category, float $amount): int
    {
        return DB::table('school_expenses')->insertGetId([
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
            'term_id' => $termId,
            'title' => $title,
            'category' => $category,
            'amount' => $amount,
            'expense_date' => '2026-09-08',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
