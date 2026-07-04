<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly Tenant $tenant,
        public readonly ?string $baseUrl = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable->getEmailForPasswordReset();
        $url = $this->baseUrl
            ? rtrim($this->baseUrl, '/') . '/reset-password/' . rawurlencode($this->token) . '?email=' . rawurlencode($email)
            : route('tenant.password.reset', [
                'slug' => $this->tenant->slug,
                'token' => $this->token,
                'email' => $email,
            ]);

        return (new MailMessage)
            ->subject($this->tenant->name . ' password reset')
            ->line('A password reset was requested for your school staff account.')
            ->action('Reset Password', $url)
            ->line('This link expires in ' . config('auth.passwords.users.expire', 60) . ' minutes.')
            ->line('If you did not request a password reset, no action is required.');
    }
}
