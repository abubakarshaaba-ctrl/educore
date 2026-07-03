<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TenantSlugLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant slug login tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
    }

    public function test_active_tenant_landing_page_loads_with_branding(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy', [
            'theme_primary' => '#123456',
            'theme_accent' => '#ABCDEF',
        ]);
        $this->setting($tenant, 'motto', 'Discipline and Excellence');

        $this->get('/school/bluerayy-academy')
            ->assertOk()
            ->assertSee('Bluerayy Academy')
            ->assertSee('Discipline and Excellence')
            ->assertSee('#123456', false)
            ->assertSee('#ABCDEF', false);
    }

    public function test_active_tenant_branded_login_page_loads(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');

        $this->get('/school/bluerayy-academy/login')
            ->assertOk()
            ->assertSee('Staff Login')
            ->assertDontSee('tenant_id');
    }

    public function test_another_tenants_branding_does_not_appear(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->tenantFixture('Greenfield School', 'greenfield-school');

        $this->get('/school/bluerayy-academy')
            ->assertOk()
            ->assertSee('Bluerayy Academy')
            ->assertDontSee('Greenfield School');
    }

    public function test_unknown_inactive_and_expired_tenants_get_generic_unavailable_page(): void
    {
        $this->tenantFixture('Suspended School', 'suspended-school', ['status' => Tenant::STATUS_SUSPENDED]);
        $this->tenantFixture('Expired School', 'expired-school', [
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->subDay()->toDateString(),
        ]);

        foreach (['missing-school', 'suspended-school', 'expired-school'] as $slug) {
            $this->get("/school/{$slug}")
                ->assertStatus(404)
                ->assertSee('This school portal is currently unavailable')
                ->assertDontSee('suspended')
                ->assertDontSee('expired');
        }
    }

    public function test_valid_tenant_staff_can_login_through_matching_slug(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $user = $this->staffFixture($tenant, 'admin@bluerayy.test');

        $this->post('/school/bluerayy-academy/login', [
            'login_id' => 'admin@bluerayy.test',
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($tenant->id, session('tenant_id'));
        $this->assertSame($tenant->slug, session('tenant_slug'));
    }

    public function test_tenant_user_cannot_login_through_another_tenant_slug(): void
    {
        $tenantA = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->tenantFixture('Greenfield School', 'greenfield-school');
        $this->staffFixture($tenantA, 'admin@bluerayy.test');

        $this->post('/school/greenfield-school/login', [
            'login_id' => 'admin@bluerayy.test',
            'password' => 'password',
        ])
            ->assertSessionHasErrors('login_id');

        $this->assertGuest();
    }

    public function test_invalid_password_inactive_user_and_inactive_employment_are_denied_generically(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->staffFixture($tenant, 'active@bluerayy.test');
        $this->staffFixture($tenant, 'inactive@bluerayy.test', ['is_active' => false]);
        $this->staffFixture($tenant, 'left@bluerayy.test', ['employment_status' => User::STAFF_STATUS_LEFT]);

        foreach ([
            ['active@bluerayy.test', 'wrong'],
            ['inactive@bluerayy.test', 'password'],
            ['left@bluerayy.test', 'password'],
        ] as [$email, $password]) {
            $this->post('/school/bluerayy-academy/login', [
                'login_id' => $email,
                'password' => $password,
            ])
                ->assertSessionHasErrors('login_id')
                ->assertSessionDoesntHaveErrors(['password']);

            $this->assertGuest();
        }
    }

    public function test_parent_student_agent_and_super_admin_accounts_do_not_use_tenant_staff_login(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->userFixture($tenant, 'parent@bluerayy.test', 'parent');
        $this->userFixture($tenant, 'student@bluerayy.test', 'student');
        $this->userFixture($tenant, 'agent@bluerayy.test', 'agent');
        User::create([
            'name' => 'Super Admin',
            'email' => 'super@educore.test',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        foreach (['parent@bluerayy.test', 'student@bluerayy.test', 'agent@bluerayy.test', 'super@educore.test'] as $email) {
            $this->post('/school/bluerayy-academy/login', [
                'login_id' => $email,
                'password' => 'password',
            ])
                ->assertSessionHasErrors('login_id');

            $this->assertGuest();
        }
    }

    public function test_global_login_redirects_tenant_staff_and_remains_functional_for_super_admin(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->staffFixture($tenant, 'admin@bluerayy.test');

        $this->post('/login', [
            'login_id' => 'admin@bluerayy.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.login'));
        $this->assertGuest();

        $super = User::create([
            'name' => 'Super Admin',
            'email' => 'super@educore.test',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'login_id' => 'super@educore.test',
            'password' => 'password',
        ])->assertRedirect(route('super.dashboard'));
        $this->assertAuthenticatedAs($super);
    }

    public function test_parent_agent_and_public_admission_routes_remain_available(): void
    {
        $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');

        $this->get('/portal/login')->assertOk();
        $this->get('/agent/portal/login')->assertOk();
        $this->get('/apply/bluerayy-academy')->assertOk()->assertSee('Bluerayy Academy');
    }

    public function test_logout_clears_authenticated_user_and_tenant_context(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');
        $this->staffFixture($tenant, 'admin@bluerayy.test');

        $this->post('/school/bluerayy-academy/login', [
            'login_id' => 'admin@bluerayy.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(session('tenant_id'));
        $this->assertNull(session('tenant_slug'));
    }

    public function test_reserved_duplicate_and_normalized_slug_rules(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy');

        $this->assertSame('blue-rayy-academy', Tenant::normalizeSlug('Blue Rayy Academy'));
        $this->assertTrue(Validator::make(['slug' => 'super'], ['slug' => Tenant::slugRules()])->fails());
        $this->assertTrue(Validator::make(['slug' => $tenant->slug], ['slug' => Tenant::slugRules()])->fails());
        $this->assertFalse(Validator::make(['slug' => 'new-school'], ['slug' => Tenant::slugRules()])->fails());
    }

    public function test_branding_output_is_escaped_and_unsafe_colour_falls_back(): void
    {
        $tenant = $this->tenantFixture('<script>alert(1)</script>', 'script-school', [
            'theme_primary' => 'javascript:alert(1)',
            'theme_accent' => '#112233',
        ]);
        $this->setting($tenant, 'motto', '<img src=x onerror=alert(1)>');

        $this->get('/school/script-school')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('#071E45', false)
            ->assertSee('#112233', false);
    }

    public function test_branding_does_not_leak_between_tenants(): void
    {
        $tenantA = $this->tenantFixture('Bluerayy Academy', 'bluerayy-academy', ['theme_primary' => '#101010']);
        $tenantB = $this->tenantFixture('Greenfield School', 'greenfield-school', ['theme_primary' => '#202020']);
        $this->setting($tenantA, 'motto', 'Blue motto');
        $this->setting($tenantB, 'motto', 'Green motto');

        $this->get('/school/bluerayy-academy')
            ->assertOk()
            ->assertSee('Blue motto')
            ->assertDontSee('Green motto')
            ->assertSee('#101010', false)
            ->assertDontSee('#202020', false);

        $this->get('/school/greenfield-school')
            ->assertOk()
            ->assertSee('Green motto')
            ->assertDontSee('Blue motto')
            ->assertSee('#202020', false)
            ->assertDontSee('#101010', false);
    }

    public function test_route_middleware_and_impersonation_routes_remain_registered(): void
    {
        $submit = Route::getRoutes()->getByName('tenant.login.submit');
        $this->assertNotNull($submit);
        $this->assertContains('tenant.slug', $submit->gatherMiddleware());
        $this->assertContains('throttle:tenant-login', $submit->gatherMiddleware());
        $this->assertNotNull(Route::getRoutes()->getByName('super.impersonate'));
        $this->assertNotNull(Route::getRoutes()->getByName('super.stop-impersonating'));
    }

    private function tenantFixture(string $name, string $slug, array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'email' => "info@{$slug}.test",
            'phone' => '08000000000',
            'address' => '1 School Road',
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'theme_primary' => '#071E45',
            'theme_accent' => '#D79A21',
        ], $overrides));
    }

    private function staffFixture(Tenant $tenant, string $email, array $overrides = []): User
    {
        return $this->userFixture($tenant, $email, $overrides['role'] ?? 'admin', array_merge([
            'staff_id' => 'STF' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'employment_status' => User::STAFF_STATUS_ACTIVE,
            'is_active' => true,
        ], $overrides));
    }

    private function userFixture(Tenant $tenant, string $email, string $role, array $overrides = []): User
    {
        return User::create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => ucwords(str_replace(['@', '.', '-'], ' ', $email)),
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
            'is_super_admin' => false,
        ], $overrides));
    }

    private function setting(Tenant $tenant, string $key, string $value): void
    {
        \App\Models\SchoolSetting::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'key' => $key,
            'value' => $value,
            'group' => 'general',
        ]);
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'admissions',
            'admission_portal_settings',
            'class_levels',
            'school_settings',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

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
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
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
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('employment_status', 40)->nullable();
            $table->date('employment_started_at')->nullable();
            $table->date('employment_ended_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->text('exit_reason')->nullable();
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
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('class_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('section')->nullable();
            $table->integer('sort_order')->default(0);
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
            $table->text('requirements')->nullable();
            $table->boolean('require_passport')->default(true);
            $table->boolean('require_birth_cert')->default(true);
            $table->boolean('require_report_card')->default(false);
            $table->boolean('notify_guardian_sms')->default(true);
            $table->boolean('notify_guardian_email')->default(false);
            $table->boolean('auto_shortlist')->default(false);
            $table->text('footer_note')->nullable();
            $table->timestamps();
        });
    }
}
