<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformMutationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform mutation tests require sqlite :memory:.');
        }

        foreach (['audit_logs', 'platform_broadcasts', 'platform_support_tickets', 'api_tokens', 'users', 'tenants'] as $table) {
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
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('device')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('platform_support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('subject', 150);
            $table->text('body');
            $table->string('status', 20)->default('open');
            $table->text('admin_reply')->nullable();
            $table->unsignedBigInteger('replied_by')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });
        Schema::create('platform_broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 150);
            $table->text('body');
            $table->string('target', 20)->default('all');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
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

    public function test_super_admin_can_reply_then_close_support_ticket_and_actions_are_audited(): void
    {
        $tenant = $this->tenant('Support School');
        $requester = $this->user('School Admin', false, $tenant->id);
        $super = $this->user('Platform Support', true, null);
        $ticket = DB::table('platform_support_tickets')->insertGetId([
            'tenant_id' => $tenant->id,
            'user_id' => $requester->id,
            'subject' => 'Help required',
            'body' => 'Please assist us with the portal.',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = ApiToken::issue($super, 'platform-support-mutation');

        $this->withToken($token)
            ->postJson('/api/v1/platform/support/'.$ticket.'/reply', ['reply' => 'We have reviewed the issue.'])
            ->assertOk()
            ->assertJsonPath('status', 'answered');

        $this->assertDatabaseHas('platform_support_tickets', [
            'id' => $ticket,
            'status' => 'answered',
            'replied_by' => $super->id,
            'admin_reply' => 'We have reviewed the issue.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_user_id' => $super->id,
            'action' => 'platform.support.replied',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/platform/support')
            ->assertOk()
            ->assertJsonPath('summary.replied', 1);

        $this->withToken($token)
            ->postJson('/api/v1/platform/support/'.$ticket.'/close')
            ->assertOk()
            ->assertJsonPath('status', 'closed');

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_user_id' => $super->id,
            'action' => 'platform.support.closed',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/platform/support/'.$ticket.'/reply', ['reply' => 'Late reply'])
            ->assertUnprocessable();
        $this->withToken($token)
            ->postJson('/api/v1/platform/support/'.$ticket.'/close')
            ->assertUnprocessable();
    }

    public function test_super_admin_can_create_and_expire_broadcast_with_audit_history(): void
    {
        $super = $this->user('Platform Broadcaster', true, null);
        $token = ApiToken::issue($super, 'platform-broadcast-mutation');

        $created = $this->withToken($token)
            ->postJson('/api/v1/platform/broadcasts', [
                'title' => 'Scheduled Maintenance',
                'body' => 'The platform will receive routine maintenance.',
                'target' => 'all',
                'expires_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertCreated();
        $broadcastId = (int) $created->json('id');

        $this->assertDatabaseHas('platform_broadcasts', [
            'id' => $broadcastId,
            'title' => 'Scheduled Maintenance',
            'target' => 'all',
            'created_by' => $super->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => null,
            'actor_user_id' => $super->id,
            'action' => 'platform.broadcast.created',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/platform/broadcasts/'.$broadcastId.'/expire')
            ->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => null,
            'actor_user_id' => $super->id,
            'action' => 'platform.broadcast.expired',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/platform/broadcasts/'.$broadcastId.'/expire')
            ->assertUnprocessable();
    }

    public function test_tenant_admin_cannot_mutate_platform_support_or_broadcasts(): void
    {
        $tenant = $this->tenant('Mutation Boundary School');
        $admin = $this->user('Boundary Admin', false, $tenant->id);
        $ticket = DB::table('platform_support_tickets')->insertGetId([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'subject' => 'Boundary test',
            'body' => 'This should remain protected.',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = ApiToken::issue($admin, 'platform-mutation-deny');

        $this->withToken($token)
            ->postJson('/api/v1/platform/support/'.$ticket.'/reply', ['reply' => 'Unauthorized'])
            ->assertForbidden();
        $this->withToken($token)
            ->postJson('/api/v1/platform/support/'.$ticket.'/close')
            ->assertForbidden();
        $this->withToken($token)
            ->postJson('/api/v1/platform/broadcasts', [
                'title' => 'Unauthorized',
                'body' => 'This must not be created.',
                'target' => 'all',
            ])
            ->assertForbidden();
    }

    private function tenant(string $name): Tenant
    {
        return Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
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
