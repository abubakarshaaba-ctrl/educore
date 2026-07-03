<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Models\Tenant;

class PlanLimitService
{
    /**
     * Check if the tenant can add more students.
     * Returns null if allowed, or an error message string if blocked.
     */
    public static function checkStudentLimit(Tenant $tenant): ?string
    {
        $plan = $tenant->activeSubscription?->plan;

        if (! $plan || $plan->max_students <= 0) {
            return null; // No plan or unlimited
        }

        $current = Student::where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->count();

        if ($current >= $plan->max_students) {
            return "Your {$plan->name} plan allows a maximum of {$plan->max_students} students. "
                . "You currently have {$current}. Upgrade your plan to add more students.";
        }

        return null;
    }

    /**
     * Check if the tenant can add more staff.
     * Returns null if allowed, or an error message string if blocked.
     */
    public static function checkStaffLimit(Tenant $tenant): ?string
    {
        $plan = $tenant->activeSubscription?->plan;

        if (! $plan || $plan->max_staff <= 0) {
            return null; // No plan or unlimited
        }

        $current = User::where('tenant_id', $tenant->id)
            ->whereNotNull('tenant_id')
            ->whereNull('deleted_at')
            ->whereNull('student_id') // exclude student user accounts
            ->count();

        if ($current >= $plan->max_staff) {
            return "Your {$plan->name} plan allows a maximum of {$plan->max_staff} staff accounts. "
                . "You currently have {$current}. Upgrade your plan to add more staff.";
        }

        return null;
    }

    /**
     * Remaining student slots for a tenant.
     */
    public static function remainingStudentSlots(Tenant $tenant): ?int
    {
        $plan = $tenant->activeSubscription?->plan;
        if (! $plan || $plan->max_students <= 0) return null;

        $current = Student::where('tenant_id', $tenant->id)->whereNull('deleted_at')->count();
        return max(0, $plan->max_students - $current);
    }

    /**
     * Remaining staff slots for a tenant.
     */
    public static function remainingStaffSlots(Tenant $tenant): ?int
    {
        $plan = $tenant->activeSubscription?->plan;
        if (! $plan || $plan->max_staff <= 0) return null;

        $current = User::where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->whereNull('student_id')
            ->count();
        return max(0, $plan->max_staff - $current);
    }
}
