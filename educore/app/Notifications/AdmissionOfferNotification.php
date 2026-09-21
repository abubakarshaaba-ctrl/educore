<?php
namespace App\Notifications;
use App\Models\Admission;
use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AdmissionOfferNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly Admission $admission, public readonly string $schoolName, public readonly string $statusUrl, public readonly string $pdfContent, public readonly ?string $replyToEmail = null) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Admission Offer — '.$this->admission->first_name.' '.$this->admission->last_name.' — '.$this->schoolName)
            ->greeting('Dear '.$this->admission->guardian_name.',')
            ->line('We are pleased to offer '.$this->admission->first_name.' '.$this->admission->last_name.' admission to '.$this->schoolName.'.')
            ->line('Please find your formal admission offer letter attached. Contact us to complete enrollment.')
            ->action('Track Application', $this->statusUrl)
            ->attachData($this->pdfContent, 'Admission-Offer-'.$this->admission->application_number.'.pdf', ['mime' => 'application/pdf']);
        return MailBranding::school($mail, $this->admission->tenant_id ? (int) $this->admission->tenant_id : null, $this->schoolName, $this->replyToEmail);
    }
}
