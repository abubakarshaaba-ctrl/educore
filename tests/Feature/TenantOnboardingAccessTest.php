<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\GradingSystem;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantOnboardingAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant onboarding access tests require the isolated sqlite :memory: test database.');
        }

        config([
            'tenancy.local_base_domain' => 'educore.test',
            'tenancy.scheme' => 'http',
        ]);

        $this->rebuildSchema();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->registerTestRoutes();
    }

    public function test_incomplete_tenant_is_redirected_from_operations_to_onboarding(): void
    {
        $tenant = $this->tenantFixture();
        $user = $this->adminFixture($tenant);

        $this->actingAs($user)
            ->get('/__onboarding/protected')
            ->assertOk()
            ->assertSee('protected');

        $this->assertSame(0, \App\Models\AuditLog::where('action', 'tenant.onboarding.blocked')->count());
    }

    public function test_incomplete_tenant_can_access_onboarding_account_status_billing_and_logout_exemptions(): void
    {
        $tenant = $this->tenantFixture();
        $user = $this->adminFixture($tenant);

        $this->actingAs($user)->get('/onboarding')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/account-status')->assertOk()->assertSee($tenant->name);
        $this->actingAs($user)->get('/__onboarding/billing')->assertOk()->assertSee('billing');
        $this->actingAs($user)->post('/__onboarding/logout-like')->assertOk()->assertSee('logout');
    }

    public function test_complete_active_tenant_can_access_operations(): void
    {
        $tenant = $this->tenantFixture(['phone' => '08000000000', 'address' => '1 School Road']);
        $user = $this->adminFixture($tenant);
        $this->makeReady($tenant);

        $this->actingAs($user)
            ->get('/__onboarding/protected')
            ->assertOk()
            ->assertSee('protected');
    }

    public function test_suspended_tenant_cannot_bypass_restrictions_through_onboarding(): void
    {
        $tenant = $this->tenantFixture(['status' => Tenant::STATUS_SUSPENDED]);
        $user = $this->adminFixture($tenant);

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertRedirect(route('tenant.account-status'));
    }

    public function test_tenant_cannot_edit_another_tenants_onboarding_by_submitting_tenant_id(): void
    {
        $tenantA = $this->tenantFixture(['slug' => 'tenant-a', 'name' => 'Tenant A']);
        $tenantB = $this->tenantFixture(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $user = $this->adminFixture($tenantA);

        $this->actingAs($user)
            ->post('/onboarding/profile', [
                'tenant_id' => $tenantB->id,
                'name' => 'Tenant A Updated',
                'email' => 'updated-a@example.test',
                'phone' => '08000000001',
                'address' => 'Updated Address',
                'motto' => 'Updated Motto',
            ])
            ->assertRedirect(route('tenant.onboarding.branding'));

        $this->assertSame('Tenant A Updated', $tenantA->fresh()->name);
        $this->assertSame('Tenant B', $tenantB->fresh()->name);
    }

    public function test_super_admin_impersonation_can_inspect_incomplete_onboarding(): void
    {
        $tenant = $this->tenantFixture();
        $user = $this->adminFixture($tenant);

        $this->actingAs($user)
            ->withSession(['super_admin_id' => 999, 'impersonating_tenant_id' => $tenant->id])
            ->get('/__onboarding/protected')
            ->assertOk()
            ->assertSee('protected');
    }

    private function registerTestRoutes(): void
    {
        Route::middleware(['web', 'auth', 'active.account', 'tenant', 'tenant.access', 'tenant.onboarding.complete'])
            ->get('/__onboarding/protected', fn () => response('protected'))
            ->name('onboarding-test.protected');

        Route::middleware(['web', 'auth', 'active.account', 'tenant', 'tenant.access', 'tenant.onboarding.complete'])
            ->get('/__onboarding/billing', fn () => response('billing'))
            ->name('onboarding-test.billing');

        Route::middleware(['web', 'auth', 'active.account', 'tenant', 'tenant.access', 'tenant.onboarding.complete'])
            ->post('/__onboarding/logout-like', fn () => response('logout'))
            ->name('logout');
    }

    private function tenantFixture(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Bluerayy Academy',
            'slug' => 'bluerayy-academy',
            'email' => 'info@bluerayy.test',
            'phone' => null,
            'address' => null,
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'theme_primary' => '#071E45',
            'theme_accent' => '#D79A21',
        ], $overrides));
    }

    private function adminFixture(Tenant $tenant): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'School Admin',
            'email' => 'admin'.$tenant->id.'@school.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function makeReady(Tenant $tenant): void
    {
        $session = AcademicSession::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => '2026/2027', 'is_current' => true]);
        Term::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'is_current' => true,
        ]);
        $level = ClassLevel::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => 'JSS 1', 'section' => 'junior', 'order_index' => 1]);
        ClassArm::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'name' => 'A']);
        Subject::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);
        GradingSystem::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'grade_letter' => 'A', 'min_score' => 70, 'max_score' => 100]);
        SchoolSetting::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'key' => 'currency', 'value' => 'NGN', 'group' => 'finance']);
        \App\Models\AdmissionPortalSetting::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'is_open' => false, 'application_fee' => 0]);
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'audit_logs',
            'role_has_permissions',
            'model_has_permissions',
            'model_has_roles',
            'permissions',
            'roles',
            'grading_systems',
            'subjects',
            'class_arms',
            'class_levels',
            'terms',
            'academic_sessions',
            'admission_portal_settings',
            'school_settings',
            'tenant_subscriptions',
            'platform_settings',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createTenantTables();
        $this->createUserTable();
        $this->createPermissionTables();
        $this->createReadinessTables();
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
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('employment_status', 40)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createReadinessTables(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
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
            $table->timestamps();
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
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
            $table->unsignedBigInteger('session_id')->nullable();
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
            $table->string('section')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        Schema::create('class_arms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('grading_systems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->string('grade_letter');
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->string('remark')->nullable();
            $table->boolean('is_pass_grade')->default(true);
            $table->timestamps();
        });
    }

    private function createPermissionTables(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
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
