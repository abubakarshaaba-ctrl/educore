<?php

namespace App\Notifications\Tenant;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent once to the new school's administrator after tenant provisioning succeeds. */
class TenantWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly ?string $trialEndsAt = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Welcome to EduCore — ' . $this->tenant->name)
            ->greeting('Welcome, ' . $notifiable->name . '!')
            ->line($this->tenant->name . ' is now active on EduCore.')
            ->line('Your administrator login is ' . ($notifiable->email ?: 'your registered email address') . '.')
            ->action('Sign in to EduCore', route('login'));

        if ($this->trialEndsAt) {
            $mail->line('Trial access is available until ' . $this->trialEndsAt . '.');
        }

        return $mail
            ->line('Next: add your school settings, academic session, classes, subjects and staff.')
            ->line('Need help? Reply to this email or contact EduCore Support on WhatsApp: +234 706 559 5768.');
    }
}
