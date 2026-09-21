<?php
namespace App\Notifications\Tenant;
use App\Models\Admission;
use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class NewAdmissionApplicationNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly Admission $admission) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('New admission application: '.$this->admission->first_name.' '.$this->admission->last_name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('A new admission application has been submitted.')
            ->line('Applicant: '.$this->admission->first_name.' '.$this->admission->last_name)
            ->line('Application number: '.$this->admission->application_number)
            ->line('Guardian: '.$this->admission->guardian_name.' ('.$this->admission->guardian_phone.')')
            ->action('Review Application', route('admissions.show', $this->admission));
        return MailBranding::school($mail, $this->admission->tenant_id ? (int) $this->admission->tenant_id : (isset($notifiable->tenant_id) ? (int) $notifiable->tenant_id : null));
    }
}
