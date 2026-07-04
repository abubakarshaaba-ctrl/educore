<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\TenantAccessDecision;
use App\Services\TenantAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantSubscriptionStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant subscription state tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
    }

    public function test_active_tenant_is_allowed(): void
    {
        $decision = $this->service()->applicationAccess($this->tenantFixture());

        $this->assertTrue($decision->allowed);
        $this->assertSame(TenantAccessDecision::STATE_ALLOWED, $decision->state);
    }

    public function test_pending_and_suspended_tenants_are_denied(): void
    {
        $pending = $this->service()->applicationAccess($this->tenantFixture([
            'status' => Tenant::STATUS_PENDING,
        ]));
        $suspended = $this->service()->applicationAccess($this->tenantFixture([
            'slug' => 'suspended-school',
            'status' => Tenant::STATUS_SUSPENDED,
        ]));

        $this->assertTrue($pending->isDenied());
        $this->assertSame(TenantAccessDecision::STATE_INACTIVE, $pending->state);
        $this->assertTrue($suspended->isDenied());
        $this->assertSame(TenantAccessDecision::STATE_SUSPENDED, $suspended->state);
    }

    public function test_subscription_expired_status_is_denied(): void
    {
        $decision = $this->service()->applicationAccess($this->tenantFixture([
            'status' => Tenant::STATUS_SUBSCRIPTION_EXPIRED,
        ]));

        $this->assertTrue($decision->isDenied());
        $this->assertSame(TenantAccessDecision::STATE_EXPIRED, $decision->state);
    }

    public function test_past_expiry_is_denied_without_grace(): void
    {
        $decision = $this->service()->applicationAccess($this->tenantFixture([
            'subscription_expires_at' => now()->subDays(2)->toDateString(),
        ]));

        $this->assertTrue($decision->isDenied());
        $this->assertSame(TenantAccessDecision::STATE_EXPIRED, $decision->state);
    }

    public function test_past_expiry_inside_configured_grace_period_is_allowed_with_warning(): void
    {
        DB::table('platform_settings')->insert([
            'key' => 'grace_period_days',
            'value' => '7',
            'type' => 'integer',
            'group' => 'billing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $decision = $this->service()->applicationAccess($this->tenantFixture([
            'subscription_expires_at' => now()->subDays(2)->toDateString(),
        ]));

        $this->assertTrue($decision->allowed);
        $this->assertTrue($decision->isWarning());
        $this->assertSame(TenantAccessDecision::STATE_GRACE, $decision->state);
    }

    public function test_trial_subscription_is_allowed_with_warning(): void
    {
        $tenant = $this->tenantFixture();
        TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'status' => 'trial',
            'billing_cycle' => 'annual',
            'amount_paid' => 0,
            'starts_at' => now()->subDay()->toDateString(),
            'expires_at' => now()->addDays(10)->toDateString(),
        ]);

        $decision = $this->service()->applicationAccess($tenant);

        $this->assertTrue($decision->allowed);
        $this->assertTrue($decision->isWarning());
        $this->assertSame(TenantAccessDecision::STATE_TRIAL, $decision->state);
    }

    public function test_expiring_soon_subscription_is_allowed_with_warning(): void
    {
        $decision = $this->service()->applicationAccess($this->tenantFixture([
            'subscription_expires_at' => now()->addDays(5)->toDateString(),
        ]));

        $this->assertTrue($decision->allowed);
        $this->assertTrue($decision->isWarning());
        $this->assertSame(TenantAccessDecision::STATE_EXPIRING_SOON, $decision->state);
    }

    public function test_public_admission_rule_remains_the_tenant_model_rule(): void
    {
        $active = $this->tenantFixture();
        $expired = $this->tenantFixture([
            'slug' => 'expired-school',
            'subscription_expires_at' => now()->subDay()->toDateString(),
        ]);

        $this->assertTrue($active->isPublicPortalAvailable());
        $this->assertFalse($expired->isPublicPortalAvailable());
    }

    private function service(): TenantAccessService
    {
        return app(TenantAccessService::class);
    }

    private function tenantFixture(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Bluerayy Academy',
            'slug' => 'bluerayy-academy',
            'email' => 'info@bluerayy.test',
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
        ], $overrides));
    }

    private function rebuildSchema(): void
    {
        foreach (['tenant_subscriptions', 'platform_settings', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('status')->default(Tenant::STATUS_PENDING);
            $table->date('subscription_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('status')->default('trial');
            $table->string('billing_cycle')->default('annual');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('starts_at');
            $table->date('expires_at');
            $table->date('next_billing_date')->nullable();
            $table->timestamps();
        });

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
