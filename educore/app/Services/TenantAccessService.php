<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TenantAccessService
{
    private const EXPIRING_SOON_DAYS = 14;

    public function applicationAccess(?Tenant $tenant): TenantAccessDecision
    {
        if (!$tenant) {
            return TenantAccessDecision::deny(
                TenantAccessDecision::STATE_MISSING,
                $this->genericUnavailableMessage()
            );
        }

        if (method_exists($tenant, 'trashed') && $tenant->trashed()) {
            return TenantAccessDecision::deny(
                TenantAccessDecision::STATE_INACTIVE,
                $this->genericUnavailableMessage()
            );
        }

        if ($tenant->status === Tenant::STATUS_SUSPENDED) {
            return TenantAccessDecision::deny(
                TenantAccessDecision::STATE_SUSPENDED,
                $this->genericUnavailableMessage()
            );
        }

        if ($tenant->status === Tenant::STATUS_SUBSCRIPTION_EXPIRED) {
            return TenantAccessDecision::deny(
                TenantAccessDecision::STATE_EXPIRED,
                'School account access is currently unavailable. Please renew the subscription or contact support.',
                $tenant->subscription_expires_at
            );
        }

        if ($tenant->status !== Tenant::STATUS_ACTIVE) {
            return TenantAccessDecision::deny(
                TenantAccessDecision::STATE_INACTIVE,
                $this->genericUnavailableMessage()
            );
        }

        // Multi-campus groups normally share the lead campus subscription. The
        // group tables are optional/legacy on some deployments, so a malformed
        // or partially migrated group record must never turn a valid mobile
        // login into an HTTP 500/503. Fall back to the tenant's own expiry.
        $expiresAt = $tenant->subscription_expires_at;
        try {
            $expiresAt = $tenant->billingTenant()->subscription_expires_at ?? $expiresAt;
        } catch (Throwable $exception) {
            report($exception);
        }

        // Free-tier detection is an enhancement to access-state calculation,
        // not a prerequisite for authentication. If enrollment metadata cannot
        // be read, continue with the tenant's subscription state rather than
        // failing the mobile bootstrap request.
        try {
            if (PricingService::isFree(PricingService::activeStudentCount($tenant->id))) {
                return TenantAccessDecision::free([
                    'student_limit' => PricingService::FREE_THRESHOLD,
                    'all_features' => true,
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        if ($expiresAt && $expiresAt->isPast()) {
            $graceDays = $this->gracePeriodDays();
            if ($graceDays > 0 && $expiresAt->copy()->addDays($graceDays)->isFuture()) {
                return TenantAccessDecision::warning(
                    TenantAccessDecision::STATE_GRACE,
                    'This school account is in its subscription grace period. Please renew to avoid service interruption.',
                    $expiresAt,
                    ['grace_days' => $graceDays]
                );
            }

            return TenantAccessDecision::deny(
                TenantAccessDecision::STATE_EXPIRED,
                'School account access is currently unavailable. Please renew the subscription or contact support.',
                $expiresAt
            );
        }

        if ($expiresAt && $expiresAt->betweenIncluded(now(), now()->addDays(self::EXPIRING_SOON_DAYS))) {
            return TenantAccessDecision::warning(
                TenantAccessDecision::STATE_EXPIRING_SOON,
                'This school subscription is expiring soon. Please renew before the expiry date.',
                $expiresAt
            );
        }

        return TenantAccessDecision::allow();
    }

    public function genericUnavailableMessage(): string
    {
        return 'This school portal is currently unavailable. Please contact the school administration.';
    }

    private function gracePeriodDays(): int
    {
        try {
            if (!Schema::hasTable('platform_settings')) {
                return 0;
            }

            $value = DB::table('platform_settings')
                ->where('key', 'grace_period_days')
                ->value('value');

            return max(0, (int) $value);
        } catch (Throwable $exception) {
            report($exception);
            return 0;
        }
    }
}
