<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\PlatformAgent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformAgentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform agent tests require sqlite :memory:.');
        }
        foreach (['audit_logs','agent_referrals','platform_agents','api_tokens','users','tenants'] as $table) Schema::dropIfExists($table);
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('status')->default(Tenant::STATUS_ACTIVE); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->string('name'); $table->string('email')->nullable()->unique();
            $table->string('password')->nullable(); $table->string('role')->nullable(); $table->boolean('is_super_admin')->default(false); $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name'); $table->string('token',64)->unique();
            $table->string('device')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        Schema::create('platform_agents', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique(); $table->string('phone')->nullable(); $table->string('state')->nullable();
            $table->decimal('commission_rate',5,2)->default(10); $table->decimal('total_earned',12,2)->default(0); $table->decimal('total_paid',12,2)->default(0);
            $table->boolean('is_active')->default(true); $table->string('referral_code',20)->unique(); $table->string('password')->nullable();
            $table->string('remember_token')->nullable(); $table->timestamp('last_login_at')->nullable(); $table->timestamps();
        });
        Schema::create('agent_referrals', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('agent_id'); $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('status')->default('pending'); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->string('action');
            $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->text('reason')->nullable();
            $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->timestamps();
        });
    }

    public function test_tenant_admin_cannot_create_agent(): void
    {
        $tenant = Tenant::create(['name'=>'Restricted','slug'=>'restricted','status'=>Tenant::STATUS_ACTIVE]);
        $admin = $this->user('Tenant Admin', false, $tenant->id);
        $this->withToken(ApiToken::issue($admin,'agent-deny'))->postJson('/api/v1/platform/agents', [
            'name'=>'Forbidden Agent','email'=>'forbidden@example.test','commission_rate'=>10,'reason'=>'Unauthorized creation attempt',
        ])->assertForbidden();
    }

    public function test_super_admin_can_create_update_and_deactivate_agent_with_audit(): void
    {
        $super = $this->user('Agent Operator', true, null);
        $token = ApiToken::issue($super,'agent-admin');
        $id = $this->withToken($token)->postJson('/api/v1/platform/agents', [
            'name'=>'North Agent','email'=>'north.agent@example.test','phone'=>'07000000000','state'=>'FCT','commission_rate'=>12.5,'reason'=>'Register approved platform representative',
        ])->assertCreated()->assertJsonPath('agent.active', true)->json('agent.id');

        $this->withToken($token)->patchJson("/api/v1/platform/agents/{$id}", [
            'commission_rate'=>15,'is_active'=>false,'reason'=>'Adjust commission and deactivate agent account',
        ])->assertOk()->assertJsonPath('agent.active', false)->assertJsonPath('agent.commission_rate', 15);

        $this->assertDatabaseHas('audit_logs',['actor_user_id'=>$super->id,'auditable_id'=>$id,'action'=>'platform.agent.created']);
        $this->assertDatabaseHas('audit_logs',['actor_user_id'=>$super->id,'auditable_id'=>$id,'action'=>'platform.agent.updated']);
        $audit = DB::table('audit_logs')->where('auditable_id',$id)->where('action','platform.agent.created')->first();
        $this->assertStringNotContainsString('north.agent@example.test',(string)$audit->new_values);
    }

    public function test_agent_email_is_immutable_through_update_contract(): void
    {
        $super = $this->user('Immutable Operator', true, null);
        $agent = PlatformAgent::create(['name'=>'Immutable Agent','email'=>'immutable@example.test','commission_rate'=>10,'referral_code'=>'IMMUTABLE1','is_active'=>true]);

        $this->withToken(ApiToken::issue($super,'agent-immutable'))->patchJson("/api/v1/platform/agents/{$agent->id}", [
            'email'=>'changed@example.test','reason'=>'Attempt email mutation',
        ])->assertUnprocessable();
        $this->assertSame('immutable@example.test',$agent->fresh()->email);
    }

    private function user(string $name, bool $super, ?int $tenantId): User
    {
        return User::create([
            'tenant_id'=>$tenantId,'name'=>$name,'email'=>str($name)->slug().'.'.uniqid().'@example.test','role'=>$super?'super_admin':'admin',
            'is_super_admin'=>$super,'is_active'=>true,'employment_status'=>$super?null:User::STAFF_STATUS_ACTIVE,
        ]);
    }
}
