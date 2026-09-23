<?php

namespace App\Services\Notifications;

use App\Models\PlatformAgent;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

final class PlatformAdminAlertService
{
    public const DEFAULT_RECIPIENT = 'admin@educoreng.online';

    public function tenantRegistered(Tenant $tenant, ?User $admin = null, ?PlatformAgent $agent = null, string $source = 'self_registration'): void
    {
        $this->send(
            'New School Registered — '.$tenant->name,
            [
                'School' => $tenant->name,
                'Administrator' => $admin?->name ?: 'Not available',
                'Administrator email' => $admin?->email ?: $tenant->email,
                'Phone' => $tenant->phone ?: 'Not provided',
                'Tenant slug' => $tenant->slug ?: 'Not available',
                'Registration source' => $source === 'agent' ? 'Agent referral/provisioning' : 'Self-registration',
                'Agent' => $agent ? $agent->name.' ('.$agent->referral_code.')' : 'None',
                'Account status' => $tenant->status ?: 'Unknown',
                'Registered at' => ($tenant->created_at ?: now())->format('d M Y, h:i A'),
            ],
            $this->tenantUrl($tenant),
            'View school'
        );
    }

    public function subscriptionPaymentReceived(Tenant $tenant, float $amount, string $reference, string $method, ?string $billingCycle = null, ?string $invoiceNumber = null): void
    {
        $this->send(
            'Subscription Payment Received — '.$tenant->name,
            [
                'School' => $tenant->name,
                'Amount' => 'NGN '.number_format($amount, 2),
                'Payment reference' => $reference,
                'Payment method' => $method,
                'Invoice' => $invoiceNumber ?: 'Not available',
                'Billing cycle' => $billingCycle ?: 'Not available',
                'Payment status' => 'Confirmed',
                'New subscription expiry' => $tenant->subscription_expires_at?->format('d M Y') ?: 'Not applicable',
                'Confirmed at' => now()->format('d M Y, h:i A'),
            ],
            $this->tenantUrl($tenant),
            'View school'
        );
    }

    public function tenantStatusChanged(Tenant $tenant, string $from, string $to, ?string $reason = null): void
    {
        $this->send(
            'School Status Changed — '.$tenant->name,
            [
                'School' => $tenant->name,
                'Previous status' => $from,
                'New status' => $to,
                'Reason' => $reason ?: 'Not provided',
                'Changed at' => now()->format('d M Y, h:i A'),
            ],
            $this->tenantUrl($tenant),
            'View school'
        );
    }

    private function send(string $subject, array $details, ?string $actionUrl = null, ?string $actionText = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        $recipient = $this->recipient();
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Platform administrative alert skipped because recipient email is invalid.', ['recipient' => $recipient]);
            return;
        }

        try {
            NotificationFacade::route('mail', $recipient)->notify(
                new PlatformAdministrativeAlert($subject, $details, $actionUrl, $actionText)
            );
        } catch (\Throwable $error) {
            // Administrative email must never break registration, payment settlement,
            // provisioning or account-status operations.
            Log::error('Platform administrative alert delivery failed.', [
                'subject' => $subject,
                'recipient' => $recipient,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function recipient(): string
    {
        try {
            return trim((string) PlatformSetting::valueFor('platform_admin_alert_email', self::DEFAULT_RECIPIENT));
        } catch (\Throwable) {
            return self::DEFAULT_RECIPIENT;
        }
    }

    private function enabled(): bool
    {
        try {
            return (bool) PlatformSetting::valueFor('platform_admin_alerts_enabled', true);
        } catch (\Throwable) {
            return true;
        }
    }

    private function tenantUrl(Tenant $tenant): string
    {
        return rtrim((string) config('app.url'), '/').'/super/tenants/'.$tenant->id;
    }
}

final class PlatformAdministrativeAlert extends Notification
{
    public function __construct(
        private readonly string $subjectLine,
        private readonly array $details,
        private readonly ?string $actionUrl = null,
        private readonly ?string $actionText = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rows = collect($this->details)
            ->map(fn ($value, $label) => '<strong>'.e((string) $label).':</strong> '.e((string) $value))
            ->values()
            ->all();

        $mail = (new MailMessage)
            ->subject($this->subjectLine);

        $mail = MailBranding::platform($mail);

        return $mail->view('mail.platform.notification', [
            'subject' => $this->subjectLine,
            'greeting' => 'Platform administrative alert',
            'introLines' => $rows,
            'actionUrl' => $this->actionUrl,
            'actionText' => $this->actionText,
            'outroLines' => ['This is an automated EduCore platform operations notification.'],
            'salutation' => "Regards,\nThe EduCore Team",
            'platformIconCid' => 'educore-icon@educoreng.online',
        ]);
    }
}
