<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Enums\MigrationMappingDecision;
use App\Models\DataMigration;
use App\Models\Invoice;
use App\Models\MigrationFinancialRecord;
use App\Models\MigrationIssue;
use App\Models\MigrationReconciliation;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialMigrationPlanningService
{
    public function __construct(private CanonicalSchemaRegistry $schemas, private CanonicalValueNormaliser $normaliser, private LifecycleAuditLogger $audit) {}

    public function plan(DataMigration $migration, User $actor): array
    {
        $this->authorise($migration, $actor);
        if ($migration->status !== DataMigrationStatus::Normalising) {
            throw new InvalidArgumentException('Prior migration planning must be complete.');
        }
        DB::transaction(function () use ($migration): void {
            foreach ($migration->datasets()->whereIn('canonical_entity', ['fee_structure', 'invoice', 'payment'])->get() as $dataset) {
                $schema = $this->schemas->entity($dataset->canonical_entity);
                $maps = $migration->mappings()->where('dataset_id', $dataset->id)->where('decision', MigrationMappingDecision::AutoMap->value)->get();
                foreach ($dataset->rows()->orderBy('id')->cursor() as $row) {
                    $mapped = [];
                    $normal = [];
                    $warnings = [];
                    $rules = [];
                    foreach ($maps as $map) {
                        $raw = $row->raw_payload[$map->source_column] ?? null;
                        $mapped[$map->destination_field] = $raw;
                        $field = $schema->field($map->destination_field);
                        if (! $field) {
                            continue;
                        }$r = $this->normaliser->normalise($raw, $field);
                        $normal[$field->name] = $r->value;
                        $rules[$field->name] = $r->rule;
                        if ($r->warning) {
                            $warnings[] = ['field' => $field->name, 'message' => $r->warning];
                        }
                    }
                    $row->update(['mapped_payload' => $mapped, 'normalised_payload' => $normal, 'warnings' => $warnings ?: null]);
                    $this->stage($migration, $dataset->id, $row->id, $dataset->canonical_entity, $normal, $rules, $warnings);
                }
            }
            $this->reconcile($migration);
        });
        $counts = ['create' => $migration->financialRecords()->where('decision', 'create')->count(), 'update' => $migration->financialRecords()->where('decision', 'update')->count(), 'conflict' => $migration->financialRecords()->where('decision', 'conflict')->count(), 'blocked' => $migration->financialRecords()->where('decision', 'blocked')->count()];
        $this->audit->record($migration->tenant_id, $actor, $migration, 'data_migration.financial_plan_created', [], $counts);

        return $counts;
    }

    private function stage(DataMigration $m, int $dataset, int $row, string $entity, array $p, array $rules, array $warnings): void
    {
        $key = match ($entity) {
            'invoice' => strtolower(trim($p['invoice_number'] ?? '')),'payment' => strtolower(trim($p['gateway_reference'] ?? '')),'fee_structure' => implode('|', array_map(fn ($f) => strtolower(trim($p[$f] ?? '')), ['fee_category', 'class_level', 'term']))
        };
        $invalid = $warnings || $key === '' || ($this->cents($p[$entity === 'payment' ? 'amount_paid' : ($entity === 'fee_structure' ? 'amount' : 'total_amount')] ?? null) === null);
        if (($p['currency'] ?? 'NGN') !== strtoupper($p['currency'] ?? 'NGN')) {
            $p['currency'] = strtoupper($p['currency']);
        }
        if (isset($p['amount_paid'],$p['total_amount']) && $this->cents($p['amount_paid']) > $this->cents($p['total_amount'])) {
            $invalid = true;
        }
        $checksum = hash('sha256', json_encode($p));
        $existing = $m->financialRecords()->where(['entity_type' => $entity, 'source_key' => $key])->first();
        if ($existing && $existing->payload_checksum !== $checksum) {
            $existing->update(['decision' => 'conflict']);
            $this->issue($m, $dataset, 'financial_duplicate_conflict', $key);

            return;
        } if ($existing) {
            return;
        }
        $matched = null;
        if ($entity === 'invoice') {
            $matched = Invoice::withTrashed()->where('tenant_id', $m->tenant_id)->where('invoice_number', $p['invoice_number'] ?? '')->first();
        }if ($entity === 'payment') {
            $matched = PaymentTransaction::where('tenant_id', $m->tenant_id)->where('gateway_reference', $p['gateway_reference'] ?? '')->first();
        }
        MigrationFinancialRecord::create(['migration_id' => $m->id, 'dataset_id' => $dataset, 'migration_row_id' => $row, 'tenant_id' => $m->tenant_id, 'entity_type' => $entity, 'source_key' => $key, 'canonical_payload' => $p, 'decision' => $invalid ? 'blocked' : ($matched ? 'update' : 'create'), 'matched_record_id' => $matched?->id, 'payload_checksum' => $checksum, 'metadata' => ['transformation_rules' => $rules, 'dry_run_only' => true]]);
        if ($invalid) {
            $this->issue($m, $dataset, 'financial_validation', $key);
        }
    }

    private function reconcile(DataMigration $m): void
    {
        $invoices = $m->financialRecords()->where('entity_type', 'invoice')->get();
        $payments = $m->financialRecords()->where('entity_type', 'payment')->get();
        $invoiceTotal = $invoices->sum(fn ($r) => $this->cents($r->canonical_payload['total_amount'] ?? 0));
        $reported = $invoices->sum(fn ($r) => $this->cents($r->canonical_payload['amount_paid'] ?? 0));
        $successful = $payments->filter(fn ($r) => ($r->canonical_payload['status'] ?? null) === 'success')->sum(fn ($r) => $this->cents($r->canonical_payload['amount_paid'] ?? 0));
        $currencies = $m->financialRecords()->pluck('canonical_payload')->map(fn ($p) => strtoupper($p['currency'] ?? 'NGN'))->unique()->values();
        $status = 'passed';
        if ($currencies->count() > 1 || ($payments->isNotEmpty() && $reported !== $successful)) {
            $status = 'failed';
        } elseif ($payments->isEmpty() && $reported > 0) {
            $status = 'needs_review';
        }
        MigrationReconciliation::updateOrCreate(['migration_id' => $m->id, 'scope' => 'financial_plan'], ['source_count' => $invoices->count() + $payments->count(), 'destination_count' => $m->financialRecords()->whereNotIn('decision', ['blocked', 'conflict'])->count(), 'source_total' => $this->decimal($invoiceTotal), 'destination_total' => $this->decimal($successful), 'status' => $status, 'details' => ['currency' => $currencies->all(), 'invoice_total' => $this->decimal($invoiceTotal), 'reported_paid' => $this->decimal($reported), 'successful_payments' => $this->decimal($successful), 'balance' => $this->decimal($invoiceTotal - $reported)]]);
        if ($status !== 'passed') {
            $this->issue($m, null, 'financial_reconciliation_'.$status, 'financial_plan');
        }
    }

    private function cents(mixed $v): ?int
    {
        if (! preg_match('/^-?\d+\.\d{2}$/', (string) $v)) {
            return null;
        }[$w,$f] = explode('.', (string) $v);

        return (int) $w * 100 + ((int) $w < 0 ? -(int) $f : (int) $f);
    }

    private function decimal(int $c): string
    {
        $sign = $c < 0 ? '-' : '';
        $c = abs($c);

        return $sign.intdiv($c, 100).'.'.str_pad((string) ($c % 100), 2, '0', STR_PAD_LEFT);
    }

    private function issue(DataMigration $m, ?int $d, string $c, string $f): void
    {
        MigrationIssue::firstOrCreate(['migration_id' => $m->id, 'dataset_id' => $d, 'category' => $c, 'field' => $f, 'status' => 'open'], ['severity' => 'error', 'message' => 'Financial migration requires review.', 'suggested_resolution' => 'Correct financial source data before approval.']);
    }

    private function authorise(DataMigration $m,User $a): void
    {
        if (! $a->isSuperAdmin() && (int) $a->tenant_id !== (int) $m->tenant_id) {
            throw new InvalidArgumentException('The user does not belong to the migration tenant.');
        }
    }
}
