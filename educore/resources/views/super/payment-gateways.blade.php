@extends('layouts.super')
@section('title','Payment Gateway Settings')
@section('page-title','Payment Gateway Settings')
@push('styles')
<style>
.pg-intro{background:linear-gradient(135deg,#071E45 0%,#0f3b6f 100%);border-radius:14px;padding:20px 24px;color:white;margin-bottom:24px}
.pg-intro-title{font-size:18px;font-weight:800;margin-bottom:4px}
.pg-intro-sub{font-size:12px;opacity:.75;line-height:1.6}

.pg-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;margin-bottom:20px}
.pg-card{background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden}
.pg-card-head{padding:14px 20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--border)}
.pg-logo{width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;flex-shrink:0}
.pg-logo-ps{background:#00C3F7;color:white}
.pg-logo-fw{background:#F5A623;color:white}
.pg-logo-mn{background:#002147;color:#F5A623}
.pg-card-title{font-size:14px;font-weight:800;color:var(--midnight)}
.pg-card-sub{font-size:11px;color:var(--slate-light);margin-top:1px}
.pg-card-body{padding:18px 20px}

.fg{margin-bottom:14px}
.fl{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--slate-light);margin-bottom:4px}
.fc{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:#F8FAFC;outline:none;transition:border 150ms}
.fc:focus{border-color:var(--indigo);background:white}
.fc[type=password]{font-family:monospace;letter-spacing:.1em}

.secret-row{position:relative}
.secret-row .fc{padding-right:38px}
.eye-btn{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--slate-light);font-size:15px;padding:4px}

.mode-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid var(--border);margin-top:6px}
.mode-label{font-size:12px;font-weight:700;color:var(--midnight)}
.mode-sub{font-size:11px;color:var(--slate-light)}
.toggle-wrap{position:relative;width:44px;height:24px;flex-shrink:0}
.toggle-wrap input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;border-radius:24px;transition:200ms;background:#CBD5E1}
.toggle-wrap input:checked + .toggle-slider{background:#059669}
.toggle-slider:before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:200ms}
.toggle-wrap input:checked + .toggle-slider:before{transform:translateX(20px)}

.status-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;margin-left:8px}
.status-configured{background:#ECFDF5;color:#059669}
.status-missing{background:#FEF2F2;color:#DC2626}

.hint{font-size:11px;color:var(--slate-light);margin-top:3px;line-height:1.5}
.hint a{color:var(--indigo)}

.btn-save{width:100%;padding:11px;background:#2563EB;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-save:hover{background:#1D4ED8}
.btn-save.green{background:#059669}
.btn-save.green:hover{background:#047857}
.btn-save.amber{background:#D97706}
.btn-save.amber:hover{background:#B45309}

.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#065F46;margin-bottom:20px}
.alert-e{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;font-size:13px;color:#991B1B;margin-bottom:20px}

.tip-box{background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:16px}
.tip-box-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--slate-light);margin-bottom:6px}
.tip-box ol{margin:0;padding-left:16px;font-size:12px;color:var(--midnight);line-height:1.8}

.divider{border:none;border-top:1px solid var(--border);margin:14px 0}

@media (max-width: 1024px) {
    .pg-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 640px) {
    .pg-intro { padding: 14px 16px; }
    .pg-card-body { padding: 14px 16px; }
}
</style>
@endpush

@section('content')

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<div class="pg-intro">
    <div class="pg-intro-title">💳 Online Payment Gateway Setup</div>
    <div class="pg-intro-sub">
        Configure payment providers used to collect subscription fees from schools.<br>
        A gateway becomes available to tenants once its credentials are filled in and saved.<br>
        Leave secret key blank to keep the existing saved value.
    </div>
</div>

<form method="POST" action="{{ route('super.payment-gateways.save') }}">
@csrf
@php
    $v = fn(string $key, $default = '') => $settings[$key] ?? $default;
    $isSet = fn(string $key) => !empty($settings[$key] ?? '');
@endphp

<div class="pg-grid">

{{-- ── PAYSTACK ──────────────────────────────────────────────────── --}}
<div class="pg-card">
    <div class="pg-card-head">
        <div class="pg-logo pg-logo-ps">P</div>
        <div>
            <div class="pg-card-title">
                Paystack
                @if($isSet('paystack_public_key') && $isSet('paystack_secret_key'))
                    <span class="status-pill status-configured">✓ Configured</span>
                @else
                    <span class="status-pill status-missing">Not set</span>
                @endif
            </div>
            <div class="pg-card-sub">Recommended for Nigeria — Card & bank transfer</div>
        </div>
    </div>
    <div class="pg-card-body">
        <div class="tip-box">
            <div class="tip-box-title">How to get keys</div>
            <ol>
                <li>Log in to <a href="https://dashboard.paystack.com" target="_blank">dashboard.paystack.com</a></li>
                <li>Go to <strong>Settings → API Keys &amp; Webhooks</strong></li>
                <li>Copy your Public Key and Secret Key</li>
            </ol>
        </div>

        <div class="fg">
            <label class="fl">Public Key</label>
            <input type="text" name="settings[paystack_public_key]" class="fc"
                   value="{{ $v('paystack_public_key') }}" placeholder="pk_test_... or pk_live_...">
            <div class="hint">Starts with <code>pk_test_</code> (sandbox) or <code>pk_live_</code> (production)</div>
        </div>

        <div class="fg">
            <label class="fl">Secret Key</label>
            <div class="secret-row">
                <input type="password" name="settings[paystack_secret_key]" class="fc" id="ps_secret"
                       placeholder="{{ $isSet('paystack_secret_key') ? '••••••••••• (saved — leave blank to keep)' : 'sk_test_...' }}"
                       autocomplete="new-password">
                <button type="button" class="eye-btn" onclick="toggleSecret('ps_secret')">👁</button>
            </div>
        </div>

        <div class="mode-row">
            <div>
                <div class="mode-label">Live Mode</div>
                <div class="mode-sub">Enable only after testing with sandbox keys</div>
            </div>
            <label class="toggle-wrap">
                <input type="checkbox" name="settings[paystack_is_live]" value="1"
                       {{ $v('paystack_is_live') == '1' ? 'checked' : '' }}>
                <span class="toggle-slider"></span>
            </label>
        </div>

        <hr class="divider">
        <button type="submit" name="gateway_save" value="paystack" class="btn-save green">💾 Save Paystack Settings</button>
    </div>
</div>

{{-- ── MONNIFY ───────────────────────────────────────────────────── --}}
<div class="pg-card">
    <div class="pg-card-head">
        <div class="pg-logo pg-logo-mn">M</div>
        <div>
            <div class="pg-card-title">
                Monnify
                @if($isSet('monnify_api_key') && $isSet('monnify_secret_key') && $isSet('monnify_contract_code'))
                    <span class="status-pill status-configured">✓ Configured</span>
                @else
                    <span class="status-pill status-missing">Not set</span>
                @endif
            </div>
            <div class="pg-card-sub">Card, bank transfer &amp; USSD (TeamApt/Moniepoint)</div>
        </div>
    </div>
    <div class="pg-card-body">
        <div class="tip-box">
            <div class="tip-box-title">How to get keys</div>
            <ol>
                <li>Log in to <a href="https://app.monnify.com" target="_blank">app.monnify.com</a></li>
                <li>Go to <strong>API Keys</strong> — copy your API Key and Secret Key</li>
                <li>Go to <strong>Contracts</strong> — copy the <strong>Contract Code</strong></li>
            </ol>
        </div>

        <div class="fg">
            <label class="fl">API Key</label>
            <input type="text" name="settings[monnify_api_key]" class="fc"
                   value="{{ $v('monnify_api_key') }}" placeholder="MK_TEST_... or MK_PROD_...">
        </div>

        <div class="fg">
            <label class="fl">Secret Key</label>
            <div class="secret-row">
                <input type="password" name="settings[monnify_secret_key]" class="fc" id="mn_secret"
                       placeholder="{{ $isSet('monnify_secret_key') ? '••••••••••• (saved — leave blank to keep)' : 'Secret key' }}"
                       autocomplete="new-password">
                <button type="button" class="eye-btn" onclick="toggleSecret('mn_secret')">👁</button>
            </div>
        </div>

        <div class="fg">
            <label class="fl">Contract Code</label>
            <input type="text" name="settings[monnify_contract_code]" class="fc"
                   value="{{ $v('monnify_contract_code') }}" placeholder="e.g. 5585729578">
            <div class="hint">Found in Monnify dashboard → Contracts → Contract Code</div>
        </div>

        <div class="mode-row">
            <div>
                <div class="mode-label">Live Mode</div>
                <div class="mode-sub">Switches from sandbox.monnify.com to api.monnify.com</div>
            </div>
            <label class="toggle-wrap">
                <input type="checkbox" name="settings[monnify_is_live]" value="1"
                       {{ $v('monnify_is_live') == '1' ? 'checked' : '' }}>
                <span class="toggle-slider"></span>
            </label>
        </div>

        <hr class="divider">
        <button type="submit" name="gateway_save" value="monnify" class="btn-save amber">💾 Save Monnify Settings</button>
    </div>
</div>

{{-- ── FLUTTERWAVE ───────────────────────────────────────────────── --}}
<div class="pg-card">
    <div class="pg-card-head">
        <div class="pg-logo pg-logo-fw">F</div>
        <div>
            <div class="pg-card-title">
                Flutterwave
                @if($isSet('flutterwave_public_key') && $isSet('flutterwave_secret_key'))
                    <span class="status-pill status-configured">✓ Configured</span>
                @else
                    <span class="status-pill status-missing">Not set</span>
                @endif
            </div>
            <div class="pg-card-sub">Pan-Africa — Card, mobile money, bank transfer</div>
        </div>
    </div>
    <div class="pg-card-body">
        <div class="tip-box">
            <div class="tip-box-title">How to get keys</div>
            <ol>
                <li>Log in to <a href="https://app.flutterwave.com" target="_blank">app.flutterwave.com</a></li>
                <li>Go to <strong>Settings → API Keys</strong></li>
                <li>Copy your Public Key and Secret Key</li>
            </ol>
        </div>

        <div class="fg">
            <label class="fl">Public Key</label>
            <input type="text" name="settings[flutterwave_public_key]" class="fc"
                   value="{{ $v('flutterwave_public_key') }}" placeholder="FLWPUBK_TEST-... or FLWPUBK-...">
        </div>

        <div class="fg">
            <label class="fl">Secret Key</label>
            <div class="secret-row">
                <input type="password" name="settings[flutterwave_secret_key]" class="fc" id="fw_secret"
                       placeholder="{{ $isSet('flutterwave_secret_key') ? '••••••••••• (saved — leave blank to keep)' : 'FLWSECK_TEST-...' }}"
                       autocomplete="new-password">
                <button type="button" class="eye-btn" onclick="toggleSecret('fw_secret')">👁</button>
            </div>
        </div>

        <div class="mode-row">
            <div>
                <div class="mode-label">Live Mode</div>
                <div class="mode-sub">Use production keys only after successful sandbox testing</div>
            </div>
            <label class="toggle-wrap">
                <input type="checkbox" name="settings[flutterwave_is_live]" value="1"
                       {{ $v('flutterwave_is_live') == '1' ? 'checked' : '' }}>
                <span class="toggle-slider"></span>
            </label>
        </div>

        <hr class="divider">
        <button type="submit" name="gateway_save" value="flutterwave" class="btn-save">💾 Save Flutterwave Settings</button>
    </div>
</div>

</div>{{-- end .pg-grid --}}
</form>

@push('scripts')
<script>
function toggleSecret(inputId) {
    var el = document.getElementById(inputId);
    el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
@endpush
@endsection
