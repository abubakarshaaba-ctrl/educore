<?php
namespace App\Notifications;
use App\Models\PayrollPeriod;
use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class PayrollPaidNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly PayrollPeriod $period, public readonly float $netPay, public readonly string $schoolName, public readonly string $loginUrl) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Payroll paid — '.$this->period->title)
            ->greeting('Hi '.($notifiable->name ?: 'Staff Member').',')
            ->line($this->schoolName.' has marked your payroll for '.$this->period->title.' as paid.')
            ->line('Net pay: ₦'.number_format($this->netPay, 2))
            ->line('Payment date: '.($this->period->payment_date ?: now()->toDateString()))
            ->line('Sign in to EduCore to review your payroll information.')
            ->action('Open EduCore', $this->loginUrl);
        return MailBranding::school($mail, isset($notifiable->tenant_id) ? (int) $notifiable->tenant_id : null, $this->schoolName);
    }
}
