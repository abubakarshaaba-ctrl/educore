<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileAccountWorkspacesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile account workspace tests require sqlite :memory:.');
        }

        foreach ([
            'push_subscriptions', 'sessions', 'guardian_student', 'guardians', 'students', 'class_arms', 'class_levels',
            'school_settings', 'audit_logs', 'staff_permissions', 'api_tokens', 'users', 'tenants',
        ] as $table) Schema::dropIfExists($table);

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('slug'); $table->string('status')->default('active');
            $table->string('motto')->nullable(); $table->string('address')->nullable(); $table->string('phone')->nullable();
            $table->string('email')->nullable(); $table->string('logo_path')->nullable(); $table->string('authorized_signature_path')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->string('name'); $table->string('email')->nullable()->unique();
            $table->string('password')->nullable(); $table->string('role')->nullable(); $table->string('staff_id')->nullable();
            $table->boolean('is_super_admin')->default(false); $table->boolean('is_active')->default(true); $table->string('employment_status')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name'); $table->string('token', 64)->unique();
            $table->string('device')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        Schema::create('staff_permissions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('user_id'); $table->string('module', 60);
            $table->string('type')->default('grant'); $table->unsignedBigInteger('granted_by'); $table->timestamps(); $table->unique(['tenant_id','user_id','module']);
        });
        Schema::create('class_levels', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->string('name'); $table->unsignedInteger('order_index')->default(0); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('class_level_id')->nullable(); $table->string('name'); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('user_id')->nullable(); $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('first_name'); $table->string('last_name'); $table->string('admission_number')->nullable(); $table->string('email')->nullable();
            $table->string('status')->default('active'); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('guardians', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('first_name'); $table->string('last_name'); $table->string('email')->nullable(); $table->string('phone')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('guardian_student', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('guardian_id'); $table->unsignedBigInteger('student_id'); $table->boolean('is_primary_contact')->default(false); $table->timestamps();
        });
        Schema::create('sessions', function (Blueprint $table): void { $table->string('id')->primary(); $table->unsignedBigInteger('user_id')->nullable(); $table->text('payload')->nullable(); $table->integer('last_activity')->default(0); });
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->unsignedBigInteger('user_id'); $table->text('endpoint');
            $table->text('public_key')->nullable(); $table->text('auth_token')->nullable(); $table->timestamps();
        });
        Schema::create('school_settings', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->string('key'); $table->text('value')->nullable(); $table->string('group')->nullable(); $table->timestamps();
            $table->unique(['tenant_id','key']);
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->string('action');
            $table->text('old_values')->nullable(); $table->text('new_values')->nullable(); $table->text('reason')->nullable();
            $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->timestamps();
        });
    }

    public function test_portal_accounts_are_tenant_scoped_and_password_reset_revokes_sessions(): void
    {
        [$tenant, $admin] = $this->school('Portal School');
        [$foreign] = $this->school('Foreign Portal School');
        $student = Student::create(['tenant_id'=>$tenant->id,'first_name'=>'Amina','last_name'=>'Ali','admission_number'=>'ST001','email'=>'amina@example.test','status'=>'active']);
        $foreignStudent = Student::create(['tenant_id'=>$foreign->id,'first_name'=>'Foreign','last_name'=>'Student','admission_number'=>'F001','email'=>'foreign@example.test','status'=>'active']);
        $token = ApiToken::issue($admin, 'portal-accounts');

        $created = $this->withToken($token)->postJson('/api/v1/portal-accounts/students/'.$student->id, [
            'email'=>'amina.portal@example.test','password'=>'TemporaryPass123!'
        ])->assertCreated()->assertJsonPath('account.active', true);
        $portalUserId = (int) $created->json('account.user_id');
        ApiToken::issue(User::findOrFail($portalUserId), 'student-session');
        DB::table('push_subscriptions')->insert(['tenant_id'=>$tenant->id,'user_id'=>$portalUserId,'endpoint'=>'https://push.example/a','created_at'=>now(),'updated_at'=>now()]);

        $this->withToken($token)->postJson('/api/v1/portal-accounts/users/'.$portalUserId.'/reset-password', ['password'=>'ReplacementPass123!'])
            ->assertOk();
        $this->assertSame(0, DB::table('api_tokens')->where('user_id',$portalUserId)->count());
        $this->assertSame(0, DB::table('push_subscriptions')->where('user_id',$portalUserId)->count());
        $this->assertTrue(Hash::check('ReplacementPass123!', User::findOrFail($portalUserId)->password));

        $this->withToken($token)->postJson('/api/v1/portal-accounts/students/'.$foreignStudent->id, [
            'email'=>'stolen@example.test','password'=>'TemporaryPass123!'
        ])->assertNotFound();
    }

    public function test_school_settings_update_only_authenticated_tenant_and_is_audited(): void
    {
        [$tenant, $admin] = $this->school('Settings School');
        [$foreign] = $this->school('Foreign Settings School');
        $token = ApiToken::issue($admin, 'school-settings');

        $this->withToken($token)->putJson('/api/v1/school-settings', [
            'name'=>'Settings School Updated','motto'=>'Knowledge and Character','address'=>'Abuja','phone'=>'08000000000',
            'email'=>'school@example.test','website'=>'https://school.example.test','established_year'=>2014,'proprietor'=>'School Proprietor','slogan'=>'Learn. Lead.'
        ])->assertOk()->assertJsonPath('school.name','Settings School Updated');

        $this->assertDatabaseHas('tenants',['id'=>$tenant->id,'name'=>'Settings School Updated']);
        $this->assertDatabaseHas('tenants',['id'=>$foreign->id,'name'=>'Foreign Settings School']);
        $this->assertDatabaseHas('school_settings',['tenant_id'=>$tenant->id,'key'=>'website','value'=>'https://school.example.test']);
        $this->assertDatabaseHas('audit_logs',['tenant_id'=>$tenant->id,'actor_user_id'=>$admin->id,'action'=>'school.settings.updated.via_mobile']);
    }

    public function test_custom_denies_block_account_management_workspaces(): void
    {
        [$tenant, $admin] = $this->school('Denied Account School');
        DB::table('staff_permissions')->insert([
            ['tenant_id'=>$tenant->id,'user_id'=>$admin->id,'module'=>'portal-accounts','type'=>'deny','granted_by'=>$admin->id,'created_at'=>now(),'updated_at'=>now()],
            ['tenant_id'=>$tenant->id,'user_id'=>$admin->id,'module'=>'settings','type'=>'deny','granted_by'=>$admin->id,'created_at'=>now(),'updated_at'=>now()],
        ]);
        $token = ApiToken::issue($admin, 'denied-account-tools');
        $this->withToken($token)->getJson('/api/v1/portal-accounts')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/school-settings')->assertForbidden();
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create(['name'=>$name,'slug'=>str($name)->slug().'-'.uniqid(),'status'=>'active']);
        $admin = User::create([
            'tenant_id'=>$tenant->id,'name'=>$name.' Admin','email'=>str($name)->slug().uniqid().'@example.test',
            'password'=>Hash::make('AdminPass123!'),'role'=>'admin','staff_id'=>strtoupper(substr(md5($name),0,8)),
            'is_super_admin'=>false,'is_active'=>true,'employment_status'=>User::STAFF_STATUS_ACTIVE,
        ]);
        return [$tenant,$admin];
    }
}
