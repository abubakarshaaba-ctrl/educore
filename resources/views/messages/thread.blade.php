@extends('layouts.app')
@section('title','Message Thread')
@section('page-title','Messages')
@push('styles')
<style>
.pg{display:grid;grid-template-columns:240px 1fr;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.msg{padding:16px 18px;border-bottom:1px solid var(--border)}
.msg:last-child{border-bottom:none}
.msg.mine{background:#EFF6FF}
.msg-header{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.msg-av{width:28px;height:28px;border-radius:50%;background:var(--indigo);color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.msg-sender{font-size:12px;font-weight:700;color:var(--midnight)}
.msg-time{font-size:11px;color:var(--slate-light);margin-left:auto}
.msg-body{font-size:13px;color:var(--slate);line-height:1.6;padding-left:36px}
.reply-box{padding:16px 18px;border-top:1px solid var(--border);background:#F8FAFC}
.fg{display:flex;flex-direction:column;gap:5px}
.fc{padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:9px;background:white;outline:none;width:100%;transition:border 200ms;resize:vertical}
.fc:focus{border-color:var(--indigo)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.btn-ghost{background:white;border:1px solid var(--border);color:var(--midnight)}
.info-row{display:flex;justify-content:space-between;padding:8px 14px;border-bottom:1px solid var(--border);font-size:12.5px}
.info-row:last-child{border-bottom:none}
.ik{color:var(--slate)}.iv{font-weight:600;color:var(--midnight)}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.closed-banner{background:#F1F5F9;border:1px solid var(--border);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--slate);margin:0 18px 16px;text-align:center}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:768px){.pg{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<a href="{{ route('messages.inbox') }}" class="back">← Inbox</a>
<div class="pg">
  <div>
    <div class="card">
        <div class="ch">Thread Info</div>
        <div class="info-row"><span class="ik">Student</span><span class="iv">{{ optional($thread->student)->full_name }}</span></div>
        <div class="info-row"><span class="ik">Adm No</span><span class="iv" style="font-size:11px">{{ optional($thread->student)->admission_number }}</span></div>
        <div class="info-row"><span class="ik">Class</span><span class="iv" style="font-size:11px">{{ optional(optional($thread->student)->currentClassArm)->optional(classLevel)->name }} {{ optional(optional($thread->student)->currentClassArm)->name }}</span></div>
        <div class="info-row"><span class="ik">Started by</span><span class="iv" style="font-size:11px">{{ optional($thread->initiator)->name }}</span></div>
        <div class="info-row"><span class="ik">Status</span><span class="iv">{{ ucfirst($thread->status) }}</span></div>
        @if($thread->status === 'open')
        <div style="padding:12px 14px">
            <form method="POST" action="{{ route('messages.close', $thread) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-ghost" style="width:100%;justify-content:center;font-size:12px">Close Thread</button>
            </form>
        </div>
        @endif
    </div>
  </div>
  <div>
    <div class="card">
        <div class="ch">{{ $thread->subject }}</div>
        @foreach($thread->replies as $reply)
        @php $isMe = $reply->sender_id === auth()->id(); @endphp
        <div class="msg {{ $isMe ? 'mine' : '' }}">
            <div class="msg-header">
                <div class="msg-av" style="{{ $isMe ? '' : 'background:var(--emerald)' }}">
                    {{ strtoupper(substr(optional($reply->sender)->name ?? 'U', 0, 1)) }}
                </div>
                <span class="msg-sender">{{ $isMe ? 'You' : optional($reply->sender)->name }}</span>
                <span class="msg-time">{{ $reply->created_at->diffForHumans() }}</span>
            </div>
            <div class="msg-body">{{ $reply->body }}</div>
        </div>
        @endforeach
        @if($thread->status === 'open')
        <div class="reply-box">
            <form method="POST" action="{{ route('messages.reply', $thread) }}">
            @csrf
            <div class="fg" style="margin-bottom:10px">
                <textarea name="body" class="fc" rows="3" placeholder="Type your reply..." required></textarea>
            </div>
            <button type="submit" class="btn btn-p">Send Reply</button>
            </form>
        </div>
        @else
        <div class="closed-banner">This thread has been closed.</div>
        @endif
    </div>
  </div>
</div>
@endsection