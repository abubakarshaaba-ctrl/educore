<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantResetPasswordNotification;
use App\Services\TenantHostResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantCustomDomainSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant custom domain tests require the isolated sqlite :memory: test database.');
        }

        config([
            'tenancy.central_hosts' => ['educore.test', 'localhost', '127.0.0.1'],
            'tenancy.local_base_domain' => 'educore.test',
            'tenancy.scheme' => 'http',
        ]);

        $this->rebuildSchema();
    }

    public function test_verified_custom_domain_loads_tenant_landing(): void
    {
        $this->tenantFixture('Custom School', 'custom-school', [
            'custom_domain' => 'school.local.test',
            'domain_verified' => true,
        ]);

        // Per-tenant landing pages were retired in favour of the unified
        // /login (see routes/web.php's tenant.host.custom. domain group) —
        // a resolved custom domain now redirects straight there.
        $this->get('http://school.local.test/')->assertRedirect();
    }

    public function test_unverified_custom_domain_is_not_resolved(): void
    {
        $this->tenantFixture('Unsafe School', 'unsafe-school', [
            'custom_domain' => 'unsafe.local.test',
            'domain_verified' => false,
        ]);

        $this->get('http://unsafe.local.test/')
            ->assertStatus(404)
            ->assertSee('This school portal is currently unavailable');
    }

    public function test_tenant_user_cannot_use_another_tenant_host_for_authenticated_routes(): void
    {
        $tenantA = $this->tenantFixture('Tenant A', 'tenant-a');
        $tenantB = $this->tenantFixture('Tenant B', 'tenant-b');
        $user = $this->staffFixture($tenantA, 'admin@tenant-a.test');

        $this->actingAs($user)
            ->get('http://tenant-b.educore.test/account-status')
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSame(1, AuditLog::where('action', 'tenant.host.auth_mismatch')->count());
    }

    public function test_host_password_reset_notification_uses_current_tenant_host(): void
    {
        Notification::fake();

        $tenant = $this->tenantFixture('Custom School', 'custom-school', [
            'custom_domain' => 'school.local.test',
            'domain_verified' => true,
        ]);
        $user = $this->staffFixture($tenant, 'admin@school.test');

        $this->post('http://school.local.test/forgot-password', [
            'email' => 'admin@school.test',
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, TenantResetPasswordNotification::class, function ($notification) {
            return $notification->baseUrl === 'http://school.local.test';
        });
    }

    public function test_custom_domain_validation_rejects_central_and_local_subdomain_hosts(): void
    {
        $resolver = app(TenantHostResolver::class);

        $this->assertNull($resolver->validateCustomDomain('educore.test'));
        $this->assertNull($resolver->validateCustomDomain('bluerayy.educore.test'));
        $this->assertSame('school.local.test', $resolver->validateCustomDomain('SCHOOL.local.test.'));
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

    private function staffFixture(Tenant $tenant, string $email): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Staff User',
            'staff_id' => 'STF' . random_int(1000, 9999),
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function rebuildSchema(): void
    {
        foreach (['password_reset_tokens', 'audit_logs', 'school_settings', 'users', 'tenants'] as $table) {
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
            $table->string('custom_domain')->nullable();
            $table->boolean('domain_verified')->default(false);
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

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
}
