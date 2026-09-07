<?php

namespace App\Services\Auth;

use App\Models\Tenant;
use App\Models\User;

class LoginUserResolver
{
    public function resolveGlobal(string $loginId): ?User
    {
        $loginId = trim($loginId);
        $normalizedLoginId = mb_strtolower($loginId);

        if (filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
            return User::whereRaw('LOWER(email) = ?', [$normalizedLoginId])->first();
        }

        $user = User::whereRaw('LOWER(staff_id) = ?', [$normalizedLoginId])->first();

        if (! $user) {
            $user = User::whereRaw('LOWER(student_id) = ?', [$normalizedLoginId])->first();
        }

        if (! $user) {
            $user = User::whereRaw('LOWER(email) = ?', [$normalizedLoginId])->first();
        }

        return $user;
    }

    public function resolveTenantStaff(Tenant $tenant, string $loginId): ?User
    {
        $loginId = trim($loginId);

        if ($loginId === '') {
            return null;
        }

        $normalizedLoginId = mb_strtolower($loginId);

        return User::tenantStaff($tenant->id)
            ->where(function ($query) use ($loginId, $normalizedLoginId) {
                if (filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
                    $query->whereRaw('LOWER(email) = ?', [$normalizedLoginId]);

                    return;
                }

                $query->whereRaw('LOWER(staff_id) = ?', [$normalizedLoginId])
                    ->orWhereRaw('LOWER(email) = ?', [$normalizedLoginId]);
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

        $normalizedLoginId = mb_strtolower($loginId);

        return User::where('tenant_id', $tenant->id)
            ->where('is_super_admin', false)
            ->where(function ($query) use ($loginId, $normalizedLoginId) {
                if (filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
                    $query->whereRaw('LOWER(email) = ?', [$normalizedLoginId]);

                    return;
                }

                $query->whereRaw('LOWER(staff_id) = ?', [$normalizedLoginId])
                    ->orWhereRaw('LOWER(student_id) = ?', [$normalizedLoginId])
                    ->orWhereRaw('LOWER(email) = ?', [$normalizedLoginId]);
            })
            ->first();
    }
}
