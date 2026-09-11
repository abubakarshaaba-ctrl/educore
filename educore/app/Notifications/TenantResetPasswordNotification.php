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
        $expires = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Reset your ' . $this->tenant->name . ' password')
            ->greeting('Reset your password')
            ->line('A password reset was requested for your ' . $this->tenant->name . ' staff account on EduCore.')
            ->line('Use the secure button below to choose a new password. This link expires in ' . $expires . ' minutes.')
            ->action('Reset Password', $url)
            ->line('If you did not request this change, ignore this email. Your current password will remain unchanged.')
            ->salutation($this->tenant->name . ' · Powered by EduCore');
    }
}
