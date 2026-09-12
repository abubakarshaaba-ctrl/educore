<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\OnlinePaymentLog;
use App\Models\PaymentTransaction;
use App\Notifications\Tenant\FeePaymentReceivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchoolFeePaymentService
{
    /**
     * Apply one already-verified online payment to its invoice exactly once.
     *
     * The amount is taken from the server-created payment log and clamped to
     * the locked invoice balance. No client amount, tenant or student id is
     * accepted here.
     */
    public function settle(OnlinePaymentLog $paymentLog): ?PaymentTransaction
    {
        $notification = null;

        $transaction = DB::transaction(function () use ($paymentLog, &$notification) {
            $log = OnlinePaymentLog::whereKey($paymentLog->id)->lockForUpdate()->first();
            abort_unless($log, 404, 'Payment record not found.');
            abort_unless($log->status === 'success' && $log->verified_at, 422,
                'Only a server-verified payment can be credited.');

            $existing = PaymentTransaction::where('gateway_reference', $log->reference)->first();
            if ($existing) {
                return $existing;
            }

            $invoice = Invoice::whereKey($log->invoice_id)->lockForUpdate()->first();
            abort_unless($invoice, 404, 'Invoice not found.');
            abort_unless((int) $invoice->tenant_id === (int) $log->tenant_id
                && (int) $invoice->student_id === (int) $log->student_id, 409,
                'Payment record does not match the invoice.');

            $remaining = max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid);
            if ($remaining <= 0) {
                return null;
            }

            $amount = min((float) $log->amount, $remaining);
            abort_unless($amount > 0, 422, 'Verified payment has no payable amount.');

            $invoice->amount_paid = (float) $invoice->amount_paid + $amount;
            $invoice->status = $invoice->amount_paid >= (float) $invoice->total_amount
                ? 'paid'
                : 'partially_paid';
            $invoice->save();

            $student = $invoice->student;
            $guardian = $student?->primaryGuardian() ?? $student?->guardians()->first();

            $transaction = PaymentTransaction::create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'gateway_reference' => $log->reference,
                'gateway' => $log->gateway,
                'amount_paid' => $amount,
                'currency' => 'NGN',
                'status' => 'success',
                'gateway_response' => $log->gateway_response,
                'paid_by_name' => $guardian?->name ?? $student?->full_name,
                'paid_by_phone' => $guardian?->phone,
                'paid_at' => $log->verified_at,
            ]);

            $notification = compact('invoice', 'student', 'guardian', 'amount');

            return $transaction;
        });

        if ($notification) {
            ['invoice' => $invoice, 'student' => $student, 'guardian' => $guardian, 'amount' => $amount] = $notification;
            $tenant = $invoice->tenant;

            try {
                app(GuardianNotifier::class)->send(
                    $guardian,
                    'Payment received'.($student ? ' — '.$student->full_name : ''),
                    [
                        'We have received a payment of ₦'.number_format($amount, 2).($student ? ' for '.$student->full_name.'.' : '.'),
                        'Invoice status: '.ucfirst(str_replace('_', ' ', $invoice->status)),
                    ],
                    smsBody: ($tenant?->name ?? 'EduCore').': Payment of ₦'.number_format($amount, 2).' received'.($student ? ' for '.$student->full_name : '').'. Thank you.',
                    schoolName: $tenant?->name,
                );
            } catch (\Throwable $e) {
                Log::error('Guardian payment notification failed', [
                    'payment_reference' => $paymentLog->reference,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $tenant?->notifyAdmins(new FeePaymentReceivedNotification(
                    $invoice,
                    $amount,
                    $student?->full_name ?? 'a student'
                ));
            } catch (\Throwable $e) {
                Log::error('Admin payment notification failed', [
                    'payment_reference' => $paymentLog->reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $transaction;
    }
}
