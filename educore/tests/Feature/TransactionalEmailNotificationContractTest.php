<?php

namespace Tests\Feature;

use Tests\TestCase;

class TransactionalEmailNotificationContractTest extends TestCase
{
    public function test_result_publication_keeps_guardian_email_hooks(): void
    {
        $conventional = file_get_contents(app_path('Http/Controllers/ReportCardController.php'));
        $parallel = file_get_contents(app_path('Http/Controllers/Api/MobileParallelCurriculumResultController.php'));
        $this->assertStringContainsString('GuardianNotifier', $conventional);
        $this->assertStringContainsString("'Results published — '", $conventional);
        $this->assertStringContainsString('GuardianNotifier', $parallel);
        $this->assertStringContainsString('notifyGuardians', $parallel);
    }

    public function test_finance_and_admission_keep_transactional_email_hooks(): void
    {
        $fees = file_get_contents(app_path('Services/SchoolFeePaymentService.php'));
        $admissions = file_get_contents(app_path('Http/Controllers/AdmissionController.php'));
        $this->assertStringContainsString('FeePaymentReceivedNotification', $fees);
        $this->assertStringContainsString('GuardianNotifier', $fees);
        $this->assertStringContainsString('AdmissionOfferNotification', $admissions);
        $this->assertStringContainsString('GuardianNotifier', $admissions);
    }

    public function test_payroll_paid_email_is_called_from_web_and_mobile_workflows(): void
    {
        $web = file_get_contents(app_path('Http/Controllers/PayrollController.php'));
        $mobile = file_get_contents(app_path('Http/Controllers/Api/MobilePayrollController.php'));
        $this->assertStringContainsString('PayrollNotificationService', $web);
        $this->assertStringContainsString('notifyPaid(', $web);
        $this->assertStringContainsString('PayrollNotificationService', $mobile);
        $this->assertStringContainsString('notifyPaid(', $mobile);
    }

    public function test_platform_broadcast_email_is_delivered_by_the_after_response_pipeline(): void
    {
        $api = file_get_contents(app_path('Http/Controllers/Api/PlatformBroadcastController.php'));
        $web = file_get_contents(app_path('Http/Controllers/WebPlatformBroadcastController.php'));
        $publisher = file_get_contents(app_path('Services/Notifications/PlatformBroadcastPublisher.php'));
        $job = file_get_contents(app_path('Jobs/DeliverPlatformBroadcastAfterResponse.php'));
        $service = file_get_contents(app_path('Services/Notifications/PlatformBroadcastEmailService.php'));

        $this->assertStringContainsString('PlatformBroadcastPublisher', $api);
        $this->assertStringContainsString('PlatformBroadcastPublisher', $web);
        $this->assertStringContainsString('DeliverPlatformBroadcastAfterResponse::dispatchAfterResponse(', $publisher);
        $this->assertStringContainsString('PlatformBroadcastEmailService', $job);
        $this->assertStringContainsString('sendToTenantIds(', $job);
        $this->assertStringContainsString('PlatformBroadcastNotification', $service);
        $this->assertStringContainsString('/platform-notices', $service);
    }

    public function test_dual_brand_mail_contract_routes_platform_and_school_origin_correctly(): void
    {
        $platform = file_get_contents(app_path('Notifications/Tenant/PlatformBroadcastNotification.php'));
        $subscription = file_get_contents(app_path('Notifications/Tenant/SubscriptionExpiringNotification.php'));
        $guardian = file_get_contents(app_path('Notifications/GuardianMailNotification.php'));
        $payroll = file_get_contents(app_path('Notifications/PayrollPaidNotification.php'));
        $branding = file_get_contents(app_path('Services/Notifications/MailBranding.php'));
        $header = file_get_contents(resource_path('views/vendor/mail/html/header.blade.php'));

        $this->assertStringContainsString('MailBranding::platformView(', $platform);
        $this->assertStringContainsString('MailBranding::platform(', $subscription);
        $this->assertStringContainsString('MailBranding::school(', $guardian);
        $this->assertStringContainsString('MailBranding::school(', $payroll);
        $this->assertStringContainsString("'context' => 'platform'", $branding);
        $this->assertStringContainsString("'context' => 'school'", $branding);
        $this->assertStringContainsString('header-school', $header);
        $this->assertStringContainsString('header-platform', $header);
        $notificationView = file_get_contents(resource_path('views/vendor/notifications/email.blade.php'));
        $this->assertStringContainsString(':brand="$mailBrand ?? []"', $notificationView);
    }

    public function test_school_branding_uses_raster_logo_and_school_reply_to(): void
    {
        $branding = file_get_contents(app_path('Services/Notifications/MailBranding.php'));
        $header = file_get_contents(resource_path('views/vendor/mail/html/header.blade.php'));
        $this->assertStringContainsString("['png', 'jpg', 'jpeg', 'webp']", $branding);
        $this->assertStringContainsString('replyTo($email, $name)', $branding);
        $this->assertStringNotContainsString('educore-icon.svg', $header);
    }

    public function test_security_recruitment_and_tenant_lifecycle_mailers_remain_present(): void
    {
        foreach ([
            'Notifications/TenantResetPasswordNotification.php',
            'Notifications/ApplicantApplicationReceivedNotification.php',
            'Notifications/ApplicantStatusChangedNotification.php',
            'Notifications/ApplicantMessageReceivedNotification.php',
            'Notifications/JobOfferNotification.php',
            'Notifications/Tenant/TenantWelcomeNotification.php',
            'Notifications/Tenant/TenantSuspendedNotification.php',
            'Notifications/Tenant/SubscriptionExpiringNotification.php',
            'Notifications/Tenant/SubscriptionRenewedNotification.php',
        ] as $relativePath) {
            $this->assertFileExists(app_path($relativePath), $relativePath);
        }
    }
}
