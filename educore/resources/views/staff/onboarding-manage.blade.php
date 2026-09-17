@extends('layouts.app')
@section('title','Staff Onboarding')
@section('page-title','Staff Onboarding')
@push('styles')
<style>
.box{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:16px;min-width:0}.box h3,.box p{overflow-wrap:anywhere}.linkrow{display:flex;gap:8px;align-items:center;flex-wrap:wrap;min-width:0}.join{flex:1 1 360px;min-width:0;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;overflow-wrap:anywhere;word-break:break-word}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 14px;border-radius:8px;border:0;text-decoration:none;font-size:12px;font-weight:700;cursor:pointer}.primary{background:var(--indigo);color:#fff}.ghost{background:#fff;border:1px solid var(--border);color:var(--midnight)}.danger{background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA}.grid{display:grid;gap:10px}.item{border:1px solid var(--border);border-radius:10px;padding:12px;min-width:0}.top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;min-width:0}.top>div{min-width:0;flex:1 1 260px}.top strong,.meta{overflow-wrap:anywhere}.meta{font-size:11px;color:var(--slate-light);margin-top:4px;line-height:1.5}.staff-id{display:inline-flex;align-items:center;margin-top:7px;padding:4px 8px;border-radius:7px;background:#EFF6FF;color:#1D4ED8;font-size:11px;font-weight:800;font-family:monospace}.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-top:10px;min-width:0}.approval-form{display:flex;gap:8px;flex-wrap:wrap;flex:1 1 420px;min-width:0}.reject-form{flex:0 0 auto}.role{flex:1 1 210px;min-width:0;max-width:100%;padding:9px;border:1px solid var(--border);border-radius:8px;background:#fff;font:inherit;font-size:12px}.pending{color:#92400E}.approved{color:#047857}.rejected{color:#B91C1C}.pagination-wrap{margin-top:12px;overflow-x:auto;-webkit-overflow-scrolling:touch}
@media(max-width:700px){.linkrow,.actions{align-items:stretch}.join,.linkrow>.btn,.linkrow>form,.linkrow>form .btn{width:100%}.approval-form{display:grid;grid-template-columns:1fr;width:100%;flex-basis:100%}.approval-form .role,.approval-form .btn{width:100%}.reject-form{width:100%}.reject-form .btn{width:100%}.item{padding:10px}.top{flex-direction:column}.top>strong{align-self:flex-start}}
@media(max-width:420px){.box{padding:13px}.join{font-size:11px}.btn{width:100%}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="box" style="background:#ECFDF5;color:#047857">{{ session('success') }}</div>@endif
@if($errors->any())<div class="box" style="background:#FEF2F2;color:#B91C1C">{{ $errors->first() }}</div>@endif
<div class="box">
    <h3 style="margin-top:0">Share Staff Profile Link</h3>
    <p style="font-size:12px;color:var(--slate)">Share this short tenant-specific URL with staff. A Staff ID is assigned and saved automatically when a profile is submitted; profiles remain pending until an administrator approves them.</p>
    <div class="linkrow">
        <div class="join" id="joinUrl">{{ $joinUrl }}</div>
        <a class="btn primary" href="{{ $joinUrl }}" target="_blank" rel="noopener">Open Link</a>
        <button class="btn ghost" type="button" onclick="navigator.clipboard.writeText(document.getElementById('joinUrl').innerText)">Copy Link</button>
        <form method="POST" action="{{ route('staff.onboarding.rotate') }}">@csrf<button class="btn ghost" type="submit">Generate New Link</button></form>
    </div>
</div>
<div class="box">
    <h3 style="margin-top:0">Profile Submissions</h3>
    <div class="grid">
        @forelse($submissions as $submission)
        <div class="item">
            <div class="top">
                <div>
                    <strong>{{ $submission->name }}</strong>
                    <div class="meta">{{ $submission->email }} · {{ ucfirst($submission->gender) }} · {{ $submission->qualification }} · {{ $submission->position_title ?: 'Position not supplied' }}</div>
                    <div class="staff-id">Staff ID: {{ $submission->staff_id ?: 'Pending assignment' }}</div>
                </div>
                <strong class="{{ $submission->status }}">{{ strtoupper($submission->status) }}</strong>
            </div>
            @if($submission->status==='pending')
            <div class="actions">
                <form method="POST" action="{{ route('staff.onboarding.approve',$submission) }}" class="approval-form">
                    @csrf
                    <select name="role" class="role" required>
                        <option value="">Assign system role</option>
                        @foreach($roleLabels as $role=>$label)<option value="{{ $role }}">{{ $label }}</option>@endforeach
                    </select>
                    <button class="btn primary" type="submit">Approve & Activate</button>
                </form>
                <form method="POST" action="{{ route('staff.onboarding.reject',$submission) }}" class="reject-form">
                    @csrf
                    <button class="btn danger" type="submit">Reject</button>
                </form>
            </div>
            @endif
        </div>
        @empty
        <div style="font-size:12px;color:var(--slate-light)">No staff profile submissions yet.</div>
        @endforelse
    </div>
    <div class="pagination-wrap">{{ $submissions->links() }}</div>
</div>
@endsection
