@extends('layouts.super')
@section('title','Agent Programme Settings')
@section('page-title','Agent Programme Settings')

@push('styles')
<style>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.page-header h1{font-size:20px;font-weight:700;color:var(--midnight);letter-spacing:-.02em}
.tabs{display:flex;gap:4px;margin-bottom:20px}
.tab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms}
.tab:hover,.tab.active{background:var(--indigo);border-color:var(--indigo);color:white}
.settings-wrap{max-width:none}
.settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;align-items:start}
.settings-form{display:contents}
.card{background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden}
.card-header{padding:13px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;gap:8px}
.card-title{font-size:13px;font-weight:700;color:var(--midnight)}
.card-body{padding:20px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
.form-control{width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;transition:border-color 200ms}
.form-control:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,.1);background:white}
.hint{font-size:11px;color:var(--slate-light);margin-top:4px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}.btn-primary:hover{background:#1D4ED8}
.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px}
.link-box{background:var(--indigo-bg);border:1px solid #BFDBFE;border-radius:10px;padding:16px}
.link-url{font-family:monospace;font-size:12px;color:var(--indigo);background:white;border:1px solid #BFDBFE;border-radius:6px;padding:9px 12px;margin-top:8px;word-break:break-all}
.bonus-box{background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:16px;margin-bottom:16px}
.bonus-title{font-size:13px;font-weight:700;color:#92400E;margin-bottom:12px}
.full-span{grid-column:1 / -1}
@media(max-width:900px){.settings-grid{grid-template-columns:1fr}}
@media(max-width:600px){.two{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>Agent Programme Settings</h1>
</div>

<div class="tabs">
    <a href="{{ route('super.agents.index') }}" class="tab">👥 Agents</a>
    <a href="{{ route('super.agents.settings') }}" class="tab active">⚙️ Programme Settings</a>
</div>

@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif

<div class="settings-wrap">
    <div class="settings-grid">
    {{-- Onboarding Link --}}
    <div class="card full-span">
        <div class="card-header"><span class="card-title">🔗 Agent Onboarding Link</span></div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--slate);margin-bottom:14px;line-height:1.6">
                Share this public URL with prospective agents. They complete a registration form and are added as a pending agent awaiting your approval.
            </p>
            <div class="link-box">
                <div style="font-size:12px;font-weight:700;color:var(--indigo)">General Registration Link</div>
                <div class="link-url" id="genLink">{{ url('/agent/register') }}</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:10px">
                    <button class="btn btn-primary" style="padding:7px 16px;font-size:12px" onclick="copyEl('genLink')">📋 Copy Link</button>
                    <span id="copyMsg" style="font-size:11px;color:var(--emerald);display:none">✓ Copied!</span>
                </div>
            </div>
            <div style="font-size:12px;color:var(--slate-light);margin-top:10px;line-height:1.6">
                💡 Each agent also has a unique personal link with their referral code. Find it on the Agents list.
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('super.agents.settings.save') }}" class="settings-form">
        @csrf

        {{-- Programme Identity --}}
        <div class="card">
            <div class="card-header"><span class="card-title">📋 Programme Identity</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Programme Name</label>
                    <input name="programme_name" class="form-control"
                           value="{{ old('programme_name', $settings['programme_name']) }}"
                           placeholder="e.g. EduCore Referral Programme">
                </div>
                <div class="form-group">
                    <label class="form-label">Programme Description</label>
                    <textarea name="programme_description" class="form-control" rows="2">{{ old('programme_description', $settings['programme_description']) }}</textarea>
                    <div class="hint">Shown to agents on their portal dashboard.</div>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Terms & Conditions</label>
                    <textarea name="terms_text" class="form-control" rows="4"
                              placeholder="Terms agents must accept when joining...">{{ old('terms_text', $settings['terms_text']) }}</textarea>
                    <div class="hint">Displayed on the agent registration page.</div>
                </div>
            </div>
        </div>

        {{-- Commission --}}
        <div class="card">
            <div class="card-header"><span class="card-title">💰 Commission Settings</span></div>
            <div class="card-body">
                <div class="two">
                    <div class="form-group">
                        <label class="form-label">Default Commission Rate (%)</label>
                        <input name="default_commission_rate" type="number" class="form-control"
                               min="1" max="50" step="0.5"
                               value="{{ old('default_commission_rate', $settings['default_commission_rate']) }}">
                        <div class="hint">Applied to new agents unless individually overridden.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Minimum Payout (₦)</label>
                        <input name="min_payout" type="number" class="form-control" min="0"
                               value="{{ old('min_payout', $settings['min_payout']) }}">
                        <div class="hint">Agent must earn at least this before payout is released.</div>
                    </div>
                </div>

                <div class="bonus-box">
                    <div class="bonus-title">🏆 Loyalty Bonus Rate (Optional)</div>
                    <p style="font-size:12px;color:#92400E;margin-bottom:14px;line-height:1.6">
                        After an agent reaches a set number of successful referrals, their commission rate is
                        automatically increased for all future payments — rewarding high-performing agents.
                    </p>
                    <div class="two">
                        <div>
                            <label class="form-label">Bonus After N Schools</label>
                            <input name="bonus_threshold" type="number" class="form-control" min="0"
                                   value="{{ old('bonus_threshold', $settings['bonus_threshold']) }}" placeholder="e.g. 5">
                            <div class="hint">Number of approved school referrals that trigger the bonus rate.</div>
                        </div>
                        <div>
                            <label class="form-label">Bonus Commission Rate (%)</label>
                            <input name="bonus_amount" type="number" step="0.5" class="form-control" min="0" max="50"
                                   value="{{ old('bonus_amount', $settings['bonus_amount']) }}" placeholder="e.g. 15">
                            <div class="hint">New % commission rate applied to all future payments once threshold is reached.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Cycle --}}
        <div class="card">
            <div class="card-header"><span class="card-title">🗓 Payment Cycle & Approvals</span></div>
            <div class="card-body">
                <div class="two">
                    <div class="form-group">
                        <label class="form-label">Payment Cycle</label>
                        <select name="payment_cycle" class="form-control">
                            @foreach(['weekly'=>'Weekly','monthly'=>'Monthly (recommended)','manual'=>'Manual — pay on demand'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('payment_cycle', $settings['payment_cycle']) === $val ? 'selected':'' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <div class="hint">How often commissions are processed and paid out.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Commission Approval</label>
                        <select name="auto_approve" class="form-control">
                            <option value="0" {{ !old('auto_approve',$settings['auto_approve']) ? 'selected':'' }}>Manual — review each commission</option>
                            <option value="1" {{ old('auto_approve',$settings['auto_approve']) ? 'selected':'' }}>Auto-approve on subscription payment</option>
                        </select>
                        <div class="hint">Auto-approve credits agent immediately on webhook.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card full-span">
            <div class="card-body" style="display:flex;gap:10px;justify-content:flex-start;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary">💾 Save Settings</button>
                <a href="{{ route('super.agents.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyEl(id) {
    const txt = document.getElementById(id).textContent.trim();
    navigator.clipboard.writeText(txt).then(() => {
        const m = document.getElementById('copyMsg');
        m.style.display = 'inline';
        setTimeout(() => m.style.display='none', 2000);
    });
}
</script>
@endpush
