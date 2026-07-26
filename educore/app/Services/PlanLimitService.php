<?php

namespace App\Services;

use App\Models\Tenant;

class PlanLimitService
{
    /**
     * Check if the tenant can add more students, based on the pay-per-student
     * capacity they've actually paid for (see PricingService). Returns null
     * if allowed, or an error message string if blocked.
     */
    public static function checkStudentLimit(Tenant $tenant): ?string
    {
        if (PricingService::canAddStudent($tenant)) {
            return null;
        }

        $capacity = PricingService::capacityFor($tenant);
        $current  = PricingService::activeStudentCount($tenant->id);

        return "Your school is paid up for {$capacity} students and currently has {$current}. "
            . "Generate and pay an invoice for additional capacity under Subscription & Billing before enrolling more students.";
    }

    /** There is no staff cap under the pay-per-student model — every feature and role is included. */
    public static function checkStaffLimit(Tenant $tenant): ?string
    {
        return null;
    }

    /** Remaining student slots within the tenant's paid capacity. */
    public static function remainingStudentSlots(Tenant $tenant): int
    {
        return PricingService::remainingCapacity($tenant);
    }

    /** Staff accounts are unlimited under the pay-per-student model. */
    public static function remainingStaffSlots(Tenant $tenant): ?int
    {
        return null;
    }
}
