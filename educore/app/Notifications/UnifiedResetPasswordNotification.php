<?php

namespace App\Notifications;

use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnifiedResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable->getEmailForPasswordReset();
        $url = route('password.reset', ['token' => $this->token, 'email' => $email]);
        $expires = (int) config('auth.passwords.users.expire', 60);

        $mail = (new MailMessage)
            ->subject('Reset your EduCore password');

        return MailBranding::platformView(
            $mail,
            'mail.platform.password-reset',
            [
                'resetUrl' => $url,
                'expires' => $expires,
            ],
        );
    }
}
