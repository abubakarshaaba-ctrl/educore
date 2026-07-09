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
.tier-row{display:flex;justify-content:space-between;align-items:center;padding:10px 18px;border-bottom:1px solid #F8FAFC;font-size:13px}
.tier-row.current{background:#EFF6FF}
.tier-row:last-child{border-bottom:none}
.cycle-toggle{display:inline-flex;background:#F1F5F9;border:1px solid var(--border);border-radius:999px;padding:3px;gap:2px}
.cycle-toggle button{border:none;background:none;font-family:inherit;font-size:12px;font-weight:700;color:var(--slate);padding:6px 16px;border-radius:999px;cursor:pointer}
.cycle-toggle button.active{background:#fff;color:var(--midnight);box-shadow:0 1px 2px rgba(0,0,0,.08)}
.amount-box{text-align:center;padding:24px}
.amount-box .amt{font-size:32px;font-weight:900;color:var(--midnight)}
.amount-box .sub{font-size:12px;color:var(--slate-light);margin-top:4px}
.pay-btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 28px;border:none;border-radius:8px;background:var(--indigo);color:#fff;font-weight:700;font-size:13px;font-family:inherit;cursor:pointer;margin-top:16px}
.pay-btn:hover{filter:brightness(1.06)}
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
        <div class="val" style="font-size:15px">{{ $studentCount }}</div>
        <div class="lbl">Active Students</div>
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

<div class="bcard">
    <div class="bch">💳 Pay-per-student pricing — every EduCore feature included, no add-on packages</div>

    <div style="padding:0 18px">
        @foreach(\App\Services\PricingService::tiers() as $tier)
        @php
            $isCurrent = false;
            if ($studentCount <= \App\Services\PricingService::FREE_THRESHOLD) $isCurrent = $loop->first;
            elseif ($studentCount <= \App\Services\PricingService::TIER2_MAX) $isCurrent = $loop->iteration === 2;
            elseif ($studentCount <= \App\Services\PricingService::TIER3_MAX) $isCurrent = $loop->iteration === 3;
            else $isCurrent = $loop->last;
        @endphp
        <div class="tier-row {{ $isCurrent ? 'current' : '' }}">
            <span>{{ $tier['range'] }}</span>
            <span style="font-weight:700">{{ $tier['rate'] }} <span style="font-weight:400;color:var(--slate-light);font-size:11px">{{ $tier['cycle'] }}</span></span>
        </div>
        @endforeach
    </div>

    @if(\App\Services\PricingService::isCustomQuote($studentCount))
        <div style="padding:20px;text-align:center">
            <p style="font-size:13px;color:var(--slate);margin-bottom:12px">
                Your school has {{ $studentCount }} active students — this qualifies for custom volume pricing.
            </p>
            <a href="mailto:support@educoreng.online" class="pay-btn" style="text-decoration:none">Contact EduCore for a Quote</a>
        </div>
    @elseif(\App\Services\PricingService::isFree($studentCount))
        <div style="padding:20px;text-align:center">
            <p style="font-size:13px;color:#059669;font-weight:700">
                ✓ Your school ({{ $studentCount }} students) is on the free plan — no invoice needed.
            </p>
        </div>
    @else
        <div class="amount-box">
            <div class="cycle-toggle" id="cycleToggle" style="margin-bottom:18px">
                <button type="button" data-cycle="termly" class="active">Per term</button>
                <button type="button" data-cycle="annual">Full year (10% off)</button>
            </div>
            <div class="amt" id="amtDisplay" data-termly="₦{{ number_format(\App\Services\PricingService::termlyAmount($studentCount)) }}" data-annual="₦{{ number_format(\App\Services\PricingService::annualAmount($studentCount)) }}">
                ₦{{ number_format(\App\Services\PricingService::termlyAmount($studentCount)) }}
            </div>
            <div class="sub">{{ $studentCount }} active students × {{ \App\Services\PricingService::tierLabel($studentCount) }}</div>

            <form method="POST" action="{{ route('billing.generate-invoice') }}">
                @csrf
                <input type="hidden" name="billing_cycle" id="cycleInput" value="termly">
                <button type="submit" class="pay-btn">Generate Invoice &amp; Pay</button>
            </form>
        </div>
    @endif
</div>

<div class="bcard">
    <div class="bch">📄 Invoices</div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>Invoice #</th><th>Cycle</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        @forelse($invoices as $inv)
        <tr>
            <td style="font-weight:700;font-family:monospace">{{ $inv->invoice_number }}</td>
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
        <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--slate-light)">No invoices yet</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('cycleToggle');
    if (!toggle) return;
    toggle.querySelectorAll('button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cycle = btn.getAttribute('data-cycle');
            toggle.querySelectorAll('button').forEach(function (b) { b.classList.toggle('active', b === btn); });
            var amt = document.getElementById('amtDisplay');
            amt.textContent = amt.getAttribute('data-' + cycle);
            document.getElementById('cycleInput').value = cycle;
        });
    });
});
</script>
@endpush
