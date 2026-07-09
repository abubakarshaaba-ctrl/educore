<?php

namespace App\Notifications;

use App\Models\JobApplicantMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the applicant's own email when the school sends them a message. */
class ApplicantMessageReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly JobApplicantMessage $message,
        public readonly string $schoolName,
        public readonly string $trackUrl,
        public readonly ?string $replyToEmail = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->theme('educore')
            ->from(config('mail.from.address'), $this->schoolName)
            ->subject('New message from ' . $this->schoolName . ' about your application')
            ->greeting('Hi ' . $this->message->applicant->name . ',')
            ->line($this->schoolName . ' sent you a message regarding your application for ' . $this->message->applicant->jobPosting->title . ':')
            ->line('"' . $this->message->body . '"')
            ->action('View & Reply', $this->trackUrl);

        if ($this->replyToEmail) {
            $mail->replyTo($this->replyToEmail, $this->schoolName);
        }

        return $mail;
    }
}
