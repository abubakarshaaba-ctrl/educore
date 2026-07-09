<?php

namespace App\Notifications;

use App\Models\JobApplicant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the applicant's own email whenever their application status changes. */
class ApplicantStatusChangedNotification extends Notification
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
        $posting = $this->applicant->jobPosting;
        $label = ucwords(str_replace('_', ' ', $this->applicant->status));

        $line = match ($this->applicant->status) {
            'shortlisted' => 'Congratulations! You have been shortlisted for the ' . $posting->title . ' role. We will be in touch with next steps.',
            'interview_scheduled' => 'You have been scheduled for an interview for the ' . $posting->title . ' role. Please check your tracking page for details or wait for us to contact you.',
            'interviewed' => 'Thank you for attending your interview for the ' . $posting->title . ' role. We are reviewing your application.',
            'offered' => 'Congratulations! We would like to offer you the ' . $posting->title . ' position. Please check your tracking page and get in touch with us.',
            'hired' => 'Congratulations and welcome! You have been confirmed for the ' . $posting->title . ' position.',
            'rejected' => 'Thank you for your interest in the ' . $posting->title . ' role. After careful consideration, we will not be proceeding with your application at this time.',
            default => 'Your application status for the ' . $posting->title . ' role has been updated to: ' . $label . '.',
        };

        $mail = (new MailMessage)
            ->theme('educore')
            ->from(config('mail.from.address'), $this->schoolName)
            ->subject('Application update — ' . $posting->title . ' at ' . $this->schoolName)
            ->greeting('Hi ' . $this->applicant->name . ',')
            ->line($line)
            ->action('View Application Status', $this->trackUrl);

        if ($this->replyToEmail) {
            $mail->replyTo($this->replyToEmail, $this->schoolName);
        }

        return $mail;
    }
}
