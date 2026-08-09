<?php

namespace Tests\Feature;

use App\Enums\DataMigrationStatus;
use App\Exceptions\InvalidMigrationStateTransition;
use App\Models\MigrationDataset;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\ImmutableSourceStorage;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationRowStager;
use App\Services\DataMigration\MigrationStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataMigrationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_creation_is_tenant_scoped_and_audited(): void
    {
        [$tenant, $admin] = $this->tenantAdmin('alpha');
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration', 'Legacy SIS');

        $this->assertSame($tenant->id, $migration->tenant_id);
        $this->assertSame(DataMigrationStatus::Uploaded, $migration->status);
        $this->assertStringStartsWith('MIG-', $migration->batch_number);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'auditable_type' => $migration::class, 'auditable_id' => $migration->id, 'action' => 'data_migration.requested']);
    }

    public function test_tenant_admin_cannot_view_another_tenants_migration(): void
    {
        [$tenantA, $adminA] = $this->tenantAdmin('tenant-a');
        [, $adminB] = $this->tenantAdmin('tenant-b');
        $migration = app(MigrationBatchService::class)->create($tenantA, $adminA, 'inbound', 'standard_import');

        $this->assertTrue(Gate::forUser($adminA)->allows('view', $migration));
        $this->assertFalse(Gate::forUser($adminB)->allows('view', $migration));
        $this->assertFalse(Gate::forUser($adminA)->allows('execute', $migration));
    }

    public function test_source_is_preserved_with_verified_checksum_and_immutable_metadata(): void
    {
        Storage::fake('local');
        config(['data_migration.source_disk' => 'local']);
        [$tenant, $admin] = $this->tenantAdmin('source-school');
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration');
        $upload = UploadedFile::fake()->createWithContent('students.csv', "admission_number,legacy_special_code\nA001,KEEP-ME\n");

        $file = app(ImmutableSourceStorage::class)->preserve($migration, $upload, $admin);

        Storage::disk('local')->assertExists($file->storage_path);
        $this->assertTrue(app(ImmutableSourceStorage::class)->verify($file));
        $this->assertSame(hash('sha256', $upload->getContent()), $file->sha256);
        $this->expectException(\LogicException::class);
        $file->update(['sha256' => str_repeat('0', 64)]);
    }

    public function test_staging_preserves_unknown_fields_and_is_idempotent_by_source_row(): void
    {
        [$tenant, $admin] = $this->tenantAdmin('staging-school');
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration');
        $dataset = MigrationDataset::create(['migration_id' => $migration->id, 'source_name' => 'students', 'classification_status' => 'unclassified']);
        $payload = ['admission_number' => 'A001', 'legacy_special_code' => 'KEEP-ME'];

        $first = app(MigrationRowStager::class)->stage($migration, $dataset, 1, $payload, 'A001');
        $second = app(MigrationRowStager::class)->stage($migration, $dataset, 1, $payload, 'A001');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('KEEP-ME', $first->raw_payload['legacy_special_code']);
        $this->assertDatabaseCount('migration_rows', 1);
    }

    public function test_state_machine_allows_only_declared_transitions(): void
    {
        [$tenant, $admin] = $this->tenantAdmin('state-school');
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration');
        $migration = app(MigrationStateMachine::class)->transition($migration, DataMigrationStatus::Inspecting, $admin);
        $this->assertSame(DataMigrationStatus::Inspecting, $migration->status);

        $this->expectException(InvalidMigrationStateTransition::class);
        app(MigrationStateMachine::class)->transition($migration, DataMigrationStatus::Completed, $admin);
    }

    private function tenantAdmin(string $slug): array
    {
        $tenant = Tenant::create(['name' => str($slug)->headline(), 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        $admin = User::create(['tenant_id' => $tenant->id, 'name' => 'Migration Admin', 'email' => "{$slug}@example.test", 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'is_super_admin' => false]);

        return [$tenant, $admin];
    }
}
