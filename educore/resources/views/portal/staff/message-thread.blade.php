@extends('layouts.app')
@section('title', $thread->subject)
@section('page-title','Messages')
@push('styles')
<style>
.back{font-size:13px;color:var(--brand-navy);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:14px;font-weight:600}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.msg{padding:14px 18px;border-bottom:1px solid var(--border)}
.msg:last-child{border-bottom:none}
.msg.mine{background:#EFF6FF}
.msg-header{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.msg-av{width:28px;height:28px;border-radius:50%;background:var(--brand-navy);color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.msg-sender{font-size:12px;font-weight:700;color:var(--midnight)}
.msg-time{font-size:11px;color:#94A3B8;margin-left:auto}
.msg-body{font-size:13px;color:var(--slate);line-height:1.6;padding-left:36px}
.reply-box{padding:16px 18px;border-top:1px solid var(--border);background:#F8FAFC}
.fc{padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:9px;background:white;outline:none;width:100%;resize:vertical}
.fc:focus{border-color:var(--brand-navy)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer}
.btn-p{background:var(--brand-navy);color:white}
.closed-banner{background:#F1F5F9;border-radius:8px;padding:12px 16px;font-size:13px;color:#64748B;margin:0 18px 16px;text-align:center}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#059669;margin-bottom:14px}
</style>
@endpush
@section('content')
<a href="{{ route('staff.portal.messages') }}" class="back">← Messages</a>

@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif

<div class="card">
    <div class="ch" style="display:flex;align-items:center;justify-content:space-between">
        <span>{{ $thread->subject }}</span>
        <span style="font-size:11px;font-weight:400;color:{{ $thread->status === 'open' ? '#059669' : '#64748B' }}">{{ ucfirst($thread->status) }}</span>
    </div>
    @foreach($thread->replies as $reply)
    @php $isMe = $reply->sender_id === auth()->id(); @endphp
    <div class="msg {{ $isMe ? 'mine' : '' }}">
        <div class="msg-header">
            <div class="msg-av" style="{{ $isMe ? '' : 'background:var(--brand-gold)' }}">
                {{ strtoupper(substr(optional($reply->sender)->name ?? 'A', 0, 1)) }}
            </div>
            <span class="msg-sender">{{ $isMe ? 'You' : (optional($reply->sender)->name ?? 'Admin') }}</span>
            <span class="msg-time">{{ $reply->created_at->diffForHumans() }}</span>
        </div>
        <div class="msg-body">{{ $reply->body }}</div>
    </div>
    @endforeach

    @if($thread->status === 'open')
    <div class="reply-box">
        <form method="POST" action="{{ route('staff.portal.messages.reply', $thread) }}">
            @csrf
            <div style="margin-bottom:10px">
                <textarea name="body" class="fc" rows="3" placeholder="Type your reply..." required></textarea>
            </div>
            <button type="submit" class="btn btn-p">Send Reply</button>
        </form>
    </div>
    @else
    <div class="closed-banner">This thread has been closed.</div>
    @endif
</div>
@endsection
