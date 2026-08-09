<?php

namespace Tests\Feature;

use App\Enums\DataMigrationStatus;
use App\Models\MigrationIssue;
use App\Models\MigrationReconciliation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationEnterpriseControlService;
use App\Services\DataMigration\MigrationEnterpriseDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;

class DataMigrationEnterpriseControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_requires_the_target_schools_administrator(): void
    {
        [$tenant, $admin] = $this->tenantAdmin('enterprise-a');
        [, $otherAdmin] = $this->tenantAdmin('enterprise-b');
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration');

        $this->expectException(UnauthorizedException::class);
        app(MigrationEnterpriseControlService::class)->request($migration, $otherAdmin, 'Replace the legacy SIS.', ['students']);
    }

    public function test_school_and_platform_approvals_are_recorded_notified_and_audited(): void
    {
        [$tenant, $admin] = $this->tenantAdmin('dual-control');
        $platformAdmin = $this->migrationAdmin();
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration');
        $migration->update(['status' => DataMigrationStatus::AwaitingApproval]);
        $controls = app(MigrationEnterpriseControlService::class);

        $request = $controls->request($migration->refresh(), $admin, 'Institution-wide transition from the legacy SIS.', ['students', 'finance']);
        $this->assertSame('critical', $request->risk_level);
        $request = $controls->approveBySchool($request, $admin, 'Scope and timing accepted by the institution.');
        $request = $controls->approveByPlatform($request, $platformAdmin, 'Dry run and safeguards verified.');

        $this->assertSame('approved', $request->status);
        $this->assertSame(DataMigrationStatus::Approved, $migration->refresh()->status);
        $this->assertDatabaseHas('migration_approvals', ['migration_id' => $migration->id, 'approval_type' => 'school_admin', 'decision' => 'approved']);
        $this->assertDatabaseHas('migration_approvals', ['migration_id' => $migration->id, 'approval_type' => 'platform_execution', 'decision' => 'approved']);
        $this->assertDatabaseHas('migration_notifications', ['migration_id' => $migration->id, 'recipient_user_id' => $platformAdmin->id, 'event' => 'migration.school_approved']);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => $request::class, 'auditable_id' => $request->id, 'action' => 'data_migration.platform_approved']);
    }

    public function test_migration_administrator_has_least_privilege_execution_access(): void
    {
        [$tenant, $admin] = $this->tenantAdmin('least-privilege');
        $migrationAdmin = $this->migrationAdmin();
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'standard_import');

        $this->assertFalse(Gate::forUser($admin)->allows('execute', $migration));
        $this->assertTrue(Gate::forUser($migrationAdmin)->allows('view', $migration));
        $this->assertTrue(Gate::forUser($migrationAdmin)->allows('execute', $migration));
        $this->assertFalse($migrationAdmin->isSuperAdmin());
    }

    public function test_dashboard_is_platform_wide_for_migration_admin_and_tenant_scoped_for_school_admin(): void
    {
        [$tenantA, $adminA] = $this->tenantAdmin('dashboard-a');
        [$tenantB, $adminB] = $this->tenantAdmin('dashboard-b');
        app(MigrationBatchService::class)->create($tenantA, $adminA, 'inbound', 'standard_import');
        app(MigrationBatchService::class)->create($tenantB, $adminB, 'inbound', 'standard_import');
        $dashboard = app(MigrationEnterpriseDashboardService::class);

        $this->assertSame(1, $dashboard->summary($adminA)['migrations']['total']);
        $this->assertSame(2, $dashboard->summary($this->migrationAdmin())['migrations']['total']);

        $this->expectException(UnauthorizedException::class);
        $dashboard->summary($adminA, $tenantB->id);
    }

    public function test_report_contains_control_decisions_issues_and_reconciliation_results(): void
    {
        [$tenant, $admin] = $this->tenantAdmin('reporting');
        $migration = app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'standard_import');
        MigrationIssue::create(['migration_id' => $migration->id, 'severity' => 'high', 'category' => 'validation', 'message' => 'Unknown student.', 'status' => 'open']);
        MigrationReconciliation::create(['migration_id' => $migration->id, 'scope' => 'students', 'source_count' => 10, 'destination_count' => 9, 'status' => 'failed']);

        $report = app(MigrationEnterpriseDashboardService::class)->report($migration, $admin);

        $this->assertSame($migration->batch_number, $report['migration']['batch_number']);
        $this->assertSame('high', $report['issues'][0]['severity']);
        $this->assertSame('failed', $report['reconciliations'][0]['status']);
    }

    private function tenantAdmin(string $slug): array
    {
        $tenant = Tenant::create(['name' => str($slug)->headline(), 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        $admin = User::create(['tenant_id' => $tenant->id, 'name' => 'School Administrator', 'email' => "{$slug}@example.test", 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'is_super_admin' => false]);

        return [$tenant, $admin];
    }

    private function migrationAdmin(): User
    {
        return User::create(['tenant_id' => null, 'name' => 'Migration Administrator', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password'), 'role' => 'staff', 'is_active' => true, 'is_super_admin' => false, 'is_migration_admin' => true]);
    }
}
