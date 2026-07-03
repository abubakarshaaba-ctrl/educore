@extends('layouts.app')
@section('title','Platform Support')
@section('page-title','Platform Support')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
label{font-size:12px;font-weight:600;color:var(--midnight)}
.fc{padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:9px;background:white;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer}
.btn-p{background:var(--indigo);color:white}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:11px 16px;font-size:13px;color:#059669;margin-bottom:14px}
.ticket{padding:14px 18px;border-bottom:1px solid var(--border)}
.ticket:last-child{border-bottom:none}
.t-subject{font-size:14px;font-weight:700;color:var(--midnight);margin-bottom:4px}
.t-body{font-size:13px;color:var(--slate);margin-bottom:8px;white-space:pre-line}
.t-meta{font-size:11px;color:#94A3B8}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px}
.b-open{background:#FEF9C3;color:#A16207}
.b-answered{background:#ECFDF5;color:#059669}
.b-closed{background:#F1F5F9;color:#64748B}
.reply-box{background:#F0FDF4;border:1px solid #A7F3D0;border-radius:8px;padding:12px 14px;margin-top:10px;font-size:13px}
.reply-box strong{color:#065F46}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif

<div class="card">
    <div class="ch">Contact Platform Support</div>
    <div style="padding:18px">
        <form method="POST" action="{{ route('support.store') }}">
            @csrf
            <div class="fg">
                <label>Subject</label>
                <input type="text" name="subject" class="fc" placeholder="Brief description of your issue" required value="{{ old('subject') }}">
                @error('subject')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror
            </div>
            <div class="fg">
                <label>Message</label>
                <textarea name="body" class="fc" rows="5" placeholder="Describe your issue or question in detail..." required>{{ old('body') }}</textarea>
                @error('body')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn-p">Send Support Request</button>
        </form>
    </div>
</div>

@if($tickets->count())
<div class="card">
    <div class="ch">My Support Tickets <span style="font-size:11px;font-weight:400;color:#64748B">{{ $tickets->count() }} ticket{{ $tickets->count() == 1 ? '' : 's' }}</span></div>
    @foreach($tickets as $ticket)
    <div class="ticket">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
            <div class="t-subject">{{ $ticket->subject }}</div>
            <span class="badge b-{{ $ticket->status }}">{{ ucfirst($ticket->status) }}</span>
        </div>
        <div class="t-body">{{ $ticket->body }}</div>
        @if($ticket->admin_reply)
        <div class="reply-box">
            <strong>Platform Team:</strong> {{ $ticket->admin_reply }}
            <div style="font-size:11px;color:#94A3B8;margin-top:4px">{{ \Carbon\Carbon::parse($ticket->replied_at)->format('d M Y, H:i') }}</div>
        </div>
        @endif
        <div class="t-meta">Submitted {{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}</div>
    </div>
    @endforeach
</div>
@endif
@endsection
