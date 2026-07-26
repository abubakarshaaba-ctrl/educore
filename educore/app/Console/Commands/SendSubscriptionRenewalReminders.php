<?php

namespace App\Console\Commands;

use App\Models\Tenant;
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

            $tenants = Tenant::whereDate('subscription_expires_at', $targetDate)->get();

            foreach ($tenants as $tenant) {
                try {
                    $tenant->notifyAdmins(new SubscriptionExpiringNotification(
                        $tenant,
                        $tenant->subscription_expires_at->format('d M Y'),
                        $days
                    ));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::error("Renewal reminder failed for tenant {$tenant->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Sent {$sent} renewal reminder(s).");
        return self::SUCCESS;
    }
}
