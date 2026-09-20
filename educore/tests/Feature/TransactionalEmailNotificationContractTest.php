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
