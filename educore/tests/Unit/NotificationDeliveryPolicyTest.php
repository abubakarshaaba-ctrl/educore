<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\ExamPeriod;
use App\Models\MessageThread;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Notifications\ActivityEmailService;
use App\Services\Notifications\NotificationDeliveryPolicy;
use App\Notifications\PayrollPaidNotification;
use App\Notifications\Tenant\PlatformBroadcastNotification;
use PHPUnit\Framework\TestCase;

class NotificationDeliveryPolicyTest extends TestCase
{
    public function test_routine_operational_activity_is_push_and_in_app_only(): void
    {
        foreach ([
            'announcement_published',
            'student_absent',
            'student_late',
            'message_received',
            'exam_supervision_published',
            'calendar_event',
        ] as $event) {
            $this->assertTrue(NotificationDeliveryPolicy::isPushInAppOnly($event), $event);
            $this->assertFalse(NotificationDeliveryPolicy::isLegacyConfigurable($event), $event);
        }
    }

    public function test_platform_broadcast_is_push_in_app_and_email(): void
    {
        $this->assertTrue(
            NotificationDeliveryPolicy::isPushInAppAndEmail('platform_broadcast')
        );
        $this->assertFalse(
            NotificationDeliveryPolicy::isPushInAppOnly('platform_broadcast')
        );
        $this->assertContains(
            'platform_broadcast',
            NotificationDeliveryPolicy::TRANSACTIONAL_EMAIL
        );

        $notification = new PlatformBroadcastNotification(
            broadcastId: 7,
            title: 'System maintenance',
            body: 'Maintenance notice',
            schoolName: 'Test School',
            actionUrl: 'https://school.example.test/platform-notices',
        );

        $this->assertSame(['mail'], $notification->via(new \stdClass()));
    }

    public function test_transactional_reminders_are_the_only_legacy_configurable_events(): void
    {
        $this->assertSame([
            'fee_payment_received',
            'report_card_published',
            'exam_scheduled',
            'admission_status_changed',
            'fee_overdue',
            'invoice_generated',
        ], NotificationDeliveryPolicy::LEGACY_CONFIGURABLE_EVENTS);
    }

    public function test_payroll_paid_notification_remains_mail_only(): void
    {
        $notification = new PayrollPaidNotification(
            new PayrollPeriod(['title' => 'September 2026']),
            250000.00,
            'Test School',
            'https://example.test/login'
        );

        $this->assertSame(['mail'], $notification->via(new \stdClass()));
    }

    public function test_routine_email_compatibility_hooks_remain_no_ops(): void
    {
        $service = new ActivityEmailService();

        $this->assertSame(0, $service->notifyAnnouncementPublished(new Announcement()));
        $this->assertSame(0, $service->notifyMessageThread(new MessageThread(), new User(), 'Routine message'));
        $this->assertSame(0, $service->notifyExamSupervisionPublished(new ExamPeriod()));
    }
}
