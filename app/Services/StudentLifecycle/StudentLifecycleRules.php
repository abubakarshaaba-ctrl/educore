<?php

namespace App\Services\StudentLifecycle;

use App\Models\Student;
use App\Models\StudentEnrollment;

class StudentLifecycleRules
{
    public const DIRECT_EXIT_STATUSES = [
        Student::STATUS_LEFT,
        Student::STATUS_WITHDRAWN,
        Student::STATUS_TRANSFERRED_OUT,
        Student::STATUS_GRADUATED,
        Student::STATUS_SUSPENDED,
    ];

    public static function statusLabels(): array
    {
        return [
            Student::STATUS_APPLICANT => 'Applicant',
            Student::STATUS_ACTIVE => 'Active',
            Student::STATUS_SUSPENDED => 'Suspended',
            Student::STATUS_LEFT => 'Left',
            Student::STATUS_WITHDRAWN => 'Withdrawn',
            Student::STATUS_TRANSFERRED_OUT => 'Transferred Out',
            Student::STATUS_GRADUATED => 'Graduated',
        ];
    }

    public static function label(?string $status): string
    {
        if (!$status) {
            return 'Unknown';
        }

        return self::statusLabels()[$status] ?? str($status)->replace('_', ' ')->title()->toString();
    }

    public static function allowedDirectDestinations(?string $currentStatus): array
    {
        return $currentStatus === Student::STATUS_ACTIVE ? self::DIRECT_EXIT_STATUSES : [];
    }

    public static function canChangeDirectly(?string $from, string $to): bool
    {
        return in_array($to, self::allowedDirectDestinations($from), true);
    }

    public static function canReactivate(?string $status): bool
    {
        return in_array($status, [
            Student::STATUS_LEFT,
            Student::STATUS_WITHDRAWN,
            Student::STATUS_SUSPENDED,
        ], true);
    }

    public static function requiresClosedEnrollmentOnExit(string $newStatus): bool
    {
        return in_array($newStatus, Student::ARCHIVE_STATUSES, true);
    }

    public static function enrollmentClosedStatusFor(string $studentStatus): string
    {
        return match ($studentStatus) {
            Student::STATUS_LEFT => StudentEnrollment::STATUS_LEFT,
            Student::STATUS_WITHDRAWN => StudentEnrollment::STATUS_WITHDRAWN,
            Student::STATUS_TRANSFERRED_OUT => StudentEnrollment::STATUS_TRANSFERRED_OUT,
            Student::STATUS_GRADUATED => StudentEnrollment::STATUS_GRADUATED,
            default => StudentEnrollment::STATUS_CLOSED,
        };
    }

    public static function auditActionFor(string $newStatus): string
    {
        return match ($newStatus) {
            Student::STATUS_LEFT => 'student.left',
            Student::STATUS_WITHDRAWN => 'student.withdrawn',
            Student::STATUS_TRANSFERRED_OUT => 'student.transferred_out',
            Student::STATUS_GRADUATED => 'student.graduated',
            Student::STATUS_SUSPENDED => 'student.suspended',
            default => 'student.status.changed',
        };
    }
}
