@extends('layouts.app')
@section('title','Pay Now')
@section('page-title','Online Payment')
@push('styles')
<style>
.pay-card{background:white;border:1px solid var(--border);border-radius:12px;max-width:480px;margin:0 auto;overflow:hidden}
.pay-header{background:linear-gradient(135deg,#F5A623,#E8930E);color:white;padding:28px 24px;text-align:center}
.pay-logo{font-size:32px;margin-bottom:8px}
.pay-school{font-size:16px;font-weight:700}
.pay-amount{font-size:36px;font-weight:800;margin:8px 0;letter-spacing:-0.03em}
.pay-label{font-size:12px;opacity:.8}
.pay-body{padding:24px}
.info-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px}
.info-row:last-child{border-bottom:none;margin-bottom:16px}
.ik{color:var(--slate)}.iv{font-weight:600;color:var(--midnight)}
.pay-btn{width:100%;padding:14px;font-size:15px;font-weight:700;font-family:inherit;border:none;border-radius:10px;cursor:pointer;background:#F5A623;color:white;transition:all 150ms}
.pay-btn:hover{background:#E8930E}
.secure-note{text-align:center;font-size:11px;color:var(--slate-light);margin-top:12px;display:flex;align-items:center;justify-content:center;gap:4px}
</style>
@endpush
@section('content')
<div class="pay-card">
    <div class="pay-header">
        <div class="pay-logo">🏫</div>
        <div class="pay-school">{{ optional(auth()->user()->tenant)->name }}</div>
        <div class="pay-amount">₦{{ number_format($balance, 2) }}</div>
        <div class="pay-label">Fee Payment</div>
    </div>
    <div class="pay-body">
        <div class="info-row"><span class="ik">Student</span><span class="iv">{{ optional($invoice->student)->full_name }}</span></div>
        <div class="info-row"><span class="ik">Invoice No</span><span class="iv" style="font-size:11px;font-family:monospace">{{ $invoice->invoice_number }}</span></div>
        <div class="info-row"><span class="ik">Reference</span><span class="iv" style="font-size:11px;font-family:monospace">{{ $reference }}</span></div>

        <button class="pay-btn" onclick="payWithFlutterwave()">Pay ₦{{ number_format($balance, 2) }} Now</button>
        <div class="secure-note">🔒 Secured by Flutterwave · SSL encrypted</div>
    </div>
</div>

<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
function payWithFlutterwave() {
    FlutterwaveCheckout({
        public_key: '{{ $config->public_key }}',
        tx_ref:     '{{ $reference }}',
        amount:     {{ $balance }},
        currency:   'NGN',
        payment_options: 'card, banktransfer, ussd',
        customer: {
            email:       '{{ $email }}',
            name:        '{{ optional($invoice->student)->full_name }}'
        },
        customizations: {
            title:       '{{ optional(auth()->user()->tenant)->name }}',
            description: 'School Fee Payment — {{ $invoice->invoice_number }}',
        },
        callback: function(data) {
            window.location.href = '{{ route("fees.gateway.flutterwave.callback") }}?transaction_id=' + data.transaction_id + '&tx_ref={{ $reference }}&status=' + data.status;
        },
        onclose: function() {}
    });
}
</script>
@endsection