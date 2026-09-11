<?php

namespace Tests\Feature;

use App\Notifications\UnifiedResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class BrandedMailContractTest extends TestCase
{
    public function test_markdown_mail_uses_educore_theme_for_web_and_api_notifications(): void
    {
        $this->assertSame('default', config('mail.markdown.theme'));
        $this->assertContains(resource_path('views/vendor/mail'), config('mail.markdown.paths'));

        $theme = file_get_contents(resource_path('views/vendor/mail/html/themes/default.css'));
        $header = file_get_contents(resource_path('views/vendor/mail/html/header.blade.php'));

        $this->assertStringContainsString('#071e45', strtolower($theme));
        $this->assertStringContainsString('#f1b947', strtolower($theme));
        $this->assertStringContainsString('educore-logo-horizontal-dark.svg', $header);
        $this->assertStringContainsString('@media only screen and (max-width: 620px)', $theme);
    }

    public function test_password_reset_notification_has_clear_security_content(): void
    {
        $notifiable = new class {
            public function getEmailForPasswordReset(): string
            {
                return 'staff@example.com';
            }
        };

        /** @var MailMessage $mail */
        $mail = (new UnifiedResetPasswordNotification('test-token'))->toMail($notifiable);

        $this->assertSame('Reset your EduCore password', $mail->subject);
        $this->assertSame('Reset your password', $mail->greeting);
        $this->assertSame('Reset Password', $mail->actionText);
        $this->assertStringContainsString('expires in', implode(' ', $mail->introLines));
        $this->assertStringContainsString('current password will remain unchanged', implode(' ', $mail->outroLines));
    }
}
