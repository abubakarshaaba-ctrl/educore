<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileStaffDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;
    private int $otherTenantId;
    private User $admin;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();
        $this->tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Directory School',
            'slug' => 'directory-school',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->otherTenantId = DB::table('tenants')->insertGetId([
            'name' => 'Other School',
            'slug' => 'other-directory-school',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $adminId = DB::table('users')->insertGetId([
            'tenant_id' => $this->tenantId,
            'name' => 'Directory Administrator',
            'email' => 'directory.admin@example.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $teacherId = DB::table('users')->insertGetId([
            'tenant_id' => $this->tenantId,
            'name' => 'Ordinary Teacher',
            'email' => 'ordinary.teacher@example.test',
            'password' => bcrypt('password'),
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rows = [];
        for ($i = 1; $i <= 58; $i++) {
            $rows[] = [
                'tenant_id' => $this->tenantId,
                'name' => sprintf('Staff Member %02d', $i),
                'staff_id' => sprintf('STF-%03d', $i),
                'email' => sprintf('staff%02d@example.test', $i),
                'phone' => sprintf('0800000%04d', $i),
                'password' => bcrypt('password'),
                'role' => 'teacher',
                'is_active' => $i % 4 !== 0,
                'employment_status' => $i % 4 !== 0 ? 'active' : 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('users')->insert($rows);

        DB::table('users')->insert([
            'tenant_id' => $this->otherTenantId,
            'name' => 'Foreign Tenant Staff',
            'staff_id' => 'FOREIGN-001',
            'email' => 'foreign.staff@example.test',
            'phone' => '08009999999',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
            'employment_status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->admin = User::findOrFail($adminId);
        $this->teacher = User::findOrFail($teacherId);
    }

    public function test_administrator_receives_paginated_tenant_scoped_directory(): void
    {
        $token = ApiToken::issue($this->admin, 'android-staff-directory-test');

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/staff?per_page=20&page=1');

        $response
            ->assertOk()
            ->assertJsonCount(20, 'staff')
            ->assertJsonPath('counts.total', 60)
            ->assertJsonPath('counts.active', 46)
            ->assertJsonPath('counts.inactive', 14)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 60)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.has_more', true);

        $payload = $response->json();
        $this->assertNotEmpty($payload['staff']);
        $this->assertArrayNotHasKey('email', $payload['staff'][0]);
        $this->assertArrayNotHasKey('phone', $payload['staff'][0]);
        $this->assertArrayNotHasKey('password', $payload['staff'][0]);
        $this->assertFalse(collect($payload['staff'])->contains(fn ($member) => ($member['staff_id'] ?? null) === 'FOREIGN-001'));
    }

    public function test_directory_supports_server_search_and_active_status_filter(): void
    {
        $token = ApiToken::issue($this->admin, 'android-staff-directory-filter-test');

        $this->withToken($token)
            ->getJson('/api/v1/admin/staff?query=STF-008')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('staff.0.staff_id', 'STF-008')
            ->assertJsonPath('staff.0.active', false);

        $this->withToken($token)
            ->getJson('/api/v1/admin/staff?status=inactive&per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.total', 14)
            ->assertJsonCount(14, 'staff');
    }

    public function test_non_administrator_cannot_open_staff_directory(): void
    {
        $token = ApiToken::issue($this->teacher, 'android-staff-directory-denied-test');

        $this->withToken($token)
            ->getJson('/api/v1/admin/staff')
            ->assertForbidden();
    }
}
