@extends('layouts.app')
@section('title','Messages')
@section('page-title','Messages')
@push('styles')
<style>
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;gap:10px;flex-wrap:wrap}.actions{display:flex;gap:8px;flex-wrap:wrap}.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}.thread-item{display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;transition:background 150ms}.thread-item:last-child{border-bottom:none}.thread-item:hover{background:#F8FAFC}.thread-item.unread{background:#EFF6FF}.th-av{width:38px;height:38px;border-radius:50%;background:var(--indigo);color:white;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}.th-subject{font-size:13px;font-weight:700;color:var(--midnight)}.th-preview{font-size:12px;color:var(--slate);margin-top:2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:500px}.th-meta{font-size:11px;color:var(--slate-light);margin-top:3px}.th-time{font-size:11px;color:var(--slate-light);white-space:nowrap;margin-left:auto;padding-left:12px}.unread-dot{width:8px;height:8px;border-radius:50%;background:var(--indigo);flex-shrink:0;margin-top:4px}.badge-count{display:inline-flex;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;background:var(--indigo);color:white}.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:white}.btn-s{background:white;border:1px solid var(--border);color:var(--midnight)}.empty{padding:60px;text-align:center;color:var(--slate-light)}
</style>
@endpush
@section('content')
<div class="ph"><div><h2 style="font-size:18px;font-weight:700">Inbox @if($unreadCount > 0)<span class="badge-count" style="margin-left:8px">{{ $unreadCount }}</span>@endif</h2><div style="font-size:12px;color:var(--slate);margin-top:3px">Private and school-wide communication stays inside your school account.</div></div><div class="actions"><a href="{{ route('messages.internal.compose') }}" class="btn btn-p">+ School Message</a><a href="{{ route('messages.compose') }}" class="btn btn-s">Student-linked Message</a></div></div>
<div class="card"><div class="ch">Conversations</div>
@forelse($threads as $thread)
@php
$lastReply=$thread->replies->first();
$hasUnread=(bool)($thread->is_unread_for_user ?? $thread->replies->where('sender_id','!=',auth()->id())->where('is_read',false)->count()>0);
$label=match($thread->conversation_type ?? 'student'){
'all_staff'=>'All Staff','all_parents'=>'All Parents','staff'=>'Staff conversation','parent'=>'Parent conversation','admin'=>'School Administration',default=>optional($thread->student)->full_name ?? optional($thread->recipient)->name ?? 'School conversation'};
@endphp
<a href="{{ route('messages.thread',$thread) }}" class="thread-item {{ $hasUnread?'unread':'' }}"><div class="th-av">{{ strtoupper(substr($label,0,1)) }}</div><div style="flex:1;min-width:0"><div class="th-subject">{{ $thread->subject }}</div><div class="th-preview">{{ optional($lastReply)->body ?? 'No messages yet' }}</div><div class="th-meta">{{ $label }} · {{ ucfirst($thread->status) }} @if($thread->audience)· Broadcast @endif</div></div>@if($hasUnread)<div class="unread-dot"></div>@endif<div class="th-time">{{ $thread->updated_at->diffForHumans(null,true) }}</div></a>
@empty
<div class="empty"><div style="font-size:32px;margin-bottom:10px">💬</div><div style="font-size:14px;font-weight:600;color:var(--midnight)">No messages yet</div><div style="font-size:13px;margin-top:4px">Start a school message or a student-linked conversation.</div></div>
@endforelse
</div>{{ $threads->links() }}
@endsection
