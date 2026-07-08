<?php

namespace App\Notifications\Tenant;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent when a school's subscription is paid for, renewed, or extended. */
class SubscriptionRenewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $expiresAt,
        public readonly ?float $amountPaid = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your EduCore subscription has been renewed')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('The EduCore subscription for ' . $this->tenant->name . ' has been renewed.')
            ->line('Your school now has access through ' . $this->expiresAt . '.');

        if ($this->amountPaid !== null) {
            $mail->line('Amount: ₦' . number_format($this->amountPaid, 2));
        }

        return $mail->action('View Billing', route('billing.subscription'));
    }
}
