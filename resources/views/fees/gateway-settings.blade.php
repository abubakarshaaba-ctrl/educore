@extends('layouts.app')
@section('title','Payment Gateway')
@section('page-title','Payment Gateway Settings')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;width:100%}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:20px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.status-card{border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:center;gap:12px;font-size:13px}
.sc-on{background:#ECFDF5;border:1px solid #A7F3D0;color:var(--emerald)}
.sc-off{background:#FEF2F2;border:1px solid #FECACA;color:var(--crimson)}
.sc-icon{font-size:24px}
.info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;font-size:12.5px;color:var(--indigo);margin-bottom:16px;line-height:1.5}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:640px) { .fr { grid-template-columns:1fr; } }
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="status-card {{ $config->is_active ? 'sc-on' : 'sc-off' }}">
    <div class="sc-icon">{{ $config->is_active ? '✅' : '⚠️' }}</div>
    <div>
        <div style="font-weight:700">{{ $config->is_active ? 'Gateway Active — '.ucfirst($config->gateway) : 'Gateway Not Configured' }}</div>
        <div style="font-size:12px;opacity:.8;margin-top:2px">{{ $config->is_active ? ($config->is_live ? 'LIVE mode — real payments active' : 'TEST mode — no real charges') : 'Configure your keys below to accept online payments' }}</div>
    </div>
</div>
<div class="info-box">
    💡 <strong>How it works:</strong> Parents can pay school fees directly on the invoice page using Paystack, Flutterwave, or Monnify. Payments are automatically recorded. Get your API keys from <a href="https://dashboard.paystack.com" target="_blank" style="color:inherit;font-weight:700">Paystack</a>, <a href="https://app.flutterwave.com" target="_blank" style="color:inherit;font-weight:700">Flutterwave</a>, or <a href="https://app.monnify.com" target="_blank" style="color:inherit;font-weight:700">Monnify</a>.
</div>
<div class="card">
    <div class="ch">Gateway Configuration</div>
    <div class="cb">
        <form method="POST" action="{{ route('fees.gateway.settings.save') }}">
        @csrf
        <div class="fg">
            <label class="fl">Payment Gateway</label>
            <select name="gateway" class="fc" id="gwSelect" onchange="toggleGwFields(this.value)">
                <option value="paystack"    {{ ($config->gateway??'paystack')==='paystack'    ?'selected':'' }}>Paystack (Recommended for Nigeria)</option>
                <option value="flutterwave" {{ ($config->gateway??'')==='flutterwave'         ?'selected':'' }}>Flutterwave</option>
                <option value="monnify"     {{ ($config->gateway??'')==='monnify'             ?'selected':'' }}>Monnify</option>
            </select>
        </div>

        {{-- Paystack / Flutterwave fields --}}
        <div id="gw-pubkey" class="fr">
            <div class="fg">
                <label class="fl" id="pubKeyLabel">Public Key</label>
                <input type="text" name="public_key" class="fc" value="{{ $config->public_key }}" placeholder="pk_test_... / pk_live_...">
            </div>
            <div class="fg">
                <label class="fl">Secret Key</label>
                <input type="password" name="secret_key" class="fc" value="{{ $config->secret_key }}" placeholder="sk_test_... / sk_live_...">
            </div>
        </div>

        {{-- Monnify contract code --}}
        <div id="gw-contract" class="fg" style="display:none">
            <label class="fl">Contract Code</label>
            <input type="text" name="contract_code" class="fc" value="{{ $config->contract_code }}" placeholder="e.g. 1234567890">
            <div style="font-size:11px;color:var(--slate-light);margin-top:4px">Found in your Monnify dashboard → Contracts.</div>
        </div>

        <div class="fg" style="flex-direction:row;align-items:center;gap:10px;margin-bottom:20px">
            <input type="checkbox" name="is_live" value="1" {{ $config->is_live ? 'checked' : '' }} id="live-toggle" style="width:16px;height:16px;accent-color:var(--indigo)">
            <label for="live-toggle" style="font-size:13px;font-weight:600;color:var(--midnight);cursor:pointer">Enable LIVE mode (real payments)</label>
        </div>
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400E;margin-bottom:16px">
            ⚠️ Only enable LIVE mode after testing with test keys. Live mode processes real payments.
        </div>
        <button type="submit" class="btn btn-p">Save Gateway Settings</button>
        </form>
    </div>
</div>
<script>
function toggleGwFields(gw) {
    const pubRow  = document.getElementById('gw-pubkey');
    const conRow  = document.getElementById('gw-contract');
    const pubLbl  = document.getElementById('pubKeyLabel');
    if (gw === 'monnify') {
        pubRow.querySelector('input[name=public_key]').placeholder = 'MK_TEST_... (API Key)';
        if (pubLbl) pubLbl.textContent = 'API Key';
        if (conRow) conRow.style.display = 'flex';
    } else {
        pubRow.querySelector('input[name=public_key]').placeholder = gw === 'flutterwave' ? 'FLWPUBK_TEST-...' : 'pk_test_...';
        if (pubLbl) pubLbl.textContent = 'Public Key';
        if (conRow) conRow.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', () => toggleGwFields(document.getElementById('gwSelect').value));
</script>
@endsection