@extends('layouts.app')
@section('title','Messages')
@section('page-title','Messages')
@push('styles')
<style>
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.thread-item{display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;transition:background 150ms}
.thread-item:last-child{border-bottom:none}
.thread-item:hover{background:#F8FAFC}
.thread-item.unread{background:#EFF6FF}
.th-av{width:38px;height:38px;border-radius:50%;background:var(--indigo);color:white;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.th-subject{font-size:13px;font-weight:700;color:var(--midnight)}
.th-preview{font-size:12px;color:var(--slate);margin-top:2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:400px}
.th-meta{font-size:11px;color:var(--slate-light);margin-top:3px}
.th-time{font-size:11px;color:var(--slate-light);white-space:nowrap;margin-left:auto;padding-left:12px}
.unread-dot{width:8px;height:8px;border-radius:50%;background:var(--indigo);flex-shrink:0;margin-top:4px}
.badge-count{display:inline-flex;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;background:var(--indigo);color:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;text-decoration:none}
.btn-p{background:var(--indigo);color:white}
.empty{padding:60px;text-align:center;color:var(--slate-light)}
</style>
@endpush
@section('content')
<div class="ph">
    <div>
        <h2 style="font-size:18px;font-weight:700">Inbox
            @if($unreadCount > 0)<span class="badge-count" style="margin-left:8px">{{ $unreadCount }}</span>@endif
        </h2>
    </div>
    <a href="{{ route('messages.compose') }}" class="btn btn-p">+ New Message</a>
</div>
<div class="card">
    <div class="ch">Conversations</div>
    @forelse($threads as $thread)
    @php
        $lastReply = $thread->replies->first();
        $hasUnread = $thread->replies->where('sender_id', '!=', auth()->id())->where('is_read', false)->count() > 0;
    @endphp
    <a href="{{ route('messages.thread', $thread) }}" class="thread-item {{ $hasUnread ? 'unread' : '' }}">
        <div class="th-av">{{ strtoupper(substr(optional($thread->student)->first_name ?? 'M', 0, 1)) }}</div>
        <div style="flex:1;min-width:0">
            <div class="th-subject">{{ $thread->subject }}</div>
            <div class="th-preview">{{ optional($lastReply)->body ?? 'No replies yet' }}</div>
            <div class="th-meta">Re: {{ optional($thread->student)->full_name ?? 'Unknown' }} · {{ ucfirst($thread->status) }}</div>
        </div>
        @if($hasUnread)<div class="unread-dot"></div>@endif
        <div class="th-time">{{ $thread->updated_at->diffForHumans(null, true) }}</div>
    </a>
    @empty
    <div class="empty">
        <div style="font-size:32px;margin-bottom:10px">💬</div>
        <div style="font-size:14px;font-weight:600;color:var(--midnight)">No messages yet</div>
        <div style="font-size:13px;margin-top:4px">Start a conversation with a parent or staff member</div>
    </div>
    @endforelse
</div>
{{ $threads->links() }}
@endsection