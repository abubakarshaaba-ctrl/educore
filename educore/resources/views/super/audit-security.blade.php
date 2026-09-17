@extends('layouts.super')

@section('title', 'Audit & Security Centre')

@section('content')
@php
    $tone = fn ($n, $inverse = false) => $inverse ? ($n > 0 ? 'warn' : 'ok') : ($n > 0 ? 'info' : 'muted');
@endphp
<style>
.asc-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.asc-head h1{font-size:22px;margin-bottom:5px}.asc-head p{font-size:12.5px;color:var(--slate)}
.asc-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.asc-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:14px;min-width:0}.asc-label{font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--slate-light)}.asc-value{font-size:24px;font-weight:800;margin-top:6px}.asc-note{font-size:11px;color:var(--slate);margin-top:3px}
.panel{background:#fff;border:1px solid var(--border);border-radius:12px;margin-bottom:16px;overflow:hidden}.panel-head{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px}.panel-head h2{font-size:14px}.panel-body{padding:14px 16px}.filter-grid{display:grid;grid-template-columns:1.1fr 1.1fr .8fr auto;gap:10px;align-items:end}.field label{display:block;font-size:10px;font-weight:700;color:var(--slate);margin-bottom:5px}.field input,.field select{width:100%;border:1px solid var(--border);border-radius:8px;padding:8px 10px;font:inherit;font-size:12px;background:#fff}
.status{display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;font-size:10px;font-weight:700}.status.ok{background:#ECFDF5;color:#047857}.status.warn{background:#FFFBEB;color:#B45309}.status.info{background:#EFF6FF;color:#1D4ED8}.status.muted{background:#F1F5F9;color:#64748B}.action{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:10.5px;overflow-wrap:anywhere}.small{font-size:11px;color:var(--slate)}.empty{padding:24px;text-align:center;color:var(--slate);font-size:12px}.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px}.kv{display:grid;grid-template-columns:1fr auto;gap:8px;padding:9px 0;border-bottom:1px solid #F1F5F9;font-size:12px}.kv:last-child{border-bottom:0}.token-device{max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.asc-table{width:100%;border-collapse:collapse}.asc-table th{font-size:9.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);text-align:left;padding:9px 10px;border-bottom:1px solid var(--border);white-space:nowrap}.asc-table td{font-size:11.5px;padding:10px;border-bottom:1px solid #F1F5F9;vertical-align:top}.asc-table tr:last-child td{border-bottom:0}.pager{padding:12px 16px}.signal-copy{font-size:10.5px;color:var(--slate);margin-top:3px}
@media(max-width:1100px){.asc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.filter-grid{grid-template-columns:1fr 1fr}.two-col{grid-template-columns:1fr}}
@media(max-width:640px){.asc-head{flex-direction:column}.asc-grid{grid-template-columns:1fr}.filter-grid{grid-template-columns:1fr}.panel-head{align-items:flex-start;flex-direction:column}.panel-body{padding:12px}.asc-table{min-width:720px}.token-device{max-width:160px}}
</style>

<div class="asc-head">
    <div>
        <h1>Audit & Security Centre</h1>
        <p>Platform-wide accountability, session visibility and security signals. Signals are indicators for review, not automatic proof of an incident.</p>
    </div>
    <a class="btn btn-ghost" href="{{ route('super.system-health') }}">System Health</a>
</div>

<form class="panel" method="GET">
    <div class="panel-head"><h2>Audit filters</h2><span class="small">Results are read-only</span></div>
    <div class="panel-body filter-grid">
        <div class="field"><label>School</label><select name="tenant_id"><option value="">All schools</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected(($filters['tenant_id'] ?? null) == $tenant->id)>{{ $tenant->name }}</option>@endforeach</select></div>
        <div class="field"><label>Action contains</label><input name="action" value="{{ $filters['action'] ?? '' }}" placeholder="e.g. password, tenant, payment"></div>
        <div class="field"><label>Period</label><select name="period"><option value="24h" @selected($period==='24h')>Last 24 hours</option><option value="7d" @selected($period==='7d')>Last 7 days</option><option value="30d" @selected($period==='30d')>Last 30 days</option><option value="all" @selected($period==='all')>All time</option></select></div>
        <button class="btn btn-primary" type="submit">Apply filters</button>
    </div>
</form>

<div class="asc-grid">
    <div class="asc-card"><div class="asc-label">Audit events</div><div class="asc-value">{{ number_format($stats['audit_events']) }}</div><div class="asc-note">Recorded in selected scope</div></div>
    <div class="asc-card"><div class="asc-label">Privileged events</div><div class="asc-value">{{ number_format($stats['privileged_events']) }}</div><div class="asc-note">Administrative/security-sensitive actions</div></div>
    <div class="asc-card"><div class="asc-label">Security signals</div><div class="asc-value">{{ number_format($stats['security_signals']) }}</div><div class="asc-note">Events matching review keywords</div></div>
    <div class="asc-card"><div class="asc-label">Unique actors</div><div class="asc-value">{{ number_format($stats['unique_actors']) }}</div><div class="asc-note">Users represented in audit trail</div></div>
    <div class="asc-card"><div class="asc-label">Active web sessions</div><div class="asc-value">{{ number_format($stats['active_web_sessions']) }}</div><div class="asc-note">Within configured session lifetime</div></div>
    <div class="asc-card"><div class="asc-label">Active mobile tokens</div><div class="asc-value">{{ number_format($stats['active_mobile_tokens']) }}</div><div class="asc-note">Unexpired API sessions</div></div>
    <div class="asc-card"><div class="asc-label">Stale mobile tokens</div><div class="asc-value">{{ number_format($stats['stale_mobile_tokens']) }}</div><div class="asc-note">Unused or not used for 30+ days</div></div>
    <div class="asc-card"><div class="asc-label">Expiring within 7 days</div><div class="asc-value">{{ number_format($stats['expiring_mobile_tokens']) }}</div><div class="asc-note">Mobile sessions nearing expiry</div></div>
</div>

<div class="two-col">
    <section class="panel">
        <div class="panel-head"><h2>Security posture</h2><span class="status {{ $twoFactor['available'] ? 'info' : 'muted' }}">{{ $twoFactor['available'] ? '2FA data available' : '2FA data unavailable' }}</span></div>
        <div class="panel-body">
            <div class="kv"><span>Super administrators</span><strong>{{ number_format($twoFactor['super_admins']) }}</strong></div>
            <div class="kv"><span>Super administrators with 2FA</span><strong>{{ number_format($twoFactor['super_admins_enabled']) }}</strong></div>
            <div class="kv"><span>Users with 2FA configured</span><strong>{{ number_format($twoFactor['users_enabled']) }}</strong></div>
            <div class="kv"><span>Audit log storage</span><span class="status {{ $hasAudit ? 'ok' : 'warn' }}">{{ $hasAudit ? 'Available' : 'Unavailable' }}</span></div>
            <div class="kv"><span>Web session visibility</span><span class="status {{ $hasSessions ? 'ok' : 'warn' }}">{{ $hasSessions ? 'Available' : 'Unavailable' }}</span></div>
            <div class="kv"><span>Mobile session visibility</span><span class="status {{ $hasTokens ? 'ok' : 'warn' }}">{{ $hasTokens ? 'Available' : 'Unavailable' }}</span></div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Recent security signals</h2><span class="small">Review context before escalation</span></div>
        @forelse($securitySignals as $log)
            <div class="panel-body" style="border-bottom:1px solid #F1F5F9">
                <div class="action">{{ $log->action }}</div>
                <div class="signal-copy">{{ $log->actor?->name ?? 'System' }} · {{ $log->tenant?->name ?? 'Platform' }} · {{ optional($log->created_at)->diffForHumans() }}</div>
            </div>
        @empty
            <div class="empty">No matching security signals in this period.</div>
        @endforelse
    </section>
</div>

<section class="panel">
    <div class="panel-head"><h2>Recent privileged activity</h2><span class="small">Administrative and sensitive operations</span></div>
    <div class="responsive-table">
        <table class="asc-table"><thead><tr><th>Time</th><th>Actor</th><th>School</th><th>Action</th><th>Reason</th><th>Source IP</th></tr></thead><tbody>
        @forelse($privilegedLogs as $log)
            <tr><td>{{ optional($log->created_at)->format('d M Y H:i') }}</td><td>{{ $log->actor?->name ?? 'System' }}</td><td>{{ $log->tenant?->name ?? 'Platform' }}</td><td class="action">{{ $log->action }}</td><td>{{ $log->reason ?: '—' }}</td><td>{{ $log->ip_address ?: '—' }}</td></tr>
        @empty<tr><td colspan="6" class="empty">No privileged activity matched this scope.</td></tr>@endforelse
        </tbody></table>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Active mobile sessions</h2><span class="small">Token hashes are never displayed</span></div>
    <div class="responsive-table">
        <table class="asc-table"><thead><tr><th>User</th><th>School ID</th><th>Device</th><th>Last used</th><th>Expires</th><th>State</th></tr></thead><tbody>
        @forelse($tokenRows as $token)
            @php $stale = !$token->last_used_at || \Illuminate\Support\Carbon::parse($token->last_used_at)->lt(now()->subDays(30)); @endphp
            <tr><td><strong>{{ $token->user_name ?? 'Unknown user' }}</strong><div class="small">{{ $token->user_email ?? '' }}</div></td><td>{{ $token->tenant_id ?? '—' }}</td><td class="token-device">{{ $token->device ?: 'Unspecified' }}</td><td>{{ $token->last_used_at ? \Illuminate\Support\Carbon::parse($token->last_used_at)->diffForHumans() : 'Never' }}</td><td>{{ $token->expires_at ? \Illuminate\Support\Carbon::parse($token->expires_at)->format('d M Y') : 'No expiry' }}</td><td><span class="status {{ $stale ? 'warn' : 'ok' }}">{{ $stale ? 'Stale' : 'Active' }}</span></td></tr>
        @empty<tr><td colspan="6" class="empty">No active mobile sessions found.</td></tr>@endforelse
        </tbody></table>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Complete audit trail</h2><span class="small">Newest first</span></div>
    <div class="responsive-table">
        <table class="asc-table"><thead><tr><th>Time</th><th>Actor</th><th>School</th><th>Action</th><th>Auditable</th><th>Reason</th></tr></thead><tbody>
        @if($hasAudit)
            @forelse($recentLogs as $log)
                <tr><td>{{ optional($log->created_at)->format('d M Y H:i') }}</td><td>{{ $log->actor?->name ?? 'System' }}</td><td>{{ $log->tenant?->name ?? 'Platform' }}</td><td class="action">{{ $log->action }}</td><td class="small">{{ class_basename($log->auditable_type ?? '') }} #{{ $log->auditable_id ?? '—' }}</td><td>{{ $log->reason ?: '—' }}</td></tr>
            @empty<tr><td colspan="6" class="empty">No audit records matched the selected filters.</td></tr>@endforelse
        @else<tr><td colspan="6" class="empty">The audit_logs table is not available in this environment.</td></tr>@endif
        </tbody></table>
    </div>
    @if($hasAudit && method_exists($recentLogs, 'links'))<div class="pager">{{ $recentLogs->links() }}</div>@endif
</section>
@endsection
