<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformExtendedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Extended Platform tests require sqlite :memory:.');
        }

        foreach ([
            'platform_broadcasts', 'platform_support_tickets', 'school_group_members', 'school_groups',
            'platform_settings', 'students', 'api_tokens', 'users', 'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default(Tenant::STATUS_ACTIVE);
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
            $table->string('last_name');
            $table->string('status')->default(Student::STATUS_ACTIVE);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->timestamps();
        });
        Schema::create('school_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_email')->nullable();
            $table->timestamps();
        });
        Schema::create('school_group_members', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('role')->default('member');
            $table->timestamps();
        });
        Schema::create('platform_support_tickets', function (Blueprint $table): void {
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
        Schema::create('platform_broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('target')->default('all');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_tenant_admin_is_denied_every_extended_platform_endpoint(): void
    {
        $tenant = $this->tenant('Restricted School');
        $admin = $this->user('Restricted Admin', false, $tenant->id);
        $token = ApiToken::issue($admin, 'extended-platform-deny');

        foreach (['analytics', 'groups', 'support', 'broadcasts', 'settings', 'gateways'] as $endpoint) {
            $this->withToken($token)
                ->getJson('/api/v1/platform/'.$endpoint)
                ->assertForbidden();
        }
    }

    public function test_gateway_contract_never_returns_decrypted_secret_credentials(): void
    {
        $super = $this->user('Gateway Operator', true, null);
        PlatformSetting::setValue('paystack_public_key', 'pk_test_PUBLIC1234567890', 'string', 'payments', 'Paystack Public Key');
        PlatformSetting::setValue('paystack_secret_key', 'sk_test_SUPER_SECRET_VALUE_1234567890', 'encrypted', 'payments', 'Paystack Secret Key');
        PlatformSetting::setValue('paystack_is_live', false, 'boolean', 'payments', 'Paystack Live Mode');

        $response = $this->withToken(ApiToken::issue($super, 'platform-gateways'))
            ->getJson('/api/v1/platform/gateways')
            ->assertOk()
            ->assertJsonPath('gateways.0.provider', 'paystack')
            ->assertJsonPath('gateways.0.secret_configured', true)
            ->assertJsonPath('gateways.0.configured', true)
            ->assertJsonPath('gateways.0.live', false);

        $json = json_encode($response->json(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('sk_test_SUPER_SECRET_VALUE_1234567890', $json);
        $this->assertStringNotContainsString('pk_test_PUBLIC1234567890', $json);
    }

    public function test_general_settings_contract_excludes_secret_setting_rows(): void
    {
        $super = $this->user('Settings Operator', true, null);
        PlatformSetting::setValue('platform_name', 'EduCore', 'string', 'general', 'Platform Name');
        PlatformSetting::setValue('flutterwave_secret_key', 'FLW_SECRET_NEVER_RETURN', 'encrypted', 'payments', 'Flutterwave Secret');

        $response = $this->withToken(ApiToken::issue($super, 'platform-settings'))
            ->getJson('/api/v1/platform/settings')
            ->assertOk()
            ->assertJsonFragment(['key' => 'platform_name', 'value' => 'EduCore']);

        $json = json_encode($response->json(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('flutterwave_secret_key', $json);
        $this->assertStringNotContainsString('FLW_SECRET_NEVER_RETURN', $json);
    }

    public function test_analytics_and_group_contracts_are_platform_wide_for_super_admin(): void
    {
        $tenant = $this->tenant('Grouped School');
        $this->student($tenant->id, 'AN001');
        $groupId = DB::table('school_groups')->insertGetId([
            'name' => 'Northern Schools',
            'slug' => 'northern-schools',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('school_group_members')->insert([
            'group_id' => $groupId,
            'tenant_id' => $tenant->id,
            'role' => 'lead',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $super = $this->user('Analytics Operator', true, null);
        $token = ApiToken::issue($super, 'platform-analytics');

        $this->withToken($token)
            ->getJson('/api/v1/platform/analytics')
            ->assertOk()
            ->assertJsonPath('metrics.schools', 1)
            ->assertJsonPath('metrics.students', 1);

        $this->withToken($token)
            ->getJson('/api/v1/platform/groups')
            ->assertOk()
            ->assertJsonPath('groups.0.name', 'Northern Schools')
            ->assertJsonPath('groups.0.member_count', 1);
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
            'first_name' => 'Analytics',
            'last_name' => 'Student',
            'status' => Student::STATUS_ACTIVE,
        ]);
    }
}
