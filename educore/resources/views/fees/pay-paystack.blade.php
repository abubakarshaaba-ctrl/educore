@extends('layouts.app')
@section('title','Pay Now')
@section('page-title','Online Payment')
@push('styles')
<style>
.pay-card{background:white;border:1px solid var(--border);border-radius:12px;max-width:480px;margin:0 auto;overflow:hidden}
.pay-header{background:linear-gradient(135deg,#2563EB,#1D4ED8);color:white;padding:28px 24px;text-align:center}
.pay-logo{font-size:32px;margin-bottom:8px}
.pay-school{font-size:16px;font-weight:700}
.pay-amount{font-size:36px;font-weight:800;margin:8px 0;letter-spacing:-0.03em}
.pay-label{font-size:12px;opacity:.8}
.pay-body{padding:24px}
.info-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px}
.info-row:last-child{border-bottom:none;margin-bottom:16px}
.ik{color:var(--slate)}.iv{font-weight:600;color:var(--midnight)}
.pay-btn{width:100%;padding:14px;font-size:15px;font-weight:700;font-family:inherit;border:none;border-radius:10px;cursor:pointer;background:#2563EB;color:white;transition:all 150ms}
.pay-btn:hover{background:#1D4ED8}
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
        <div class="info-row"><span class="ik">Email</span><span class="iv" style="font-size:11px">{{ $email }}</span></div>
        <div class="info-row"><span class="ik">Reference</span><span class="iv" style="font-size:11px;font-family:monospace">{{ $reference }}</span></div>

        <button class="pay-btn" onclick="payWithPaystack()">Pay ₦{{ number_format($balance, 2) }} Now</button>
        <div class="secure-note">🔒 Secured by Paystack · SSL encrypted</div>
    </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
function payWithPaystack() {
    var handler = PaystackPop.setup({
        key:       '{{ $config->public_key }}',
        email:     '{{ $email }}',
        amount:    {{ $balance * 100 }},
        currency:  'NGN',
        ref:       '{{ $reference }}',
        metadata: {
            invoice_id: {{ $invoice->id }},
            student:    '{{ optional($invoice->student)->full_name }}'
        },
        callback: function(response) {
            window.location.href = '{{ route("fees.gateway.paystack.callback") }}?reference=' + response.reference;
        },
        onClose: function() {
            alert('Payment window closed. Click Pay again to complete payment.');
        }
    });
    handler.openIframe();
}
</script>
@endsection