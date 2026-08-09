<?php

namespace Tests\Feature;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\DataMigration;
use App\Models\MigrationDataset;
use App\Models\MigrationMapping;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\CoreEntityPlanningService;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationRowStager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataMigrationCorePlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalisation_is_deterministic_and_preserves_raw_values(): void
    {
        [$migration, $admin] = $this->batch('core-normalise');
        $dataset = $this->dataset($migration, 'student', [[
            'Adm No' => ' ST-001 ', 'First' => '  aDA ', 'Last' => 'okafor', 'Sex' => 'Girl', 'DOB' => '31/12/2012',
        ]], ['Adm No' => 'admission_number', 'First' => 'first_name', 'Last' => 'last_name', 'Sex' => 'gender', 'DOB' => 'date_of_birth']);

        $counts = app(CoreEntityPlanningService::class)->plan($migration, $admin);
        $row = $dataset->rows()->first();

        $this->assertSame(1, $counts['create']);
        $this->assertSame(' ST-001 ', $row->raw_payload['Adm No']);
        $this->assertSame('ST-001', $row->normalised_payload['admission_number']);
        $this->assertSame('Ada', $row->normalised_payload['first_name']);
        $this->assertSame('female', $row->normalised_payload['gender']);
        $this->assertSame('2012-12-31', $row->normalised_payload['date_of_birth']);
        $this->assertDatabaseCount('students', 0);
        $this->assertSame(DataMigrationStatus::Normalising, $migration->refresh()->status);
    }

    public function test_guardian_student_relationship_is_planned_with_resolved_core_parents(): void
    {
        [$migration, $admin] = $this->batch('core-relations');
        $this->dataset($migration, 'student', [['Adm' => 'ST-001', 'First' => 'Ada', 'Last' => 'Okafor']], ['Adm' => 'admission_number', 'First' => 'first_name', 'Last' => 'last_name']);
        $this->dataset($migration, 'guardian', [['Email' => 'PARENT@EXAMPLE.COM ', 'First' => 'Ngozi', 'Last' => 'Okafor', 'Student' => 'ST-001', 'Primary' => 'yes']], ['Email' => 'email', 'First' => 'first_name', 'Last' => 'last_name', 'Student' => 'student_admission_number', 'Primary' => 'is_primary_contact']);

        $counts = app(CoreEntityPlanningService::class)->plan($migration, $admin);

        $this->assertSame(1, $counts['relationships']);
        $relationship = $migration->coreRecords()->where('entity_type', 'guardian_student')->firstOrFail();
        $this->assertSame('create', $relationship->decision);
        $this->assertCount(2, $relationship->dependencies);
        $this->assertFalse($relationship->dependencies->contains('resolution_status', 'unresolved'));
        $this->assertDatabaseCount('guardians', 0);
    }

    public function test_enrolment_is_blocked_when_required_blueprint_parent_is_unresolved(): void
    {
        [$migration, $admin] = $this->batch('core-blocked');
        $this->dataset($migration, 'student', [['Adm' => 'ST-001', 'First' => 'Ada', 'Last' => 'Okafor']], ['Adm' => 'admission_number', 'First' => 'first_name', 'Last' => 'last_name']);
        $this->dataset($migration, 'student_enrollment', [['Adm' => 'ST-001', 'Class' => 'JSS 1 A', 'Session' => '2025/2026', 'Term' => 'First Term']], ['Adm' => 'student_admission_number', 'Class' => 'class_arm', 'Session' => 'session', 'Term' => 'term']);

        $counts = app(CoreEntityPlanningService::class)->plan($migration, $admin);

        $this->assertSame(1, $counts['blocked']);
        $this->assertDatabaseHas('migration_issues', ['category' => 'core_parent_unresolved', 'status' => 'open']);
        $this->assertDatabaseCount('student_enrollments', 0);
    }

    public function test_staff_email_owned_by_another_tenant_is_a_conflict_and_no_password_is_generated(): void
    {
        [$migration, $admin] = $this->batch('core-staff');
        $other = Tenant::create(['name' => 'Other', 'slug' => 'core-other', 'status' => Tenant::STATUS_ACTIVE]);
        User::create(['tenant_id' => $other->id, 'name' => 'Existing', 'email' => 'teacher@example.test', 'password' => bcrypt('secret'), 'role' => 'teacher', 'is_active' => true]);
        $this->dataset($migration, 'staff', [['Name' => 'New Teacher', 'Email' => 'TEACHER@EXAMPLE.TEST', 'Role' => 'teacher']], ['Name' => 'name', 'Email' => 'email', 'Role' => 'role']);

        $counts = app(CoreEntityPlanningService::class)->plan($migration, $admin);
        $record = $migration->coreRecords()->where('entity_type', 'staff')->firstOrFail();

        $this->assertSame(1, $counts['conflict']);
        $this->assertArrayNotHasKey('password', $record->canonical_payload);
        $this->assertDatabaseHas('migration_issues', ['category' => 'core_identity_conflict']);
    }

    public function test_replanning_is_idempotent(): void
    {
        [$migration, $admin] = $this->batch('core-idempotent');
        $this->dataset($migration, 'student', [['Adm' => 'ST-001', 'First' => 'Ada', 'Last' => 'Okafor']], ['Adm' => 'admission_number', 'First' => 'first_name', 'Last' => 'last_name']);
        app(CoreEntityPlanningService::class)->plan($migration, $admin);
        app(CoreEntityPlanningService::class)->plan($migration->refresh(), $admin);
        $this->assertDatabaseCount('migration_core_records', 1);
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
