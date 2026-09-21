<?php

namespace App\Notifications\Tenant;

use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

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
        $body = $this->plainBody();

        $mail = (new MailMessage)
            ->subject('EduCore Platform Broadcast — '.$this->title);

        if ($this->imagePath) {
            try {
                $disk = Storage::disk('public');
                if ($disk->exists($this->imagePath)) {
                    $mail->attach(
                        $disk->path($this->imagePath),
                        [
                            'as' => basename($this->imagePath),
                            'mime' => $disk->mimeType($this->imagePath) ?: 'application/octet-stream',
                        ],
                    );
                }
            } catch (\Throwable) {
                // Attachment failure must not block the platform notification.
            }
        }

        return MailBranding::platformView(
            $mail,
            'mail.platform.broadcast',
            [
                'broadcastTitle' => $this->title,
                'recipientName' => $name,
                'schoolName' => $this->schoolName,
                'messageBody' => $body,
                'expiresAt' => $this->expiresAt,
                'actionUrl' => $this->actionUrl,
            ],
        );
    }

    private function plainBody(): string
    {
        $body = preg_replace('/<br\s*\/?>/i', "\n", $this->body) ?? $this->body;
        $body = strip_tags($body);
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace("/[\t ]+\n/u", "\n", $body) ?? $body;
        $body = preg_replace("/\n{3,}/u", "\n\n", $body) ?? $body;

        return trim($body);
    }
}
