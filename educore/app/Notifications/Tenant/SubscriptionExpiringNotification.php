<?php

namespace App\Notifications\Tenant;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent ahead of subscription expiry so a school can renew before losing access. */
class SubscriptionExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $expiresAt,
        public readonly int $daysLeft,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your EduCore subscription expires in {$this->daysLeft} day" . ($this->daysLeft === 1 ? '' : 's'))
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line($this->tenant->name . "'s EduCore subscription expires on {$this->expiresAt}.")
            ->line('Renew before then to avoid any interruption to your school\'s access.')
            ->action('Renew Now', route('billing.subscription'));
    }
}
