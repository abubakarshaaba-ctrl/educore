<?php

namespace Tests\Feature;

use App\Enums\DataMigrationStatus;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationChangeJournalService;
use App\Services\DataMigration\MigrationReconciliationService;
use App\Services\DataMigration\MigrationRollbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DataMigrationRollbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_before_image_restores_update_and_created_record_is_deleted(): void
    {
        $t = Tenant::create(['name' => 'Rollback', 'slug' => 'rollback', 'status' => Tenant::STATUS_ACTIVE]);
        $u = $this->super();
        $m = app(MigrationBatchService::class)->create($t, $u, 'inbound', 'full_migration');
        $old = Student::create(['tenant_id' => $t->id, 'admission_number' => 'OLD', 'first_name' => 'Ada', 'last_name' => 'One']);
        $before = app(MigrationChangeJournalService::class)->snapshot($old);
        $old->update(['last_name' => 'Changed']);
        app(MigrationChangeJournalService::class)->record($m, 'student', $old, 'updated_by_migration', $before, $u);
        $new = Student::create(['tenant_id' => $t->id, 'admission_number' => 'NEW', 'first_name' => 'New', 'last_name' => 'Student']);
        app(MigrationChangeJournalService::class)->record($m, 'student', $new, 'created_by_migration', [], $u);
        $m->update(['status' => DataMigrationStatus::Completed]);
        $c = app(MigrationRollbackService::class)->rollback($m, $u);
        $this->assertSame(1, $c['restored']);
        $this->assertSame(1, $c['deleted']);
        $this->assertSame('One', $old->refresh()->last_name);
        $this->assertDatabaseMissing('students', ['admission_number' => 'NEW']);
        $this->assertSame(DataMigrationStatus::RolledBack, $m->refresh()->status);
    }

    public function test_drift_requires_compensation_and_failed_reconciliation_blocks_completion(): void
    {
        $t = Tenant::create(['name' => 'Drift', 'slug' => 'drift', 'status' => Tenant::STATUS_ACTIVE]);
        $u = $this->super('drift');
        $m = app(MigrationBatchService::class)->create($t, $u, 'inbound', 'full_migration');
        $s = Student::create(['tenant_id' => $t->id, 'admission_number' => 'D1', 'first_name' => 'A', 'last_name' => 'B']);
        app(MigrationChangeJournalService::class)->record($m, 'student', $s, 'created_by_migration', [], $u);
        $s->update(['last_name' => 'External']);
        $m->update(['status' => DataMigrationStatus::Completed]);
        $c = app(MigrationRollbackService::class)->rollback($m, $u);
        $this->assertSame(1, $c['compensation_required']);
        $this->assertDatabaseHas('migration_change_journals', ['rollback_status' => 'compensation_required']);
        $m->update(['status' => DataMigrationStatus::Reconciling, 'total_created' => 1]);
        $this->expectException(InvalidArgumentException::class);
        app(MigrationReconciliationService::class)->reconcile($m, $u, true);
    }

    private function super(string $x = 'base'): User
    {
        return User::create(['name' => 'Super', 'email' => "super-$x@test", 'password' => bcrypt('x'), 'is_super_admin' => true, 'is_active' => true]);
    }
}
