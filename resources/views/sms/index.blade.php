@extends('layouts.app')
@section('title','SMS Campaigns')
@section('page-title','SMS Campaigns')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}td{padding:10px 14px;border-bottom:1px solid var(--border);color:var(--midnight)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.badge{display:inline-flex;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-sent{background:#ECFDF5;color:#059669}.b-draft{background:#F1F5F9;color:#64748B}.b-scheduled{background:#FFFBEB;color:#D97706}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
</style>
@endpush
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div>
        <h2 style="font-size:16px;font-weight:700">📱 SMS Campaigns</h2>
        <p style="font-size:12px;color:var(--slate-light);margin-top:3px">Compose and send bulk SMS to parents, staff or classes</p>
    </div>
    <a href="{{ route('sms.create') }}" class="btn btn-primary">+ New Campaign</a>
</div>
@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif
<div class="card">
    <div class="card-head"><span style="font-size:13px;font-weight:700">All Campaigns</span></div>
    <div class="tbl"><table><thead><tr><th>Title</th><th>Audience</th><th>Recipients</th><th>Status</th><th>Created</th><th>Sent</th><th></th></tr></thead>
    <tbody>
    @forelse($campaigns as $c)
    <tr>
        <td style="font-weight:600">{{ $c->title }}</td>
        <td style="text-transform:capitalize;font-size:12px">{{ str_replace('_',' ',$c->audience) }}</td>
        <td>{{ number_format($c->recipient_count) }}</td>
        <td><span class="badge b-{{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
        <td style="font-size:12px;color:var(--slate-light)">{{ $c->created_at->format('d M Y') }}</td>
        <td style="font-size:12px;color:var(--slate-light)">{{ $c->sent_at ? $c->sent_at->format('d M Y H:i') : '—' }}</td>
        <td>
            <div style="display:flex;gap:6px">
                <a href="{{ route('sms.show',$c) }}" class="btn btn-ghost" style="padding:5px 10px;font-size:11px">View</a>
                @if($c->status === 'draft')
                <form method="POST" action="{{ route('sms.send',$c) }}"><@csrf
                    <button class="btn btn-primary" style="padding:5px 10px;font-size:11px">Send</button>
                </form>
                @endif
                <form method="POST" action="{{ route('sms.destroy',$c) }}">@csrf @method('DELETE')
                    <button class="btn btn-danger" style="padding:5px 10px;font-size:11px" onclick="return confirm('Delete?')">✕</button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" style="text-align:center;padding:50px;color:var(--slate-light)">No campaigns yet. <a href="{{ route('sms.create') }}" style="color:var(--indigo)">Create one →</a></td></tr>
    @endforelse
    </tbody></table></div>
</div>
{{ $campaigns->links() }}
@endsection
