<?php

namespace App\Services\Notifications;

/**
 * Canonical EduCore activity-delivery policy.
 *
 * Routine operational activity stays inside EduCore and on registered devices.
 * Email is reserved for higher-value transactional communication handled by
 * dedicated notification classes/services.
 */
final class NotificationDeliveryPolicy
{
    public const PUSH_IN_APP_ONLY = [
        'announcement_published',
        'student_absent',
        'student_late',
        'message_received',
        'exam_scheduled',
        'exam_supervision_published',
        'platform_broadcast',
        'calendar_event',
    ];

    public const TRANSACTIONAL_EMAIL = [
        'account_security',
        'report_card_published',
        'fee_payment_received',
        'fee_overdue',
        'invoice_generated',
        'admission_status_changed',
        'recruitment',
        'payroll',
        'subscription_lifecycle',
        'tenant_lifecycle',
    ];

    /**
     * Legacy trigger settings are retained only for transactional reminders.
     * Routine operational events must never be re-routed to email/SMS here.
     */
    public const LEGACY_CONFIGURABLE_EVENTS = [
        'fee_payment_received',
        'report_card_published',
        'admission_status_changed',
        'fee_overdue',
        'invoice_generated',
    ];

    public static function isPushInAppOnly(string $event): bool
    {
        return in_array($event, self::PUSH_IN_APP_ONLY, true);
    }

    public static function isLegacyConfigurable(string $event): bool
    {
        return in_array($event, self::LEGACY_CONFIGURABLE_EVENTS, true);
    }
}
