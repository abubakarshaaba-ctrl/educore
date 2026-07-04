<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantAuthenticationSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant authentication security tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
    }

    public function test_tenant_login_success_regenerates_session_sets_context_and_writes_audit(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $user = $this->staffFixture($tenant, 'admin@bluerayy.test');

        $this->withSession(['url.intended' => '/school/other-school/login'])
            ->post('/school/bluerayy-academy/login', [
                'login_id' => 'admin@bluerayy.test',
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($tenant->id, session('tenant_id'));
        $this->assertSame($tenant->slug, session('tenant_slug'));
        $this->assertSame(1, AuditLog::where('action', 'auth.login.success')->count());
    }

    public function test_invalid_tenant_login_is_generic_and_audited_without_raw_identifier(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');

        $this->post('/school/bluerayy-academy/login', [
            'login_id' => 'missing@bluerayy.test',
            'password' => 'wrong',
        ])->assertSessionHasErrors('login_id');

        $log = AuditLog::where('action', 'auth.login.denied')->firstOrFail();
        $this->assertSame('invalid_credentials_or_tenant_mismatch', $log->reason);
        $this->assertArrayHasKey('login_id_hash', $log->new_values);
        $this->assertStringNotContainsString('missing@bluerayy.test', json_encode($log->new_values));
    }

    public function test_tenant_aware_login_rate_limit_does_not_cross_slug_boundaries(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->tenantFixture('Greenfield School', 'greenfield-school');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/school/bluerayy-academy/login', [
                'login_id' => 'unknown@example.test',
                'password' => 'wrong',
            ])->assertSessionHasErrors('login_id');
        }

        $this->post('/school/bluerayy-academy/login', [
            'login_id' => 'unknown@example.test',
            'password' => 'wrong',
        ])->assertStatus(429);

        $this->post('/school/greenfield-school/login', [
            'login_id' => 'unknown@example.test',
            'password' => 'wrong',
        ])->assertSessionHasErrors('login_id');
    }

    public function test_authenticated_cross_tenant_login_page_visit_does_not_switch_context(): void
    {
        $tenantA = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->tenantFixture('Greenfield School', 'greenfield-school');
        $user = $this->staffFixture($tenantA, 'admin@bluerayy.test');

        $this->actingAs($user)
            ->get('/school/greenfield-school/login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, AuditLog::where('action', 'auth.login.denied')->count());
    }

    public function test_logout_writes_audit_and_clears_tenant_context(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $user = $this->staffFixture($tenant, 'admin@bluerayy.test');

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id, 'tenant_slug' => $tenant->slug])
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(session('tenant_id'));
        $this->assertSame(1, AuditLog::where('action', 'auth.logout')->count());
    }

    public function test_global_login_parent_agent_admissions_super_and_impersonation_routes_remain_registered(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->staffFixture($tenant, 'admin@bluerayy.test');

        $this->post('/login', [
            'login_id' => 'admin@bluerayy.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.login'));
        $this->assertGuest();

        $this->assertNotNull(Route::getRoutes()->getByName('portal.parent.login'));
        $this->assertNotNull(Route::getRoutes()->getByName('agent.portal.login'));
        $this->assertNotNull(Route::getRoutes()->getByName('portal.landing'));
        $this->assertNotNull(Route::getRoutes()->getByName('super.impersonate'));
        $this->assertNotNull(Route::getRoutes()->getByName('super.stop-impersonating'));
    }

    public function test_tenant_password_and_login_routes_use_tenant_aware_throttle_names(): void
    {
        $this->assertContains('throttle:tenant-login', Route::getRoutes()->getByName('tenant.login.submit')->gatherMiddleware());
        $this->assertContains('throttle:tenant-password', Route::getRoutes()->getByName('tenant.password.email')->gatherMiddleware());
        $this->assertContains('throttle:tenant-password', Route::getRoutes()->getByName('tenant.password.update')->gatherMiddleware());
    }

    private function tenantFixture(string $name, string $slug, array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'email' => "info@{$slug}.test",
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'theme_primary' => '#071E45',
            'theme_accent' => '#D79A21',
        ], $overrides));
    }

    private function staffFixture(Tenant $tenant, string $email, array $overrides = []): User
    {
        return User::create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Staff User',
            'staff_id' => 'STF' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ], $overrides));
    }

    private function rebuildSchema(): void
    {
        foreach (['audit_logs', 'school_settings', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('status')->default(Tenant::STATUS_PENDING);
            $table->date('subscription_expires_at')->nullable();
            $table->string('theme_primary', 20)->nullable();
            $table->string('theme_accent', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('staff_id')->nullable()->unique();
            $table->string('student_id')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('employment_status', 40)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
