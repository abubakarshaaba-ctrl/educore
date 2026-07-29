<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subscription Payment — {{ optional($tenant)->name }}</title>
<style>
*{box-sizing:border-box}
body{font-family:system-ui,sans-serif;background:linear-gradient(145deg,#071E45,#0F274B 56%,#183B66);display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;color:#101828}
.card{background:white;border-radius:20px;padding:32px;max-width:720px;width:100%;box-shadow:0 24px 70px rgba(0,0,0,.34)}
.school{font-size:13px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px}
.plan{font-size:22px;font-weight:900;color:#1E293B;margin-bottom:4px}
.cycle{font-size:13px;color:#64748B;margin-bottom:20px}
.divider{height:1px;background:#F1F5F9;margin:16px 0}
.row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px}
.row .lbl{color:#64748B}
.row .val{font-weight:700;color:#1E293B}
.amount{font-size:40px;font-weight:900;color:#1E3A5F;text-align:center;margin:16px 0 4px}
.amount-sub{font-size:12px;color:#94A3B8;text-align:center;margin-bottom:20px}
.methods{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:22px}
.method{border:1px solid #E2E8F0;border-radius:14px;padding:18px;background:#F8FAFC}
.method h2{font-size:15px;margin:0 0 5px;color:#071E45}.method p{font-size:12px;color:#64748B;line-height:1.55;margin:0 0 14px}
.bank-detail{display:flex;justify-content:space-between;gap:16px;padding:7px 0;border-bottom:1px solid #E2E8F0;font-size:12px}.bank-detail:last-of-type{border-bottom:0}.bank-detail span{color:#64748B}.bank-detail strong{text-align:right;color:#101828}
.reference{width:100%;padding:11px 12px;border:1px solid #CBD5E1;border-radius:9px;font:13px inherit;margin:13px 0 0;background:#fff}
.btn{width:100%;display:block;text-align:center;text-decoration:none;padding:13px;background:#2563EB;color:white;border:none;border-radius:10px;font-size:14px;font-weight:800;cursor:pointer;font-family:inherit;transition:background 150ms;margin-top:10px}
.btn:hover{background:#1D4ED8}
.btn-bank{background:#D79A21;color:#071E45}.btn-bank:hover{background:#C58B18}
.online-stack{display:grid;gap:9px}
.alert{padding:11px 13px;border-radius:9px;font-size:12px;margin:12px 0}.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:#B42318}.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#047857}
.secure{font-size:11px;color:#94A3B8;text-align:center;margin-top:10px}
.back{display:block;text-align:center;margin-top:14px;font-size:12px;color:#94A3B8;text-decoration:none}
.back:hover{color:#475569}
@media(max-width:640px){body{padding:12px}.card{padding:22px 18px}.methods{grid-template-columns:1fr}.amount{font-size:32px}}
</style>
</head>
<body>
<div class="card">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <div class="school">{{ optional($tenant)->name }}</div>
    <div class="plan">Choose a Payment Method</div>
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
    <div class="amount-sub">{{ number_format((int) ($invoice->student_count ?? 0)) }} anticipated students · {{ ucfirst($invoice->billing_cycle ?? 'termly') }} billing</div>

    <div class="methods">
        @if(!empty($bankTransferEnabled))
        <section class="method">
            <h2>Bank Transfer</h2>
            <p>Transfer the exact invoice amount, then submit your bank reference for verification.</p>
            <div class="bank-detail"><span>Bank</span><strong>{{ $bankTransfer->bank_name }}</strong></div>
            <div class="bank-detail"><span>Account name</span><strong>{{ $bankTransfer->account_name }}</strong></div>
            <div class="bank-detail"><span>Account number</span><strong>{{ $bankTransfer->account_number }}</strong></div>
            <form method="POST" action="{{ route('super.billing.bank-transfer', $invoice->id) }}">
                @csrf
                <input class="reference" name="transfer_reference" value="{{ old('transfer_reference', $invoice->payment_method === 'bank_transfer' ? $invoice->payment_ref : '') }}" required maxlength="100" placeholder="Bank transfer reference">
                <button type="submit" class="btn btn-bank">Submit Transfer Reference</button>
            </form>
            @if($invoice->payment_method === 'bank_transfer' && $invoice->payment_ref)
            <div class="alert alert-success">Reference received. Verification is pending.</div>
            @endif
        </section>
        @endif

        @if(!empty($paystackEnabled) || !empty($monnifyEnabled))
        <section class="method">
            <h2>Online Payment</h2>
            <p>Pay instantly through one of the secure payment gateways enabled by EduCore.</p>
            <div class="online-stack">
                @if(!empty($paystackEnabled))
                    <button class="btn" onclick="payNow()">Pay with Paystack</button>
                @endif
                @if(!empty($monnifyEnabled))
                    <a href="{{ route('super.billing.pay.monnify', $invoice->id) }}" class="btn" style="background:#0B6E4F">Pay with Monnify</a>
                @endif
            </div>
        </section>
        @endif
    </div>
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
