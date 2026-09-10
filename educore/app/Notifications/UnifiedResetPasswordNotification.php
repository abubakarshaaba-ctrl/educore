<?php

namespace App\Notifications;

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

        return (new MailMessage)
            ->subject('Reset your EduCore password')
            ->greeting('Reset your password')
            ->line('We received a request to reset the password for your EduCore account.')
            ->line('Use the secure button below to choose a new password. For your protection, this link expires in ' . $expires . ' minutes.')
            ->action('Reset Password', $url)
            ->line('If you did not request this change, you can safely ignore this email. Your current password will remain unchanged.')
            ->salutation('EduCore Security');
    }
}
