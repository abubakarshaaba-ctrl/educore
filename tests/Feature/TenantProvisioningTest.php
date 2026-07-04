<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\StaffWorkHistory;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant provisioning tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_super_admin_can_access_tenant_creation(): void
    {
        $this->planFixture();

        $this->actingAs($this->superAdmin())
            ->get(route('super.tenants.create'))
            ->assertOk()
            ->assertSee('Provision New School')
            ->assertSee('Subscription Plan')
            ->assertSee('Employment Start Date');
    }

    public function test_tenant_and_primary_administrator_are_created_atomically(): void
    {
        $plan = $this->planFixture();

        $this->actingAs($this->superAdmin())
            ->post(route('super.tenants.store'), $this->payload($plan))
            ->assertRedirect();

        $tenant = Tenant::where('slug', 'blue-rayy-academy')->firstOrFail();
        $admin = User::where('email', 'admin@bluerayy.test')->firstOrFail();

        $this->assertSame($tenant->id, $admin->tenant_id);
        $this->assertSame('admin', $admin->role);
        $this->assertFalse((bool) $admin->is_super_admin);
        $this->assertTrue((bool) $admin->is_active);
        $this->assertSame(User::STAFF_STATUS_ACTIVE, $admin->employment_status);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertSame(1, StaffWorkHistory::withoutTenantScope()->where('user_id', $admin->id)->whereNull('end_date')->count());
        $this->assertDatabaseHas('tenant_subscriptions', ['tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $this->assertDatabaseHas('school_settings', ['tenant_id' => $tenant->id, 'key' => 'currency', 'value' => 'NGN']);
        $this->assertDatabaseHas('admission_portal_settings', ['tenant_id' => $tenant->id, 'is_open' => false]);
        $this->assertSame(1, AuditLog::where('action', 'tenant.provisioning.administrator_created')->count());
        $this->assertSame(0, AuditLog::where('action', 'tenant.provisioning.administrator_created')->where('new_values', 'like', '%SecretPass%')->count());

        $this->get(route('super.tenant.show', $tenant))
            ->assertOk()
            ->assertSee('Onboarding Readiness')
            ->assertSee('Local Host Login');
    }

    public function test_failed_retry_does_not_create_duplicate_tenant_or_admin(): void
    {
        $plan = $this->planFixture();
        $payload = $this->payload($plan);
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('super.tenants.store'), $payload)->assertRedirect();
        $this->actingAs($superAdmin)->from(route('super.tenants.create'))->post(route('super.tenants.store'), $payload)
            ->assertRedirect(route('super.tenants.create'))
            ->assertSessionHasErrors(['slug', 'admin_email']);

        $this->assertSame(1, Tenant::where('slug', 'blue-rayy-academy')->count());
        $this->assertSame(1, User::where('email', 'admin@bluerayy.test')->count());
    }

    private function payload(SubscriptionPlan $plan, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Blue Rayy Academy',
            'slug' => 'Blue Rayy Academy',
            'subdomain' => 'bluerayy',
            'email' => 'info@bluerayy.test',
            'phone' => '08000000000',
            'address' => '1 School Road',
            'plan_id' => $plan->id,
            'billing_cycle' => 'annual',
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'admin_name' => 'School Administrator',
            'admin_email' => 'admin@bluerayy.test',
            'admin_password' => 'SecretPass123!',
            'admin_employment_started_at' => now()->toDateString(),
        ], $overrides);
    }

    private function planFixture(array $overrides = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name' => 'Standard',
            'slug' => 'standard',
            'monthly_price' => 1000,
            'annual_price' => 10000,
            'max_students' => 500,
            'max_staff' => 50,
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function superAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'super@educore.test',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'audit_logs',
            'staff_work_histories',
            'tenant_subscriptions',
            'admission_portal_settings',
            'school_settings',
            'platform_payments',
            'model_has_roles',
            'roles',
            'subscription_plans',
            'students',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createTenantTables();
        $this->createUserTable();
        $this->createStudentsTable();
        $this->createRoleTables();
        $this->createSubscriptionTables();
        $this->createSettingsTables();
        $this->createPlatformPaymentsTable();
        $this->createStaffWorkHistoriesTable();
        $this->createAuditLogsTable();
    }

    private function createTenantTables(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->string('logo_path')->nullable();
            $table->string('motto')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default(Tenant::STATUS_PENDING);
            $table->date('subscription_expires_at')->nullable();
            $table->string('theme_primary', 20)->nullable();
            $table->string('theme_accent', 20)->nullable();
            $table->string('theme_sidebar', 20)->nullable();
            $table->string('custom_domain')->nullable();
            $table->boolean('domain_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createUserTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('staff_id')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('employment_status', 40)->nullable();
            $table->date('employment_started_at')->nullable();
            $table->date('employment_ended_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->text('exit_reason')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createStudentsTable(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createRoleTables(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
    }

    private function createSubscriptionTables(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('annual_price', 10, 2)->default(0);
            $table->integer('max_students')->default(0);
            $table->integer('max_staff')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('status')->default('active');
            $table->string('billing_cycle')->default('annual');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('starts_at');
            $table->date('expires_at');
            $table->date('next_billing_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    private function createSettingsTables(): void
    {
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('admission_portal_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->boolean('is_open')->default(false);
            $table->decimal('application_fee', 10, 2)->default(0);
            $table->text('welcome_message')->nullable();
            $table->boolean('notify_guardian_sms')->default(false);
            $table->boolean('notify_guardian_email')->default(true);
            $table->timestamps();
        });
    }

    private function createPlatformPaymentsTable(): void
    {
        Schema::create('platform_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('confirmed');
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    private function createStaffWorkHistoriesTable(): void
    {
        Schema::create('staff_work_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('position_title')->nullable();
            $table->string('department_name')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('functional_role')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('appointment_type')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('change_type');
            $table->text('reason')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    private function createAuditLogsTable(): void
    {
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
    }
}
