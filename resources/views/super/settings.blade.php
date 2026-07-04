@extends('layouts.super')
@section('title', 'Platform Settings')
@section('page-title', 'Platform Settings')

@push('styles')
<style>
    .settings-card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; width:100%; }
    .settings-section { padding:16px 20px; border-bottom:1px solid var(--border); }
    .settings-section:last-child { border-bottom:none; }
    .section-title { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#DC2626; margin-bottom:14px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block; font-size:11px; font-weight:600; color:#64748B; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:5px; }
    .form-control { width:100%; padding:9px 12px; font-size:13px; font-family:inherit; border:1px solid var(--border); border-radius:8px; background:#F8FAFC; outline:none; transition:border-color 200ms; }
    .form-control:focus { border-color:#DC2626; background:white; }
    .form-hint { font-size:11px; color:#94A3B8; margin-top:3px; }
    .alert-success { background:#ECFDF5; border:1px solid #A7F3D0; border-radius:8px; padding:12px 16px; font-size:13px; color:#059669; margin-bottom:16px; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; font-size:13px; font-weight:600; font-family:inherit; border-radius:8px; border:none; cursor:pointer; transition:all 150ms; }
    .btn-primary { background:#DC2626; color:white; }
    .btn-primary:hover { background:#B91C1C; }
    .empty-state { padding:20px; text-align:center; color:#94A3B8; font-size:13px; }

    /* toggle switch */
    .toggle-wrap { position:relative; width:44px; height:24px; flex-shrink:0; display:inline-block; }
    .toggle-wrap input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; cursor:pointer; inset:0; border-radius:24px; background:#CBD5E1; transition:200ms; }
    .toggle-wrap input:checked + .toggle-slider { background:#DC2626; }
    .toggle-slider:before { content:''; position:absolute; width:18px; height:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:200ms; }
    .toggle-wrap input:checked + .toggle-slider:before { transform:translateX(20px); }

    @media(max-width:640px) {
        .settings-section { padding:14px 16px; }
    }
</style>
@endpush

@section('content')

@if(session('success'))<div class="alert-success">&#10003; {{ session('success') }}</div>@endif

@php
    // Pre-compute values safely — avoids nullsafe ?-> in blade attributes
    $val = function(string $key, $default = '') use ($settings) {
        $item = $settings[$key] ?? null;
        if ($item === null) return $default;
        return is_object($item) ? ($item->value ?? $default) : ($item ?? $default);
    };
@endphp

<form method="POST" action="{{ route('super.settings.save') }}">
    @csrf
    <div class="settings-card">

        {{-- General --}}
        <div class="settings-section">
            <div class="section-title">&#9881; General Settings</div>
            <div class="form-group">
                <label class="form-label">Platform Name</label>
                <input type="text" name="settings[platform_name]" class="form-control"
                       value="{{ $val('platform_name', 'EduCore') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Support Email</label>
                <input type="email" name="settings[support_email]" class="form-control"
                       value="{{ $val('support_email') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Support Phone</label>
                <input type="text" name="settings[support_phone]" class="form-control"
                       value="{{ $val('support_phone') }}">
            </div>
        </div>

        {{-- Billing --}}
        <div class="settings-section">
            <div class="section-title">&#128176; Billing Settings</div>
            <div class="form-group">
                <label class="form-label">Free Trial Days</label>
                <input type="number" name="settings[trial_days]" class="form-control"
                       value="{{ $val('trial_days', 30) }}" min="0" max="365">
                <div class="form-hint">Number of free trial days for new schools</div>
            </div>
            <div class="form-group">
                <label class="form-label">Grace Period (days after expiry)</label>
                <input type="number" name="settings[grace_period_days]" class="form-control"
                       value="{{ $val('grace_period_days', 7) }}" min="0" max="90">
                <div class="form-hint">Days schools can still access after subscription expires</div>
            </div>
        </div>

        {{-- SMS --}}
        <div class="settings-section">
            <div class="section-title">&#128241; SMS Gateway Settings</div>
            <div class="form-group">
                <label class="form-label">Default SMS Gateway</label>
                <select name="settings[default_sms_gateway]" class="form-control">
                    <option value="termii" {{ $val('default_sms_gateway','termii') === 'termii' ? 'selected' : '' }}>Termii</option>
                    <option value="africas_talking" {{ $val('default_sms_gateway') === 'africas_talking' ? 'selected' : '' }}>Africa's Talking</option>
                    <option value="twilio" {{ $val('default_sms_gateway') === 'twilio' ? 'selected' : '' }}>Twilio</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">SMS Sender ID</label>
                <input type="text" name="settings[sms_sender_id]" class="form-control"
                       value="{{ $val('sms_sender_id', 'SchoolSMS') }}">
                <div class="form-hint">Displayed name on SMS messages (max 11 characters)</div>
            </div>
        </div>

        {{-- Payment Gateways moved to Super Admin → Payment Gateways --}}
        <div class="settings-section" style="background:#F8FAFC">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-size:13px;font-weight:600;color:#374151">💳 Payment Gateway Settings</div>
                    <div class="form-hint" style="margin-top:3px">Paystack, Monnify and Flutterwave are now managed on a dedicated page.</div>
                </div>
                <a href="{{ route('super.payment-gateways') }}"
                   style="flex-shrink:0;padding:7px 14px;background:#2563EB;color:white;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none">
                    Go to Payment Gateways →
                </a>
            </div>
        </div>

        {{-- Maintenance --}}
        <div class="settings-section">
            <div class="section-title">&#128295; System</div>
            <div class="form-group" style="display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-size:13px;font-weight:600;color:#1E293B">Maintenance Mode</div>
                    <div class="form-hint">Block all tenant access while performing updates</div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="settings[maintenance_mode]" value="1"
                           {{ $val('maintenance_mode') == '1' ? 'checked' : '' }}>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <div class="settings-section">
            <button type="submit" class="btn btn-primary">&#128190; Save Settings</button>
        </div>
    </div>
</form>

@endsection
