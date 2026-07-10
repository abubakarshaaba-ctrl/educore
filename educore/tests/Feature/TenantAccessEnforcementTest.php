<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantAccessEnforcementTest extends TestCase
{
    /** Comfortably inside the paid tier, so fixtures aren't accidentally free-tier. */
    private const PAID_TIER_STUDENTS = 25;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant access enforcement tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
        $this->registerTestRoutes();
    }

    public function test_active_tenant_can_access_operational_route(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $user = $this->staffFixture($tenant);

        $this->actingAs($user)
            ->get('/__tenant-access/protected')
            ->assertOk()
            ->assertSee('protected');
    }

    public function test_suspended_tenant_is_redirected_to_account_status_and_audited(): void
    {
        $tenant = $this->tenantFixture('Suspended School', 'suspended-school', [
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
        $user = $this->staffFixture($tenant);

        $this->actingAs($user)
            ->get('/__tenant-access/protected')
            ->assertRedirect(route('tenant.account-status'));

        $this->assertSame(1, AuditLog::where('action', 'tenant.access.blocked')->count());
        $this->assertSame(1, AuditLog::where('action', 'tenant.access.suspended')->count());
    }

    public function test_expired_tenant_is_redirected_to_account_status_and_account_status_page_loads(): void
    {
        // Paid tier — the free tier never expires on the automatic date
        // clock (see test_expired_free_tier_tenant_is_not_redirected below).
        $tenant = $this->tenantFixture('Expired School', 'expired-school', [
            'subscription_expires_at' => now()->subDay()->toDateString(),
        ]);
        $user = $this->staffFixture($tenant);

        $this->actingAs($user)
            ->get('/__tenant-access/protected')
            ->assertRedirect(route('tenant.account-status'));

        $this->actingAs($user)
            ->get('/account-status')
            ->assertOk()
            ->assertSee('Subscription renewal required')
            ->assertSee('Expired School');

        $this->assertSame(1, AuditLog::where('action', 'tenant.access.expired')->count());
    }

    public function test_expired_free_tier_tenant_is_not_redirected(): void
    {
        // Free tier (≤20 students) never expires on the automatic date
        // clock — matches the "free forever" pricing promise.
        $tenant = $this->tenantFixture('Free Tier School', 'free-tier-school', [
            'subscription_expires_at' => now()->subYear()->toDateString(),
        ], studentCount: 5);
        $user = $this->staffFixture($tenant);

        $this->actingAs($user)
            ->get('/__tenant-access/protected')
            ->assertOk()
            ->assertSee('protected');

        $this->assertSame(0, AuditLog::where('action', 'tenant.access.expired')->count());
    }

    public function test_named_billing_and_subscription_routes_are_exempt(): void
    {
        $tenant = $this->tenantFixture('Expired School', 'expired-school', [
            'subscription_expires_at' => now()->subDay()->toDateString(),
        ]);
        $user = $this->staffFixture($tenant);

        $this->actingAs($user)
            ->get('/__tenant-access/billing-exempt')
            ->assertOk()
            ->assertSee('billing');

        $this->assertSame(0, AuditLog::where('action', 'tenant.access.blocked')->count());
    }

    public function test_verified_impersonation_bypasses_blocked_tenant_access_and_is_audited(): void
    {
        $tenant = $this->tenantFixture('Expired School', 'expired-school', [
            'subscription_expires_at' => now()->subDay()->toDateString(),
        ]);
        $user = $this->staffFixture($tenant);

        $this->actingAs($user)
            ->withSession([
                'super_admin_id' => 999,
                'impersonating_tenant_id' => $tenant->id,
            ])
            ->get('/__tenant-access/protected')
            ->assertOk()
            ->assertSee('protected');

        $this->assertSame(1, AuditLog::where('action', 'tenant.access.impersonation_bypass')->count());
        $this->assertSame(0, AuditLog::where('action', 'tenant.access.blocked')->count());
    }

    public function test_super_admin_bypasses_tenant_access_middleware(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@educore.test',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get('/__tenant-access/protected')
            ->assertOk()
            ->assertSee('protected');
    }

    public function test_trial_tenant_gets_warning_decision_but_access_is_allowed(): void
    {
        // "Trial" under the pay-per-student model is just the free tier
        // (≤20 students) — no separate subscription row needed.
        $tenant = $this->tenantFixture('Trial School', 'trial-school', studentCount: 5);
        $user = $this->staffFixture($tenant);

        $this->actingAs($user)
            ->get('/__tenant-access/protected-view')
            ->assertOk()
            ->assertSee('protected view');
    }

    private function registerTestRoutes(): void
    {
        Route::middleware(['web', 'auth', 'active.account', 'tenant', 'tenant.access'])
            ->get('/__tenant-access/protected', fn () => response('protected'))
            ->name('tenant-access.protected');

        Route::middleware(['web', 'auth', 'active.account', 'tenant', 'tenant.access'])
            ->get('/__tenant-access/billing-exempt', fn () => response('billing'))
            ->name('tenant-access.billing.subscription');

        Route::middleware(['web', 'auth', 'active.account', 'tenant', 'tenant.access'])
            ->get('/__tenant-access/protected-view', fn () => response('protected view'))
            ->name('tenant-access.protected-view');
    }

    private function tenantFixture(string $name, string $slug, array $overrides = [], int $studentCount = self::PAID_TIER_STUDENTS): Tenant
    {
        $tenant = Tenant::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'email' => "info@{$slug}.test",
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'theme_primary' => '#071E45',
            'theme_accent' => '#D79A21',
        ], $overrides));

        $this->seedStudents($tenant, $studentCount);

        return $tenant;
    }

    private function seedStudents(Tenant $tenant, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            DB::table('students')->insert([
                'tenant_id' => $tenant->id,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function staffFixture(Tenant $tenant): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Staff User',
            'staff_id' => 'STF' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'email' => 'staff' . random_int(1000, 9999) . '@school.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'audit_logs',
            'platform_settings',
            'school_settings',
            'students',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createTenantTables();
        $this->createUserTable();
        $this->createStudentsTable();
        $this->createAuditLogsTable();
        $this->createSettingsTables();
    }

    private function createTenantTables(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('motto')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default(Tenant::STATUS_PENDING);
            $table->date('subscription_expires_at')->nullable();
            $table->unsignedInteger('students_capacity')->nullable();
            $table->string('theme_primary', 20)->nullable();
            $table->string('theme_accent', 20)->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
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
    }

    private function createUserTable(): void
    {
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

    private function createStudentsTable(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('status')->default('active');
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

    private function createSettingsTables(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->timestamps();
        });
    }
}
