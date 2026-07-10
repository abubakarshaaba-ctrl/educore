@extends('layouts.super')
@section('title','Pricing')
@section('page-title','Pricing')
@push('styles')
<style>
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#065F46;margin-bottom:16px}
.alert-e{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;font-size:13px;color:#991B1B;margin-bottom:16px}
.card{background:white;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:24px}
.card-head{padding:16px 20px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,var(--midnight),#1a3a6b);color:white;font-size:15px;font-weight:800}
.tier-row{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border)}
.tier-row:last-child{border-bottom:none}
.tier-row.free{background:#F0FDF4}
.tier-range{font-size:14px;font-weight:700;color:var(--midnight)}
.tier-rate{font-size:15px;font-weight:800;color:var(--midnight)}
.tier-cycle{font-size:11px;color:var(--slate-light)}
.note{padding:14px 20px;font-size:12px;color:var(--slate-light);background:#F8FAFC;border-top:1px solid var(--border)}

.legacy-banner{background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:14px 18px;margin-bottom:16px;font-size:13px;color:#92400E}
.plan-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
.pc{background:white;border:1px solid var(--border);border-radius:12px;padding:16px 18px;opacity:0.85}
.pc-name{font-size:14px;font-weight:800;color:var(--midnight)}
.pc-price{font-size:12px;color:var(--slate-light);margin-top:2px}
.pc-badge{padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:#F1F5F9;color:#64748B}
.feat-tags{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0}
.feat-tag{padding:2px 8px;background:#F1F5F9;color:#64748B;border-radius:20px;font-size:10px;font-weight:600}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">&#10003; {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e"><strong>Could not save:</strong>@foreach($errors->all() as $e)<div>&bull; {{ $e }}</div>@endforeach</div>@endif

{{-- ══ Active pricing model — what's actually enforced ══════════════ --}}
<div class="card">
    <div class="card-head">💳 Active Pricing — Pay Per Student</div>
    @foreach(\App\Services\PricingService::tiers() as $tier)
    <div class="tier-row {{ $loop->first ? 'free' : '' }}">
        <span class="tier-range">{{ $tier['range'] }}</span>
        <span>
            <span class="tier-rate">{{ $tier['rate'] }}</span>
            <span class="tier-cycle">{{ $tier['cycle'] }}</span>
        </span>
    </div>
    @endforeach
    <div class="note">
        Every tenant gets every EduCore feature regardless of enrollment size — there are no
        feature-gated tiers. Pricing and enforcement are defined in <code>App\Services\PricingService</code>
        and applied automatically per school based on their active student count
        (see each school's Subscription &amp; Billing page for their current capacity).
        These tiers aren't editable from this screen — they're a fixed constant in code, changed
        via a code deployment rather than a database record, so pricing can't drift out of sync
        with what's actually enforced.
    </div>
</div>

{{-- ══ Legacy plans — historical reference only ══════════════════════ --}}
<div class="legacy-banner">
    ⚠️ The plans below are from the old tiered subscription model and are kept only for
    historical invoice records. They no longer control feature access or billing for any
    school — every school is on the pay-per-student model above.
</div>
<div class="plan-grid">
@forelse($plans as $plan)
@php $planFeatures = is_array($plan->features) ? $plan->features : (json_decode($plan->features ?? '[]', true) ?? []); @endphp
<div class="pc">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
            <div class="pc-name">{{ $plan->name }}</div>
            <div class="pc-price">₦{{ number_format($plan->monthly_price) }}/mo (legacy) &nbsp;·&nbsp; ₦{{ number_format($plan->annual_price) }}/yr (legacy)</div>
        </div>
        <span class="pc-badge">Historical</span>
    </div>
    <div class="feat-tags">
        <span class="feat-tag">{{ count($planFeatures) }} features (legacy)</span>
        <span class="feat-tag">{{ number_format($plan->max_students) }} students (legacy cap)</span>
    </div>
</div>
@empty
<p style="color:var(--slate-light);font-size:13px">No legacy plans on record.</p>
@endforelse
</div>
@endsection
