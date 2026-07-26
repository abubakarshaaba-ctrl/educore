<?php

namespace App\Notifications\Tenant;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the new school's admin(s) right after a tenant is provisioned. */
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
            ->subject('Welcome to EduCore, ' . $this->tenant->name . '!')
            ->greeting('Welcome to EduCore, ' . $notifiable->name . '!')
            ->line('Your school, ' . $this->tenant->name . ', is now set up on EduCore.')
            ->line('Sign in any time with your email address at the link below.')
            ->action('Go to EduCore', route('login'));

        if ($this->trialEndsAt) {
            $mail->line('You are on a free trial until ' . $this->trialEndsAt . '. Add your students, staff, and classes to get started.');
        }

        return $mail->line('Need help getting set up? Just reply to this email or reach us on WhatsApp at +2347065595768.');
    }
}
