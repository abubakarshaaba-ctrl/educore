@extends('layouts.app')
@section('title','Campaign Details')
@section('page-title','SMS Campaigns')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}td{padding:9px 14px;border-bottom:1px solid var(--border)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;text-decoration:none}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}.btn-primary{background:var(--indigo);color:white}
.badge{display:inline-flex;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-queued{background:#EFF6FF;color:var(--indigo)}.b-sent{background:#ECFDF5;color:#059669}.b-failed{background:#FEF2F2;color:#DC2626}
</style>
@endpush
@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
    <a href="{{ route('sms.index') }}" class="btn btn-ghost">← Back</a>
    <h2 style="font-size:15px;font-weight:700">{{ $campaign->title }}</h2>
    @if($campaign->status === 'draft')
    <form method="POST" action="{{ route('sms.send',$campaign) }}" style="margin-left:auto">@csrf
        <button class="btn btn-primary">📱 Send Now</button>
    </form>
    @endif
</div>
<div class="card">
    <div class="card-head"><span style="font-size:13px;font-weight:700">Campaign Details</span></div>
    <div style="padding:18px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div><div style="font-size:11px;color:var(--slate-light);font-weight:700;text-transform:uppercase;margin-bottom:3px">Audience</div><div style="font-weight:600">{{ str_replace('_',' ', ucfirst($campaign->audience)) }}</div></div>
        <div><div style="font-size:11px;color:var(--slate-light);font-weight:700;text-transform:uppercase;margin-bottom:3px">Recipients</div><div style="font-weight:600">{{ number_format($campaign->recipient_count) }}</div></div>
        <div><div style="font-size:11px;color:var(--slate-light);font-weight:700;text-transform:uppercase;margin-bottom:3px">Status</div><span class="badge b-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span></div>
        <div><div style="font-size:11px;color:var(--slate-light);font-weight:700;text-transform:uppercase;margin-bottom:3px">Sent At</div><div>{{ $campaign->sent_at ? $campaign->sent_at->format('d M Y H:i') : '—' }}</div></div>
        <div style="grid-column:span 2"><div style="font-size:11px;color:var(--slate-light);font-weight:700;text-transform:uppercase;margin-bottom:6px">Message</div>
            <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:12px;font-size:13px;white-space:pre-wrap">{{ $campaign->message }}</div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-head"><span style="font-size:13px;font-weight:700">Delivery Log</span><span style="font-size:12px;color:var(--slate-light)">{{ $logs->total() }} entries</span></div>
    <div class="tbl"><table><thead><tr><th>Phone</th><th>Status</th><th>Queued At</th></tr></thead>
    <tbody>
    @forelse($logs as $log)
    <tr><td>{{ $log->phone }}</td><td><span class="badge b-{{ $log->status }}">{{ ucfirst($log->status) }}</span></td><td style="font-size:12px;color:var(--slate-light)">{{ $log->created_at->format('d M H:i') }}</td></tr>
    @empty
    <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--slate-light)">No log entries.</td></tr>
    @endforelse
    </tbody></table></div>
    <div style="padding:14px">{{ $logs->links() }}</div>
</div>
@endsection
