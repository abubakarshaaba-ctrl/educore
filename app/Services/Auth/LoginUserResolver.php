<?php

namespace App\Services\Auth;

use App\Models\Tenant;
use App\Models\User;

class LoginUserResolver
{
    public function resolveGlobal(string $loginId): ?User
    {
        $loginId = trim($loginId);

        if (filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $loginId)->first();
        }

        $user = User::where('staff_id', $loginId)->first();

        if (!$user) {
            $user = User::where('student_id', $loginId)->first();
        }

        if (!$user) {
            $user = User::where('email', $loginId)->first();
        }

        return $user;
    }

    public function resolveTenantStaff(Tenant $tenant, string $loginId): ?User
    {
        $loginId = trim($loginId);

        if ($loginId === '') {
            return null;
        }

        return User::tenantStaff($tenant->id)
            ->where(function ($query) use ($loginId) {
                if (filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
                    $query->where('email', $loginId);
                    return;
                }

                $query->where('staff_id', $loginId)
                    ->orWhere('email', $loginId);
            })
            ->first();
    }

    /**
     * Resolve any user (staff, student, or parent) belonging to the given tenant.
     * Used by the subdomain login which must accept all user types.
     */
    public function resolveTenantUser(Tenant $tenant, string $loginId): ?User
    {
        $loginId = trim($loginId);

        if ($loginId === '') {
            return null;
        }

        return User::where('tenant_id', $tenant->id)
            ->where('is_super_admin', false)
            ->where(function ($query) use ($loginId) {
                if (filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
                    $query->where('email', $loginId);
                    return;
                }

                $query->where('staff_id', $loginId)
                    ->orWhere('student_id', $loginId)
                    ->orWhere('email', $loginId);
            })
            ->first();
    }
}
