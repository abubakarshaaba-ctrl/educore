<?php

namespace App\Console\Commands;

use App\Models\TenantSubscription;
use App\Notifications\Tenant\SubscriptionExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Automated version of SuperAdminController::sendRenewalReminders() — that
 * one only runs when a super admin manually clicks a button. This runs
 * daily so schools actually get reminded without anyone remembering to.
 *
 * Only fires at specific day-thresholds (not every single day between now
 * and expiry) so a school isn't emailed daily for a month straight.
 */
class SendSubscriptionRenewalReminders extends Command
{
    protected $signature = 'tenants:send-renewal-reminders';

    protected $description = 'Email schools whose subscription is approaching expiry (30/14/7/3/1 days out).';

    private const THRESHOLDS = [30, 14, 7, 3, 1];

    public function handle(): int
    {
        $sent = 0;

        foreach (self::THRESHOLDS as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $subscriptions = TenantSubscription::where('status', 'active')
                ->whereDate('expires_at', $targetDate)
                ->with('tenant')
                ->get();

            foreach ($subscriptions as $sub) {
                if (!$sub->tenant) {
                    continue;
                }

                try {
                    $sub->tenant->notifyAdmins(new SubscriptionExpiringNotification(
                        $sub->tenant,
                        $sub->expires_at->format('d M Y'),
                        $days
                    ));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::error("Renewal reminder failed for tenant {$sub->tenant->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Sent {$sent} renewal reminder(s).");
        return self::SUCCESS;
    }
}
