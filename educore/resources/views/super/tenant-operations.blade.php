@extends('layouts.super')

@section('title', 'Tenant Operations Dashboard')

@section('content')
<style>
.ops-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px}.ops-head h1{font-size:22px;margin-bottom:5px}.ops-head p{font-size:12.5px;color:var(--slate)}
.ops-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:18px}.stat{background:#fff;border:1px solid var(--border);border-radius:12px;padding:14px;min-width:0}.stat small{font-size:9.5px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light)}.stat strong{display:block;font-size:22px;margin-top:6px}.stat span{display:block;font-size:10.5px;color:var(--slate);margin-top:2px}
.panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}.panel-head{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:10px}.panel-head h2{font-size:14px}.panel-body{padding:14px 16px}.filters{display:grid;grid-template-columns:1.4fr .8fr auto auto;gap:10px;align-items:end}.field label{display:block;font-size:10px;font-weight:700;color:var(--slate);margin-bottom:5px}.field input,.field select{width:100%;border:1px solid var(--border);border-radius:8px;padding:8px 10px;font:inherit;font-size:12px;background:#fff}.check{display:flex;gap:7px;align-items:center;font-size:11px;min-height:36px}.check input{width:auto}
.ops-table{width:100%;border-collapse:collapse;min-width:1050px}.ops-table th{font-size:9.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);text-align:left;padding:9px 10px;border-bottom:1px solid var(--border);white-space:nowrap}.ops-table td{padding:10px;font-size:11.5px;border-bottom:1px solid #F1F5F9;vertical-align:top}.ops-table tr:last-child td{border-bottom:0}.school{font-weight:800;font-size:12px}.slug{font-size:10px;color:var(--slate);margin-top:2px}.badge2{display:inline-flex;padding:3px 7px;border-radius:999px;font-size:9.5px;font-weight:800;text-transform:capitalize}.active{background:#ECFDF5;color:#047857}.pending{background:#FFFBEB;color:#B45309}.suspended,.subscription_expired{background:#FEF2F2;color:#B91C1C}.unknown{background:#F1F5F9;color:#64748B}.metric{font-weight:700}.subtle{font-size:10px;color:var(--slate);margin-top:2px}.attention{display:flex;flex-wrap:wrap;gap:4px;max-width:260px}.flag{display:inline-flex;padding:2px 6px;border-radius:999px;background:#FFF7ED;color:#C2410C;font-size:9px;font-weight:700}.okflag{display:inline-flex;padding:2px 6px;border-radius:999px;background:#ECFDF5;color:#047857;font-size:9px;font-weight:700}.pager{padding:12px 16px}.empty{padding:24px;text-align:center;color:var(--slate);font-size:12px}
@media(max-width:1200px){.ops-stats{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:800px){.filters{grid-template-columns:1fr 1fr}.ops-head{flex-direction:column}}
@media(max-width:640px){.ops-stats{grid-template-columns:1fr 1fr}.filters{grid-template-columns:1fr}.panel-head{flex-direction:column;align-items:flex-start}}
@media(max-width:420px){.ops-stats{grid-template-columns:1fr}}
</style>

<div class="ops-head">
    <div><h1>Tenant Operations Dashboard</h1><p>Cross-school operational visibility for accounts, subscriptions, users, sessions, support and billing attention points.</p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-ghost" href="{{ route('super.audit-security') }}">Audit & Security</a><a class="btn btn-ghost" href="{{ route('super.system-health') }}">System Health</a></div>
</div>

<div class="ops-stats">
    <div class="stat"><small>Total schools</small><strong>{{ number_format($summary['total']) }}</strong><span>Current tenant registry</span></div>
    <div class="stat"><small>Active</small><strong>{{ number_format($summary['active']) }}</strong><span>Operational accounts</span></div>
    <div class="stat"><small>Pending</small><strong>{{ number_format($summary['pending']) }}</strong><span>Awaiting activation/readiness</span></div>
    <div class="stat"><small>Suspended</small><strong>{{ number_format($summary['suspended']) }}</strong><span>Access restricted</span></div>
    <div class="stat"><small>Subscription expired</small><strong>{{ number_format($summary['expired']) }}</strong><span>Expired account status</span></div>
    <div class="stat"><small>Expiring ≤14 days</small><strong>{{ number_format($summary['expiring_14d']) }}</strong><span>Renewal attention window</span></div>
</div>

<form class="panel" method="GET">
    <div class="panel-head"><h2>Operational filters</h2><span style="font-size:10.5px;color:var(--slate)">Read-only control plane</span></div>
    <div class="panel-body filters">
        <div class="field"><label>Search school</label><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="School name, slug or email"></div>
        <div class="field"><label>Status</label><select name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucwords(str_replace('_',' ', $status)) }}</option>@endforeach</select></div>
        <label class="check"><input type="checkbox" name="attention" value="1" @checked(($filters['attention'] ?? '') === '1')> Attention only</label>
        <button class="btn btn-primary" type="submit">Apply</button>
    </div>
</form>

<section class="panel">
    <div class="panel-head"><h2>School operations</h2><span style="font-size:10.5px;color:var(--slate)">{{ $tenants->total() }} school{{ $tenants->total() === 1 ? '' : 's' }} matched the selected filters</span></div>
    <div class="responsive-table">
        <table class="ops-table">
            <thead><tr><th>School</th><th>Status</th><th>Students</th><th>Users</th><th>Live sessions</th><th>Subscription</th><th>Support</th><th>Billing</th><th>Recent activity</th><th>Attention</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $status = $row->tenant->status ?? 'unknown';
                    $expiry = $row->tenant->subscription_expires_at ?? null;
                @endphp
                <tr>
                    <td><div class="school">{{ $row->tenant->name }}</div><div class="slug">{{ $row->tenant->slug ?? '—' }}</div></td>
                    <td><span class="badge2 {{ in_array($status,['active','pending','suspended','subscription_expired']) ? $status : 'unknown' }}">{{ str_replace('_',' ', $status) }}</span></td>
                    <td><span class="metric">{{ number_format($row->students) }}</span></td>
                    <td><span class="metric">{{ number_format($row->active_users) }}</span><div class="subtle">{{ number_format($row->users) }} total</div></td>
                    <td><span class="metric">{{ number_format($row->web_sessions + $row->mobile_sessions) }}</span><div class="subtle">Web {{ $row->web_sessions }} · Mobile {{ $row->mobile_sessions }}</div></td>
                    <td>@if($expiry)<span class="metric">{{ \Illuminate\Support\Carbon::parse($expiry)->format('d M Y') }}</span><div class="subtle">@if($row->days_to_expiry < 0)Expired {{ abs($row->days_to_expiry) }}d ago@elseif($row->days_to_expiry === 0)Expires today@else{{ $row->days_to_expiry }}d remaining@endif</div>@else<span class="subtle">No expiry date</span>@endif</td>
                    <td><span class="metric">{{ $row->open_support }}</span><div class="subtle">open/pending</div></td>
                    <td><span class="metric">{{ $row->unpaid_invoices }}</span><div class="subtle">unpaid invoices</div></td>
                    <td>@if($row->last_activity){{ \Illuminate\Support\Carbon::parse($row->last_activity)->diffForHumans() }}@else<span class="subtle">No login timestamp</span>@endif</td>
                    <td><div class="attention">@forelse($row->attention as $flag)<span class="flag">{{ $flag }}</span>@empty<span class="okflag">No current flag</span>@endforelse</div></td>
                </tr>
            @empty
                <tr><td colspan="10" class="empty">No schools matched the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">{{ $tenants->links() }}</div>
</section>
@endsection
