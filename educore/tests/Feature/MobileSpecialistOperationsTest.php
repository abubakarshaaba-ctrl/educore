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

class MobileSpecialistOperationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Specialist mobile tests require sqlite :memory:.');
        }

        foreach ([
            'transport_assignments', 'transport_routes', 'transport_buses',
            'student_health_records', 'students', 'staff_permissions',
            'api_tokens', 'users', 'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
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

        Schema::create('staff_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('module', 60);
            $table->string('type')->default('grant');
            $table->unsignedBigInteger('granted_by');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id', 'module']);
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('other_names')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_health_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('blood_group', 5)->nullable();
            $table->string('genotype', 5)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('current_medications')->nullable();
            $table->string('disability')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'student_id']);
        });

        Schema::create('transport_buses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('plate_number', 30);
            $table->string('model', 100)->nullable();
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->unsignedSmallInteger('year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transport_routes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('fare', 10, 2)->default(0);
            $table->string('morning_time', 10)->nullable();
            $table->string('evening_time', 10)->nullable();
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('assistant_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transport_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('route_id');
            $table->string('pickup_stop', 150)->nullable();
            $table->string('direction')->default('both');
            $table->timestamps();
            $table->unique(['tenant_id', 'student_id']);
        });
    }

    public function test_health_technician_receives_health_access_and_manage_capability(): void
    {
        [$tenant, $user] = $this->school('Health School', 'health_technician');

        $this->withToken(ApiToken::issue($user, 'health-tech'))
            ->getJson('/api/v1/health-officer/dashboard')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonPath('metrics.students', 0);

        $this->assertSame($tenant->id, $user->tenant_id);
    }

    public function test_health_dashboard_searches_and_paginates_active_students(): void
    {
        [$tenant, $user] = $this->school('Paged Health School', 'health_technician');
        foreach (range(1, 12) as $index) {
            Student::create([
                'tenant_id' => $tenant->id,
                'admission_number' => sprintf('HLT-%03d', $index),
                'first_name' => $index === 12 ? 'Target' : 'Student',
                'last_name' => 'Number '.$index,
                'status' => Student::STATUS_ACTIVE,
            ]);
        }

        $token = ApiToken::issue($user, 'health-page');
        $this->withToken($token)
            ->getJson('/api/v1/health-officer/dashboard?per_page=10&page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonCount(10, 'students');

        $this->withToken($token)
            ->getJson('/api/v1/health-officer/dashboard?search=Target&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('students.0.name', 'Target Number 12');
    }

    public function test_custom_health_deny_overrides_role_access(): void
    {
        [$tenant, $user] = $this->school('Denied Health School', 'health_technician');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'module' => 'health',
            'type' => 'deny',
            'granted_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($user, 'health-denied'))
            ->getJson('/api/v1/health-officer/dashboard')
            ->assertForbidden();
    }

    public function test_transport_officer_receives_transport_access_and_manage_capability(): void
    {
        [, $user] = $this->school('Transport School', 'transport_officer');

        $this->withToken(ApiToken::issue($user, 'transport-officer'))
            ->getJson('/api/v1/transport-officer/dashboard')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonPath('metrics.routes', 0);
    }

    public function test_custom_transport_grant_allows_staff_without_default_role_access(): void
    {
        [$tenant, $user] = $this->school('Granted Transport School', 'admin_officer');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'module' => 'transport',
            'type' => 'grant',
            'granted_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($user, 'transport-granted'))
            ->getJson('/api/v1/transport-officer/dashboard')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true);
    }

    public function test_transport_assignment_rejects_foreign_student_and_route(): void
    {
        [, $officer] = $this->school('Boundary Transport School', 'transport_officer');
        [$foreignTenant] = $this->school('Foreign Transport School', 'transport_officer');

        $foreignStudent = Student::create([
            'tenant_id' => $foreignTenant->id,
            'admission_number' => 'FOREIGN-001',
            'first_name' => 'Foreign',
            'last_name' => 'Student',
            'status' => Student::STATUS_ACTIVE,
        ]);
        $foreignRouteId = DB::table('transport_routes')->insertGetId([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Route',
            'fare' => 1000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($officer, 'transport-boundary'))
            ->postJson('/api/v1/transport-officer/assignments', [
                'student_id' => $foreignStudent->id,
                'route_id' => $foreignRouteId,
                'direction' => 'both',
            ])
            ->assertUnprocessable();
    }

    public function test_transport_officer_can_unassign_only_own_tenant_students(): void
    {
        [$tenant, $officer] = $this->school('Unassign Transport School', 'transport_officer');
        [$foreignTenant] = $this->school('Foreign Unassign School', 'transport_officer');
        $localStudent = Student::create([
            'tenant_id' => $tenant->id,
            'admission_number' => 'LOCAL-001',
            'first_name' => 'Local',
            'last_name' => 'Rider',
            'status' => Student::STATUS_ACTIVE,
        ]);
        $foreignStudent = Student::create([
            'tenant_id' => $foreignTenant->id,
            'admission_number' => 'FOREIGN-002',
            'first_name' => 'Foreign',
            'last_name' => 'Rider',
            'status' => Student::STATUS_ACTIVE,
        ]);
        $localRoute = DB::table('transport_routes')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Local Route',
            'fare' => 1000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $foreignRoute = DB::table('transport_routes')->insertGetId([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Route',
            'fare' => 1000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transport_assignments')->insert([
            [
                'tenant_id' => $tenant->id,
                'student_id' => $localStudent->id,
                'route_id' => $localRoute,
                'direction' => 'both',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $foreignTenant->id,
                'student_id' => $foreignStudent->id,
                'route_id' => $foreignRoute,
                'direction' => 'both',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $token = ApiToken::issue($officer, 'transport-unassign');
        $this->withToken($token)
            ->deleteJson('/api/v1/transport-officer/assignments/'.$localStudent->id)
            ->assertOk();
        $this->assertDatabaseMissing('transport_assignments', [
            'tenant_id' => $tenant->id,
            'student_id' => $localStudent->id,
        ]);

        $this->withToken($token)
            ->deleteJson('/api/v1/transport-officer/assignments/'.$foreignStudent->id)
            ->assertNotFound();
        $this->assertDatabaseHas('transport_assignments', [
            'tenant_id' => $foreignTenant->id,
            'student_id' => $foreignStudent->id,
        ]);
    }

    public function test_health_update_rejects_foreign_student(): void
    {
        [, $healthUser] = $this->school('Boundary Health School', 'health_technician');
        [$foreignTenant] = $this->school('Foreign Health School', 'health_technician');
        $foreignStudent = Student::create([
            'tenant_id' => $foreignTenant->id,
            'admission_number' => 'HEALTH-FOREIGN-001',
            'first_name' => 'Foreign',
            'last_name' => 'Patient',
            'status' => Student::STATUS_ACTIVE,
        ]);

        $this->withToken(ApiToken::issue($healthUser, 'health-boundary'))
            ->postJson('/api/v1/health-officer/students/'.$foreignStudent->id, [
                'blood_group' => 'O+',
            ])
            ->assertNotFound();
    }

    private function school(string $name, string $role): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' User',
            'role' => $role,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $user];
    }
}
