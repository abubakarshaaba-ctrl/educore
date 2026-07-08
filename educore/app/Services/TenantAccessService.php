<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // Multi-campus groups share one subscription, held by the group's
        // "lead" campus — a member campus's expiry follows the lead's,
        // rather than needing its own subscription kept current.
        $expiresAt = $tenant->billingTenant()->subscription_expires_at;
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

        $latestSubscription = $this->latestSubscription($tenant);
        if ($latestSubscription?->status === 'trial'
            && (!$latestSubscription->expires_at || $latestSubscription->expires_at->isFuture())) {
            return TenantAccessDecision::warning(
                TenantAccessDecision::STATE_TRIAL,
                'This school account is currently using a trial subscription.',
                $latestSubscription->expires_at ?? $expiresAt,
                ['subscription_id' => $latestSubscription->id]
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

    private function latestSubscription(Tenant $tenant): ?TenantSubscription
    {
        if (!Schema::hasTable('tenant_subscriptions')) {
            return null;
        }

        return TenantSubscription::where('tenant_id', $tenant->id)
            ->latest('created_at')
            ->latest('id')
            ->first();
    }

    private function gracePeriodDays(): int
    {
        if (!Schema::hasTable('platform_settings')) {
            return 0;
        }

        $value = DB::table('platform_settings')
            ->where('key', 'grace_period_days')
            ->value('value');

        return max(0, (int) $value);
    }
}
