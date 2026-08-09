<?php

namespace Tests\Feature;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\MigrationDataset;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\CanonicalSchemaRegistry;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationMappingProfileService;
use App\Services\DataMigration\MigrationMappingService;
use App\Services\DataMigration\MigrationRowStager;
use App\Services\DataMigration\MigrationStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DataMigrationMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_is_versioned_and_uses_business_relationship_references(): void
    {
        $student = app(CanonicalSchemaRegistry::class)->entity('student');
        $this->assertSame('1.0', $student->version);
        $this->assertTrue($student->field('admission_number')->tenantUnique);
        $this->assertSame('class_arm', $student->field('current_class_arm')->relationshipEntity);
    }

    public function test_aliases_auto_map_and_unknown_columns_are_never_silently_dropped(): void
    {
        [$migration, $dataset, $admin] = $this->staged(['Admission No' => 'A001', 'Surname' => 'Okafor', 'First Name' => 'Ada', 'Legacy House Code' => 'RED']);
        $mappings = collect(app(MigrationMappingService::class)->generate($migration, $dataset, 'student', $admin))->keyBy('source_column');
        $this->assertSame(MigrationMappingDecision::AutoMap, $mappings['Admission No']->decision);
        $this->assertSame('admission_number', $mappings['Admission No']->destination_field);
        $this->assertSame(MigrationMappingDecision::Unmapped, $mappings['Legacy House Code']->decision);
        $this->assertDatabaseHas('migration_issues', ['field' => 'Legacy House Code', 'category' => 'unmapped_column', 'status' => 'open']);
        $this->assertSame('RED', $dataset->rows()->first()->raw_payload['Legacy House Code']);
        $this->assertDatabaseCount('students', 0);
        $this->assertSame(DataMigrationStatus::NeedsReview, $migration->refresh()->status);
    }

    public function test_manual_decisions_are_validated_audited_and_can_complete_mapping(): void
    {
        [$migration, $dataset, $admin] = $this->staged(['Admission No' => 'A001', 'Mystery' => 'x']);
        $mappings = collect(app(MigrationMappingService::class)->generate($migration, $dataset, 'student', $admin))->keyBy('source_column');
        $this->expectException(InvalidArgumentException::class);
        app(MigrationMappingService::class)->override($mappings['Mystery'], $admin, MigrationMappingDecision::IgnoreExplicitly);
    }

    public function test_explicit_ignore_requires_reason_resolves_issue_and_profile_is_reusable(): void
    {
        [$migration, $dataset, $admin] = $this->staged(['Admission No' => 'A001', 'Surname' => 'Okafor', 'First Name' => 'Ada', 'Mystery' => 'x'], 'LegacyPro');
        $mappings = collect(app(MigrationMappingService::class)->generate($migration, $dataset, 'student', $admin))->keyBy('source_column');
        app(MigrationMappingService::class)->override($mappings['Mystery'], $admin, MigrationMappingDecision::IgnoreExplicitly, reason: 'Legacy-only reporting column.');
        $this->assertSame(DataMigrationStatus::Mapped, $migration->refresh()->status);
        $this->assertDatabaseHas('migration_issues', ['field' => 'Mystery', 'status' => 'resolved']);
        $profile = app(MigrationMappingProfileService::class)->save($migration, $dataset->refresh(), $admin, 'LegacyPro Students');
        $this->assertSame($migration->tenant_id, $profile->tenant_id);
        $this->assertArrayHasKey('Admission No', $profile->mappings);
        $this->assertDatabaseHas('audit_logs', ['action' => 'data_migration.mapping_overridden']);

        $second = app(MigrationBatchService::class)->create($migration->tenant, $admin, 'inbound', 'full_migration', 'LegacyPro');
        $second = app(MigrationStateMachine::class)->transition($second, DataMigrationStatus::Inspecting, $admin);
        $second = app(MigrationStateMachine::class)->transition($second, DataMigrationStatus::Extracted, $admin);
        $secondDataset = MigrationDataset::create(['migration_id' => $second->id, 'source_name' => 'students-2', 'source_schema' => ['headers' => ['Admission No', 'Mystery']]]);
        app(MigrationRowStager::class)->stage($second, $secondDataset, 1, ['Admission No' => 'A002', 'Mystery' => 'y']);
        $reused = collect(app(MigrationMappingService::class)->generate($second, $secondDataset, 'student', $admin))->keyBy('source_column');
        $this->assertSame(MigrationMappingDecision::IgnoreExplicitly, $reused['Mystery']->decision);
        $this->assertSame('saved_profile_explicit_ignore', $reused['Mystery']->metadata['basis']);
        $this->assertSame(1, $profile->refresh()->usage_count);
    }

    public function test_mapping_profiles_are_tenant_scoped_and_cross_tenant_mapping_is_denied(): void
    {
        [$migration, $dataset] = $this->staged(['Admission No' => 'A001']);
        [, , $otherAdmin] = $this->staged(['Admission No' => 'B001'], 'Other', 'other');
        $this->expectException(InvalidArgumentException::class);
        app(MigrationMappingService::class)->generate($migration, $dataset, 'student', $otherAdmin);
    }

    private function staged(array $payload, string $source = 'Legacy SIS', string $slug = 'mapping-school'): array
    {
        $tenant = Tenant::create(['name' => str($slug)->headline(), 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        $admin = User::create(['tenant_id' => $tenant->id, 'name' => 'Migration Admin', 'email' => "{$slug}@example.test", 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'is_super_admin' => false]);
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration', $source);
        $migration = app(MigrationStateMachine::class)->transition($migration, DataMigrationStatus::Inspecting, $admin);
        $migration = app(MigrationStateMachine::class)->transition($migration, DataMigrationStatus::Extracted, $admin);
        $dataset = MigrationDataset::create(['migration_id' => $migration->id, 'source_name' => 'students', 'classification_status' => 'unclassified', 'source_schema' => ['headers' => array_keys($payload)]]);
        app(MigrationRowStager::class)->stage($migration, $dataset, 1, $payload);

        return [$migration->refresh(), $dataset->refresh(), $admin];
    }
}
