<?php

namespace Tests\Feature;

use App\Http\Controllers\AgentController;
use App\Models\AuditLog;
use App\Models\ApiToken;
use App\Models\PlatformSetting;
use App\Models\PlatformAgent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

    public function test_super_admin_boundary_is_enforced_at_the_route_layer(): void
    {
        $middleware = app('router')->getRoutes()->getByName('super.settings')->gatherMiddleware();

        $this->assertContains('super.admin', $middleware);

        $tenant = $this->tenantFixture('Boundary School', 'boundary-school');
        $this->actingAs($this->tenantAdmin($tenant))
            ->post(route('super.settings.save'), $this->settingsPayload())
            ->assertForbidden();
    }

    public function test_platform_settings_use_a_typed_allowlist_and_current_operations_values(): void
    {
        $payload = $this->settingsPayload();
        $payload['settings']['unexpected_key'] = 'must-not-be-stored';

        $this->actingAs($this->superAdmin())
            ->post(route('super.settings.save'), $payload)
            ->assertSessionHasErrors('settings');

        $this->assertDatabaseMissing('platform_settings', ['key' => 'unexpected_key']);

        $this->post(route('super.settings.save'), $this->settingsPayload())
            ->assertRedirect();

        $this->assertSame('EduCore', PlatformSetting::valueFor('platform_name'));
        $this->assertSame('support@educoreng.online', PlatformSetting::valueFor('support_email'));
        $this->assertSame('Abuja, FCT, Nigeria', PlatformSetting::valueFor('office_address'));
        $this->assertSame(7, PlatformSetting::valueFor('grace_period_days'));
    }

    public function test_super_admin_can_activate_an_incomplete_tenant_with_an_audited_override(): void
    {
        $tenant = $this->tenantFixture('Pending School', 'pending-school', [
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
        $this->tenantAdmin($tenant);

        $this->actingAs($this->superAdmin())
            ->patch(route('super.tenant.toggle', $tenant), [
                'status' => Tenant::STATUS_ACTIVE,
                'reason' => 'Platform operations activation.',
            ])
            ->assertRedirect();

        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'tenant.status_changed',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'tenant.onboarding.activation_overridden',
        ]);
    }

    public function test_verified_tenant_removal_is_recoverable_and_revokes_mobile_access(): void
    {
        $tenant = $this->tenantFixture('Remove Me Academy', 'remove-me-academy');
        $admin = $this->tenantAdmin($tenant);
        ApiToken::create([
            'user_id' => $admin->id,
            'name' => 'mobile',
            'token' => hash('sha256', 'remove-token'),
            'expires_at' => now()->addMonth(),
        ]);
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->delete(route('super.tenant.destroy', $tenant), [
                'confirmation' => $tenant->name,
                'current_password' => 'wrong-password',
                'reason' => 'Requested account closure.',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertNull($tenant->fresh()->deleted_at);

        $this->delete(route('super.tenant.destroy', $tenant), [
            'confirmation' => $tenant->name,
            'current_password' => 'password',
            'reason' => 'Requested account closure.',
        ])->assertRedirect(route('super.tenants'));

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => false]);
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'tenant.removed']);
    }

    public function test_current_platform_operations_pages_render_without_database_specific_sql(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->get(route('super.analytics'))
            ->assertOk()
            ->assertSee('Platform Analytics')
            ->assertSee('Active Schools');

        $this->get(route('super.support'))
            ->assertOk()
            ->assertSee('Support Inbox');

        $this->get(route('super.broadcasts'))
            ->assertOk()
            ->assertSee('Broadcasts to Schools');
    }

    public function test_agent_commission_approval_never_claims_an_unverified_bank_payout(): void
    {
        $agent = PlatformAgent::create([
            'name' => 'Verified Agent',
            'email' => 'agent@educore.test',
            'commission_rate' => 10,
            'is_active' => true,
            'referral_code' => 'AGENT001',
            'bank_name' => 'Test Bank',
            'bank_account_number' => '0123456789',
        ]);
        $tenant = $this->tenantFixture('Referred School', 'referred-school');
        DB::table('tenants')->where('id', $tenant->id)->update(['referred_by_agent_id' => $agent->id]);

        PlatformSetting::setValue('agent_programme_settings', [
            'auto_approve' => true,
            'bonus_threshold' => 5,
            'bonus_amount' => 15,
        ], 'json', 'agents', 'Agent Programme Settings');

        AgentController::recordReferralCommission($tenant->id, 1000);

        $this->assertDatabaseHas('agent_referrals', [
            'agent_id' => $agent->id,
            'tenant_id' => $tenant->id,
            'status' => 'approved',
            'commission_amount' => 100,
        ]);
        $this->assertSame(100.0, $agent->fresh()->total_earned);
        $this->assertSame(0.0, $agent->fresh()->total_paid);
        $this->assertDatabaseCount('agent_payouts', 0);
    }

    public function test_tenant_payment_callbacks_cannot_read_or_credit_another_school_invoice(): void
    {
        $owner = $this->tenantFixture('Invoice Owner', 'invoice-owner');
        $attacker = $this->tenantFixture('Other School', 'other-school');
        DB::table('platform_invoices')->insert([
            'tenant_id' => $owner->id,
            'invoice_number' => 'INV-SECURITY-1',
            'amount' => 30000,
            'status' => 'pending',
            'billing_cycle' => 'termly',
            'payment_reference' => 'SECURE-REFERENCE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->tenantAdmin($attacker))
            ->get(route('super.billing.pay.callback', ['reference' => 'SECURE-REFERENCE']))
            ->assertForbidden();

        $this->get(route('super.billing.pay.monnify.callback', ['reference' => 'SECURE-REFERENCE']))
            ->assertForbidden();
    }

    private function settingsPayload(): array
    {
        return [
            'settings' => [
                'platform_name' => 'EduCore',
                'support_email' => 'support@educoreng.online',
                'support_phone' => '07065595768',
                'support_whatsapp' => '+2347065595768',
                'support_website' => 'https://educoreng.online',
                'office_address' => 'Abuja, FCT, Nigeria',
                'trial_days' => 30,
                'grace_period_days' => 7,
                'default_sms_gateway' => 'termii',
                'sms_sender_id' => 'EduCore',
                'maintenance_mode' => false,
            ],
        ];
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
            'api_tokens',
            'agent_payouts',
            'agent_referrals',
            'platform_agents',
            'platform_broadcast_dismissals',
            'platform_broadcasts',
            'platform_support_tickets',
            'platform_settings',
            'platform_invoices',
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
            $table->unsignedBigInteger('referred_by_agent_id')->nullable();
            $table->string('referral_code_used', 20)->nullable();
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
            $table->timestamp('status_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('status')->default('active');
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
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('reference')->unique();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('confirmed');
            $table->string('payment_method')->nullable();
            $table->string('payment_channel')->nullable();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->string('billing_cycle')->default('termly');
            $table->unsignedInteger('student_count')->nullable();
            $table->date('due_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_ref')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
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

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name')->default('mobile');
            $table->string('token', 64)->unique();
            $table->string('device')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('open');
            $table->text('admin_reply')->nullable();
            $table->unsignedBigInteger('replied_by')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('target')->default('all');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_broadcast_dismissals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('broadcast_id');
            $table->unsignedBigInteger('tenant_id');
            $table->timestamp('dismissed_at');
            $table->unique(['broadcast_id', 'tenant_id']);
        });

        Schema::create('platform_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10);
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('referral_code', 20)->unique();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 20)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->decimal('sale_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->date('sale_date')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('status')->default('paid');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamps();
        });
    }
}
