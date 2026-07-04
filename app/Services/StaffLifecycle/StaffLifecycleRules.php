<?php

namespace App\Services\StaffLifecycle;

use App\Models\StaffWorkHistory;
use App\Models\User;

class StaffLifecycleRules
{
    public const EXIT_STATUSES = [
        User::STAFF_STATUS_LEFT,
        User::STAFF_STATUS_RESIGNED,
        User::STAFF_STATUS_TERMINATED,
    ];

    public const EXPOSED_WORK_CHANGE_TYPES = [
        StaffWorkHistory::CHANGE_APPOINTMENT,
        StaffWorkHistory::CHANGE_CONFIRMATION,
        StaffWorkHistory::CHANGE_PROMOTION,
        StaffWorkHistory::CHANGE_TRANSFER,
        StaffWorkHistory::CHANGE_REASSIGNMENT,
        StaffWorkHistory::CHANGE_ACTING_APPOINTMENT,
        StaffWorkHistory::CHANGE_DEMOTION,
    ];

    public static function statusLabels(): array
    {
        return User::STAFF_STATUS_LABELS;
    }

    public static function label(?string $status): string
    {
        if (!$status) {
            return 'Unknown';
        }

        return self::statusLabels()[$status] ?? str($status)->replace('_', ' ')->title()->toString();
    }

    public static function allowedExitDestinations(?string $currentStatus): array
    {
        return ($currentStatus ?: User::STAFF_STATUS_ACTIVE) === User::STAFF_STATUS_ACTIVE
            ? self::EXIT_STATUSES
            : [];
    }

    public static function canChangeDirectly(?string $from, string $to): bool
    {
        return in_array($to, self::allowedExitDestinations($from), true);
    }

    public static function canReinstate(?string $status): bool
    {
        return in_array($status, User::STAFF_ARCHIVE_STATUSES, true);
    }

    public static function exitWorkChangeTypeFor(string $newStatus): string
    {
        return match ($newStatus) {
            User::STAFF_STATUS_RESIGNED => StaffWorkHistory::CHANGE_RESIGNATION,
            User::STAFF_STATUS_TERMINATED => StaffWorkHistory::CHANGE_TERMINATION,
            default => StaffWorkHistory::CHANGE_EXIT,
        };
    }

    public static function auditActionFor(string $newStatus): string
    {
        return match ($newStatus) {
            User::STAFF_STATUS_LEFT => 'staff.left',
            User::STAFF_STATUS_RESIGNED => 'staff.resigned',
            User::STAFF_STATUS_TERMINATED => 'staff.terminated',
            default => 'staff.status.changed',
        };
    }

    public static function isContinuityAdmin(User $staff): bool
    {
        return in_array($staff->roleKey(), User::STAFF_ADMIN_CONTINUITY_ROLES, true);
    }
}
