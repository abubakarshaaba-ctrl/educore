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
.pay-btn:disabled{background:#CBD5E1;color:#64748B;cursor:not-allowed;filter:none}
.estimate-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(250px,.75fr);gap:22px;align-items:center;padding:24px}
.estimate-copy h2{font-size:18px;color:var(--midnight);margin-bottom:7px}.estimate-copy p{font-size:12px;line-height:1.65;color:var(--slate)}
.estimate-field{margin-top:16px}.estimate-field label{display:block;font-size:11px;font-weight:800;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
.estimate-field input{width:100%;padding:12px 13px;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;font:600 14px inherit;outline:0}.estimate-field input:focus{background:#fff;border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.estimate-result{background:linear-gradient(145deg,#071E45,#123D70);color:#fff;border-radius:14px;padding:22px;text-align:center}.estimate-result .amt{font-size:32px;font-weight:900}.estimate-result .sub{color:#CBD5E1;font-size:11px;line-height:1.5;margin-top:5px}
@media(max-width:760px){.estimate-grid{grid-template-columns:1fr;padding:18px}.estimate-result{padding:18px}.amount-box{padding:16px}.bcard table{min-width:680px}}
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
        <div class="val" style="font-size:15px">{{ $studentCount }} / {{ $capacity }}</div>
        <div class="lbl">Students / Paid Capacity</div>
    </div>
    <div class="bstat">
        <div class="val">₦{{ number_format($totalPaid) }}</div>
        <div class="lbl">Total Paid</div>
    </div>
</div>

@if($atCapacity)
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
    <div style="font-size:24px">🚫</div>
    <div>
        <div style="font-size:13px;font-weight:700;color:#991B1B">You've reached your paid capacity of {{ $capacity }} students</div>
        <div style="font-size:12px;color:#B91C1B;margin-top:3px">New students can't be enrolled until you generate and pay an invoice for additional capacity below.</div>
    </div>
</div>
@endif

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
            $isCurrent = \App\Services\PricingService::isFree($studentCount) ? $loop->first : $loop->iteration === 2;
        @endphp
        <div class="tier-row {{ $isCurrent ? 'current' : '' }}">
            <span>{{ $tier['range'] }}</span>
            <span style="font-weight:700">{{ $tier['rate'] }} <span style="font-weight:400;color:var(--slate-light);font-size:11px">{{ $tier['cycle'] }}</span></span>
        </div>
        @endforeach
    </div>
    <form method="POST" action="{{ route('billing.generate-invoice') }}" id="estimateForm">
        @csrf
        <input type="hidden" name="billing_cycle" id="cycleInput" value="{{ old('billing_cycle', 'termly') }}">
        <div class="estimate-grid">
            <div class="estimate-copy">
                <h2>Plan for your next enrollment</h2>
                <p>Enter the number of students you anticipate for the coming term. EduCore calculates the payable amount immediately. Enrollment up to 50 students remains free; above 50, the rate is &#8358;300 per student per term.</p>
                <div class="estimate-field">
                    <label for="anticipatedEnrollment">Anticipated enrollment</label>
                    <input type="number" id="anticipatedEnrollment" name="anticipated_enrollment" min="{{ max(1, $studentCount) }}" max="1000000" required value="{{ old('anticipated_enrollment', max($studentCount, $capacity)) }}" inputmode="numeric">
                    <div style="font-size:11px;color:var(--slate-light);margin-top:5px">Current active enrollment: {{ number_format($studentCount) }} students. Your estimate cannot be below this figure.</div>
                </div>
                <div class="cycle-toggle" id="cycleToggle" style="margin-top:16px">
                    <button type="button" data-cycle="termly" class="active">Per term</button>
                    <button type="button" data-cycle="annual">Full year (3 terms)</button>
                </div>
            </div>
            <div class="estimate-result">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#F2D58E;font-weight:800">Estimated payment</div>
                <div class="amt" id="amtDisplay">&#8358;0</div>
                <div class="sub" id="estimateDescription">Up to 50 students is free.</div>
                <button type="submit" class="pay-btn" id="paymentButton">Continue to Payment</button>
            </div>
        </div>
    </form>
</div>

<div class="bcard">
    <div class="bch">📄 Invoices</div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>Invoice #</th><th>Cycle</th><th>Capacity</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        @forelse($invoices as $inv)
        <tr>
            <td style="font-weight:700;font-family:monospace">{{ $inv->invoice_number }}</td>
            <td style="font-size:12px;text-transform:capitalize">
                {{ $inv->billing_cycle ?? '—' }}
                @if($inv->billing_cycle === 'monthly')<span class="badge" style="background:#F1F5F9;color:#64748B">Legacy plan</span>@endif
            </td>
            <td style="font-size:12px">{{ $inv->student_count ?? '—' }}</td>
            <td style="font-weight:700">₦{{ number_format($inv->amount) }}</td>
            <td style="font-size:12px;color:{{ \Carbon\Carbon::parse($inv->due_date)->isPast() && $inv->status !== 'paid' ? '#DC2626':'' }}">
                {{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}
            </td>
            <td><span class="badge b-{{ $inv->status }}">{{ ucfirst($inv->status) }}</span></td>
            <td>
                @if($inv->status !== 'paid' && $paymentConfigured && (float) $inv->amount > 0)
                <a href="{{ route('super.billing.pay', $inv->id) }}"
                   style="display:inline-flex;align-items:center;gap:4px;padding:6px 14px;background:#2563EB;color:white;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none">
                    💳 Pay Now
                </a>
                @elseif($inv->status !== 'paid' && (float) $inv->amount <= 0)
                <span style="font-size:11px;color:#D97706">Recalculate above</span>
                @elseif($inv->status !== 'paid')
                <span style="font-size:11px;color:#94A3B8">Payment setup pending</span>
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
    var toggle = document.getElementById('cycleToggle');
    var enrollment = document.getElementById('anticipatedEnrollment');
    var amount = document.getElementById('amtDisplay');
    var description = document.getElementById('estimateDescription');
    var paymentButton = document.getElementById('paymentButton');
    var cycleInput = document.getElementById('cycleInput');
    if (!toggle || !enrollment || !amount) return;

    function updateEstimate() {
        var count = Math.max(0, parseInt(enrollment.value || '0', 10));
        var terms = cycleInput.value === 'annual' ? 3 : 1;
        var payable = count <= 50 ? 0 : count * 300 * terms;
        amount.textContent = '\u20A6' + payable.toLocaleString('en-NG');
        paymentButton.disabled = payable === 0;
        paymentButton.textContent = payable === 0 ? 'No Payment Required' : 'Continue to Payment';
        description.textContent = payable === 0
            ? 'Up to 50 students is covered by the free plan.'
            : count.toLocaleString('en-NG') + ' students \u00D7 \u20A6300 \u00D7 ' + terms + (terms === 1 ? ' term' : ' terms');
    }

    toggle.querySelectorAll('button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cycle = btn.getAttribute('data-cycle');
            toggle.querySelectorAll('button').forEach(function (b) { b.classList.toggle('active', b === btn); });
            cycleInput.value = cycle;
            updateEstimate();
        });
    });
    enrollment.addEventListener('input', updateEstimate);
    updateEstimate();
});
</script>
@endpush
