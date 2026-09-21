<?php
namespace App\Notifications\Tenant;
use App\Models\Tenant;
use App\Services\Notifications\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class TenantSuspendedNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly Tenant $tenant, public readonly bool $reactivated = false) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->reactivated) {
            $mail = (new MailMessage)
                ->subject('Your EduCore account has been reactivated')
                ->greeting('Hi '.$notifiable->name.',')
                ->line($this->tenant->name."'s EduCore account has been reactivated. You can sign back in now.")
                ->action('Sign In', route('login'));
            return MailBranding::platform($mail);
        }
        $mail = (new MailMessage)
            ->subject('Your EduCore account has been suspended')
            ->greeting('Hi '.$notifiable->name.',')
            ->line($this->tenant->name."'s EduCore account has been suspended, and access is currently unavailable.")
            ->line('Contact support if you believe this is a mistake or need to resolve an outstanding issue.')
            ->line('Phone: 07065595768 · WhatsApp: +2347065595768 · support@educoreng.online');
        return MailBranding::platform($mail);
    }
}
