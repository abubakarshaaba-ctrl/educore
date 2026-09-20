<?php

namespace App\Notifications\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Email copy of a platform broadcast sent by the EduCore Super Admin.
 *
 * Delivery is intentionally synchronous, matching the existing tenant email
 * notifications, so shared-host deployments do not depend on a queue worker.
 */
class PlatformBroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $broadcastId,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $imagePath = null,
        public readonly ?string $expiresAt = null,
        public readonly ?string $schoolName = null,
        public readonly ?string $actionUrl = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = trim((string) ($notifiable->name ?? ''));
        $mail = (new MailMessage)
            ->subject('EduCore Platform Broadcast: ' . $this->title)
            ->greeting($name !== '' ? 'Hello ' . $name . ',' : 'Hello,')
            ->line(
                'A new broadcast has been sent'
                .($this->schoolName ? ' to '.$this->schoolName : ' to your school')
                .' by the EduCore Platform Super Admin.'
            )
            ->line($this->title);

        $body = trim(strip_tags($this->body));
        if ($body !== '') {
            foreach (preg_split('/\R+/u', $body) ?: [] as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $mail->line($line);
                }
            }
        }

        if ($this->imagePath) {
            try {
                $disk = Storage::disk('public');
                if ($disk->exists($this->imagePath)) {
                    $mail->attach($disk->path($this->imagePath), [
                        'as' => basename($this->imagePath),
                        'mime' => $disk->mimeType($this->imagePath) ?: 'application/octet-stream',
                    ]);
                }
            } catch (\Throwable) {
                // The email still carries the text and portal link if an image
                // cannot be attached from the configured public disk.
            }
        }

        if ($this->expiresAt) {
            $mail->line('This notice is available until ' . $this->expiresAt . '.');
        }

        if ($this->actionUrl) {
            $mail->action('View Platform Notices', $this->actionUrl);
        }

        return $mail->line(
            'This message was sent to the registered administrator/contact email for your school.'
        );
    }
}
