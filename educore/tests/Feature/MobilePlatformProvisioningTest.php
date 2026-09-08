<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformProvisioningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform provisioning tests require sqlite :memory:.');
        }
        Notification::fake();
        foreach (['audit_logs','api_tokens','users','tenants'] as $table) Schema::dropIfExists($table);
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('subdomain')->nullable()->unique();
            $table->string('email')->nullable(); $table->string('phone')->nullable(); $table->string('address')->nullable();
            $table->string('status')->default(Tenant::STATUS_ACTIVE); $table->timestamp('subscription_expires_at')->nullable();
            $table->string('theme_primary')->nullable(); $table->string('theme_accent')->nullable(); $table->string('theme_sidebar')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->string('name'); $table->string('email')->nullable()->unique();
            $table->string('password')->nullable(); $table->string('role')->nullable(); $table->boolean('is_super_admin')->default(false); $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable(); $table->date('employment_started_at')->nullable(); $table->timestamp('status_changed_at')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name'); $table->string('token',64)->unique();
            $table->string('device')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->string('action');
            $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->text('reason')->nullable();
            $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->timestamps();
        });
    }

    public function test_non_super_admin_cannot_provision_school(): void
    {
        $existing = Tenant::create(['name'=>'Existing','slug'=>'existing','status'=>Tenant::STATUS_ACTIVE]);
        $admin = $this->user('Tenant Admin', false, $existing->id);
        $this->withToken(ApiToken::issue($admin,'provision-deny'))->postJson('/api/v1/platform/tenants', $this->payload())
            ->assertForbidden();
        $this->assertDatabaseMissing('tenants',['slug'=>'new-model-school']);
    }

    public function test_super_admin_provisions_school_and_admin_atomically_with_audit(): void
    {
        $super = $this->user('Provisioning Operator', true, null);
        $response = $this->withToken(ApiToken::issue($super,'provision-school'))
            ->postJson('/api/v1/platform/tenants', $this->payload())
            ->assertCreated()
            ->assertJsonPath('tenant.slug','new-model-school')
            ->assertJsonPath('administrator.email','school.admin@example.test');

        $tenantId = $response->json('tenant.id');
        $tenant = Tenant::findOrFail($tenantId);
        $admin = User::where('tenant_id',$tenantId)->where('email','school.admin@example.test')->firstOrFail();
        $this->assertSame(Tenant::STATUS_ACTIVE,$tenant->status);
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertTrue(Hash::check('StrongPass123!',$admin->password));
        $this->assertSame(User::STAFF_STATUS_ACTIVE,$admin->employment_status);
        $this->assertDatabaseHas('audit_logs',['tenant_id'=>$tenantId,'actor_user_id'=>$super->id,'action'=>'tenant.provisioning.started']);
        $this->assertDatabaseHas('audit_logs',['tenant_id'=>$tenantId,'actor_user_id'=>$super->id,'action'=>'tenant.provisioning.tenant_created']);
        $this->assertDatabaseHas('audit_logs',['tenant_id'=>$tenantId,'actor_user_id'=>$super->id,'action'=>'tenant.provisioning.administrator_created']);
        $this->assertDatabaseHas('audit_logs',['tenant_id'=>$tenantId,'actor_user_id'=>$super->id,'action'=>'tenant.provisioning.default_settings_created']);
        $audit = \DB::table('audit_logs')->where('tenant_id',$tenantId)->where('action','tenant.provisioning.administrator_created')->first();
        $this->assertStringNotContainsString('school.admin@example.test',(string)$audit->new_values);
    }

    public function test_duplicate_admin_email_is_rejected_before_any_tenant_is_created(): void
    {
        $super = $this->user('Duplicate Operator', true, null);
        User::create([
            'name'=>'Existing Admin','email'=>'school.admin@example.test','role'=>'admin','is_super_admin'=>false,'is_active'=>true,
            'employment_status'=>User::STAFF_STATUS_ACTIVE,
        ]);
        $before = Tenant::count();

        $this->withToken(ApiToken::issue($super,'provision-duplicate'))
            ->postJson('/api/v1/platform/tenants', $this->payload())
            ->assertUnprocessable();

        $this->assertSame($before,Tenant::count());
        $this->assertDatabaseMissing('tenants',['slug'=>'new-model-school']);
    }

    private function payload(): array
    {
        return [
            'name'=>'New Model School','slug'=>'New Model School','subdomain'=>'new-model-school',
            'email'=>'school@example.test','phone'=>'07000000000','address'=>'Abuja, Nigeria',
            'admin_name'=>'School Administrator','admin_email'=>'school.admin@example.test','admin_password'=>'StrongPass123!',
            'admin_employment_started_at'=>now()->subYear()->toDateString(),
        ];
    }

    private function user(string $name, bool $super, ?int $tenantId): User
    {
        return User::create([
            'tenant_id'=>$tenantId,'name'=>$name,'email'=>str($name)->slug().'.'.uniqid().'@example.test','role'=>$super?'super_admin':'admin',
            'is_super_admin'=>$super,'is_active'=>true,'employment_status'=>$super?null:User::STAFF_STATUS_ACTIVE,
        ]);
    }
}
