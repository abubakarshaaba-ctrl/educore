<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Mobile\MobileModuleService;
use App\Services\TenantAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileBootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile bootstrap tests require sqlite :memory:.');
        }

        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('students');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->date('subscription_expires_at')->nullable();
            $table->unsignedInteger('students_capacity')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('theme_primary')->nullable();
            $table->string('theme_accent')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('motto')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('staff_id')->nullable();
            $table->string('student_id')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamp('last_login_at')->nullable();
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

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    public function test_mobile_login_issues_a_token_that_immediately_bootstraps(): void
    {
        $user = User::create([
            'name' => 'Platform Administrator',
            'email' => 'mobile-login@example.test',
            'password' => Hash::make('Correct-password-123!'),
            'role' => 'super_admin',
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'login_id' => 'MOBILE-LOGIN@EXAMPLE.TEST',
            'password' => 'Correct-password-123!',
            'device' => 'Android contract test',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.portal', 'platform')
            ->assertJsonPath('user.roles.0', 'super_admin')
            ->assertJsonPath('school.slug', 'platform');

        $plainToken = $login->json('token');

        $this->assertIsString($plainToken);
        $this->assertNotSame('', $plainToken);
        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
        ]);

        $this->withToken($plainToken)
            ->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.portal', 'platform')
            ->assertJsonPath('access.allowed', true)
            ->assertJsonPath('contract_version', 1);
    }

    public function test_legacy_api_token_schema_does_not_break_bootstrap_and_is_repaired(): void
    {
        Schema::table('api_tokens', function (Blueprint $table): void {
            $table->dropColumn('last_used_at');
        });
        $this->assertFalse(Schema::hasColumn('api_tokens', 'last_used_at'));

        $user = User::create([
            'name' => 'Legacy Token Administrator',
            'email' => 'legacy-token@example.test',
            'role' => 'super_admin',
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $token = ApiToken::issue($user, 'legacy-token-schema');

        $response = $this->withToken($token)
            ->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('access.allowed', true)
            ->assertJsonMissing(['degraded' => true]);

        $this->assertStringStartsWith('MB-', (string) $response->headers->get('X-EduCore-Request-Id'));

        $migration = require database_path(
            'migrations/2026_09_12_170000_repair_api_token_last_used_at.php'
        );
        $migration->up();

        $this->assertTrue(Schema::hasColumn('api_tokens', 'last_used_at'));
    }

    public function test_bootstrap_never_fabricates_allowed_access_after_access_service_failure(): void
    {
        $tenant = Tenant::create([
            'name' => 'Access Contract School',
            'slug' => 'access-contract-school',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'School Administrator',
            'email' => 'access-contract@example.test',
            'role' => 'admin',
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $access = $this->createMock(TenantAccessService::class);
        $access->method('applicationAccess')
            ->willThrowException(new \RuntimeException('Simulated access metadata failure.'));
        $this->app->instance(TenantAccessService::class, $access);

        $response = $this->withToken(ApiToken::issue($user, 'access-contract-test'))
            ->getJson('/api/v1/bootstrap')
            ->assertStatus(503)
            ->assertJsonPath(
                'message',
                'EduCore could not verify the school access state. Please try again.'
            )
            ->assertJsonStructure(['request_id'])
            ->assertJsonMissingPath('access.allowed');

        $this->assertStringStartsWith('MB-', (string) $response->headers->get('X-EduCore-Request-Id'));
    }

    public function test_mobile_login_accepts_staff_id_without_case_sensitivity(): void
    {
        $tenant = Tenant::create([
            'name' => 'Greenfield Academy',
            'slug' => 'greenfield-academy',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Class Teacher',
            'email' => 'teacher@example.test',
            'password' => Hash::make('Correct-password-123!'),
            'staff_id' => 'GFA-STF-0042',
            'role' => 'subject_teacher',
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'login_id' => 'gfa-stf-0042',
            'password' => 'Correct-password-123!',
            'device' => 'Android contract test',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.portal', 'staff')
            ->assertJsonPath('school.id', $tenant->id);

        $this->withToken($login->json('token'))
            ->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.portal', 'staff');
    }

    public function test_mobile_login_accepts_admission_number_without_case_sensitivity(): void
    {
        $tenant = Tenant::create([
            'name' => 'Greenfield Academy',
            'slug' => 'greenfield-academy',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Student Account',
            'email' => 'student@example.test',
            'password' => Hash::make('Correct-password-123!'),
            'student_id' => 'GFA-ADM-2026-019',
            'role' => 'student',
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'login_id' => 'gfa-adm-2026-019',
            'password' => 'Correct-password-123!',
            'device' => 'Android contract test',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.portal', 'student')
            ->assertJsonPath('school.id', $tenant->id);

        $this->withToken($login->json('token'))
            ->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.portal', 'student')
            ->assertJsonFragment(['key' => 'student.dashboard']);
    }

    public function test_authenticated_super_admin_receives_one_authoritative_bootstrap_contract(): void
    {
        $user = User::create([
            'name' => 'Platform Administrator',
            'email' => 'platform@example.test',
            'role' => 'super_admin',
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $token = ApiToken::issue($user, 'bootstrap-test');

        $this->withToken($token)
            ->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.portal', 'platform')
            ->assertJsonPath('school.slug', 'platform')
            ->assertJsonPath('access.allowed', true)
            ->assertJsonPath('access.state', 'allowed')
            ->assertJsonPath('contract_version', 1)
            ->assertJsonPath('school.branding.primary_color', '#071E45')
            ->assertJsonPath('academic.session', null)
            ->assertJsonPath('academic.term', null)
            ->assertJsonFragment(['key' => 'platform.dashboard'])
            ->assertJsonStructure([
                'contract_version',
                'user',
                'school' => ['branding' => ['primary_color', 'accent_color', 'motto']],
                'academic' => ['session', 'term'],
                'access' => ['allowed', 'state', 'message', 'severity', 'expires_at'],
                'permissions',
                'features',
                'modules',
                'token' => ['expires_at'],
                'server_time',
            ]);
    }

    public function test_bootstrap_rejects_missing_bearer_token(): void
    {
        $this->getJson('/api/v1/bootstrap')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_suspended_tenant_receives_no_permissions_or_modules(): void
    {
        $tenant = Tenant::create([
            'name' => 'Suspended School',
            'slug' => 'suspended-school',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'School Administrator',
            'email' => 'admin@suspended.test',
            'role' => 'admin',
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $token = ApiToken::issue($user, 'suspended-test');

        $this->withToken($token)
            ->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('access.allowed', false)
            ->assertJsonPath('access.state', 'suspended')
            ->assertJsonPath('permissions', [])
            ->assertJsonPath('features', [])
            ->assertJsonPath('modules', []);
    }

    public function test_staff_module_service_uses_the_existing_role_matrix(): void
    {
        $teacher = new User([
            'role' => 'subject_teacher',
            'is_super_admin' => false,
        ]);

        $keys = collect(app(MobileModuleService::class)->forUser($teacher))->pluck('key');

        $this->assertTrue($keys->contains('scores'));
        $this->assertTrue($keys->contains('lesson-planner'));
        $this->assertFalse($keys->contains('fees'));
    }

    public function test_subject_teacher_can_open_authorized_cbt_workspace_but_not_finance(): void
    {
        $tenant = Tenant::create(['name' => 'CBT School', 'slug' => 'cbt-school', 'status' => Tenant::STATUS_ACTIVE]);
        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Subject Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $token = ApiToken::issue($teacher, 'portal-test');

        $this->withToken($token)->postJson('/api/v1/portal/session', ['path' => '/cbt'])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $this->withToken($token)->postJson('/api/v1/portal/session', ['path' => '/fees/invoices'])
            ->assertForbidden();
    }

    public function test_bootstrap_exposes_effective_role_permissions_to_native_client(): void
    {
        $tenant = Tenant::create(['name' => 'Role School', 'slug' => 'role-school', 'status' => Tenant::STATUS_ACTIVE]);
        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Form Subject Teacher',
            'role' => 'form_subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $this->withToken(ApiToken::issue($teacher, 'rbac-test'))->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonFragment(['scores.entry'])
            ->assertJsonFragment(['attendance'])
            ->assertJsonFragment(['cbt']);
    }

    public function test_mobile_password_reset_requires_a_valid_email_address(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_mobile_password_reset_does_not_disclose_unknown_accounts(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.test'])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'If that email is registered, a password reset link has been sent.'
            );
    }

    public function test_platform_dashboard_returns_one_normalized_real_data_contract(): void
    {
        Tenant::create([
            'name' => 'Greenfield Academy',
            'slug' => 'greenfield-academy',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = User::create([
            'name' => 'Platform Administrator',
            'email' => 'dashboard@example.test',
            'role' => 'super_admin',
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $token = ApiToken::issue($user, 'dashboard-test');

        $this->withToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('contract_version', 1)
            ->assertJsonPath('scope', 'platform')
            ->assertJsonPath('role_key', 'super_admin')
            ->assertJsonPath('metrics.0.key', 'schools')
            ->assertJsonPath('metrics.0.display_value', '1')
            ->assertJsonMissing(['module_key' => 'platform.dashboard'])
            ->assertJsonStructure([
                'generated_at',
                'metrics' => [['key', 'label', 'display_value', 'tone', 'module_key']],
                'quick_actions' => [['module_key', 'title', 'icon', 'path']],
                'sections',
            ]);
    }

    public function test_dashboard_rejects_missing_bearer_token(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
