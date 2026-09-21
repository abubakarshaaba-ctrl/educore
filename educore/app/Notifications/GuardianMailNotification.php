<?php

namespace App\Notifications;

use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuardianMailNotification extends Notification
{
    use Queueable;

    /** @param string[] $lines */
    public function __construct(
        public readonly string $subject,
        public readonly string $greetingName,
        public readonly array $lines,
        public readonly ?string $actionLabel = null,
        public readonly ?string $actionUrl = null,
        public readonly ?string $schoolName = null,
        public readonly ?string $replyToEmail = null,
        public readonly ?int $tenantId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->greeting('Dear '.$this->greetingName.',');

        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        if ($this->actionLabel && $this->actionUrl) {
            $mail->action($this->actionLabel, $this->actionUrl);
        }

        return MailBranding::school($mail, $this->tenantId, $this->schoolName, $this->replyToEmail);
    }
}
