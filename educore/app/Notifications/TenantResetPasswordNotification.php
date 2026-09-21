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
            ->subject('Reset your '.$this->tenant->name.' password')
            ->greeting('Reset your password')
            ->line('A password reset was requested for your '.$this->tenant->name.' account.')
            ->line('Use the secure button below to choose a new password.')
            ->action('Reset Password', $url)
            ->line('This secure link expires in '.$expires.' minutes.')
            ->line('If you did not request this change, ignore this email. Your current password will remain unchanged.');

        return MailBranding::school(
            $mail,
            (int) $this->tenant->id,
            $this->tenant->name,
            $this->tenant->email,
        );
    }
}
