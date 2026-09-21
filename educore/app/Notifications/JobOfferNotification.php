<?php
namespace App\Notifications;
use App\Models\JobApplicant;
use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class JobOfferNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly JobApplicant $applicant, public readonly string $schoolName, public readonly string $trackUrl, public readonly string $pdfContent, public readonly ?string $replyToEmail = null) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Job Offer — '.$this->applicant->jobPosting->title.' at '.$this->schoolName)
            ->greeting('Dear '.$this->applicant->name.',')
            ->line('We are pleased to offer you the '.$this->applicant->jobPosting->title.' position at '.$this->schoolName.'.')
            ->line('Please find your formal offer letter attached. Contact us to confirm your acceptance.')
            ->action('View Application Status', $this->trackUrl)
            ->attachData($this->pdfContent, 'Job-Offer-'.$this->applicant->name.'.pdf', ['mime' => 'application/pdf']);
        return MailBranding::school($mail, $this->applicant->tenant_id ? (int) $this->applicant->tenant_id : null, $this->schoolName, $this->replyToEmail);
    }
}
