<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Mobile\MobileModuleService;
use Illuminate\Database\Schema\Blueprint;
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
