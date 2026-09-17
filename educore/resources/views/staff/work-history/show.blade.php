@extends('layouts.app')
@section('title', 'Work History Detail')
@section('page-title', 'Work History Detail')

@push('styles')
<style>
.work-detail-head{margin-bottom:18px;min-width:0}
.work-detail-back{font-size:13px;color:var(--indigo);text-decoration:none}
.work-detail-title{font-size:20px;font-weight:800;color:var(--midnight);margin-top:6px;overflow-wrap:anywhere}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;width:100%;min-width:0}
.card-header{padding:14px 18px;background:#F8FAFC;border-bottom:1px solid var(--border);font-weight:700;color:var(--midnight);overflow-wrap:anywhere}
.card-body{padding:18px;min-width:0}
.row{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:10px 0;border-bottom:1px solid var(--border);font-size:13px;min-width:0}
.row:last-child{border-bottom:0}
.key{color:var(--slate);flex:0 0 150px}
.val{font-weight:700;color:var(--midnight);text-align:right;min-width:0;overflow-wrap:anywhere;word-break:break-word}
.reason-block{margin-top:16px;min-width:0}
.reason-label{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
.reason-text{font-size:13px;color:var(--midnight);line-height:1.55;overflow-wrap:anywhere;word-break:break-word}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 14px;min-height:40px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:700;text-decoration:none;max-width:100%}
.btn-ghost{background:#fff;color:var(--midnight)}
@media(max-width:620px){
    .work-detail-title{font-size:18px}
    .card-header{padding:12px 14px}
    .card-body{padding:14px}
    .row{display:grid;grid-template-columns:1fr;gap:4px;padding:9px 0}
    .key{flex:none;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
    .val{text-align:left}
    .btn{width:100%}
}
</style>
@endpush

@section('content')
<div class="work-detail-head">
    <a href="{{ route('staff.work-history.index', $history->staff) }}" class="work-detail-back">Back to work history</a>
    <h1 class="work-detail-title">{{ optional($history->staff)->name }} - Work History</h1>
</div>

<div class="card">
    <div class="card-header">{{ ucfirst(str_replace('_', ' ', $history->change_type)) }}</div>
    <div class="card-body">
        <div class="row"><span class="key">Position</span><span class="val">{{ $history->position_title ?: '-' }}</span></div>
        <div class="row"><span class="key">Department</span><span class="val">{{ $history->department_name ?: '-' }}</span></div>
        <div class="row"><span class="key">Employment Type</span><span class="val">{{ $history->employment_type ?: '-' }}</span></div>
        <div class="row"><span class="key">Functional Role</span><span class="val">{{ $history->functional_role ?: '-' }}</span></div>
        <div class="row"><span class="key">Grade Level</span><span class="val">{{ $history->grade_level ?: '-' }}</span></div>
        <div class="row"><span class="key">Appointment Type</span><span class="val">{{ $history->appointment_type ?: '-' }}</span></div>
        <div class="row"><span class="key">Start Date</span><span class="val">{{ optional($history->start_date)->format('d M Y') ?: '-' }}</span></div>
        <div class="row"><span class="key">End Date</span><span class="val">{{ optional($history->end_date)->format('d M Y') ?: 'Current' }}</span></div>
        <div class="row"><span class="key">Recorded By</span><span class="val">{{ optional($history->recordedBy)->name ?: '-' }}</span></div>
        <div class="row"><span class="key">Approved By</span><span class="val">{{ optional($history->approvedBy)->name ?: '-' }}</span></div>
        <div class="reason-block">
            <div class="reason-label">Reason</div>
            <div class="reason-text">{{ $history->reason ?: 'No reason recorded.' }}</div>
        </div>
        @if($history->document_path)
            <a href="{{ route('staff.work-history.document', $history) }}" class="btn btn-ghost" style="margin-top:16px">Download Document</a>
        @endif
    </div>
</div>
@endsection
