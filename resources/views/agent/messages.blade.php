@extends('agent.layout')
@section('title','Messages from Platform')
@section('content')
@if($messages->count() === 0)
<div style="text-align:center;padding:60px;background:white;border-radius:12px;border:1px solid #E2E8F0">
    <div style="font-size:40px;margin-bottom:12px">💬</div>
    <div style="font-size:15px;font-weight:700;color:#1E293B">No messages yet</div>
    <div style="font-size:13px;color:#64748B;margin-top:6px">Messages from the platform admin will appear here.</div>
</div>
@else
@foreach($messages as $msg)
<div style="background:white;border:1px solid #E2E8F0;border-radius:12px;padding:20px;margin-bottom:12px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px">
        <div style="font-size:14px;font-weight:700;color:#1E293B">{{ $msg->subject }}</div>
        <div style="font-size:11px;color:#94A3B8;white-space:nowrap;flex-shrink:0">{{ $msg->sent_at->format('d M Y, g:ia') }}</div>
    </div>
    <div style="font-size:13px;color:#475569;line-height:1.7;white-space:pre-wrap">{{ $msg->body }}</div>
</div>
@endforeach
<div>{{ $messages->links() }}</div>
@endif
@endsection
