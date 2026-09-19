<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_conventional_subject_can_be_created_with_unique_code(): void
    {
        [$tenant, $admin] = $this->tenantAdmin();

        $response = $this->withoutMiddleware()
            ->actingAs($admin)
            ->post(route('subjects.store'), [
                'name' => 'Biology',
                'code' => 'bio',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'tenant_id' => $tenant->id,
            'name' => 'Biology',
            'code' => 'BIO',
            'is_active' => 1,
        ]);
    }

    public function test_duplicate_subject_code_returns_validation_error_instead_of_server_error(): void
    {
        [$tenant, $admin] = $this->tenantAdmin();

        Subject::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Basic Science',
            'code' => 'BSC',
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware()
            ->actingAs($admin)
            ->from(route('subjects.create'))
            ->post(route('subjects.store'), [
                'name' => 'Business Studies',
                'code' => 'bsc',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('subjects.create'));
        $response->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('subjects', [
            'tenant_id' => $tenant->id,
            'name' => 'Business Studies',
        ]);
    }

    public function test_subject_name_and_code_uniqueness_are_scoped_per_tenant(): void
    {
        [$firstTenant] = $this->tenantAdmin('first');
        [$secondTenant, $secondAdmin] = $this->tenantAdmin('second');

        Subject::withoutTenantScope()->create([
            'tenant_id' => $firstTenant->id,
            'name' => 'Mathematics',
            'code' => 'MTH',
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware()
            ->actingAs($secondAdmin)
            ->post(route('subjects.store'), [
                'name' => 'Mathematics',
                'code' => 'MTH',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'tenant_id' => $secondTenant->id,
            'name' => 'Mathematics',
            'code' => 'MTH',
        ]);
    }

    private function tenantAdmin(string $suffix = 'school'): array
    {
        $tenant = Tenant::create([
            'name' => 'Subject Test '.ucfirst($suffix),
            'slug' => 'subject-test-'.$suffix.'-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'School Admin',
            'email' => 'subject-admin-'.$suffix.'-'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $admin];
    }
}
