<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Notifications\GuardianMailNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
        if (! $guardian) {
            return;
        }

        if ($schoolName === null && $guardian->tenant_id) {
            $tenant = Tenant::find($guardian->tenant_id);
            $schoolName ??= $tenant?->name;
            $replyToEmail ??= $tenant?->email;
        }

        if ($guardian->email) {
            try {
                Notification::route('mail', $guardian->email)->notify(
                    new GuardianMailNotification(
                        $subject,
                        $guardian->full_name,
                        $lines,
                        $actionLabel,
                        $actionUrl,
                        $schoolName,
                        $replyToEmail,
                        $guardian->tenant_id ? (int) $guardian->tenant_id : null,
                    )
                );
            } catch (\Throwable $e) {
                Log::error("Guardian email notification failed ({$guardian->id}): ".$e->getMessage());
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
                Log::error("Guardian SMS notification failed ({$guardian->id}): ".$e->getMessage());
            }
        }
    }
}
