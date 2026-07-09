<?php

namespace App\Notifications;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the guardian's email with the formal admission offer letter attached. */
class AdmissionOfferNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Admission $admission,
        public readonly string $schoolName,
        public readonly string $statusUrl,
        public readonly string $pdfContent,
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
            ->subject('Admission Offer — ' . $this->admission->first_name . ' ' . $this->admission->last_name . ' — ' . $this->schoolName)
            ->greeting('Dear ' . $this->admission->guardian_name . ',')
            ->line('We are pleased to offer ' . $this->admission->first_name . ' ' . $this->admission->last_name . ' admission to ' . $this->schoolName . '.')
            ->line('Please find your formal admission offer letter attached. Contact us to complete enrollment.')
            ->action('Track Application', $this->statusUrl)
            ->attachData($this->pdfContent, 'Admission-Offer-' . $this->admission->application_number . '.pdf', [
                'mime' => 'application/pdf',
            ]);

        if ($this->replyToEmail) {
            $mail->replyTo($this->replyToEmail, $this->schoolName);
        }

        return $mail;
    }
}
