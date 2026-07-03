@extends('layouts.super')
@section('title', 'Reseller Agents')
@section('page-title', 'Reseller Agents')

@php
    $openAddAgentModal = $errors->hasAny(['name', 'email', 'phone', 'state', 'commission_rate']);
    $openBroadcastModal = $errors->hasAny(['subject', 'body', 'audience']);
@endphp

@push('styles')
<style>
.page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px}
.page-header h1{font-size:20px;font-weight:700;color:var(--midnight);letter-spacing:-.02em}
.agent-actions{display:flex;gap:8px;align-items:center;flex-wrap:nowrap;overflow-x:auto;padding-bottom:2px;margin-bottom:16px}
.agent-actions > *{flex:0 0 auto;white-space:nowrap}
.agent-action{display:inline-flex;align-items:center;justify-content:center;padding:9px 14px;border-radius:8px;border:1.5px solid var(--border);background:white;color:var(--midnight);font-size:13px;font-weight:600;text-decoration:none;transition:all 150ms}
.agent-action:hover{border-color:var(--indigo);color:var(--indigo)}
.agent-action.active{background:var(--indigo);border-color:var(--indigo);color:white}
.stats-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px}
.stat-card{background:white;border:1px solid var(--border);border-radius:10px;padding:16px 18px}
.stat-val{font-size:22px;font-weight:800;color:var(--midnight);word-break:break-word}
.stat-lbl{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:3px}
.page-grid{display:block}
.card{background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden}
.card-header{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;gap:10px}
.card-title{font-size:13px;font-weight:700;color:var(--midnight)}
.card-body{padding:18px}
table{width:100%;border-collapse:collapse;table-layout:fixed}
thead th{font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle;overflow-wrap:anywhere}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px}
.badge-on{background:#ECFDF5;color:var(--emerald)}
.badge-off{background:#FEF2F2;color:var(--crimson)}
.badge-pending{background:#FFFBEB;color:var(--amber)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}.btn-primary:hover{background:#1D4ED8}
.btn-sm{padding:5px 10px;font-size:11px}
.btn-success{background:var(--emerald);color:white}
.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.form-group{margin-bottom:14px}
.form-label{display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.form-control{width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;transition:border-color 200ms}
.form-control:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,.1);background:white}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px}
.link-box{background:var(--indigo-bg);border:1.5px solid #BFDBFE;border-radius:10px;padding:14px 16px;margin-bottom:14px}
.link-code{font-family:monospace;font-size:12px;color:var(--indigo);background:white;border:1px solid #BFDBFE;border-radius:6px;padding:8px 12px;word-break:break-all;margin-top:6px}
.ref-code{font-family:monospace;font-size:11px;font-weight:700;letter-spacing:.08em;background:#F1F5F9;padding:3px 8px;border-radius:5px;display:inline-block;max-width:100%}
.ref-link{display:block;margin-top:5px;font-size:11px;color:var(--indigo);text-decoration:none;word-break:break-all}
.action-group{display:flex;gap:5px;flex-wrap:wrap}
.muted{font-size:11px;color:var(--slate-light);line-height:1.5}
.agent-cell{display:flex;flex-direction:column;gap:4px}
.agent-text{font-size:11px;color:var(--slate-light);line-height:1.45}
.center-cell{text-align:center}
.summary-list{display:grid;gap:0}
.summary-row{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid var(--border);font-size:12px}
.summary-row:last-child{border-bottom:none}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:18px}
.modal-panel{background:white;border-radius:12px;padding:24px;width:min(560px,100%);box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:calc(100vh - 36px);overflow:auto}
.modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px}
.modal-title{font-size:16px;font-weight:800;color:var(--midnight)}
.modal-sub{font-size:13px;color:var(--slate-light);margin-top:4px;line-height:1.6}
@media(max-width:1100px){
    .stats-row{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:720px){
    .stats-row{grid-template-columns:1fr}
    .page-header{flex-direction:column}
}
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Reseller Agents</h1>
        <div class="muted" style="margin-top:4px">Manage agent accounts, referral links, commissions, and broadcasts from one place.</div>
    </div>
</div>

<div class="agent-actions" aria-label="Agent page actions">
    <a href="{{ route('super.agents.index') }}" class="agent-action active">Agents</a>
    <button type="button" class="agent-action" onclick="openAddAgentModal()">Add Agents</button>
    <a href="{{ route('super.agents.settings') }}" class="agent-action">Programme Settings</a>
    <button type="button" class="agent-action" onclick="openBroadcastModal()">Broadcast Message to Agents</button>
    <button type="button" class="agent-action" onclick="openSummaryModal()">Programme Summary</button>
</div>

@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-val" style="color:var(--indigo)">{{ $stats['total_agents'] }}</div>
        <div class="stat-lbl">Total Agents</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color:var(--emerald)">{{ $stats['active_agents'] }}</div>
        <div class="stat-lbl">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-val">₦{{ number_format($stats['total_commissions']) }}</div>
        <div class="stat-lbl">Total Commissions</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color:var(--crimson)">₦{{ number_format($stats['unpaid']) }}</div>
        <div class="stat-lbl">Unpaid Balance</div>
    </div>
</div>

<div class="link-box">
    <div style="font-size:12px;font-weight:700;color:var(--indigo);margin-bottom:4px">Agent onboarding link</div>
    <div class="link-code" id="onboardUrl">{{ url('/agent/register') }}</div>
    <div style="display:flex;align-items:center;gap:8px;margin-top:8px;flex-wrap:wrap">
        <button class="btn btn-primary btn-sm" type="button" onclick="copyText('onboardUrl', 'copiedMsg')">Copy link</button>
        <span id="copiedMsg" style="font-size:11px;color:var(--emerald);display:none">Copied</span>
        <span class="muted">Default rate: <strong>{{ $settings['default_commission_rate'] }}%</strong> · Cycle: <strong>{{ ucfirst($settings['payment_cycle']) }}</strong> · Min payout: <strong>₦{{ number_format($settings['min_payout']) }}</strong></span>
    </div>
</div>

<div class="page-grid">
    <div class="card">
        <div class="card-header">
            <span class="card-title">All Agents ({{ $agents->total() }})</span>
        </div>
        <div style="overflow:auto">
            <table>
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Referral</th>
                        <th>Rate</th>
                        <th>Schools</th>
                        <th>Earned</th>
                        <th>Unpaid</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($agents as $agent)
                <tr>
                    <td style="width:220px">
                        <div class="agent-cell">
                            <div style="font-weight:600;color:var(--midnight)">{{ $agent->name }}</div>
                            <div class="agent-text">{{ $agent->email }}</div>
                            @if($agent->state)<div class="agent-text">📍 {{ $agent->state }}</div>@endif
                        </div>
                    </td>
                    <td style="width:260px">
                        <div class="ref-code">{{ $agent->referral_code }}</div>
                        <a href="{{ $agent->referralLink() }}" target="_blank" rel="noopener" class="ref-link">{{ $agent->referralLink() }}</a>
                        <button type="button" class="btn btn-ghost btn-sm" style="margin-top:6px" onclick='copyTextFromValue(@json($agent->referralLink()), "linkCopied{{ $agent->id }}")'>Copy referral link</button>
                        <span id="linkCopied{{ $agent->id }}" style="font-size:11px;color:var(--emerald);display:none;margin-left:6px">Copied</span>
                    </td>
                    <td class="center-cell" style="font-weight:600;color:var(--emerald)">{{ $agent->commission_rate }}%</td>
                    <td class="center-cell" style="font-weight:600">{{ $agent->referrals_count }}</td>
                    <td class="center-cell">₦{{ number_format($agent->total_earned) }}</td>
                    <td class="center-cell" style="font-weight:600;color:{{ $agent->unpaidBalance() > 0 ? 'var(--crimson)' : 'var(--slate-light)' }}">
                        ₦{{ number_format($agent->unpaidBalance()) }}
                    </td>
                    <td class="center-cell">
                        @if(!$agent->password)
                            <span class="badge badge-pending">Pending</span>
                        @elseif($agent->is_active)
                            <span class="badge badge-on">Active</span>
                        @else
                            <span class="badge badge-off">Inactive</span>
                        @endif
                    </td>
                    <td style="width:180px" class="center-cell">
                        <div class="action-group">
                            <a href="{{ route('super.agents.show', $agent) }}" class="btn btn-ghost btn-sm">View</a>
                            @if(!$agent->password)
                                <button type="button" class="btn btn-success btn-sm"
                                        onclick='activateAgent({{ $agent->id }}, @json($agent->name))'>Activate</button>
                            @else
                                <form method="POST" action="{{ route('super.agents.toggle', $agent) }}" style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm {{ $agent->is_active ? 'btn-danger' : 'btn-success' }}">
                                        {{ $agent->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:50px;color:var(--slate-light)">
                        <div style="font-size:32px;margin-bottom:10px">Agents</div>
                        No agents yet. Use Add Agent or share the onboarding link above.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px">{{ $agents->links() }}</div>
    </div>
</div>

<div id="agentModal" class="modal" style="display:{{ $openAddAgentModal ? 'flex' : 'none' }}">
    <div class="modal-panel">
        <div class="modal-head">
            <div>
                <div class="modal-title">Add Agent</div>
                <div class="modal-sub">Create a new agent account without leaving the page.</div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeAddAgentModal()">Close</button>
        </div>

        <form method="POST" action="{{ route('super.agents.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="Agent full name">
            </div>
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" required value="{{ old('email') }}" placeholder="agent@email.com">
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="08012345678">
            </div>
            <div class="form-group">
                <label class="form-label">State / Location</label>
                <input type="text" name="state" class="form-control" value="{{ old('state') }}" placeholder="Lagos, Abuja, Kano...">
            </div>
            <div class="form-group">
                <label class="form-label">Commission Rate (%)</label>
                <input type="number" name="commission_rate" class="form-control" value="{{ old('commission_rate', $settings['default_commission_rate']) }}" min="1" max="50" step="0.5">
                <div class="muted" style="margin-top:4px">Default: {{ $settings['default_commission_rate'] }}%. Adjust per agent if needed.</div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-ghost" onclick="closeAddAgentModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Agent Account</button>
            </div>
        </form>
    </div>
</div>

<div id="broadcastModal" class="modal" style="display:{{ $openBroadcastModal ? 'flex' : 'none' }}">
    <div class="modal-panel">
        <div class="modal-head">
            <div>
                <div class="modal-title">Broadcast Message to Agents</div>
                <div class="modal-sub">Send a message to all agents, active agents only, or inactive and pending agents only.</div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeBroadcastModal()">Close</button>
        </div>
        <form method="POST" action="{{ route('super.agents.messages.send') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Subject *</label>
                <input type="text" name="subject" class="form-control" required value="{{ old('subject') }}" placeholder="e.g. New commission rate update">
            </div>
            <div class="form-group">
                <label class="form-label">Audience</label>
                <select name="audience" class="form-control">
                    <option value="all" {{ old('audience') === 'all' ? 'selected' : '' }}>All Agents</option>
                    <option value="active" {{ old('audience') === 'active' ? 'selected' : '' }}>Active Agents Only</option>
                    <option value="inactive" {{ old('audience') === 'inactive' ? 'selected' : '' }}>Inactive / Pending Only</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Message *</label>
                <textarea name="body" class="form-control" rows="5" required placeholder="Type your message to agents...">{{ old('body') }}</textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-ghost" onclick="closeBroadcastModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </div>
        </form>
    </div>
</div>

<div id="summaryModal" class="modal">
    <div class="modal-panel">
        <div class="modal-head">
            <div>
                <div class="modal-title">Programme Summary</div>
                <div class="modal-sub">Key agent-programme settings and payout rules.</div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeSummaryModal()">Close</button>
        </div>
        <div class="card" style="margin:0;border:none;box-shadow:none">
            <div class="card-body" style="padding:0">
                <div style="font-size:13px;font-weight:600;color:var(--midnight);margin-bottom:6px">
                    {{ $settings['programme_name'] }}
                </div>
                <div style="font-size:12px;color:var(--slate-light);margin-bottom:12px;line-height:1.6">
                    {{ $settings['programme_description'] }}
                </div>
                <div class="summary-list">
                    @foreach([
                        ['Default Rate', $settings['default_commission_rate'].'%'],
                        ['Bonus', $settings['bonus_threshold'].' referrals → ₦'.number_format($settings['bonus_amount'])],
                        ['Auto-Approve', $settings['auto_approve'] ? 'Yes' : 'Manual review'],
                        ['Payment Cycle', ucfirst($settings['payment_cycle'])],
                        ['Min Payout', '₦'.number_format($settings['min_payout'])],
                    ] as [$lbl, $val])
                    <div class="summary-row">
                        <span style="color:var(--slate)">{{ $lbl }}</span>
                        <span style="font-weight:600;color:var(--midnight);text-align:right">{{ $val }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
            <a href="{{ route('super.agents.settings') }}" class="btn btn-ghost">Edit Settings</a>
        </div>
    </div>
</div>

<div id="activateModal" class="modal">
    <div class="modal-panel" style="width:min(420px,100%)">
        <div class="modal-head">
            <div>
                <div class="modal-title">Activate Agent</div>
                <div class="modal-sub" id="activateName"></div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeActivateModal()">Close</button>
        </div>
        <form method="POST" id="activateForm">
            @csrf
            <div class="form-group">
                <label class="form-label">Set Portal Password *</label>
                <input type="password" name="password" id="activatePwd" required class="form-control" placeholder="Minimum 8 characters">
                <div class="muted" style="margin-top:4px">Share this with the agent for their first login.</div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-ghost" onclick="closeActivateModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Activate and Enable Portal Access</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openAddAgentModal() {
    document.getElementById('agentModal').style.display = 'flex';
}

function closeAddAgentModal() {
    document.getElementById('agentModal').style.display = 'none';
}

function openBroadcastModal() {
    document.getElementById('broadcastModal').style.display = 'flex';
}

function closeBroadcastModal() {
    document.getElementById('broadcastModal').style.display = 'none';
}

function openSummaryModal() {
    document.getElementById('summaryModal').style.display = 'flex';
}

function closeSummaryModal() {
    document.getElementById('summaryModal').style.display = 'none';
}

function closeActivateModal() {
    document.getElementById('activateModal').style.display = 'none';
}

function copyText(sourceId, copiedId) {
    const text = document.getElementById(sourceId).textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        const msg = document.getElementById(copiedId);
        msg.style.display = 'inline';
        setTimeout(() => msg.style.display = 'none', 1800);
    });
}

function copyTextFromValue(value, copiedId) {
    navigator.clipboard.writeText(value).then(() => {
        const msg = document.getElementById(copiedId);
        msg.style.display = 'inline';
        setTimeout(() => msg.style.display = 'none', 1800);
    });
}

function activateAgent(id, name) {
    document.getElementById('activateName').textContent = 'Activating: ' + name;
    document.getElementById('activateForm').action = '/super/agents/' + id + '/activate';
    document.getElementById('activatePwd').value = '';
    document.getElementById('activateModal').style.display = 'flex';
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeAddAgentModal();
        closeBroadcastModal();
        closeSummaryModal();
        closeActivateModal();
    }
});

@if($openAddAgentModal)
document.getElementById('agentModal').style.display = 'flex';
@endif

@if($openBroadcastModal)
document.getElementById('broadcastModal').style.display = 'flex';
@endif
</script>
@endpush
