@extends('layouts.app')
@section('title', 'Message Logs')
@section('page-title', 'Messaging')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .filters { background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end; }
    .filter-group { display:flex;flex-direction:column;gap:5px; }
    .filter-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .filter-control { padding:8px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;min-width:160px; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:11px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .message-cell { max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .badge { display:inline-flex;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px; }
    .badge-success { background:#ECFDF5;color:var(--emerald); }
    .badge-warning { background:#FFFBEB;color:var(--amber); }
    .badge-error   { background:#FEF2F2;color:var(--crimson); }
    .badge-info    { background:var(--indigo-bg);color:var(--indigo); }
    .empty-state { text-align:center;padding:50px;color:var(--slate-light); }
    .empty-state h3 { font-size:15px;font-weight:600;color:var(--slate);margin-bottom:6px; }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('notifications.index') }}" class="page-tab">Compose</a>
    <a href="{{ route('notifications.logs') }}" class="page-tab active">Message Logs</a>
</div>

<form method="GET">
    <div class="filters">
        <div class="filter-group">
            <span class="filter-label">Channel</span>
            <select name="channel" class="filter-control">
                <option value="">All Channels</option>
                <option value="sms"   {{ request('channel') === 'sms'   ? 'selected' : '' }}>SMS</option>
                <option value="email" {{ request('channel') === 'email' ? 'selected' : '' }}>Email</option>
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Status</span>
            <select name="status" class="filter-control">
                <option value="">All Status</option>
                <option value="sent"      {{ request('status') === 'sent'      ? 'selected' : '' }}>Sent</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Failed</option>
                <option value="queued"    {{ request('status') === 'queued'    ? 'selected' : '' }}>Queued</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>

<div class="card">
    @if($logs->count())
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>Recipient</th>
                <th>Channel</th>
                <th>Message</th>
                <th>Status</th>
                <th>Cost</th>
                <th>Sent At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>
                    <strong>{{ $log->recipient }}</strong>
                    @if($log->student)<div style="font-size:11px;color:var(--slate-light)">{{ $log->student->full_name }}</div>@endif
                </td>
                <td><span class="badge badge-info">{{ strtoupper($log->channel) }}</span></td>
                <td class="message-cell" title="{{ $log->message }}">{{ $log->message }}</td>
                <td>
                    @if(in_array($log->status, ['sent','delivered']))
                        <span class="badge badge-success">{{ ucfirst($log->status) }}</span>
                    @elseif($log->status === 'queued')
                        <span class="badge badge-warning">Queued</span>
                    @else
                        <span class="badge badge-error">Failed</span>
                    @endif
                </td>
                <td style="font-size:12px;color:var(--slate)">
                    {{ $log->unit_cost > 0 ? '₦' . number_format($log->unit_cost, 2) : '—' }}
                </td>
                <td style="font-size:12px;color:var(--slate)">{{ optional($log->sent_at)->format('d M Y, g:ia') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
    @else
    <div class="empty-state">
        <h3>No messages found</h3>
        <p>Messages will appear here after you send them.</p>
    </div>
    @endif
</div>
@endsection
