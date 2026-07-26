<?php

namespace App\Console\Commands;

use App\Models\FeeReminder;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\GuardianNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Automated weekly fee-default reminder — the manual "Send Reminder" /
 * "Bulk Send" actions in FeeReminderController already work, but only
 * when a school admin remembers to click them. This runs on a schedule
 * across every tenant so parents actually get nudged.
 *
 * Skips an invoice if a reminder was already sent for it in the last 7
 * days, so this doesn't pile on top of reminders staff send manually.
 */
class SendFeeDefaulterReminders extends Command
{
    protected $signature = 'fees:send-defaulter-reminders';

    protected $description = 'Email/SMS guardians of students with an outstanding, unpaid fee invoice.';

    public function handle(GuardianNotifier $notifier): int
    {
        $sent = 0;

        Tenant::where('status', 'active')->chunk(50, function ($tenants) use ($notifier, &$sent) {
            foreach ($tenants as $tenant) {
                $invoices = Invoice::withoutTenantScope()
                    ->where('tenant_id', $tenant->id)
                    ->whereHas('student', fn ($q) => $q->billingEligible())
                    ->where('status', '!=', 'paid')
                    ->whereRaw('amount_paid < total_amount')
                    ->with(['student.guardians'])
                    ->get();

                foreach ($invoices as $invoice) {
                    if (!$invoice->student) {
                        continue;
                    }

                    $recentlyReminded = FeeReminder::withoutTenantScope()
                        ->where('invoice_id', $invoice->id)
                        ->where('sent_at', '>=', now()->subDays(7))
                        ->exists();

                    if ($recentlyReminded) {
                        continue;
                    }

                    $guardian = $invoice->student->guardians->first();
                    if (!$guardian) {
                        continue;
                    }

                    $balance = $invoice->total_amount - $invoice->amount_paid;

                    try {
                        $notifier->send(
                            $guardian,
                            'Outstanding Fee Balance — ' . $invoice->student->full_name,
                            [
                                "This is a reminder that {$invoice->student->full_name} has an outstanding fee balance of ₦" . number_format($balance, 2) . '.',
                                'Please make payment at your earliest convenience to avoid any disruption.',
                            ],
                            smsBody: "Dear Parent, {$invoice->student->full_name} has an outstanding balance of ₦" . number_format($balance, 2) . " at {$tenant->name}. Please settle at your earliest convenience.",
                            actionLabel: 'Pay Now',
                            actionUrl: route('login'),
                            schoolName: $tenant->name,
                        );

                        FeeReminder::create([
                            'tenant_id'  => $tenant->id,
                            'student_id' => $invoice->student_id,
                            'invoice_id' => $invoice->id,
                            'channel'    => 'email',
                            'recipient'  => $guardian->email ?? $guardian->phone,
                            'message'    => 'Automated weekly defaulter reminder',
                            'status'     => 'sent',
                            'sent_at'    => now(),
                        ]);

                        $sent++;
                    } catch (\Throwable $e) {
                        Log::error("Fee defaulter reminder failed for invoice {$invoice->id}: " . $e->getMessage());
                    }
                }
            }
        });

        $this->info("Sent {$sent} fee defaulter reminder(s).");
        return self::SUCCESS;
    }
}
