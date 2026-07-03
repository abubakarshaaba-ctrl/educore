<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subscription Payment — {{ optional($tenant)->name }}</title>
<style>
body{font-family:system-ui,sans-serif;background:#0F172A;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.card{background:white;border-radius:16px;padding:36px;max-width:460px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.4)}
.school{font-size:13px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px}
.plan{font-size:22px;font-weight:900;color:#1E293B;margin-bottom:4px}
.cycle{font-size:13px;color:#64748B;margin-bottom:20px}
.divider{height:1px;background:#F1F5F9;margin:16px 0}
.row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px}
.row .lbl{color:#64748B}
.row .val{font-weight:700;color:#1E293B}
.amount{font-size:40px;font-weight:900;color:#1E3A5F;text-align:center;margin:16px 0 4px}
.amount-sub{font-size:12px;color:#94A3B8;text-align:center;margin-bottom:20px}
.btn{width:100%;padding:15px;background:#2563EB;color:white;border:none;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:background 150ms}
.btn:hover{background:#1D4ED8}
.secure{font-size:11px;color:#94A3B8;text-align:center;margin-top:10px}
.back{display:block;text-align:center;margin-top:14px;font-size:12px;color:#94A3B8;text-decoration:none}
.back:hover{color:#475569}
</style>
</head>
<body>
<div class="card">
    <div class="school">{{ optional($tenant)->name }}</div>
    <div class="plan">Subscription Renewal</div>
    <div class="cycle">
        Invoice #{{ $invoice->invoice_number }}
        · {{ ucfirst($invoice->billing_cycle ?? 'monthly') }} plan
    </div>
    <div class="divider"></div>
    <div class="row"><span class="lbl">Due Date</span><span class="val">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</span></div>
    <div class="row"><span class="lbl">Status</span><span class="val" style="color:#D97706">{{ ucfirst($invoice->status) }}</span></div>
    @if($invoice->notes)
    <div class="row"><span class="lbl">Notes</span><span class="val">{{ $invoice->notes }}</span></div>
    @endif
    <div class="divider"></div>
    <div class="amount">₦{{ number_format($amount) }}</div>
    <div class="amount-sub">One-time payment · Secure checkout</div>
    @if(!empty($paystackEnabled))
        <button class="btn" onclick="payNow()">💳 Pay with Paystack</button>
    @endif
    @if(!empty($monnifyEnabled))
        <a href="{{ route('super.billing.pay.monnify', $invoice->id) }}" class="btn" style="display:block;text-align:center;text-decoration:none;margin-top:{{ !empty($paystackEnabled) ? '10px' : '0' }};background:#0B6E4F">🏦 Pay with Monnify</a>
    @endif
    <div class="secure">🔒 256-bit SSL Encryption · Secure checkout</div>
    <a href="javascript:history.back()" class="back">← Go back</a>
</div>

@if(!empty($paystackEnabled))
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
function payNow() {
    var handler = PaystackPop.setup({
        key: '{{ $settings->paystack_public_key }}',
        email: '{{ $email }}',
        amount: {{ $amount * 100 }},
        currency: 'NGN',
        ref: '{{ $reference }}',
        metadata: {
            invoice_number: '{{ $invoice->invoice_number }}',
            school: '{{ optional($tenant)->name }}',
            type: 'subscription'
        },
        callback: function(response) {
            window.location = '{{ route("super.billing.pay.callback") }}?reference=' + response.reference;
        },
        onClose: function() { alert('Payment cancelled.'); }
    });
    handler.openIframe();
}
</script>
@endif
</body>
</html>
