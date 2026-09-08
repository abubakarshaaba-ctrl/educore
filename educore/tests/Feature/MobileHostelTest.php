<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileHostelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile hostel tests require sqlite :memory:.');
        }

        foreach ([
            'hostel_allocations', 'hostel_rooms', 'hostels', 'students', 'academic_sessions',
            'staff_permissions', 'api_tokens', 'users', 'tenants',
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
            $table->string('staff_id')->nullable();
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
        Schema::create('academic_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('hostels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 120);
            $table->string('gender')->default('mixed');
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedBigInteger('warden_id')->nullable();
            $table->timestamps();
        });
        Schema::create('hostel_rooms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('hostel_id');
            $table->string('room_number', 30);
            $table->unsignedInteger('capacity')->default(4);
            $table->timestamps();
            $table->unique(['hostel_id', 'room_number']);
        });
        Schema::create('hostel_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('hostel_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->decimal('boarding_fee_amount', 12, 2)->default(0);
            $table->string('boarding_fee_status')->default('unpaid');
            $table->date('allocated_at');
            $table->date('vacated_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function test_hostel_workspace_is_tenant_scoped_and_exposes_manage_capability(): void
    {
        [$tenant, $admin] = $this->school('Boarding School');
        [$otherTenant] = $this->school('Other Boarding School');

        DB::table('hostels')->insert([
            ['tenant_id' => $tenant->id, 'name' => 'Amina Hall', 'gender' => 'female', 'capacity' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $otherTenant->id, 'name' => 'Foreign Hall', 'gender' => 'mixed', 'capacity' => 50, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withToken(ApiToken::issue($admin, 'hostel-index'))
            ->getJson('/api/v1/hostels')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonCount(1, 'hostels')
            ->assertJsonPath('hostels.0.name', 'Amina Hall');
    }

    public function test_hostel_creation_rejects_foreign_warden(): void
    {
        [, $admin] = $this->school('Warden School');
        [$foreignTenant] = $this->school('Foreign Warden School');
        $foreignWarden = User::create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Warden',
            'role' => 'teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $this->withToken(ApiToken::issue($admin, 'hostel-warden'))
            ->postJson('/api/v1/hostels', [
                'name' => 'New Hall',
                'gender' => 'mixed',
                'capacity' => 30,
                'warden_id' => $foreignWarden->id,
            ])
            ->assertUnprocessable();
    }

    public function test_room_capacity_cannot_push_total_rooms_above_hostel_capacity(): void
    {
        [$tenant, $admin] = $this->school('Capacity School');
        $hostelId = DB::table('hostels')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Capacity Hall',
            'gender' => 'mixed',
            'capacity' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('hostel_rooms')->insert([
            'tenant_id' => $tenant->id,
            'hostel_id' => $hostelId,
            'room_number' => 'R1',
            'capacity' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($admin, 'hostel-room-capacity'))
            ->postJson("/api/v1/hostels/{$hostelId}/rooms", ['room_number' => 'R2', 'capacity' => 2])
            ->assertUnprocessable();
    }

    public function test_allocation_prevents_duplicate_active_student_and_room_over_capacity(): void
    {
        [$tenant, $admin, $sessionId] = $this->school('Allocation School');
        $studentA = $this->student($tenant->id, 'A001', 'Aisha');
        $studentB = $this->student($tenant->id, 'A002', 'Binta');
        $hostelId = DB::table('hostels')->insertGetId([
            'tenant_id' => $tenant->id, 'name' => 'Girls Hall', 'gender' => 'female', 'capacity' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $roomId = DB::table('hostel_rooms')->insertGetId([
            'tenant_id' => $tenant->id, 'hostel_id' => $hostelId, 'room_number' => 'G1', 'capacity' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $token = ApiToken::issue($admin, 'hostel-allocation');
        $this->withToken($token)->postJson('/api/v1/hostels/allocations', [
            'student_id' => $studentA,
            'hostel_id' => $hostelId,
            'room_id' => $roomId,
        ])->assertOk();

        $this->assertDatabaseHas('hostel_allocations', [
            'tenant_id' => $tenant->id,
            'student_id' => $studentA,
            'session_id' => $sessionId,
            'status' => 'active',
        ]);

        $this->withToken($token)->postJson('/api/v1/hostels/allocations', [
            'student_id' => $studentA,
            'hostel_id' => $hostelId,
            'room_id' => $roomId,
        ])->assertUnprocessable();

        $this->withToken($token)->postJson('/api/v1/hostels/allocations', [
            'student_id' => $studentB,
            'hostel_id' => $hostelId,
            'room_id' => $roomId,
        ])->assertUnprocessable();
    }

    public function test_allocation_rejects_foreign_room_and_vacate_is_tenant_safe(): void
    {
        [$tenant, $admin] = $this->school('Boundary Hostel School');
        [$foreignTenant] = $this->school('Foreign Hostel School');
        $studentId = $this->student($tenant->id, 'BOUNDARY-01', 'Maryam');
        $foreignHostelId = DB::table('hostels')->insertGetId([
            'tenant_id' => $foreignTenant->id, 'name' => 'Foreign Hall', 'gender' => 'mixed', 'capacity' => 5,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $foreignRoomId = DB::table('hostel_rooms')->insertGetId([
            'tenant_id' => $foreignTenant->id, 'hostel_id' => $foreignHostelId, 'room_number' => 'F1', 'capacity' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $token = ApiToken::issue($admin, 'hostel-boundary');
        $this->withToken($token)->postJson('/api/v1/hostels/allocations', [
            'student_id' => $studentId,
            'hostel_id' => $foreignHostelId,
            'room_id' => $foreignRoomId,
        ])->assertUnprocessable();

        $foreignAllocationId = DB::table('hostel_allocations')->insertGetId([
            'tenant_id' => $foreignTenant->id,
            'student_id' => $this->student($foreignTenant->id, 'FOREIGN-01', 'Foreign'),
            'hostel_id' => $foreignHostelId,
            'room_id' => $foreignRoomId,
            'allocated_at' => now()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)
            ->postJson("/api/v1/hostels/allocations/{$foreignAllocationId}/vacate")
            ->assertNotFound();
    }

    public function test_custom_deny_blocks_hostel_access(): void
    {
        [$tenant, $admin] = $this->school('Denied Hostel School');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'module' => 'hostels',
            'type' => 'deny',
            'granted_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($admin, 'hostel-denied'))
            ->getJson('/api/v1/hostels')
            ->assertForbidden();
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => 'active',
        ]);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' Admin',
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $sessionId = DB::table('academic_sessions')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenant, $admin, $sessionId];
    }

    private function student(int $tenantId, string $admissionNumber, string $name): int
    {
        return DB::table('students')->insertGetId([
            'tenant_id' => $tenantId,
            'admission_number' => $admissionNumber,
            'first_name' => $name,
            'last_name' => 'Student',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
