<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuperAdminTenantEditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Super Admin tenant edit tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
    }

    public function test_super_admin_can_open_edit_form_from_list_and_detail(): void
    {
        $tenant = $this->tenantFixture('Blue Rayy Academy', 'blue-rayy-academy');
        $this->tenantAdmin($tenant);

        $this->actingAs($this->superAdmin())
            ->get(route('super.tenants'))
            ->assertOk()
            ->assertSee(route('super.tenant.edit', $tenant), false);

        $this->get(route('super.tenant.show', $tenant))
            ->assertOk()
            ->assertSee('Edit School')
            ->assertSee(route('super.tenant.edit', $tenant), false);

        $this->get(route('super.tenant.edit', $tenant))
            ->assertOk()
            ->assertSee('Edit School')
            ->assertDontSee('name="tenant_id"', false);
    }

    public function test_super_admin_updates_tenant_details_and_sees_updated_urls(): void
    {
        $tenant = $this->tenantFixture('Blue Rayy Academy', 'blue-rayy-academy', [
            'subdomain' => 'bluerayy',
            'custom_domain' => 'old.local.test',
            'domain_verified' => true,
        ]);

        $this->actingAs($this->superAdmin())
            ->patch(route('super.tenant.update', $tenant), $this->payload([
                'name' => 'Nova Academy',
                'slug' => 'Nova Academy',
                'subdomain' => 'nova-school',
                'email' => 'info@nova.test',
                'custom_domain' => 'new.local.test',
                'theme_primary' => '#112233',
            ]))
            ->assertRedirect(route('super.tenant.edit', $tenant));

        $tenant->refresh();

        $this->assertSame('Nova Academy', $tenant->name);
        $this->assertSame('nova-academy', $tenant->slug);
        $this->assertSame('nova-school', $tenant->subdomain);
        $this->assertSame('info@nova.test', $tenant->email);
        $this->assertSame('new.local.test', $tenant->custom_domain);
        $this->assertFalse((bool) $tenant->domain_verified);
        $this->assertSame('#112233', $tenant->theme_primary);

        $audit = AuditLog::where('action', 'tenant.updated')->firstOrFail();
        $this->assertSame($tenant->id, $audit->tenant_id);
        $this->assertSame('blue-rayy-academy', $audit->old_values['slug']);
        $this->assertSame('nova-academy', $audit->new_values['slug']);
        $this->assertArrayNotHasKey('tenant_id', $audit->new_values);

        // The old /school/{slug} path was retired — the edit page's "School
        // Login" URL is now the tenant's own (slug-based) subdomain host.
        $this->get(route('super.tenant.edit', $tenant))
            ->assertOk()
            ->assertSee('nova-academy.educore.test/login', false)
            ->assertSee('nova-school.educore.test/login', false);
    }

    public function test_validation_blocks_reserved_and_duplicate_ownership_values(): void
    {
        $tenant = $this->tenantFixture('Blue Rayy Academy', 'blue-rayy-academy', [
            'subdomain' => 'bluerayy',
            'custom_domain' => 'blue.local.test',
        ]);
        $other = $this->tenantFixture('Green School', 'green-school', [
            'subdomain' => 'green',
            'custom_domain' => 'green.local.test',
        ]);

        $super = $this->superAdmin();

        $this->actingAs($super)->patch(route('super.tenant.update', $tenant), $this->payload(['slug' => 'super']))
            ->assertSessionHasErrors('slug');

        $this->actingAs($super)->patch(route('super.tenant.update', $tenant), $this->payload(['slug' => $other->slug]))
            ->assertSessionHasErrors('slug');

        $this->actingAs($super)->patch(route('super.tenant.update', $tenant), $this->payload(['subdomain' => $other->subdomain]))
            ->assertSessionHasErrors('subdomain');

        $this->actingAs($super)->patch(route('super.tenant.update', $tenant), $this->payload(['custom_domain' => $other->custom_domain]))
            ->assertSessionHasErrors('custom_domain');

        $this->actingAs($super)->patch(route('super.tenant.update', $tenant), $this->payload(['custom_domain' => 'blue-rayy-academy.educore.test']))
            ->assertSessionHasErrors('custom_domain');
    }

    public function test_non_super_admin_cannot_edit_or_update_tenant(): void
    {
        $tenant = $this->tenantFixture('Blue Rayy Academy', 'blue-rayy-academy');
        $admin = $this->tenantAdmin($tenant);

        $this->actingAs($admin)
            ->get(route('super.tenant.edit', $tenant))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('super.tenant.update', $tenant), $this->payload(['name' => 'Blocked Update']))
            ->assertForbidden();
    }

    public function test_submitted_tenant_id_and_primary_key_are_ignored(): void
    {
        $tenant = $this->tenantFixture('Blue Rayy Academy', 'blue-rayy-academy');
        $other = $this->tenantFixture('Green School', 'green-school');

        $this->actingAs($this->superAdmin())
            ->patch(route('super.tenant.update', $tenant), $this->payload([
                'id' => $other->id,
                'tenant_id' => $other->id,
                'name' => 'Safe Rename',
                'slug' => 'safe-rename',
            ]))
            ->assertRedirect(route('super.tenant.edit', $tenant));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Safe Rename',
            'slug' => 'safe-rename',
        ]);
        $this->assertDatabaseHas('tenants', [
            'id' => $other->id,
            'name' => 'Green School',
            'slug' => 'green-school',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Blue Rayy Academy',
            'slug' => 'blue-rayy-academy',
            'subdomain' => 'bluerayy',
            'email' => 'info@bluerayy.test',
            'phone' => '08000000000',
            'address' => '1 School Road',
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'motto' => 'Learn and lead',
            'logo_path' => 'storage/logos/bluerayy.png',
            'theme_primary' => '#071E45',
            'theme_accent' => '#D79A21',
            'theme_sidebar' => '#071E45',
            'primary_color' => '#071E45',
            'secondary_color' => '#D79A21',
            'custom_domain' => null,
        ], $overrides);
    }

    private function tenantFixture(string $name, string $slug, array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'subdomain' => null,
            'email' => "info@{$slug}.test",
            'phone' => '08000000000',
            'address' => '1 School Road',
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'theme_primary' => '#071E45',
            'theme_accent' => '#D79A21',
            'theme_sidebar' => '#071E45',
            'domain_verified' => false,
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

    private function tenantAdmin(Tenant $tenant): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'School Admin',
            'email' => 'admin' . $tenant->id . '@school.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'audit_logs',
            'platform_payments',
            'tenant_subscriptions',
            'subscription_plans',
            'students',
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

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('annual_price', 10, 2)->default(0);
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
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('confirmed');
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
