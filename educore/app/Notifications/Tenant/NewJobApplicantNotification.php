<?php
namespace App\Notifications\Tenant;
use App\Models\JobApplicant;
use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class NewJobApplicantNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly JobApplicant $applicant) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('New job application: '.$this->applicant->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('A new candidate has applied through the careers page.')
            ->line('Applicant: '.$this->applicant->name)
            ->line('Posting: '.$this->applicant->jobPosting->title)
            ->action('Review Applicant', route('recruitment.show', $this->applicant->job_posting_id));
        return MailBranding::school($mail, $this->applicant->tenant_id ? (int) $this->applicant->tenant_id : (isset($notifiable->tenant_id) ? (int) $notifiable->tenant_id : null));
    }
}
