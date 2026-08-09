<?php

namespace Tests\Feature;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\DataMigration;
use App\Models\MigrationBlueprintNode;
use App\Models\MigrationCoreRecord;
use App\Models\MigrationDataset;
use App\Models\MigrationMapping;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\AcademicHistoryPlanningService;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationRowStager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataMigrationAcademicHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_history_is_normalized_and_dependency_planned_without_live_write(): void
    {
        [$migration, $admin] = $this->batch('history-score');
        $this->parents($migration);
        $dataset = $this->dataset($migration, 'score', [['Student' => 'ST-1', 'Subject' => 'MTH', 'Assessment' => 'CA 1', 'Term' => 'First Term', 'Session' => '2025/2026', 'Mark' => '18.50']], ['Student' => 'student_admission_number', 'Subject' => 'subject_code', 'Assessment' => 'assessment_type', 'Term' => 'term', 'Session' => 'session', 'Mark' => 'score']);
        $counts = app(AcademicHistoryPlanningService::class)->plan($migration, $admin);
        $record = $migration->historicalRecords()->firstOrFail();
        $this->assertSame(1, $counts['create']);
        $this->assertSame(18.5, $record->canonical_payload['score']);
        $this->assertCount(5, $record->dependencies);
        $this->assertFalse($record->dependencies->contains('resolution_status', 'unresolved'));
        $this->assertDatabaseCount('scores', 0);
        $this->assertSame('18.50', $dataset->rows()->first()->raw_payload['Mark']);
    }

    public function test_invalid_attendance_and_missing_parents_are_blocked(): void
    {
        [$migration, $admin] = $this->batch('history-blocked');
        $this->dataset($migration, 'attendance', [['Student' => 'UNKNOWN', 'Class' => 'JSS 1 A', 'Term' => 'First Term', 'Date' => '01/02/2025', 'Status' => 'Holiday']], ['Student' => 'student_admission_number', 'Class' => 'class_arm', 'Term' => 'term', 'Date' => 'attendance_date', 'Status' => 'status']);
        $counts = app(AcademicHistoryPlanningService::class)->plan($migration, $admin);
        $this->assertSame(1, $counts['blocked']);
        $this->assertDatabaseHas('migration_issues', ['category' => 'historical_validation']);
        $this->assertDatabaseHas('migration_issues', ['category' => 'historical_parent_unresolved']);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_conflicting_composite_history_is_visible_and_replanning_is_idempotent(): void
    {
        [$migration, $admin] = $this->batch('history-conflict');
        $this->parents($migration);
        $this->dataset($migration, 'score', [
            ['Student' => 'ST-1', 'Subject' => 'MTH', 'Assessment' => 'CA 1', 'Term' => 'First Term', 'Session' => '2025/2026', 'Mark' => '18'],
            ['Student' => 'ST-1', 'Subject' => 'MTH', 'Assessment' => 'CA 1', 'Term' => 'First Term', 'Session' => '2025/2026', 'Mark' => '19'],
        ], ['Student' => 'student_admission_number', 'Subject' => 'subject_code', 'Assessment' => 'assessment_type', 'Term' => 'term', 'Session' => 'session', 'Mark' => 'score']);
        app(AcademicHistoryPlanningService::class)->plan($migration, $admin);
        app(AcademicHistoryPlanningService::class)->plan($migration->refresh(), $admin);
        $this->assertDatabaseCount('migration_historical_records', 1);
        $this->assertDatabaseHas('migration_historical_records', ['decision' => 'conflict']);
        $this->assertDatabaseHas('migration_issues', ['category' => 'historical_duplicate_conflict']);
    }

    private function parents(DataMigration $migration): void
    {
        MigrationCoreRecord::create(['migration_id' => $migration->id, 'tenant_id' => $migration->tenant_id, 'entity_type' => 'student', 'source_key' => 'st-1', 'canonical_payload' => ['admission_number' => 'ST-1'], 'decision' => 'create', 'payload_checksum' => hash('sha256', 'student')]);
        foreach ([['subject', 'mth', ['code' => 'MTH']], ['assessment_type', 'first term|ca 1', ['name' => 'CA 1', 'term' => 'First Term']], ['term', '2025/2026|first term', ['name' => 'First Term', 'session' => '2025/2026']], ['academic_session', '2025/2026', ['name' => '2025/2026']]] as [$entity, $key, $payload]) {
            MigrationBlueprintNode::create(['migration_id' => $migration->id, 'tenant_id' => $migration->tenant_id, 'entity_type' => $entity, 'source_key' => $key, 'canonical_payload' => $payload, 'decision' => 'proposed', 'confidence' => 0, 'payload_checksum' => hash('sha256', $entity)]);
        }
    }

    private function batch(string $slug): array
    {
        $tenant = Tenant::create(['name' => str($slug)->headline(), 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        $admin = User::create(['tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => "{$slug}@example.test", 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true]);
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration', 'Legacy');
        $migration->update(['status' => DataMigrationStatus::Normalising]);

        return [$migration->refresh(), $admin];
    }

    private function dataset(DataMigration $migration, string $entity, array $rows, array $mappings): MigrationDataset
    {
        $dataset = MigrationDataset::create(['migration_id' => $migration->id, 'source_name' => $entity, 'canonical_entity' => $entity, 'classification_status' => 'classified', 'source_schema' => ['headers' => array_keys($rows[0])]]);
        foreach ($mappings as $source => $field) {
            MigrationMapping::create(['migration_id' => $migration->id, 'dataset_id' => $dataset->id, 'source_column' => $source, 'destination_entity' => $entity, 'destination_field' => $field, 'decision' => MigrationMappingDecision::AutoMap, 'confidence' => 100]);
        }
        foreach ($rows as $i => $row) {
            app(MigrationRowStager::class)->stage($migration, $dataset, $i + 1, $row);
        }

        return $dataset;
    }
}
