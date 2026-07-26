<?php

namespace App\Notifications\Tenant;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to school admins when a parent pays a fee invoice online. */
class FeePaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly float $amountPaid,
        public readonly string $studentName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Fee payment received — ' . $this->studentName)
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('A fee payment has been received for ' . $this->studentName . '.')
            ->line('Amount paid: ₦' . number_format($this->amountPaid, 2))
            ->line('Invoice status: ' . ucfirst(str_replace('_', ' ', $this->invoice->status)))
            ->action('View Invoice', route('fees.invoices'));
    }
}
