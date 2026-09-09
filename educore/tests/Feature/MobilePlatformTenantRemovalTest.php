<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformTenantRemovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform tenant removal tests require sqlite :memory:.');
        }

        foreach (['audit_logs', 'sessions', 'device_tokens', 'push_subscriptions', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('status')->default(Tenant::STATUS_ACTIVE);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->string('name'); $table->string('email')->nullable()->unique();
            $table->string('password')->nullable(); $table->string('role')->nullable(); $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true); $table->string('employment_status')->nullable(); $table->timestamp('status_changed_at')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name'); $table->string('token', 64)->unique();
            $table->string('device')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('tenant_id'); $table->text('endpoint');
            $table->string('public_key')->nullable(); $table->string('auth_token')->nullable(); $table->timestamps();
        });
        Schema::create('device_tokens', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('token')->unique(); $table->string('platform')->nullable();
            $table->timestamp('last_seen_at')->nullable(); $table->timestamps();
        });
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary(); $table->unsignedBigInteger('user_id')->nullable()->index(); $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable(); $table->longText('payload'); $table->integer('last_activity')->index();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->string('action');
            $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->text('reason')->nullable();
            $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->timestamps();
        });
    }

    public function test_non_super_admin_cannot_remove_school(): void
    {
        [$tenant, $schoolAdmin] = $this->school('Protected Removal School');
        $this->withToken(ApiToken::issue($schoolAdmin, 'remove-denied'))
            ->deleteJson('/api/v1/platform/tenants/'.$tenant->id, [
                'confirmation' => $tenant->name,
                'current_password' => 'secret1234',
                'reason' => 'This should never be accepted.',
            ])->assertForbidden();
    }

    public function test_wrong_name_password_and_short_reason_leave_school_untouched(): void
    {
        [$tenant] = $this->school('Guarded Removal School');
        $super = $this->superAdmin('StrongPass!123');
        $token = ApiToken::issue($super, 'remove-guard');

        $this->withToken($token)->deleteJson('/api/v1/platform/tenants/'.$tenant->id, [
            'confirmation' => 'Wrong school', 'current_password' => 'StrongPass!123', 'reason' => 'A sufficiently detailed reason.',
        ])->assertUnprocessable();
        $this->withToken($token)->deleteJson('/api/v1/platform/tenants/'.$tenant->id, [
            'confirmation' => $tenant->name, 'current_password' => 'WrongPassword', 'reason' => 'A sufficiently detailed reason.',
        ])->assertUnprocessable();
        $this->withToken($token)->deleteJson('/api/v1/platform/tenants/'.$tenant->id, [
            'confirmation' => $tenant->name, 'current_password' => 'StrongPass!123', 'reason' => 'short',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'deleted_at' => null]);
    }

    public function test_successful_removal_revokes_accounts_sessions_push_and_preserves_audit(): void
    {
        [$tenant, $schoolAdmin] = $this->school('Removal Lifecycle School');
        $super = $this->superAdmin('StrongPass!123');
        $schoolToken = ApiToken::issue($schoolAdmin, 'school-mobile');
        DB::table('push_subscriptions')->insert([
            'user_id' => $schoolAdmin->id, 'tenant_id' => $tenant->id, 'endpoint' => 'https://push.example/subscription',
            'public_key' => 'public', 'auth_token' => 'auth', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('device_tokens')->insert([
            'user_id' => $schoolAdmin->id, 'token' => 'fcm-removal-token', 'platform' => 'android',
            'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('sessions')->insert([
            'id' => 'school-browser-session', 'user_id' => $schoolAdmin->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'Removal test', 'payload' => 'serialized-session', 'last_activity' => now()->timestamp,
        ]);

        $this->withToken(ApiToken::issue($super, 'remove-school'))
            ->deleteJson('/api/v1/platform/tenants/'.$tenant->id, [
                'confirmation' => $tenant->name,
                'current_password' => 'StrongPass!123',
                'reason' => 'School requested formal platform decommissioning.',
            ])->assertOk();

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => Tenant::STATUS_SUSPENDED]);
        $this->assertDatabaseHas('users', ['id' => $schoolAdmin->id, 'is_active' => false]);
        $this->assertDatabaseMissing('api_tokens', ['id' => $schoolToken->id]);
        $this->assertDatabaseMissing('push_subscriptions', ['user_id' => $schoolAdmin->id]);
        $this->assertDatabaseMissing('device_tokens', ['user_id' => $schoolAdmin->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $schoolAdmin->id]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_user_id' => $super->id,
            'action' => 'tenant.removed.via_platform',
            'reason' => 'School requested formal platform decommissioning.',
        ]);
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create(['name' => $name, 'slug' => str($name)->slug().'-'.uniqid(), 'status' => Tenant::STATUS_ACTIVE]);
        $admin = User::create([
            'tenant_id' => $tenant->id, 'name' => $name.' Admin', 'email' => uniqid().'@school.test',
            'password' => Hash::make('secret1234'), 'role' => 'admin', 'is_super_admin' => false,
            'is_active' => true, 'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        return [$tenant, $admin];
    }

    private function superAdmin(string $password): User
    {
        return User::create([
            'tenant_id' => null, 'name' => 'Platform Remover', 'email' => uniqid().'@platform.test',
            'password' => Hash::make($password), 'role' => 'super_admin', 'is_super_admin' => true, 'is_active' => true,
        ]);
    }
}
