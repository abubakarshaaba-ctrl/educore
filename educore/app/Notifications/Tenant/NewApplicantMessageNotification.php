<?php
namespace App\Notifications\Tenant;
use App\Models\JobApplicantMessage;
use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class NewApplicantMessageNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly JobApplicantMessage $message) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $applicant = $this->message->applicant;
        $mail = (new MailMessage)
            ->subject('New message from applicant: '.$applicant->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line($applicant->name.' (applying for '.$applicant->jobPosting->title.') sent a message:')
            ->line('"'.$this->message->body.'"')
            ->action('View Applicant', route('recruitment.show', $applicant->job_posting_id));
        return MailBranding::school($mail, $applicant->tenant_id ? (int) $applicant->tenant_id : (isset($notifiable->tenant_id) ? (int) $notifiable->tenant_id : null));
    }
}
