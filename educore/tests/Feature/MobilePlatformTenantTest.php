<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformTenantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform tenant tests require sqlite :memory:.');
        }

        foreach (['audit_logs', 'students', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->default(Tenant::STATUS_ACTIVE);
            $table->timestamp('subscription_expires_at')->nullable();
            $table->unsignedInteger('students_capacity')->nullable();
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
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('status')->default(Student::STATUS_ACTIVE);
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

    public function test_non_super_admin_cannot_read_or_extend_school_lifecycle(): void
    {
        $tenant = $this->tenant('Restricted School');
        $admin = $this->user('Restricted Admin', false, $tenant->id);
        $token = ApiToken::issue($admin, 'platform-school-deny');

        $this->withToken($token)->getJson("/api/v1/platform/tenants/{$tenant->id}")->assertForbidden();
        $this->withToken($token)->postJson("/api/v1/platform/tenants/{$tenant->id}/extend", [
            'months' => 3,
            'reason' => 'Unauthorized extension attempt',
        ])->assertForbidden();
    }

    public function test_paid_school_extension_is_atomic_reactivates_school_and_is_audited(): void
    {
        $tenant = $this->tenant('Paid School', Tenant::STATUS_SUSPENDED, now()->addMonth());
        $tenant->update(['students_capacity' => 100]);
        $this->seedStudents($tenant->id, 51);
        $super = $this->user('Platform Operator', true, null);
        $token = ApiToken::issue($super, 'platform-school-extend');
        $before = $tenant->fresh()->subscription_expires_at;

        $response = $this->withToken($token)
            ->postJson("/api/v1/platform/tenants/{$tenant->id}/extend", [
                'months' => 3,
                'reason' => 'Approved three-month service extension',
            ])
            ->assertOk()
            ->assertJsonPath('tenant.status', Tenant::STATUS_ACTIVE);

        $tenant->refresh();
        $this->assertTrue($tenant->subscription_expires_at->greaterThan($before));
        $this->assertSame($response->json('tenant.subscription_expires_at'), $tenant->subscription_expires_at->toDateString());
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_user_id' => $super->id,
            'action' => 'tenant.subscription.extended.via_api',
            'reason' => 'Approved three-month service extension',
        ]);
    }

    public function test_free_school_extension_is_rejected_without_mutation(): void
    {
        $tenant = $this->tenant('Free School', Tenant::STATUS_ACTIVE, null);
        $this->seedStudents($tenant->id, 10);
        $super = $this->user('Free Tier Operator', true, null);

        $this->withToken(ApiToken::issue($super, 'platform-free-extend'))
            ->postJson("/api/v1/platform/tenants/{$tenant->id}/extend", [
                'months' => 12,
                'reason' => 'Should not create an expiry on the free plan',
            ])
            ->assertUnprocessable();

        $this->assertNull($tenant->fresh()->subscription_expires_at);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'tenant.subscription.extended.via_api']);
    }

    private function tenant(string $name, string $status = Tenant::STATUS_ACTIVE, $expiry = null): Tenant
    {
        return Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => $status,
            'subscription_expires_at' => $expiry,
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

    private function seedStudents(int $tenantId, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Student::create([
                'tenant_id' => $tenantId,
                'admission_number' => 'ST'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'first_name' => 'Student',
                'last_name' => (string) $i,
                'status' => Student::STATUS_ACTIVE,
            ]);
        }
    }
}
