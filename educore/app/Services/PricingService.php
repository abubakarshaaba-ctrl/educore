<?php

namespace App\Services;

use App\Models\Student;

/**
 * The single, system-wide pricing model: every tenant gets full feature
 * access regardless of size — price scales only with active enrollment,
 * billed per term. Replaces the old Basic/Standard/Premium tiered plans.
 */
class PricingService
{
    public const FREE_THRESHOLD = 20;
    public const TIER2_MAX = 200;
    public const TIER2_RATE = 500.0;
    public const TIER3_MAX = 500;
    public const TIER3_RATE = 400.0;

    /** 10% off when paying for a full academic year (3 terms) upfront. */
    public const ANNUAL_DISCOUNT = 0.10;

    /** Per-student rate for this enrollment size, or null if it needs a custom quote. */
    public static function ratePerStudent(int $studentCount): ?float
    {
        if ($studentCount <= self::FREE_THRESHOLD) {
            return 0.0;
        }
        if ($studentCount <= self::TIER2_MAX) {
            return self::TIER2_RATE;
        }
        if ($studentCount <= self::TIER3_MAX) {
            return self::TIER3_RATE;
        }

        return null;
    }

    public static function isCustomQuote(int $studentCount): bool
    {
        return $studentCount > self::TIER3_MAX;
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

        return $termly === null ? null : round($termly * 3 * (1 - self::ANNUAL_DISCOUNT), 2);
    }

    public static function tierLabel(int $studentCount): string
    {
        if (self::isFree($studentCount)) {
            return 'Free plan';
        }
        if (self::isCustomQuote($studentCount)) {
            return 'Custom volume pricing';
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

    /** All published tiers, for display on pricing/marketing pages. */
    public static function tiers(): array
    {
        return [
            ['range' => 'Up to ' . self::FREE_THRESHOLD . ' students', 'rate' => 'Free', 'cycle' => '—'],
            ['range' => (self::FREE_THRESHOLD + 1) . ' – ' . self::TIER2_MAX . ' students', 'rate' => '₦' . number_format(self::TIER2_RATE) . ' / student', 'cycle' => 'Per term'],
            ['range' => (self::TIER2_MAX + 1) . ' – ' . self::TIER3_MAX . ' students', 'rate' => '₦' . number_format(self::TIER3_RATE) . ' / student', 'cycle' => 'Per term'],
            ['range' => (self::TIER3_MAX + 1) . '+ students', 'rate' => 'Custom volume pricing', 'cycle' => 'Per term'],
        ];
    }
}
