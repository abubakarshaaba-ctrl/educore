<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantResetPasswordNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantPasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant password reset tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
    }

    public function test_forgot_password_sends_tenant_specific_reset_notification_to_eligible_staff(): void
    {
        Notification::fake();
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $user = $this->staffFixture($tenant, 'admin@bluerayy.test');

        $this->post('/school/bluerayy-academy/forgot-password', [
            'email' => 'admin@bluerayy.test',
        ])->assertSessionHas('status', 'If the account is eligible, a password reset link has been sent.');

        Notification::assertSentTo($user, TenantResetPasswordNotification::class, function ($notification) use ($tenant) {
            return $notification->tenant->is($tenant)
                && str_contains(route('tenant.password.reset', [
                    'slug' => $tenant->slug,
                    'token' => $notification->token,
                    'email' => 'admin@bluerayy.test',
                ]), '/school/bluerayy-academy/reset-password/');
        });

        $this->assertSame(1, AuditLog::where('action', 'auth.password_reset.requested')->count());
    }

    public function test_forgot_password_response_is_generic_for_missing_and_ineligible_accounts(): void
    {
        Notification::fake();
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->staffFixture($tenant, 'left@bluerayy.test', ['employment_status' => User::STAFF_STATUS_LEFT]);

        foreach (['missing@bluerayy.test', 'left@bluerayy.test'] as $email) {
            $this->post('/school/bluerayy-academy/forgot-password', [
                'email' => $email,
            ])->assertSessionHas('status', 'If the account is eligible, a password reset link has been sent.');
        }

        Notification::assertNothingSent();
        $this->assertSame(2, AuditLog::where('action', 'auth.password_reset.denied')->count());
    }

    public function test_inactive_tenant_cannot_use_password_reset_pages(): void
    {
        $this->tenantFixture('Suspended School', 'suspended-school', ['status' => Tenant::STATUS_SUSPENDED]);

        $this->get('/school/suspended-school/forgot-password')
            ->assertStatus(404)
            ->assertSee('This school portal is currently unavailable');

        $this->post('/school/suspended-school/forgot-password', [
            'email' => 'admin@suspended.test',
        ])->assertStatus(404);
    }

    public function test_reset_password_updates_password_and_deletes_token(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $user = $this->staffFixture($tenant, 'admin@bluerayy.test');
        $token = Password::broker()->createToken($user);

        $this->post('/school/bluerayy-academy/reset-password', [
            'token' => $token,
            'email' => 'admin@bluerayy.test',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('tenant.login', 'bluerayy-academy'));

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'admin@bluerayy.test']);
        $this->assertSame(1, AuditLog::where('action', 'auth.password_reset.completed')->count());
    }

    public function test_cross_tenant_reset_token_is_rejected(): void
    {
        $tenantA = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->tenantFixture('Greenfield School', 'greenfield-school');
        $user = $this->staffFixture($tenantA, 'admin@bluerayy.test');
        $token = Password::broker()->createToken($user);

        $this->post('/school/greenfield-school/reset-password', [
            'token' => $token,
            'email' => 'admin@bluerayy.test',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertSame(1, AuditLog::where('action', 'auth.password_reset.failed')->count());
    }

    public function test_reset_requires_valid_token_and_confirmed_password(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->staffFixture($tenant, 'admin@bluerayy.test');

        $this->post('/school/bluerayy-academy/reset-password', [
            'token' => 'bad-token',
            'email' => 'admin@bluerayy.test',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'Mismatch123!',
        ])->assertSessionHasErrors('password');

        $this->post('/school/bluerayy-academy/reset-password', [
            'token' => 'bad-token',
            'email' => 'admin@bluerayy.test',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_form_renders_with_email_and_token(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');

        $this->get('/school/bluerayy-academy/reset-password/test-token?email=admin%40bluerayy.test')
            ->assertOk()
            ->assertSee('admin@bluerayy.test')
            ->assertSee('test-token', false)
            ->assertDontSee('tenant_id');
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
        foreach (['audit_logs', 'password_reset_tokens', 'school_settings', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createTenantAndUserTables();

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });

        $this->createAuditLogsTable();
    }

    private function createTenantAndUserTables(): void
    {
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
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
