@php
    $subscriptionTenant = $tenant?->billingTenant();
    $isFreePlan = $subscriptionTenant ? \App\Services\PricingService::isFreeForTenant($subscriptionTenant) : false;
    $subscriptionExpiry = $subscriptionTenant?->subscription_expires_at;
    $today = now()->startOfDay();
    $expiryDay = $subscriptionExpiry?->copy()->startOfDay();
    $daysRemaining = $expiryDay ? $today->diffInDays($expiryDay, false) : null;

    $subscriptionState = match (true) {
        $isFreePlan => 'free',
        $daysRemaining !== null && $daysRemaining < 0 => 'expired',
        $daysRemaining !== null && $daysRemaining <= 7 => 'critical',
        $daysRemaining !== null && $daysRemaining <= 30 => 'warning',
        default => 'active',
    };

    $subscriptionHeadline = match (true) {
        $isFreePlan => 'Free plan',
        $daysRemaining === null => 'Subscription active',
        $daysRemaining < 0 => 'Subscription expired',
        $daysRemaining === 0 => 'Expires today',
        $daysRemaining === 1 => '1 day remaining',
        default => number_format($daysRemaining).' days remaining',
    };

    $subscriptionDetail = $isFreePlan
        ? 'No paid subscription countdown applies to this school.'
        : ($subscriptionExpiry
            ? 'Expires '.$subscriptionExpiry->format('d M Y')
            : 'No subscription expiry date is currently set.');

    $subscriptionTheme = match ($subscriptionState) {
        'expired', 'critical' => ['bg' => '#FFF1F2', 'border' => '#FECDD3', 'accent' => '#BE123C', 'badgeBg' => '#FFE4E6', 'badge' => 'Action required'],
        'warning' => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'accent' => '#B45309', 'badgeBg' => '#FEF3C7', 'badge' => 'Renew soon'],
        'free' => ['bg' => '#F8FAFC', 'border' => '#CBD5E1', 'accent' => '#475569', 'badgeBg' => '#E2E8F0', 'badge' => 'Free'],
        default => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'accent' => '#15803D', 'badgeBg' => '#DCFCE7', 'badge' => 'Active'],
    };
@endphp

<div style="background:{{ $subscriptionTheme['bg'] }};border:1px solid {{ $subscriptionTheme['border'] }};border-radius:14px;padding:16px 18px;margin:0 0 22px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,.04)">
    <div style="width:44px;height:44px;flex:0 0 44px;border-radius:12px;background:#071E45;color:#D79A21;display:flex;align-items:center;justify-content:center;font-size:20px">
        <i class="fas fa-clock"></i>
    </div>
    <div style="min-width:0;flex:1">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;margin-bottom:3px">Subscription status</div>
        <div style="font-size:17px;font-weight:800;color:#0F172A;line-height:1.25">{{ $subscriptionHeadline }}</div>
        <div style="font-size:12px;color:#64748B;margin-top:3px">{{ $subscriptionDetail }}</div>
        @if($subscriptionTenant && $tenant && $subscriptionTenant->id !== $tenant->id)
            <div style="font-size:11px;color:#64748B;margin-top:3px">Billing is managed by {{ $subscriptionTenant->name }}.</div>
        @endif
    </div>
    <span style="flex:0 0 auto;border-radius:999px;padding:5px 10px;background:{{ $subscriptionTheme['badgeBg'] }};color:{{ $subscriptionTheme['accent'] }};font-size:11px;font-weight:800;white-space:nowrap">{{ $subscriptionTheme['badge'] }}</span>
</div>
