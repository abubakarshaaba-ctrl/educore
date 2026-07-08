<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic guardian/parent email — used for every parent-facing event
 * (payment received, admission received, enrollment, results published,
 * fee reminders) instead of a bespoke Notification class per event.
 * Routed ad hoc via Notification::route('mail', $guardian->email), since
 * Guardian is a plain contact record, not a Notifiable/authenticatable model.
 */
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
            ->greeting('Dear ' . $this->greetingName . ',');

        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        if ($this->actionLabel && $this->actionUrl) {
            $mail->action($this->actionLabel, $this->actionUrl);
        }

        return $mail;
    }
}
