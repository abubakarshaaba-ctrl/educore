@extends('layouts.app')
@section('title','Platform Notices')
@section('page-title','Platform Notices')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.notice{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:14px}
.notice:last-child{border-bottom:none}
.notice.read{opacity:.65}
.n-icon{width:38px;height:38px;border-radius:10px;background:#EFF6FF;color:#3B82F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px}
.n-icon.read{background:#F1F5F9;color:#94A3B8}
.n-title{font-size:14px;font-weight:700;color:var(--midnight);margin-bottom:4px}
.n-body{font-size:13px;color:var(--slate);white-space:pre-line;margin-bottom:8px}
.n-meta{font-size:11px;color:#94A3B8;display:flex;align-items:center;gap:10px}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px}
.b-new{background:#EFF6FF;color:#3B82F6}
.b-read{background:#F1F5F9;color:#64748B}
.btn-sm{padding:5px 12px;font-size:11px;font-weight:700;font-family:inherit;border-radius:6px;border:1px solid var(--border);background:white;cursor:pointer;color:var(--midnight)}
.empty{padding:60px;text-align:center;color:#94A3B8}
</style>
@endpush
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div>
        <h2 style="font-size:17px;font-weight:800;color:var(--midnight)">Platform Notices</h2>
        <div style="font-size:12px;color:#64748B;margin-top:2px">Messages and announcements sent by the platform team</div>
    </div>
    <a href="{{ route('support.index') }}" style="font-size:12px;font-weight:600;color:var(--indigo);text-decoration:none;padding:7px 14px;background:#EFF6FF;border-radius:8px">Contact Support →</a>
</div>

<div class="card">
    <div class="ch">
        <span>All Notices</span>
        <span style="font-size:11px;font-weight:400;color:#64748B">{{ count($notices) }} notice{{ count($notices) == 1 ? '' : 's' }}</span>
    </div>
    @forelse($notices as $notice)
    <div class="notice {{ $notice->dismissed ? 'read' : '' }}">
        <div class="n-icon {{ $notice->dismissed ? 'read' : '' }}">📢</div>
        <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <div class="n-title">{{ $notice->title }}</div>
                @if(!$notice->dismissed)
                    <span class="badge b-new">New</span>
                @else
                    <span class="badge b-read">Seen</span>
                @endif
            </div>
            <div class="n-body">{{ $notice->body }}</div>
            <div class="n-meta">
                <span>{{ \Carbon\Carbon::parse($notice->created_at)->format('d M Y, H:i') }}</span>
                @if($notice->expires_at)
                    <span>· Expires {{ \Carbon\Carbon::parse($notice->expires_at)->format('d M Y') }}</span>
                @endif
                @if(!$notice->dismissed)
                <form method="POST" action="{{ route('broadcast.dismiss', $notice->id) }}" style="margin-left:4px">
                    @csrf
                    <button type="submit" class="btn-sm">Mark as read</button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="empty">
        <div style="font-size:32px;margin-bottom:10px">📭</div>
        <div style="font-size:14px;font-weight:600;color:var(--midnight)">No notices yet</div>
        <div style="font-size:13px;margin-top:4px">Platform announcements will appear here</div>
    </div>
    @endforelse
</div>
@endsection
