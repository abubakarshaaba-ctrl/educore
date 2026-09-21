<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Services\Notifications\MailBranding;
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
            ? rtrim($this->baseUrl, '/').'/reset-password/'.rawurlencode($this->token).'?email='.rawurlencode($email)
            : route('tenant.password.reset', [
                'slug' => $this->tenant->slug,
                'token' => $this->token,
                'email' => $email,
            ]);
        $expires = (int) config('auth.passwords.users.expire', 60);

        $mail = (new MailMessage)
            ->subject('Reset your '.$this->tenant->name.' password');

        return MailBranding::schoolView(
            $mail,
            'mail.school.password-reset',
            [
                'resetUrl' => $url,
                'expires' => $expires,
            ],
            (int) $this->tenant->id,
            $this->tenant->name,
            $this->tenant->email,
        );
    }
}
