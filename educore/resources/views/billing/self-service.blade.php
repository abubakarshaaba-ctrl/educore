@extends('layouts.app')
@section('title', 'Subscription & Billing')
@section('page-title', 'Subscription & Billing')

@push('styles')
<style>
.bcard{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.bch{padding:13px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--midnight);background:#F8FAFC}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:10px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);border-bottom:1px solid var(--border);background:#F8FAFC}
td{padding:11px 14px;border-bottom:1px solid #F8FAFC;color:var(--midnight)}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px}
.b-paid{background:#ECFDF5;color:#059669}
.b-pending{background:#FFFBEB;color:#D97706}
.b-overdue{background:#FEF2F2;color:#DC2626}
.bstat{background:white;border:1px solid var(--border);border-radius:10px;padding:16px;text-align:center;flex:1;min-width:120px}
.bstat .val{font-size:22px;font-weight:900;color:var(--midnight)}
.bstat .lbl{font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:3px}
.plan-toggle{display:inline-flex;background:#F1F5F9;border:1px solid var(--border);border-radius:999px;padding:3px;gap:2px}
.plan-toggle button{border:none;background:none;font-family:inherit;font-size:12px;font-weight:700;color:var(--slate);padding:6px 16px;border-radius:999px;cursor:pointer}
.plan-toggle button.active{background:#fff;color:var(--midnight);box-shadow:0 1px 2px rgba(0,0,0,.08)}
.plan-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;padding:18px}
.plan{border:1.5px solid var(--border);border-radius:12px;padding:18px;display:flex;flex-direction:column;background:#fff}
.plan.is-current{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(215,154,33,.12)}
.plan-name{font-size:15px;font-weight:800;color:var(--midnight)}
.plan-desc{font-size:12px;color:var(--slate);margin:4px 0 12px;line-height:1.45;min-height:34px}
.plan-price{font-size:24px;font-weight:900;color:var(--midnight)}
.plan-price small{font-size:12px;font-weight:600;color:var(--slate-light)}
.plan-features{list-style:none;margin:14px 0;padding:0;font-size:12.5px;color:var(--midnight);line-height:1.85;flex:1}
.plan-features li::before{content:"✓ ";color:#059669;font-weight:800}
.plan-btn{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:10px;border:none;border-radius:8px;background:var(--indigo);color:#fff;font-weight:700;font-size:13px;font-family:inherit;cursor:pointer;transition:filter 140ms}
.plan-btn:hover{filter:brightness(1.06)}
.plan-current-tag{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:10px;border-radius:8px;background:#ECFDF5;color:#059669;font-weight:700;font-size:13px}
</style>
@endpush

@section('content')

@if(session('success'))
<div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:16px">✓ {{ session('success') }}</div>
@endif
@if($errors->any())
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:16px">{{ $errors->first() }}</div>
@endif

<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    <div class="bstat">
        <div class="val" style="color:{{ $tenant->isExpired() ? '#DC2626' : '#059669' }}">
            {{ $tenant->isExpired() ? 'Expired' : 'Active' }}
        </div>
        <div class="lbl">Subscription</div>
    </div>
    <div class="bstat">
        <div class="val" style="font-size:15px">{{ optional($tenant->subscription_expires_at)->format('d M Y') ?? '—' }}</div>
        <div class="lbl">Expires</div>
    </div>
    <div class="bstat">
        @php
            // Use $currentPlanId (properly computed in controller) to display plan name.
            // Do NOT use $tenant->activeSubscription->plan->name — it may return a
            // plan-less admin-created subscription if that was created more recently.
            $displayPlan = $currentPlanId
                ? $plans->firstWhere('id', $currentPlanId)
                : null;
        @endphp
        <div class="val" style="font-size:15px">{{ $displayPlan ? $displayPlan->name : 'No plan' }}</div>
        <div class="lbl">Current Plan</div>
    </div>
    <div class="bstat">
        <div class="val">₦{{ number_format($totalPaid) }}</div>
        <div class="lbl">Total Paid</div>
    </div>
</div>

@if($tenant->isExpired() || $tenant->isExpiringSoon())
<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
    <div style="font-size:24px">⚠️</div>
    <div>
        <div style="font-size:13px;font-weight:700;color:#92400E">
            {{ $tenant->isExpired() ? 'Subscription expired' : 'Expiring soon' }}
        </div>
        <div style="font-size:12px;color:#B45309;margin-top:3px">{{ ($hasOutstandingInvoice ?? false) ? 'Pay an outstanding invoice below to renew your access.' : 'Renew your subscription to keep uninterrupted access.' }}</div>
    </div>
</div>
@endif

@if($plans->count())
<div class="bcard">
    <div class="bch" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <span>⭐ Choose a plan</span>
        <div class="plan-toggle" id="planCycleToggle">
            <button type="button" data-cycle="monthly" class="active">Monthly</button>
            <button type="button" data-cycle="annual">Annual</button>
        </div>
    </div>
    <div class="plan-grid">
        @foreach($plans as $plan)
        <div class="plan {{ ($currentPlanId ?? null) == $plan->id ? 'is-current' : '' }}">
            <div class="plan-name">{{ $plan->name }}</div>
            <div class="plan-desc">{{ $plan->description }}</div>
            <div class="plan-price"
                 data-monthly="₦{{ number_format($plan->monthly_price) }}"
                 data-annual="₦{{ number_format($plan->annual_price) }}">
                ₦{{ number_format($plan->monthly_price) }}<small class="plan-cycle-label"> /month</small>
            </div>
            <ul class="plan-features">
                @foreach($plan->feature_list as $feature)<li>{{ $feature }}</li>@endforeach
            </ul>
            @if(($currentPlanId ?? null) == $plan->id)
                <span class="plan-current-tag">✓ Current plan</span>
            @else
                <form method="POST" action="{{ route('billing.select-plan') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <input type="hidden" name="billing_cycle" value="monthly" class="plan-cycle-input">
                    <button type="submit" class="plan-btn">Subscribe &amp; Pay</button>
                </form>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="bcard">
    <div class="bch">📄 Invoices</div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>Invoice #</th><th>Plan</th><th>Cycle</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        @forelse($invoices as $inv)
        <tr>
            <td style="font-weight:700;font-family:monospace">{{ $inv->invoice_number }}</td>
            <td>{{ $inv->plan_name ?? '—' }}</td>
            <td style="font-size:12px;text-transform:capitalize">{{ $inv->billing_cycle ?? '—' }}</td>
            <td style="font-weight:700">₦{{ number_format($inv->amount) }}</td>
            <td style="font-size:12px;color:{{ \Carbon\Carbon::parse($inv->due_date)->isPast() && $inv->status !== 'paid' ? '#DC2626':'' }}">
                {{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}
            </td>
            <td><span class="badge b-{{ $inv->status }}">{{ ucfirst($inv->status) }}</span></td>
            <td>
                @if($inv->status !== 'paid' && $gatewayConfigured)
                <a href="{{ route('super.billing.pay', $inv->id) }}"
                   style="display:inline-flex;align-items:center;gap:4px;padding:6px 14px;background:#2563EB;color:white;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none">
                    💳 Pay Now
                </a>
                @elseif($inv->status !== 'paid')
                <span style="font-size:11px;color:#94A3B8">Contact support</span>
                @else
                <span style="font-size:12px;color:#059669;font-weight:600">✓ Paid</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--slate-light)">No invoices yet</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('planCycleToggle');
    if (!toggle) return;
    toggle.querySelectorAll('button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cycle = btn.getAttribute('data-cycle');
            toggle.querySelectorAll('button').forEach(function (b) { b.classList.toggle('active', b === btn); });
            document.querySelectorAll('.plan-price').forEach(function (p) {
                var amt = p.getAttribute('data-' + cycle);
                if (amt) p.innerHTML = amt + '<small> ' + (cycle === 'annual' ? '/year' : '/month') + '</small>';
            });
            document.querySelectorAll('.plan-cycle-input').forEach(function (i) { i.value = cycle; });
        });
    });
});
</script>
@endpush
