<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application Fee Payment</title>
<style>
body{font-family:system-ui,sans-serif;background:#F8FAFC;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.card{background:white;border-radius:16px;padding:32px;max-width:460px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.1);text-align:center}
.amount{font-size:36px;font-weight:900;color:#1E3A5F;margin:16px 0 4px}
.desc{font-size:13px;color:#64748B;margin-bottom:24px}
.btn{width:100%;padding:14px;background:#2563EB;color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit}
.btn:hover{background:#1D4ED8}
.secure{font-size:11px;color:#94A3B8;margin-top:12px}
</style>
</head>
<body>
<div class="card">
    <div style="font-size:32px;margin-bottom:8px">🏫</div>
    <div style="font-size:18px;font-weight:800;color:#1E293B;margin-bottom:4px">Application Fee</div>
    <div style="font-size:13px;color:#64748B;margin-bottom:4px">{{ $admission->first_name }} {{ $admission->last_name }}</div>
    <div style="font-size:11px;color:#94A3B8;margin-bottom:16px">Ref: {{ $admission->application_number }}</div>
    <div class="amount">₦{{ number_format($amount) }}</div>
    <div class="desc">Pay your application fee to complete your submission</div>
    <button class="btn" onclick="payWithPaystack()">💳 Pay Now with Paystack</button>
    <div class="secure">🔒 Secured by Paystack · SSL Encrypted</div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
function payWithPaystack() {
    var handler = PaystackPop.setup({
        key: '{{ $config->public_key }}',
        email: '{{ $email }}',
        amount: {{ $amount * 100 }},
        currency: 'NGN',
        ref: '{{ $reference }}',
        metadata: { application_number: '{{ $admission->application_number }}', type: 'application_fee' },
        callback: function(response) {
            window.location = '/admissions/fee-callback?reference=' + encodeURIComponent(response.reference) + '&slug={{ $slug }}';
        },
        onClose: function() { alert('Payment window closed. Please try again.'); }
    });
    handler.openIframe();
}
</script>
</body>
</html>
