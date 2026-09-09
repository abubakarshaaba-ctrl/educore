<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebPlatformImpersonationSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform impersonation tests require sqlite :memory:.');
        }

        foreach (['audit_logs', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default(Tenant::STATUS_ACTIVE);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function test_super_admin_can_impersonate_only_active_school_admin_and_return_safely(): void
    {
        $tenant = $this->tenant('Impersonated School');
        $super = $this->user('Platform Operator', true, null, null);
        $admin = $this->user('School Administrator', false, $tenant->id, User::STAFF_STATUS_ACTIVE);

        $this->actingAs($super)
            ->post(route('super.impersonate', $tenant))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('super_admin_id', $super->id)
            ->assertSessionHas('impersonating_tenant_id', $tenant->id)
            ->assertSessionHas('impersonated_admin_id', $admin->id);

        $this->assertSame($admin->id, Auth::id());
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_user_id' => $super->id,
            'action' => 'platform.impersonation.started',
        ]);

        $this->post(route('super.stop-impersonating'))
            ->assertRedirect(route('super.dashboard'))
            ->assertSessionMissing('super_admin_id')
            ->assertSessionMissing('impersonating_tenant_id')
            ->assertSessionMissing('impersonated_admin_id');

        $this->assertSame($super->id, Auth::id());
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_user_id' => $super->id,
            'action' => 'platform.impersonation.stopped',
        ]);
    }

    public function test_inactive_school_admin_cannot_be_impersonated(): void
    {
        $tenant = $this->tenant('Inactive Admin School');
        $super = $this->user('Inactive Target Operator', true, null, null);
        $this->user('Inactive School Admin', false, $tenant->id, User::STAFF_STATUS_ACTIVE, false);

        $this->actingAs($super)
            ->post(route('super.impersonate', $tenant))
            ->assertSessionHasErrors('tenant');

        $this->assertSame($super->id, Auth::id());
        $this->assertDatabaseMissing('audit_logs', ['action' => 'platform.impersonation.started']);
    }

    public function test_nested_impersonation_is_rejected(): void
    {
        $tenant = $this->tenant('Nested School');
        $super = $this->user('Nested Operator', true, null, null);
        $this->user('Nested School Admin', false, $tenant->id, User::STAFF_STATUS_ACTIVE);

        $this->actingAs($super)
            ->withSession([
                'super_admin_id' => $super->id,
                'impersonating_tenant_id' => 999,
            ])
            ->post(route('super.impersonate', $tenant))
            ->assertSessionHasErrors('impersonation');

        $this->assertSame($super->id, Auth::id());
    }

    private function tenant(string $name): Tenant
    {
        return Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    }

    private function user(
        string $name,
        bool $super,
        ?int $tenantId,
        ?string $employmentStatus,
        bool $active = true,
    ): User {
        return User::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'role' => $super ? 'super_admin' : 'admin',
            'is_super_admin' => $super,
            'is_active' => $active,
            'employment_status' => $employmentStatus,
        ]);
    }
}
