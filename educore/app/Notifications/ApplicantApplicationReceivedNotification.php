<?php

namespace App\Notifications;

use App\Models\JobApplicant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the applicant's own email confirming a careers-page application was received. */
class ApplicantApplicationReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly JobApplicant $applicant,
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
            ->subject('Application received — ' . $this->applicant->jobPosting->title . ' at ' . $this->schoolName)
            ->greeting('Hi ' . $this->applicant->name . ',')
            ->line('Thank you for applying for the ' . $this->applicant->jobPosting->title . ' role at ' . $this->schoolName . '.')
            ->line('Your application has been received and is under review.')
            ->action('Track Your Application & Message Us', $this->trackUrl)
            ->line('You can use the link above at any time to check your status or send us a message.');

        if ($this->replyToEmail) {
            $mail->replyTo($this->replyToEmail, $this->schoolName);
        }

        return $mail;
    }
}
