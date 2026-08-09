<?php

namespace Tests\Feature;

use App\Models\DataMigration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\MigrationBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataMigrationManagementUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_sidebar_exposes_tenant_scoped_migration_center(): void
    {
        [$tenant, $admin] = $this->tenantAdmin('migration-ui-school');
        app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'standard_import');

        $response = $this->withoutMiddleware()->actingAs($admin)->get(route('migrations.index'));

        $response->assertOk()
            ->assertSee('Migration Center')
            ->assertSee('Start new migration')
            ->assertSee('Upload source exports or ZIP package')
            ->assertSee('Migration batches');
    }

    public function test_super_admin_sidebar_exposes_platform_migration_control(): void
    {
        $superAdmin = User::create([
            'name' => 'Platform Administrator', 'email' => 'platform-migrations@example.test',
            'password' => bcrypt('password'), 'role' => 'super_admin', 'is_active' => true, 'is_super_admin' => true,
        ]);

        $response = $this->withoutMiddleware()->actingAs($superAdmin)->get(route('super.migrations.index'));

        $response->assertOk()
            ->assertSee('Migration Control')
            ->assertSee('Institution oversight')
            ->assertSee('All institutions')
            ->assertSee('Destination institution')
            ->assertSee('Source platform');
    }

    public function test_school_admin_can_create_a_migration_request_from_the_interface(): void
    {
        Storage::fake('local');
        [$tenant, $admin] = $this->tenantAdmin('migration-request-ui');

        $response = $this->withoutMiddleware()->actingAs($admin)->post(route('migrations.store'), [
            'direction' => 'inbound',
            'migration_type' => 'full_migration',
            'source_platform' => 'Legacy database',
            'source_files' => [UploadedFile::fake()->createWithContent('students.csv', "admission_number,name\nA001,Amina Bello\n")],
            'business_justification' => 'Replace the existing school information system with EduCore.',
            'data_scope' => ['students', 'academics', 'finance'],
        ]);

        $migration = DataMigration::query()->firstOrFail();
        $response->assertRedirect(route('migrations.show', $migration));
        $this->assertDatabaseHas('migration_requests', [
            'migration_id' => $migration->id,
            'tenant_id' => $tenant->id,
            'status' => 'awaiting_school_approval',
        ]);
        $this->assertDatabaseHas('migration_files', ['migration_id' => $migration->id, 'original_filename' => 'students.csv']);
    }

    public function test_super_admin_can_upload_another_platform_export_for_a_selected_school(): void
    {
        Storage::fake('local');
        [$tenant] = $this->tenantAdmin('platform-upload-school');
        $superAdmin = User::create([
            'name' => 'Platform Administrator', 'email' => 'platform-upload@example.test',
            'password' => bcrypt('password'), 'role' => 'super_admin', 'is_active' => true, 'is_super_admin' => true,
        ]);

        $response = $this->withoutMiddleware()->actingAs($superAdmin)->post(route('super.migrations.store'), [
            'tenant_id' => $tenant->id,
            'direction' => 'inbound',
            'migration_type' => 'full_migration',
            'source_platform' => 'PowerSchool',
            'business_justification' => 'Platform-assisted onboarding from the institution previous system.',
            'data_scope' => ['students', 'guardians'],
            'source_files' => [UploadedFile::fake()->createWithContent('powerschool-export.csv', "student_number,name\nP001,John Doe\n")],
        ]);

        $migration = DataMigration::query()->firstOrFail();
        $response->assertRedirect(route('super.migrations.show', $migration));
        $this->assertSame($tenant->id, $migration->tenant_id);
        $this->assertSame('PowerSchool', $migration->source_system);
        $this->assertDatabaseHas('migration_files', ['migration_id' => $migration->id, 'original_filename' => 'powerschool-export.csv']);
    }

    private function tenantAdmin(string $slug): array
    {
        $tenant = Tenant::create(['name' => str($slug)->headline(), 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        $admin = User::create([
            'tenant_id' => $tenant->id, 'name' => 'School Administrator', 'email' => "{$slug}@example.test",
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'is_super_admin' => false,
        ]);

        return [$tenant, $admin];
    }
}
