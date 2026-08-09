<?php

namespace Tests\Feature;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\MigrationDataset;
use App\Models\MigrationMapping;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\FinancialMigrationPlanningService;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationRowStager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataMigrationFinancialPlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_balanced_financial_plan_reconciles_without_live_writes(): void
    {
        [$m,$a] = $this->batch('finance-pass');
        $this->dataset($m, 'invoice', [['No' => 'INV-1', 'Student' => 'ST-1', 'Term' => 'First Term', 'Session' => '2025/2026', 'Total' => '1,000.00', 'Paid' => '400']], ['No' => 'invoice_number', 'Student' => 'student_admission_number', 'Term' => 'term', 'Session' => 'session', 'Total' => 'total_amount', 'Paid' => 'amount_paid']);
        $this->dataset($m, 'payment', [['Ref' => 'PAY-1', 'Invoice' => 'INV-1', 'Student' => 'ST-1', 'Amount' => '400.00', 'Currency' => 'ngn', 'Status' => 'success']], ['Ref' => 'gateway_reference', 'Invoice' => 'invoice_number', 'Student' => 'student_admission_number', 'Amount' => 'amount_paid', 'Currency' => 'currency', 'Status' => 'status']);
        app(FinancialMigrationPlanningService::class)->plan($m, $a);
        $this->assertDatabaseHas('migration_reconciliations', ['scope' => 'financial_plan', 'status' => 'passed', 'source_total' => 1000]);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertSame('1000.00', $m->financialRecords()->where('entity_type', 'invoice')->first()->canonical_payload['total_amount']);
    }

    public function test_mismatched_payment_total_fails_reconciliation(): void
    {
        [$m,$a] = $this->batch('finance-fail');
        $this->dataset($m, 'invoice', [['No' => 'INV-2', 'Student' => 'ST-2', 'Term' => 'First', 'Session' => '2025', 'Total' => '500.00', 'Paid' => '300.00']], ['No' => 'invoice_number', 'Student' => 'student_admission_number', 'Term' => 'term', 'Session' => 'session', 'Total' => 'total_amount', 'Paid' => 'amount_paid']);
        $this->dataset($m, 'payment', [['Ref' => 'P2', 'Invoice' => 'INV-2', 'Student' => 'ST-2', 'Amount' => '200.00', 'Currency' => 'NGN', 'Status' => 'success']], ['Ref' => 'gateway_reference', 'Invoice' => 'invoice_number', 'Student' => 'student_admission_number', 'Amount' => 'amount_paid', 'Currency' => 'currency', 'Status' => 'status']);
        app(FinancialMigrationPlanningService::class)->plan($m, $a);
        $this->assertDatabaseHas('migration_reconciliations', ['scope' => 'financial_plan', 'status' => 'failed']);
        $this->assertDatabaseHas('migration_issues', ['category' => 'financial_reconciliation_failed']);
    }

    private function batch(string $s): array
    {
        $t = Tenant::create(['name' => $s, 'slug' => $s, 'status' => Tenant::STATUS_ACTIVE]);
        $a = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => "$s@test.local", 'password' => bcrypt('x'), 'role' => 'admin', 'is_active' => true]);
        $m = app(MigrationBatchService::class)->create($t, $a, 'inbound', 'full_migration');
        $m->update(['status' => DataMigrationStatus::Normalising]);

        return [$m->refresh(), $a];
    }

    private function dataset($m, string $e, array $rows, array $maps): void
    {
        $d = MigrationDataset::create(['migration_id' => $m->id, 'source_name' => $e, 'canonical_entity' => $e, 'classification_status' => 'classified']);
        foreach ($maps as $s => $f) {
            MigrationMapping::create(['migration_id' => $m->id, 'dataset_id' => $d->id, 'source_column' => $s, 'destination_entity' => $e, 'destination_field' => $f, 'decision' => MigrationMappingDecision::AutoMap, 'confidence' => 100]);
        }foreach ($rows as $i => $r) {
            app(MigrationRowStager::class)->stage($m,$d,$i + 1,$r);
        }
    }
}
