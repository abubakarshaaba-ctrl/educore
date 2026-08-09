<?php

namespace Tests\Feature;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\AcademicSession;
use App\Models\DataMigration;
use App\Models\MigrationDataset;
use App\Models\MigrationMapping;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationRowStager;
use App\Services\DataMigration\SchoolBlueprintReconstructionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DataMigrationBlueprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconstruction_matches_existing_and_proposes_missing_structures_without_live_writes(): void
    {
        [$migration, $admin, $tenant] = $this->batch('blueprint-match');
        AcademicSession::create(['tenant_id' => $tenant->id, 'name' => '2025/2026', 'is_current' => false]);
        $this->dataset($migration, 'academic_session', [['Session' => '2025/2026'], ['Session' => '2026/2027']], ['Session' => 'name']);

        $counts = app(SchoolBlueprintReconstructionService::class)->reconstruct($migration, $admin);

        $this->assertSame(1, $counts['matched']);
        $this->assertSame(1, $counts['proposed']);
        $this->assertDatabaseHas('migration_blueprint_nodes', ['entity_type' => 'academic_session', 'source_key' => '2025/2026', 'decision' => 'matched']);
        $this->assertDatabaseHas('migration_blueprint_nodes', ['entity_type' => 'academic_session', 'source_key' => '2026/2027', 'decision' => 'proposed']);
        $this->assertDatabaseCount('academic_sessions', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'data_migration.blueprint_reconstructed']);

        app(SchoolBlueprintReconstructionService::class)->reconstruct($migration->refresh(), $admin);
        $this->assertDatabaseCount('migration_blueprint_nodes', 2);
    }

    public function test_dependencies_are_resolved_against_staged_parent_nodes(): void
    {
        [$migration, $admin] = $this->batch('blueprint-deps');
        $this->dataset($migration, 'academic_session', [['Session' => '2025/2026']], ['Session' => 'name']);
        $this->dataset($migration, 'term', [['Session' => '2025/2026', 'Term' => 'First Term']], ['Session' => 'session', 'Term' => 'name']);

        $counts = app(SchoolBlueprintReconstructionService::class)->reconstruct($migration, $admin);

        $this->assertSame(0, $counts['unresolved_dependencies']);
        $this->assertDatabaseHas('migration_blueprint_dependencies', ['relationship_field' => 'session', 'parent_entity_type' => 'academic_session', 'parent_source_key' => '2025/2026', 'resolution_status' => 'resolved']);
        $this->assertDatabaseCount('terms', 0);
    }

    public function test_missing_parent_and_conflicting_definitions_are_visible_issues(): void
    {
        [$migration, $admin] = $this->batch('blueprint-conflict');
        $this->dataset($migration, 'term', [['Session' => 'Missing Year', 'Term' => 'First Term']], ['Session' => 'session', 'Term' => 'name']);
        $this->dataset($migration, 'subject', [['Code' => 'MTH', 'Name' => 'Mathematics'], ['Code' => 'MTH', 'Name' => 'Further Mathematics']], ['Code' => 'code', 'Name' => 'name']);

        $counts = app(SchoolBlueprintReconstructionService::class)->reconstruct($migration, $admin);

        $this->assertSame(1, $counts['unresolved_dependencies']);
        $this->assertDatabaseHas('migration_issues', ['category' => 'blueprint_parent_unresolved', 'status' => 'open']);
        $this->assertDatabaseHas('migration_issues', ['category' => 'blueprint_conflict', 'status' => 'open']);
    }

    public function test_manual_match_is_tenant_scoped(): void
    {
        [$migration, $admin] = $this->batch('blueprint-review');
        [, , $otherTenant] = $this->batch('blueprint-other');
        $otherSession = AcademicSession::create(['tenant_id' => $otherTenant->id, 'name' => '2025/2026']);
        $this->dataset($migration, 'academic_session', [['Session' => '2026/2027']], ['Session' => 'name']);
        app(SchoolBlueprintReconstructionService::class)->reconstruct($migration, $admin);

        $this->expectException(InvalidArgumentException::class);
        app(SchoolBlueprintReconstructionService::class)->review($migration->blueprintNodes()->firstOrFail(), $admin, 'matched', $otherSession->id);
    }

    private function batch(string $slug): array
    {
        $tenant = Tenant::create(['name' => str($slug)->headline(), 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        $admin = User::create(['tenant_id' => $tenant->id, 'name' => 'Migration Admin', 'email' => "{$slug}@example.test", 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'is_super_admin' => false]);
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration', 'Legacy SIS');
        $migration->update(['status' => DataMigrationStatus::Mapped]);

        return [$migration->refresh(), $admin, $tenant];
    }

    private function dataset(DataMigration $migration, string $entity, array $rows, array $mappings): MigrationDataset
    {
        $dataset = MigrationDataset::create(['migration_id' => $migration->id, 'source_name' => $entity, 'canonical_entity' => $entity, 'classification_status' => 'classified', 'source_schema' => ['headers' => array_keys($rows[0])]]);
        foreach ($mappings as $source => $destination) {
            MigrationMapping::create(['migration_id' => $migration->id, 'dataset_id' => $dataset->id, 'source_column' => $source, 'destination_entity' => $entity, 'destination_field' => $destination, 'decision' => MigrationMappingDecision::AutoMap, 'confidence' => 100]);
        }
        foreach ($rows as $index => $payload) {
            app(MigrationRowStager::class)->stage($migration, $dataset, $index + 1, $payload);
        }

        return $dataset;
    }
}
