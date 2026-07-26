<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Notifications\GuardianMailNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Central place for every parent/guardian-facing notification (payment
 * received, admission received, enrollment, results published, fee
 * reminders). Sends email always (when an address exists) and optionally
 * SMS (when a phone number exists and a message is supplied), through the
 * same gateway wiring used elsewhere (NotificationController).
 *
 * Failures are logged, never thrown — a notification hiccup must never
 * break the underlying action (payment recorded, student enrolled, etc).
 */
class GuardianNotifier
{
    /** @param string[] $lines */
    public function send(
        ?Guardian $guardian,
        string $subject,
        array $lines,
        ?string $smsBody = null,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
        ?string $schoolName = null,
        ?string $replyToEmail = null,
    ): void {
        if (!$guardian) {
            return;
        }

        // Emails to a parent should read as coming from their child's school,
        // not a generic platform sender — resolve the tenant unless the
        // caller already has the name handy (avoids an extra query in that case).
        if ($schoolName === null && $guardian->tenant_id) {
            $tenant = Tenant::find($guardian->tenant_id);
            $schoolName ??= $tenant?->name;
            $replyToEmail ??= $tenant?->email;
        }

        if ($guardian->email) {
            try {
                Notification::route('mail', $guardian->email)->notify(
                    new GuardianMailNotification($subject, $guardian->full_name, $lines, $actionLabel, $actionUrl, $schoolName, $replyToEmail)
                );
            } catch (\Throwable $e) {
                Log::error("Guardian email notification failed ({$guardian->id}): " . $e->getMessage());
            }
        }

        if ($guardian->phone && $smsBody) {
            try {
                $gateway = PlatformSetting::valueFor('default_sms_gateway', 'termii');
                $notifier = new \App\Http\Controllers\NotificationController();
                $gateway === 'africas_talking'
                    ? $notifier->sendSmsViaAfricasTalking($guardian->phone, $smsBody)
                    : $notifier->sendSmsViaTermii($guardian->phone, $smsBody, (string) $guardian->tenant_id);
            } catch (\Throwable $e) {
                Log::error("Guardian SMS notification failed ({$guardian->id}): " . $e->getMessage());
            }
        }
    }
}
