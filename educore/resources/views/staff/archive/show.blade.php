@extends('layouts.app')
@section('title', 'Archived Staff Profile')
@section('page-title', 'Staff Archive')

@push('styles')
<style>
.page-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap;min-width:0}
.page-head>div{min-width:0}
.page-head h1{overflow-wrap:anywhere}
.head-actions{display:flex;gap:8px;flex-wrap:wrap}
.grid{display:grid;grid-template-columns:300px minmax(0,1fr);gap:16px;min-width:0}
.grid>div{min-width:0}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;min-width:0}
.card-header{padding:14px 18px;background:#F8FAFC;border-bottom:1px solid var(--border);font-weight:700;color:var(--midnight);overflow-wrap:anywhere}
.card-body{padding:18px;min-width:0;overflow-wrap:anywhere}
.row{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px;min-width:0}
.row:last-child{border-bottom:0}
.key{color:var(--slate);flex:0 1 44%;min-width:0}
.val{font-weight:700;color:var(--midnight);text-align:right;flex:1 1 auto;min-width:0;overflow-wrap:anywhere;word-break:break-word}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 14px;min-height:40px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:700;text-decoration:none;box-sizing:border-box}
.btn-primary{background:var(--indigo);color:#fff;border-color:var(--indigo)}
.btn-ghost{background:#fff;color:var(--midnight)}
.timeline{display:grid;gap:10px}
.timeline-item{border:1px solid var(--border);border-radius:10px;padding:12px;min-width:0;overflow-wrap:anywhere}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
@media(max-width:640px){
    .page-head{display:grid;grid-template-columns:1fr}
    .head-actions{display:grid;grid-template-columns:1fr;width:100%}
    .head-actions .btn{width:100%}
    .card-header{padding:12px 14px}
    .card-body{padding:14px}
    .timeline-item .btn{width:100%}
}
@media(max-width:480px){
    .row{flex-direction:column;gap:4px}
    .key,.val{width:100%;max-width:100%;flex:none;text-align:left}
    .card-body{padding:12px}
}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--slate);margin-bottom:4px;overflow-wrap:anywhere">
            <a href="{{ route('staff.archive.index') }}" style="color:var(--indigo);text-decoration:none">Staff Archive</a> / {{ $staff->name }}
        </div>
        <h1 style="font-size:20px;font-weight:800;color:var(--midnight)">{{ $staff->name }}</h1>
        <div style="font-size:13px;color:var(--slate);overflow-wrap:anywhere">{{ $staff->roleLabel() }} - {{ $staff->employmentStatusLabel() }}</div>
    </div>
    <div class="head-actions">
        @can('staff.reinstate')
            <a href="{{ route('staff.reinstate.form', $staff) }}" class="btn btn-primary">Reinstate</a>
        @endcan
        <a href="{{ route('staff.archive.index') }}" class="btn btn-ghost">Back</a>
    </div>
</div>

@if(session('success'))<div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 14px;color:#047857;margin-bottom:14px;overflow-wrap:anywhere">{{ session('success') }}</div>@endif

<div class="grid">
    <div>
        <div class="card">
            <div class="card-header">Staff Details</div>
            <div class="card-body">
                <div class="row"><span class="key">Staff ID</span><span class="val">{{ $staff->staff_id ?: '-' }}</span></div>
                <div class="row"><span class="key">Email</span><span class="val">{{ $staff->email }}</span></div>
                <div class="row"><span class="key">Phone</span><span class="val">{{ $staff->phone ?: '-' }}</span></div>
                <div class="row"><span class="key">Status</span><span class="val">{{ $staff->employmentStatusLabel() }}</span></div>
                <div class="row"><span class="key">Started</span><span class="val">{{ optional($staff->employment_started_at)->format('d M Y') ?: '-' }}</span></div>
                <div class="row"><span class="key">Ended</span><span class="val">{{ optional($staff->employment_ended_at)->format('d M Y') ?: '-' }}</span></div>
                <div class="row"><span class="key">Login</span><span class="val">{{ $staff->is_active ? 'Enabled' : 'Disabled' }}</span></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Historical Summary</div>
            <div class="card-body">
                <div class="row"><span class="key">Attendance Records</span><span class="val">{{ $attendanceCount }}</span></div>
                <div class="row"><span class="key">Payslips</span><span class="val">{{ $payrollSummary->payslip_count ?? 0 }}</span></div>
                <div class="row"><span class="key">Net Payroll Total</span><span class="val">{{ number_format((float)($payrollSummary->net_total ?? 0), 2) }}</span></div>
            </div>
        </div>
    </div>
    <div>
        <div class="card">
            <div class="card-header">Exit Reason</div>
            <div class="card-body">{{ $staff->exit_reason ?: 'No exit reason recorded.' }}</div>
        </div>
        <div class="card">
            <div class="card-header">Status History</div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($staff->staffStatusHistories->sortByDesc('created_at') as $history)
                    <div class="timeline-item">
                        <strong>{{ $history->old_status ?: 'None' }} -> {{ $history->new_status }}</strong>
                        <div style="font-size:12px;color:var(--slate);margin-top:4px">
                            Effective {{ optional($history->effective_date)->format('d M Y') }} by {{ optional($history->changedBy)->name ?? 'Unknown' }}
                        </div>
                        <div style="margin-top:8px">{{ $history->reason }}</div>
                        @if($history->document_path)
                            <a href="{{ route('staff.status.document', $history) }}" class="btn btn-ghost" style="margin-top:10px">Download Document</a>
                        @endif
                    </div>
                    @empty
                    <div style="color:var(--slate);font-size:13px">No status history recorded.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Work History</div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($staff->workHistories->sortByDesc('start_date') as $history)
                    <div class="timeline-item">
                        <strong>{{ $history->position_title ?: ucfirst(str_replace('_', ' ', $history->change_type)) }}</strong>
                        <div style="font-size:12px;color:var(--slate);margin-top:4px">
                            {{ optional($history->start_date)->format('d M Y') }} - {{ optional($history->end_date)->format('d M Y') ?: 'Current' }}
                        </div>
                        <div style="margin-top:6px;color:var(--slate)">{{ $history->department_name ?: '-' }}</div>
                    </div>
                    @empty
                    <div style="color:var(--slate);font-size:13px">No work history recorded.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
