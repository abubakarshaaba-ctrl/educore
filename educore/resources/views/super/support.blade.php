@extends('layouts.super')
@section('title', 'Support Inbox')
@section('page-title', 'Support Inbox')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between;background:#F8FAFC}
.ticket{padding:16px 18px;border-bottom:1px solid var(--border)}
.ticket:last-child{border-bottom:none}
.t-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.t-subject{font-size:14px;font-weight:700;color:var(--midnight)}
.t-meta{font-size:11px;color:#94A3B8;margin-bottom:8px}
.t-body{font-size:13px;color:var(--slate);white-space:pre-line;margin-bottom:12px}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px}
.b-open{background:#FEF9C3;color:#A16207}
.b-answered{background:#ECFDF5;color:#059669}
.b-closed{background:#F1F5F9;color:#64748B}
.reply-area{border-top:1px solid #E2E8F0;padding-top:12px}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:white;outline:none;width:100%;resize:vertical}
.fc:focus{border-color:#DC2626}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;margin-right:6px}
.btn-p{background:#DC2626;color:white}
.btn-ghost{background:white;border:1px solid var(--border);color:var(--midnight)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:11px 16px;font-size:13px;color:#059669;margin-bottom:14px}
.filter-tabs{display:flex;gap:6px;margin-bottom:16px}
.tab{padding:6px 14px;font-size:12px;font-weight:600;border-radius:20px;border:1px solid var(--border);text-decoration:none;color:var(--midnight);background:white}
.tab.active{background:#DC2626;color:white;border-color:#DC2626}
.prev-reply{background:#F0FDF4;border:1px solid #A7F3D0;border-radius:8px;padding:10px 13px;font-size:13px;margin-bottom:12px}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif

<div class="filter-tabs">
    <a href="{{ route('super.support', ['status'=>'open']) }}" class="tab {{ $status==='open'?'active':'' }}">Open</a>
    <a href="{{ route('super.support', ['status'=>'answered']) }}" class="tab {{ $status==='answered'?'active':'' }}">Answered</a>
    <a href="{{ route('super.support', ['status'=>'closed']) }}" class="tab {{ $status==='closed'?'active':'' }}">Closed</a>
    <a href="{{ route('super.support', ['status'=>'all']) }}" class="tab {{ $status==='all'?'active':'' }}">All</a>
</div>

<div class="card">
    <div class="ch">Support Tickets <span style="font-size:11px;font-weight:400;color:#64748B">{{ $tickets->total() }} ticket{{ $tickets->total() == 1 ? '' : 's' }}</span></div>
    @forelse($tickets as $ticket)
    <div class="ticket">
        <div class="t-top">
            <div class="t-subject">{{ $ticket->subject }}</div>
            <span class="badge b-{{ $ticket->status }}">{{ ucfirst($ticket->status) }}</span>
        </div>
        <div class="t-meta">
            <strong>{{ $ticket->school_name ?? 'Unknown School' }}</strong>
            · {{ $ticket->sender_name ?? 'Unknown' }}
            · {{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y, H:i') }}
        </div>
        <div class="t-body">{{ $ticket->body }}</div>
        @if($ticket->admin_reply)
        <div class="prev-reply">
            <strong>Your reply:</strong> {{ $ticket->admin_reply }}
            <div style="font-size:11px;color:#94A3B8;margin-top:3px">{{ \Carbon\Carbon::parse($ticket->replied_at)->format('d M Y, H:i') }}</div>
        </div>
        @endif
        @if($ticket->status !== 'closed')
        <div class="reply-area">
            <form method="POST" action="{{ route('super.support.reply', $ticket->id) }}" style="margin-bottom:8px">
                @csrf
                <textarea name="reply" class="fc" rows="2" placeholder="Type your reply..." required>{{ old('reply') }}</textarea>
                <div style="margin-top:8px">
                    <button type="submit" class="btn btn-p">Send Reply</button>
                </div>
            </form>
            <form method="POST" action="{{ route('super.support.close', $ticket->id) }}">
                @csrf
                <button type="submit" class="btn btn-ghost" onclick="return confirm('Close this ticket?')">Close Ticket</button>
            </form>
        </div>
        @endif
    </div>
    @empty
    <div style="padding:40px;text-align:center;color:#94A3B8;font-size:13px">No {{ $status === 'all' ? '' : $status }} support tickets found.</div>
    @endforelse
</div>
{{ $tickets->withQueryString()->links() }}
@endsection
