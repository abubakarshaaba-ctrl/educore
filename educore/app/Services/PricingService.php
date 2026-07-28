<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Tenant;

/**
 * The single, system-wide pricing model: every tenant gets full feature
 * access regardless of size — price scales only with active enrollment,
 * billed per term. Replaces the old Basic/Standard/Premium tiered plans.
 */
class PricingService
{
    public const FREE_THRESHOLD = 50;
    public const PAID_RATE = 300.0;

    /** Per-student rate for this enrollment size. */
    public static function ratePerStudent(int $studentCount): ?float
    {
        if ($studentCount <= self::FREE_THRESHOLD) {
            return 0.0;
        }
        return self::PAID_RATE;
    }

    public static function isCustomQuote(int $studentCount): bool
    {
        return false;
    }

    public static function isFree(int $studentCount): bool
    {
        return $studentCount <= self::FREE_THRESHOLD;
    }

    public static function termlyAmount(int $studentCount): ?float
    {
        $rate = self::ratePerStudent($studentCount);

        return $rate === null ? null : round($studentCount * $rate, 2);
    }

    public static function annualAmount(int $studentCount): ?float
    {
        $termly = self::termlyAmount($studentCount);

        return $termly === null ? null : round($termly * 3, 2);
    }

    public static function tierLabel(int $studentCount): string
    {
        if (self::isFree($studentCount)) {
            return 'Free plan';
        }

        return '₦' . number_format(self::ratePerStudent($studentCount)) . ' / student / term';
    }

    public static function activeStudentCount(int $tenantId): int
    {
        return Student::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->count();
    }

    /**
     * The number of students this tenant is currently paid up for.
     * Null capacity (never paid, or never explicitly raised) defaults to
     * the free threshold — every tenant starts able to hold the free tier.
     */
    public static function capacityFor(Tenant $tenant): int
    {
        return $tenant->students_capacity ?? self::FREE_THRESHOLD;
    }

    /** Whether this tenant can enroll one more active student within their paid capacity. */
    public static function canAddStudent(Tenant $tenant): bool
    {
        return self::activeStudentCount($tenant->id) < self::capacityFor($tenant);
    }

    public static function remainingCapacity(Tenant $tenant): int
    {
        return max(0, self::capacityFor($tenant) - self::activeStudentCount($tenant->id));
    }

    /** All published tiers, for display on pricing/marketing pages. */
    public static function tiers(): array
    {
        return [
            ['range' => 'Up to ' . self::FREE_THRESHOLD . ' students', 'rate' => 'Free', 'cycle' => '—'],
            ['range' => (self::FREE_THRESHOLD + 1) . '+ students', 'rate' => '₦' . number_format(self::PAID_RATE) . ' / student', 'cycle' => 'Per term'],
        ];
    }
}
