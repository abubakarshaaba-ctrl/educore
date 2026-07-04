@extends('layouts.app')
@section('title', 'Work History Detail')
@section('page-title', 'Work History Detail')

@push('styles')
<style>
.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;width:100%}
.card-header{padding:14px 18px;background:#F8FAFC;border-bottom:1px solid var(--border);font-weight:700;color:var(--midnight)}
.card-body{padding:18px}
.row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);font-size:13px}
.row:last-child{border-bottom:0}
.key{color:var(--slate)}
.val{font-weight:700;color:var(--midnight);text-align:right}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:700;text-decoration:none}
.btn-ghost{background:#fff;color:var(--midnight)}
</style>
@endpush

@section('content')
<div style="margin-bottom:18px">
    <a href="{{ route('staff.work-history.index', $history->staff) }}" style="font-size:13px;color:var(--indigo);text-decoration:none">Back to work history</a>
    <h1 style="font-size:20px;font-weight:800;color:var(--midnight);margin-top:6px">{{ optional($history->staff)->name }} - Work History</h1>
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
        <div style="margin-top:16px">
            <div style="font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Reason</div>
            <div style="font-size:13px;color:var(--midnight)">{{ $history->reason ?: 'No reason recorded.' }}</div>
        </div>
        @if($history->document_path)
            <a href="{{ route('staff.work-history.document', $history) }}" class="btn btn-ghost" style="margin-top:16px">Download Document</a>
        @endif
    </div>
</div>
@endsection
