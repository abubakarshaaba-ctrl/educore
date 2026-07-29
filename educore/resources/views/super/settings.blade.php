@extends('layouts.super')
@section('title', 'Platform Settings')
@section('page-title', 'Platform Settings')

@push('styles')
<style>
.settings-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:18px;align-items:start}
.settings-grid>form{min-width:0}
.settings-stack{display:grid;gap:18px}
.settings-card{background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;width:100%;box-shadow:0 1px 3px rgba(15,23,42,.04)}
.settings-section{padding:18px 20px;border-bottom:1px solid var(--border)}
.settings-section:last-child{border-bottom:0}
.section-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#B42318;margin-bottom:14px}
.form-group{margin-bottom:14px}
.form-group:last-child{margin-bottom:0}
.form-label{display:block;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.form-control{width:100%;padding:10px 12px;font:13px inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:0;transition:150ms}
.form-control:focus{border-color:#D79A21;background:#fff;box-shadow:0 0 0 3px rgba(215,154,33,.12)}
.form-hint{font-size:11px;color:#94A3B8;margin-top:4px;line-height:1.55}
.operations-hero{background:linear-gradient(135deg,#071E45,#103C72);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:18px}
.operations-hero h1{font-size:19px;margin-bottom:5px}
.operations-hero p{font-size:12px;line-height:1.6;color:#CBD5E1;max-width:720px}
.operations-badge{white-space:nowrap;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);padding:8px 12px;border-radius:999px;font-size:11px;font-weight:800}
.pricing-summary{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.pricing-metric{border:1px solid #F2D58E;background:#FEF9EC;border-radius:10px;padding:13px}
.pricing-metric strong{display:block;font-size:19px;color:#8A5A00}
.pricing-metric span{display:block;margin-top:3px;font-size:10px;color:#866C38;line-height:1.4;text-transform:uppercase;letter-spacing:.04em}
.settings-link{display:inline-flex;align-items:center;justify-content:center;padding:9px 13px;border-radius:8px;text-decoration:none;background:#071E45;color:#fff;font-size:11px;font-weight:800}
.btn-save{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 18px;background:#D79A21;color:#071E45;border:0;border-radius:8px;font:800 13px inherit;cursor:pointer}
.toggle-row{display:flex;align-items:center;justify-content:space-between;gap:16px}
.toggle-wrap{position:relative;width:44px;height:24px;flex:none}
.toggle-wrap input[type=checkbox]{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;border-radius:24px;background:#CBD5E1;transition:200ms}
.toggle-wrap input:checked+.toggle-slider{background:#B42318}
.toggle-slider:before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:200ms}
.toggle-wrap input:checked+.toggle-slider:before{transform:translateX(20px)}
.operational-row{font-size:12px;display:flex;justify-content:space-between;gap:14px;padding:8px 0;border-bottom:1px solid #EEF2F6}
.operational-row:last-child{border-bottom:0}
@media(max-width:960px){.settings-grid{grid-template-columns:1fr}}
@media(max-width:640px){.settings-section{padding:15px}.operations-hero{padding:16px;align-items:flex-start;flex-direction:column}.pricing-summary{grid-template-columns:1fr}.toggle-row{align-items:flex-start}}
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

@php
    $val = function (string $key, $default = '') use ($settings) {
        $item = $settings[$key] ?? null;
        return $item === null ? $default : (is_object($item) ? ($item->value ?? $default) : $item);
    };
@endphp

<div class="operations-hero">
    <div>
        <h1>Platform Operations Control</h1>
        <p>Manage EduCore’s public identity, support channels, billing access window, notifications and maintenance state.</p>
    </div>
    <div class="operations-badge">RBAC · Super Admin only</div>
</div>

<div class="settings-grid">
    <form method="POST" action="{{ route('super.settings.save') }}">
    @csrf
    <div class="settings-card">
        <div class="settings-section">
            <div class="section-title">General and contact information</div>
            <div class="form-group">
                <label class="form-label">Platform Name</label>
                <input class="form-control" type="text" name="settings[platform_name]" value="{{ old('settings.platform_name', $val('platform_name', 'EduCore')) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Support Email</label>
                <input class="form-control" type="email" name="settings[support_email]" value="{{ old('settings.support_email', $val('support_email', 'support@educoreng.online')) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Support Phone</label>
                <input class="form-control" type="text" name="settings[support_phone]" value="{{ old('settings.support_phone', $val('support_phone', '07065595768')) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Support WhatsApp</label>
                <input class="form-control" type="text" name="settings[support_whatsapp]" value="{{ old('settings.support_whatsapp', $val('support_whatsapp', '+2347065595768')) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Website</label>
                <input class="form-control" type="url" name="settings[support_website]" value="{{ old('settings.support_website', $val('support_website', 'https://educoreng.online')) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Office Address</label>
                <input class="form-control" type="text" name="settings[office_address]" value="{{ old('settings.office_address', $val('office_address', 'Abuja, FCT, Nigeria')) }}" required>
            </div>
        </div>

        <div class="settings-section">
            <div class="section-title">Billing access</div>
            <div class="form-group">
                <label class="form-label">Initial Subscription Window (days)</label>
                <input class="form-control" type="number" name="settings[trial_days]" min="0" max="365" value="{{ old('settings.trial_days', $val('trial_days', 30)) }}" required>
                <div class="form-hint">Initial account window for newly provisioned schools. The free plan remains free while enrollment is 50 students or fewer.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Grace Period (days)</label>
                <input class="form-control" type="number" name="settings[grace_period_days]" min="0" max="90" value="{{ old('settings.grace_period_days', $val('grace_period_days', 7)) }}" required>
                <div class="form-hint">Temporary access period for paid schools after subscription expiry.</div>
            </div>
        </div>

        <div class="settings-section">
            <div class="section-title">Bank transfer collection account</div>
            <div class="form-hint" style="margin-bottom:12px">When all three fields are supplied, schools can choose bank transfer during subscription checkout. A submitted transfer remains pending until a Platform Super Admin verifies and marks the invoice paid.</div>
            <div class="form-group">
                <label class="form-label">Bank Name</label>
                <input class="form-control" type="text" name="settings[bank_transfer_bank_name]" value="{{ old('settings.bank_transfer_bank_name', $val('bank_transfer_bank_name')) }}" placeholder="e.g. First Bank">
            </div>
            <div class="form-group">
                <label class="form-label">Account Name</label>
                <input class="form-control" type="text" name="settings[bank_transfer_account_name]" value="{{ old('settings.bank_transfer_account_name', $val('bank_transfer_account_name')) }}" placeholder="EduCore Education Technology">
            </div>
            <div class="form-group">
                <label class="form-label">Account Number</label>
                <input class="form-control" type="text" inputmode="numeric" name="settings[bank_transfer_account_number]" value="{{ old('settings.bank_transfer_account_number', $val('bank_transfer_account_number')) }}" maxlength="30" placeholder="Collection account number">
            </div>
        </div>

        <div class="settings-section">
            <div class="section-title">Notifications</div>
            <div class="form-group">
                <label class="form-label">Default SMS Gateway</label>
                <select class="form-control" name="settings[default_sms_gateway]" required>
                    @foreach(['termii' => 'Termii', 'africas_talking' => "Africa's Talking", 'twilio' => 'Twilio'] as $key => $label)
                        <option value="{{ $key }}" @selected(old('settings.default_sms_gateway', $val('default_sms_gateway', 'termii')) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">SMS Sender ID</label>
                <input class="form-control" type="text" name="settings[sms_sender_id]" minlength="3" maxlength="11" value="{{ old('settings.sms_sender_id', $val('sms_sender_id', 'EduCore')) }}" required>
                <div class="form-hint">The recognizable sender name shown on supported SMS networks.</div>
            </div>
        </div>

        <div class="settings-section">
            <div class="section-title">System availability</div>
            <div class="toggle-row">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#1E293B">Maintenance Mode</div>
                    <div class="form-hint">Blocks tenant access during planned maintenance. The Platform Super Admin remains available.</div>
                </div>
                <label class="toggle-wrap">
                    <input type="hidden" name="settings[maintenance_mode]" value="0">
                    <input type="checkbox" name="settings[maintenance_mode]" value="1" @checked(old('settings.maintenance_mode', $val('maintenance_mode', '0')) == '1')>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <div class="settings-section">
            <button class="btn-save" type="submit">Save Operations Settings</button>
        </div>
    </div>
    </form>

    <div class="settings-stack">
        <div class="settings-card">
            <div class="settings-section">
                <div class="section-title">Current Commercial Model</div>
                <div class="pricing-summary">
                    <div class="pricing-metric"><strong>50</strong><span>Students included free</span></div>
                    <div class="pricing-metric"><strong>&#8358;300</strong><span>Per active student / term</span></div>
                </div>
                <div class="form-hint" style="margin-top:12px">All features are included. There are no percentage discounts and no legacy feature tiers.</div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-section">
                <div class="section-title">Payments</div>
                <div style="font-size:13px;font-weight:700;color:#1E293B;margin-bottom:5px">Encrypted gateway credentials</div>
                <div class="form-hint" style="margin-bottom:12px">Manage Paystack, Monnify and Flutterwave credentials on the dedicated secure page.</div>
                <a class="settings-link" href="{{ route('super.payment-gateways') }}">Manage Payment Gateways</a>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-section">
                <div class="section-title">Operational Defaults</div>
                <div class="operational-row"><span>Currency</span><strong>NGN</strong></div>
                <div class="operational-row"><span>Timezone</span><strong>Africa/Lagos</strong></div>
                <div class="operational-row"><span>Access control</span><strong>Strict RBAC</strong></div>
                <div class="operational-row"><span>Mobile platform</span><strong>Android</strong></div>
            </div>
        </div>

        <div class="settings-card">
            <form method="POST" action="{{ route('super.password.update') }}">
                @csrf
                <div class="settings-section">
                    <div class="section-title">Account Security</div>
                    @if(session('success_pw'))<div class="alert-success">{{ session('success_pw') }}</div>@endif
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input class="form-control" type="password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input class="form-control" type="password" name="password" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input class="form-control" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="settings-section">
                    <button class="btn-save" type="submit">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
