<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformGroupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform group tests require sqlite :memory:.');
        }

        foreach (['audit_logs', 'school_group_members', 'school_groups', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('slug')->unique();
            $table->string('status')->default(Tenant::STATUS_ACTIVE); $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->string('name');
            $table->string('email')->nullable()->unique(); $table->string('password')->nullable(); $table->string('role')->nullable();
            $table->boolean('is_super_admin')->default(false); $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name'); $table->string('token', 64)->unique();
            $table->string('device')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        Schema::create('school_groups', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('slug')->nullable(); $table->text('description')->nullable();
            $table->string('owner_name')->nullable(); $table->string('owner_email')->nullable(); $table->timestamps();
        });
        Schema::create('school_group_members', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('group_id'); $table->unsignedBigInteger('tenant_id');
            $table->string('role')->default('member'); $table->timestamps();
            $table->unique('tenant_id');
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->string('action');
            $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->text('reason')->nullable();
            $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->timestamps();
        });
    }

    public function test_non_super_admin_cannot_mutate_school_groups(): void
    {
        $tenant = $this->tenant('Restricted School');
        $admin = $this->user('Restricted Admin', false, $tenant->id);

        $this->withToken(ApiToken::issue($admin, 'group-deny'))
            ->postJson('/api/v1/platform/groups', ['name' => 'Forbidden Group'])
            ->assertForbidden();
    }

    public function test_school_can_belong_to_only_one_group(): void
    {
        $super = $this->user('Group Operator', true, null);
        $token = ApiToken::issue($super, 'group-operator');
        $school = $this->tenant('Single Membership School');

        $first = $this->withToken($token)->postJson('/api/v1/platform/groups', ['name' => 'First Group'])
            ->assertCreated()->json('id');
        $second = $this->withToken($token)->postJson('/api/v1/platform/groups', ['name' => 'Second Group'])
            ->assertCreated()->json('id');

        $this->withToken($token)->postJson("/api/v1/platform/groups/{$first}/members", [
            'tenant_id' => $school->id,
            'role' => 'lead',
        ])->assertOk();

        $this->withToken($token)->postJson("/api/v1/platform/groups/{$second}/members", [
            'tenant_id' => $school->id,
            'role' => 'member',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('school_group_members', 1);
    }

    public function test_lead_campus_is_protected_until_another_member_becomes_lead(): void
    {
        $super = $this->user('Lead Operator', true, null);
        $token = ApiToken::issue($super, 'lead-operator');
        $lead = $this->tenant('Lead Campus');
        $member = $this->tenant('Member Campus');
        $group = $this->withToken($token)->postJson('/api/v1/platform/groups', ['name' => 'Protected Group'])
            ->assertCreated()->json('id');

        $this->withToken($token)->postJson("/api/v1/platform/groups/{$group}/members", ['tenant_id' => $lead->id, 'role' => 'lead'])->assertOk();
        $this->withToken($token)->postJson("/api/v1/platform/groups/{$group}/members", ['tenant_id' => $member->id, 'role' => 'member'])->assertOk();

        $this->withToken($token)->deleteJson("/api/v1/platform/groups/{$group}/members/{$lead->id}")
            ->assertUnprocessable();

        $this->withToken($token)->postJson("/api/v1/platform/groups/{$group}/members/{$member->id}/lead")
            ->assertOk();
        $this->assertDatabaseHas('school_group_members', ['group_id' => $group, 'tenant_id' => $member->id, 'role' => 'lead']);
        $this->assertDatabaseHas('school_group_members', ['group_id' => $group, 'tenant_id' => $lead->id, 'role' => 'member']);

        $this->withToken($token)->deleteJson("/api/v1/platform/groups/{$group}/members/{$lead->id}")
            ->assertOk();
        $this->assertDatabaseMissing('school_group_members', ['group_id' => $group, 'tenant_id' => $lead->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'platform.group.lead_changed', 'auditable_id' => $group]);
    }

    private function tenant(string $name): Tenant
    {
        return Tenant::create(['name' => $name, 'slug' => str($name)->slug().'-'.uniqid(), 'status' => Tenant::STATUS_ACTIVE]);
    }

    private function user(string $name, bool $super, ?int $tenantId): User
    {
        return User::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'role' => $super ? 'super_admin' : 'admin',
            'is_super_admin' => $super,
            'is_active' => true,
            'employment_status' => $super ? null : User::STAFF_STATUS_ACTIVE,
        ]);
    }
}
