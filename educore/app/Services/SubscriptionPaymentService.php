<?php

namespace App\Services;

use App\Http\Controllers\AgentController;
use App\Models\Tenant;
use App\Notifications\Tenant\SubscriptionRenewedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionPaymentService
{
    /**
     * Settle one already-verified platform invoice exactly once.
     *
     * Gateway verification must happen before this method is called. This
     * service deliberately accepts no client-controlled amount or tenant id:
     * both are read from the locked platform invoice.
     */
    public function settle(int $invoiceId, string $reference, string $method): Tenant
    {
        $result = DB::transaction(function () use ($invoiceId, $reference, $method) {
            $invoice = DB::table('platform_invoices')
                ->where('id', $invoiceId)
                ->lockForUpdate()
                ->first();

            abort_unless($invoice, 404, 'Subscription invoice not found.');

            $tenant = Tenant::lockForUpdate()->findOrFail($invoice->tenant_id);

            if ($invoice->status === 'paid') {
                return ['tenant' => $tenant, 'settled' => false, 'amount' => (float) $invoice->amount];
            }

            $duplicate = DB::table('platform_payments')
                ->where('reference', $reference)
                ->first();

            if ($duplicate) {
                abort_unless((int) $duplicate->tenant_id === (int) $invoice->tenant_id, 409,
                    'Payment reference is already associated with another school.');

                return ['tenant' => $tenant, 'settled' => false, 'amount' => (float) $invoice->amount];
            }

            DB::table('platform_payments')->insert([
                'tenant_id' => $tenant->id,
                'subscription_id' => null,
                'reference' => $reference,
                'amount' => $invoice->amount,
                'currency' => 'NGN',
                'status' => 'confirmed',
                'payment_method' => $method,
                'description' => 'Invoice '.$invoice->invoice_number.' payment',
                'paid_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('platform_invoices')->where('id', $invoice->id)->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $method,
                'payment_ref' => $reference,
                'updated_at' => now(),
            ]);

            $days = match ($invoice->billing_cycle) {
                'annual' => 365,
                'termly' => 112,
                default => 30,
            };

            $base = $tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture()
                ? $tenant->subscription_expires_at->copy()
                : now();
            $expiry = $base->addDays($days);

            $capacity = (int) ($tenant->students_capacity ?? PricingService::FREE_THRESHOLD);
            if (! empty($invoice->student_count)) {
                $capacity = max($capacity, (int) $invoice->student_count);
            }

            $tenant->forceFill([
                'status' => Tenant::STATUS_ACTIVE,
                'subscription_expires_at' => $expiry,
                'students_capacity' => max(PricingService::FREE_THRESHOLD, $capacity),
            ])->save();

            return ['tenant' => $tenant->fresh(), 'settled' => true, 'amount' => (float) $invoice->amount];
        });

        /** @var Tenant $tenant */
        $tenant = $result['tenant'];

        if ($result['settled']) {
            try {
                AgentController::recordReferralCommission($tenant->id, (float) $result['amount']);
            } catch (\Throwable $e) {
                Log::error('Referral commission credit failed after subscription payment', [
                    'tenant_id' => $tenant->id,
                    'reference' => $reference,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $tenant->notifyAdmins(new SubscriptionRenewedNotification(
                    $tenant,
                    $tenant->subscription_expires_at->format('d M Y'),
                    (float) $result['amount']
                ));
            } catch (\Throwable $e) {
                Log::error('Subscription renewal notification failed', [
                    'tenant_id' => $tenant->id,
                    'reference' => $reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $tenant->fresh();
    }
}
