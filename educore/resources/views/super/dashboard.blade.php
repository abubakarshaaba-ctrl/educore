@extends('layouts.super')
@section('title', 'Platform Dashboard')
@section('page-title', 'Platform Dashboard')

@push('styles')
<style>
    .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
    .stat-card { background:white; border:1px solid var(--border); border-radius:12px; padding:18px 20px; }
    .stat-card.highlight { background:linear-gradient(135deg,var(--midnight),var(--navy)); color:white; border:none; }
    .stat-val { font-size:26px; font-weight:800; letter-spacing:-0.03em; }
    .stat-lbl { font-size:11px; font-weight:600; opacity:0.7; text-transform:uppercase; letter-spacing:0.05em; margin-top:4px; }
    .stat-sub { font-size:12px; margin-top:6px; opacity:0.8; }
    .warn-val { color:#D97706; }
    .good-val { color:#059669; }
    .bad-val  { color:#DC2626; }

    .row-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
    .row-3 { display:grid; grid-template-columns:2fr 1fr; gap:16px; }
    .card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
    .card-h { padding:13px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:#F8FAFC; }
    .card-title { font-size:13px; font-weight:700; color:#0F172A; }
    table { width:100%; border-collapse:collapse; }
    thead th { font-size:10px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; padding:8px 14px; text-align:left; background:#F8FAFC; border-bottom:1px solid var(--border); }
    tbody td { padding:10px 14px; border-bottom:1px solid var(--border); font-size:12.5px; color:#0F172A; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .badge { display:inline-flex; font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; }
    .badge-green { background:#ECFDF5; color:#059669; }
    .badge-red   { background:#FEF2F2; color:#DC2626; }
    .badge-amber { background:#FFFBEB; color:#D97706; }
    .badge-blue  { background:#EFF6FF; color:#2563EB; }
    .btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; font-size:12px; font-weight:600; font-family:inherit; border-radius:8px; border:none; cursor:pointer; text-decoration:none; transition:all 150ms; }
    
    
    .btn-sm { padding:4px 10px; font-size:11px; }
    .alert-success { background:#ECFDF5; border:1px solid #A7F3D0; border-radius:8px; padding:12px 16px; font-size:13px; color:#059669; margin-bottom:16px; }

    .warn-banner { background:#FFFBEB; border:1px solid #FDE68A; border-radius:10px; padding:14px 18px; margin-bottom:20px; }
    .warn-banner h3 { font-size:13px; font-weight:700; color:#D97706; margin-bottom:8px; }
    .warn-item { display:flex; align-items:center; justify-content:space-between; padding:6px 0; border-bottom:1px solid #FEF3C7; font-size:12px; }
    .warn-item:last-child { border-bottom:none; }

    @media(max-width:1200px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
    @media(max-width:768px)  { .row-2,.row-3 { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

{{-- Expiring soon warning --}}
@if($expiringTenants->count())
<div class="warn-banner">
    <h3>⚠️ {{ $expiringTenants->count() }} school(s) expiring within 14 days</h3>
    @foreach($expiringTenants as $t)
    <div class="warn-item">
        <span><strong>{{ $t->name }}</strong></span>
        <span style="color:#D97706">Expires {{ optional($t->subscription_expires_at)->format('d M Y') }}</span>
        <a href="{{ route('super.tenant.show', $t) }}" class="btn btn-primary btn-sm">Renew</a>
    </div>
    @endforeach
</div>
@endif

{{-- Stats --}}
<div class="stats-grid">
    <div class="stat-card highlight">
        <div class="stat-val">₦{{ number_format($stats['revenue_total']) }}</div>
        <div class="stat-lbl" style="color:rgba(255,255,255,0.7)">Total Revenue</div>
        <div class="stat-sub">₦{{ number_format($stats['revenue_this_month']) }} this month</div>
    </div>
    <div class="stat-card">
        <div class="stat-val good-val">{{ $stats['active'] }}</div>
        <div class="stat-lbl">Active Schools</div>
        <div class="stat-sub">{{ $stats['tenants'] }} total · {{ $stats['pending'] }} pending setup</div>
    </div>
    <div class="stat-card">
        <div class="stat-val">{{ number_format($stats['total_students']) }}</div>
        <div class="stat-lbl">Total Students</div>
        <div class="stat-sub">{{ number_format($stats['total_users']) }} staff accounts</div>
    </div>
    <div class="stat-card">
        <div class="stat-val bad-val">{{ $stats['expired'] }}</div>
        <div class="stat-lbl">Expired / Suspended</div>
        <div class="stat-sub">{{ $stats['suspended'] }} suspended · {{ $stats['expiring_soon'] }} expiring soon</div>
    </div>
</div>

<div class="row-3">
    {{-- Recent Schools --}}
    <div class="card">
        <div class="card-h">
            <span class="card-title">Recent Schools</span>
            <div style="display:flex;gap:8px">
                <a href="{{ route('super.tenants.create') }}" class="btn btn-primary btn-sm">+ Add School</a>
                <a href="{{ route('super.tenants') }}" class="btn btn-sm" style="background:#F1F5F9;color:#0F172A">View All</a>
            </div>
        </div>
        <div class="tbl"><table>
            <thead><tr><th>School</th><th>Capacity</th><th>Status</th><th>Expires</th><th></th></tr></thead>
            <tbody>
                @foreach($recentTenants as $t)
                <tr>
                    <td>
                        <strong>{{ $t->name }}</strong>
                        <div style="font-size:10px;color:#94A3B8">{{ $t->email }}</div>
                    </td>
                    <td style="font-size:11px">{{ \App\Services\PricingService::capacityFor($t) }} students</td>
                    <td>
                        <span class="badge {{ $t->status==='active'?'badge-green':($t->status==='suspended'?'badge-red':'badge-amber') }}">
                            {{ ucfirst(str_replace('_',' ',$t->status)) }}
                        </span>
                    </td>
                    <td style="font-size:11px;color:{{ optional($t->subscription_expires_at)->isPast()?'#DC2626':'#64748B' }}">
                        {{ optional($t->subscription_expires_at)->format('d M Y') ?? '—' }}
                    </td>
                    <td>
                        <form method="POST" action="{{ route('super.impersonate', $t) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Login</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>

    {{-- Recent Payments --}}
    <div class="card">
        <div class="card-h">
            <span class="card-title">Recent Payments</span>
            <a href="{{ route('super.payments') }}" style="font-size:11px;color:#2563EB;text-decoration:none">View all →</a>
        </div>
        @forelse($recentPayments as $pay)
        <div style="padding:10px 14px;border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:12px;font-weight:600">{{ $pay->school_name }}</span>
                <span style="font-size:13px;font-weight:700;color:#059669">₦{{ number_format($pay->amount) }}</span>
            </div>
            <div style="font-size:10px;color:#94A3B8;margin-top:2px">
                {{ $pay->payment_method ?? '—' }} · {{ \Carbon\Carbon::parse($pay->created_at)->diffForHumans() }}
            </div>
        </div>
        @empty
        <div style="padding:30px;text-align:center;color:#94A3B8;font-size:12px">No payments yet</div>
        @endforelse
    </div>
</div>

{{-- Payment Gateway Setup Notice --}}
@php
    $pgConfigured = \Illuminate\Support\Facades\DB::table('platform_settings')
        ->whereIn('key', ['paystack_public_key', 'monnify_api_key'])
        ->whereNotNull('value')->where('value', '!=', '')->exists();
@endphp
@if(!$pgConfigured)
<div style="background:#FFF7ED;border:1px solid #FDE68A;border-radius:12px;padding:16px 20px;margin-top:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <div style="font-size:13px;font-weight:700;color:#92400E;margin-bottom:3px">⚠️ Payment Gateway Not Configured</div>
        <div style="font-size:12px;color:#B45309;line-height:1.5">Schools cannot pay their subscription fees online until you configure at least one payment gateway (Paystack or Monnify).</div>
    </div>
    <a href="{{ route('super.payment-gateways') }}"
       style="flex-shrink:0;padding:9px 18px;background:#D97706;color:white;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap">
        💳 Setup Payment Gateway →
    </a>
</div>
@endif
@endsection
