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
@endsection
