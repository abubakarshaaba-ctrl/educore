<?php

namespace App\Notifications\Tenant;

use App\Models\JobApplicant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to school admins when a new job applicant applies via the public careers page. */
class NewJobApplicantNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly JobApplicant $applicant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New job application: ' . $this->applicant->name)
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('A new candidate has applied through the careers page.')
            ->line('Applicant: ' . $this->applicant->name)
            ->line('Posting: ' . $this->applicant->jobPosting->title)
            ->action('Review Applicant', route('recruitment.show', $this->applicant->job_posting_id));
    }
}
