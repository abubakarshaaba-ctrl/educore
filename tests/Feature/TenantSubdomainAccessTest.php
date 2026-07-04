<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantSubdomainAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant subdomain access tests require the isolated sqlite :memory: test database.');
        }

        config([
            'tenancy.central_hosts' => ['educore.test', 'localhost', '127.0.0.1'],
            'tenancy.local_base_domain' => 'educore.test',
            'tenancy.scheme' => 'http',
        ]);

        $this->rebuildSchema();
    }

    public function test_local_subdomain_landing_and_login_pages_load(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy');

        $this->get('http://bluerayy.educore.test/')
            ->assertOk()
            ->assertSee('Bluerayy Academy');

        $this->get('http://bluerayy.educore.test/login')
            ->assertOk()
            ->assertSee('Staff Login')
            ->assertSee('action="http://bluerayy.educore.test/login"', false)
            ->assertSee('href="http://bluerayy.educore.test/forgot-password"', false)
            ->assertDontSee('tenant_id');
    }

    public function test_valid_staff_can_login_through_local_subdomain(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy');
        $user = $this->staffFixture($tenant, 'admin@bluerayy.test');

        $this->post('http://bluerayy.educore.test/login', [
            'login_id' => 'admin@bluerayy.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($tenant->id, session('tenant_id'));
        $this->assertSame('bluerayy.educore.test', session('tenant_host'));
    }

    public function test_forgot_password_page_loads_on_local_subdomain(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy');

        $this->get('http://bluerayy.educore.test/forgot-password')
            ->assertOk()
            ->assertSee('Reset password')
            ->assertSee('action="http://bluerayy.educore.test/forgot-password"', false);
    }

    public function test_reset_password_page_uses_local_subdomain_actions(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy');

        $this->get('http://bluerayy.educore.test/reset-password/test-token?email=admin%40bluerayy.test')
            ->assertOk()
            ->assertSee('Set new password')
            ->assertSee('value="test-token"', false)
            ->assertSee('action="http://bluerayy.educore.test/reset-password"', false)
            ->assertSee('href="http://bluerayy.educore.test/login"', false);
    }

    public function test_host_based_admission_landing_uses_existing_admissions_flow(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy');
        $this->classLevel($tenant);

        $this->get('http://bluerayy.educore.test/apply')
            ->assertOk()
            ->assertSee('Bluerayy Academy');
    }

    public function test_unknown_subdomain_gets_generic_unavailable_page(): void
    {
        $this->get('http://missing.educore.test/')
            ->assertStatus(404)
            ->assertSee('This school portal is currently unavailable');
    }

    public function test_expired_tenant_host_login_is_unavailable_but_account_status_loads_for_authenticated_user(): void
    {
        $tenant = $this->tenantFixture('Expired School', 'expired', [
            'subscription_expires_at' => now()->subDay()->toDateString(),
        ]);
        $user = $this->staffFixture($tenant, 'admin@expired.test');

        $this->get('http://expired.educore.test/login')
            ->assertStatus(404)
            ->assertSee('This school portal is currently unavailable');

        $this->actingAs($user)
            ->get('http://expired.educore.test/account-status')
            ->assertOk()
            ->assertSee('Expired School');
    }

    public function test_host_resolution_is_audited_for_resolved_hosts(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy');

        $this->get('http://bluerayy.educore.test/')->assertOk();

        $this->assertSame(1, AuditLog::where('action', 'tenant.host.resolved')->count());
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

    private function classLevel(Tenant $tenant): void
    {
        \DB::table('class_levels')->insert([
            'tenant_id' => $tenant->id,
            'name' => 'JSS 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'admissions',
            'admission_portal_settings',
            'class_levels',
            'audit_logs',
            'school_settings',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createCoreTables();
        $this->createAdmissionsTables();
    }

    private function createCoreTables(): void
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
    }

    private function createAdmissionsTables(): void
    {
        Schema::create('class_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('admission_portal_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->boolean('is_open')->default(true);
            $table->date('opens_on')->nullable();
            $table->date('closes_on')->nullable();
            $table->string('academic_year')->nullable();
            $table->decimal('application_fee', 10, 2)->default(0);
            $table->text('welcome_message')->nullable();
            $table->timestamps();
        });
    }
}
