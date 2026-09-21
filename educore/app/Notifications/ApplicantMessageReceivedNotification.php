<?php
namespace App\Notifications;
use App\Models\JobApplicantMessage;
use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class ApplicantMessageReceivedNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly JobApplicantMessage $message, public readonly string $schoolName, public readonly string $trackUrl, public readonly ?string $replyToEmail = null) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $applicant = $this->message->applicant;
        $mail = (new MailMessage)
            ->subject('New message from '.$this->schoolName.' about your application')
            ->greeting('Hi '.$applicant->name.',')
            ->line($this->schoolName.' sent you a message regarding your application for '.$applicant->jobPosting->title.':')
            ->line('"'.$this->message->body.'"')
            ->action('View & Reply', $this->trackUrl);
        return MailBranding::school($mail, $applicant->tenant_id ? (int) $applicant->tenant_id : null, $this->schoolName, $this->replyToEmail);
    }
}
