<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile Platform tests require sqlite :memory:.');
        }

        foreach (['students', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default(Tenant::STATUS_ACTIVE);
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->unsignedInteger('students_capacity')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
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
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('status')->default(Student::STATUS_ACTIVE);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_tenant_admin_cannot_access_any_platform_api_surface(): void
    {
        $tenant = $this->tenant('Tenant School');
        $user = $this->user('Tenant Admin', false, $tenant->id);
        $token = ApiToken::issue($user, 'tenant-admin-platform-deny');

        foreach (['dashboard', 'tenants', 'billing', 'plans', 'agents'] as $endpoint) {
            $this->withToken($token)
                ->getJson('/api/v1/platform/'.$endpoint)
                ->assertForbidden();
        }
    }

    public function test_super_admin_dashboard_reports_platform_wide_counts(): void
    {
        $first = $this->tenant('First School');
        $second = $this->tenant('Second School');
        $this->user('First Admin', false, $first->id);
        $this->user('Second Admin', false, $second->id);
        $this->student($first->id, 'P001');
        $this->student($second->id, 'P002');
        $super = $this->user('Platform Operator', true, null);

        $this->withToken(ApiToken::issue($super, 'platform-dashboard'))
            ->getJson('/api/v1/platform/dashboard')
            ->assertOk()
            ->assertJsonPath('operator.role', 'Platform Super Admin')
            ->assertJsonPath('metrics.schools', 2)
            ->assertJsonPath('metrics.active_schools', 2)
            ->assertJsonPath('metrics.students', 2)
            ->assertJsonPath('metrics.platform_users', 2)
            ->assertJsonCount(2, 'recent_schools');
    }

    public function test_super_admin_can_use_native_plans_contract_without_tenant_context(): void
    {
        $super = $this->user('Platform Pricing Operator', true, null);

        $response = $this->withToken(ApiToken::issue($super, 'platform-plans'))
            ->getJson('/api/v1/platform/plans')
            ->assertOk()
            ->assertJsonPath('model', 'Pay per active student');

        $this->assertNotEmpty($response->json('plans'));
        $this->assertSame('All EduCore modules', $response->json('plans.0.features.0'));
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

    private function student(int $tenantId, string $admission): void
    {
        Student::create([
            'tenant_id' => $tenantId,
            'admission_number' => $admission,
            'first_name' => 'Platform',
            'last_name' => 'Student',
            'status' => Student::STATUS_ACTIVE,
        ]);
    }
}
