<?php

namespace App\Services;

use App\Http\Controllers\AgentController;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Tenant\SubscriptionRenewedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlatformBillingService
{
    public function generateInvoice(
        Tenant $tenant,
        string $billingCycle,
        int $requestedCapacity,
        string $dueDate,
        ?string $notes,
        ?User $actor = null,
        ?Request $request = null,
    ): object {
        abort_unless(Schema::hasTable('platform_invoices'), 503, 'Platform invoice storage is unavailable.');

        $studentCount = PricingService::activeStudentCount($tenant->id);
        $capacity = max($studentCount, $requestedCapacity);
        if (PricingService::isFree($capacity)) {
            throw ValidationException::withMessages([
                'capacity' => "Anticipated enrollment of {$capacity} students is covered by the free plan. No invoice is required.",
            ]);
        }

        $amount = $billingCycle === 'annual'
            ? PricingService::annualAmount($capacity)
            : PricingService::termlyAmount($capacity);
        $reference = $this->uniqueInvoiceReference();

        $id = DB::transaction(function () use ($tenant, $billingCycle, $capacity, $dueDate, $notes, $amount, $reference, $actor, $request): int {
            $id = DB::table('platform_invoices')->insertGetId([
                'tenant_id' => $tenant->id,
                'plan_id' => null,
                'invoice_number' => $reference,
                'amount' => $amount,
                'student_count' => $capacity,
                'billing_cycle' => $billingCycle,
                'status' => 'pending',
                'due_date' => $dueDate,
                'notes' => trim((string) $notes) ?: ($capacity.' anticipated students'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit(
                action: 'platform.invoice.generated',
                tenantId: $tenant->id,
                auditableId: $id,
                actor: $actor,
                request: $request,
                oldValues: [],
                newValues: [
                    'invoice_number' => $reference,
                    'amount' => (float) $amount,
                    'student_count' => $capacity,
                    'billing_cycle' => $billingCycle,
                    'due_date' => $dueDate,
                ],
            );

            return $id;
        });

        return DB::table('platform_invoices')->where('id', $id)->first();
    }

    /**
     * Settle a pending invoice exactly once and credit the school subscription.
     * Returns ['invoice' => object, 'tenant' => Tenant, 'processed' => bool].
     */
    public function settleInvoice(
        int $invoiceId,
        string $paymentMethod,
        ?string $paymentReference = null,
        ?User $actor = null,
        ?Request $request = null,
    ): array {
        abort_unless(Schema::hasTable('platform_invoices') && Schema::hasTable('platform_payments'), 503, 'Platform billing storage is unavailable.');

        $reference = trim((string) $paymentReference) ?: $this->uniquePaymentReference();
        $settled = DB::transaction(function () use ($invoiceId, $paymentMethod, $reference, $actor, $request): array {
            $invoice = DB::table('platform_invoices')->where('id', $invoiceId)->lockForUpdate()->first();
            abort_unless($invoice, 404);

            if ($invoice->status === 'paid') {
                $tenant = Tenant::findOrFail($invoice->tenant_id);
                return ['invoice' => $invoice, 'tenant' => $tenant, 'processed' => false];
            }
            if (DB::table('platform_payments')->where('reference', $reference)->exists()) {
                throw ValidationException::withMessages(['payment_ref' => 'This payment reference has already been used.']);
            }

            DB::table('platform_invoices')->where('id', $invoiceId)->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $paymentMethod,
                'payment_ref' => $reference,
                'updated_at' => now(),
            ]);
            DB::table('platform_payments')->insert([
                'reference' => $reference,
                'tenant_id' => $invoice->tenant_id,
                'amount' => $invoice->amount,
                'currency' => 'NGN',
                'status' => 'confirmed',
                'payment_method' => $paymentMethod,
                'description' => 'Invoice '.$invoice->invoice_number.' payment',
                'confirmed_by' => $actor?->id,
                'paid_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tenant = Tenant::whereKey($invoice->tenant_id)->lockForUpdate()->firstOrFail();
            $base = $tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture()
                ? $tenant->subscription_expires_at->copy()
                : now();
            $expiry = match ($invoice->billing_cycle) {
                'annual' => $base->copy()->addMonthsNoOverflow(12),
                'termly' => $base->copy()->addDays(112),
                default => $base->copy()->addMonthNoOverflow(),
            };
            $tenant->update([
                'status' => Tenant::STATUS_ACTIVE,
                'subscription_expires_at' => $expiry,
                'students_capacity' => $invoice->student_count
                    ? max((int) $invoice->student_count, (int) ($tenant->students_capacity ?? 0))
                    : $tenant->students_capacity,
            ]);

            $this->audit(
                action: 'platform.invoice.settled',
                tenantId: $tenant->id,
                auditableId: $invoiceId,
                actor: $actor,
                request: $request,
                oldValues: ['status' => $invoice->status],
                newValues: [
                    'status' => 'paid',
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $reference,
                    'subscription_expires_at' => $expiry->toDateString(),
                    'students_capacity' => $tenant->fresh()->students_capacity,
                ],
            );

            return [
                'invoice' => DB::table('platform_invoices')->where('id', $invoiceId)->first(),
                'tenant' => $tenant->fresh(),
                'processed' => true,
            ];
        });

        if ($settled['processed']) {
            $tenant = $settled['tenant'];
            AgentController::recordReferralCommission($tenant->id, (float) $settled['invoice']->amount);
            try {
                $tenant->notifyAdmins(new SubscriptionRenewedNotification(
                    $tenant,
                    $tenant->subscription_expires_at->format('d M Y'),
                    (float) $settled['invoice']->amount,
                ));
            } catch (\Throwable $error) {
                Log::error("Subscription renewed notification failed for tenant {$tenant->id}: {$error->getMessage()}");
            }
        }

        return $settled;
    }

    private function uniqueInvoiceReference(): string
    {
        do {
            $reference = 'INV-'.strtoupper(Str::random(8));
        } while (DB::table('platform_invoices')->where('invoice_number', $reference)->exists());

        return $reference;
    }

    private function uniquePaymentReference(): string
    {
        do {
            $reference = 'PAY-'.strtoupper(Str::random(12));
        } while (DB::table('platform_payments')->where('reference', $reference)->exists());

        return $reference;
    }

    private function audit(
        string $action,
        int $tenantId,
        int $auditableId,
        ?User $actor,
        ?Request $request,
        array $oldValues,
        array $newValues,
    ): void {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }
        AuditLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actor?->id,
            'auditable_type' => 'platform_invoice',
            'auditable_id' => $auditableId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
