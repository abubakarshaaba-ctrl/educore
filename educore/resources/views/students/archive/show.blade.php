@extends('layouts.app')

@section('title', 'Archived Student')
@section('page-title', 'Archived Student')

@push('styles')
<style>
.grid{display:grid;grid-template-columns:320px minmax(0,1fr);gap:18px;min-width:0}
.grid>div{min-width:0}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;box-shadow:0 1px 4px rgba(0,0,0,.04);min-width:0}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);overflow-wrap:anywhere}
.cb{padding:18px;min-width:0}
.info-row{display:flex;justify-content:space-between;gap:14px;padding:8px 0;border-bottom:1px solid #F8FAFC;font-size:13px;min-width:0}
.info-row:last-child{border-bottom:none}
.info-key{color:var(--slate-light);font-weight:500;flex:0 1 44%;min-width:0}
.info-val{color:var(--midnight);font-weight:700;text-align:right;flex:1 1 auto;min-width:0;overflow-wrap:anywhere;word-break:break-word}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;min-height:40px;font-size:13px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;margin:0 6px 6px 0;box-sizing:border-box}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.tbl-wrap{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;overscroll-behavior-inline:contain}
table{width:100%;border-collapse:collapse;font-size:12.5px;min-width:620px}
th{padding:9px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);border-bottom:1px solid var(--border);background:#F8FAFC;white-space:nowrap}
td{padding:10px 12px;border-bottom:1px solid #F8FAFC;color:var(--midnight);vertical-align:top;overflow-wrap:anywhere}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:16px;overflow-wrap:anywhere}
.page-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;min-width:0}
.page-head>div{min-width:0}
.page-title-name{font-size:16px;font-weight:800;color:var(--midnight);overflow-wrap:anywhere}
.page-title-meta{font-size:11px;color:var(--slate-light);overflow-wrap:anywhere}
.audit-row{border-bottom:1px solid #F8FAFC;padding:9px 0;overflow-wrap:anywhere}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
@media(max-width:640px){
    .page-head{display:grid;grid-template-columns:1fr}
    .page-head>.btn{width:100%;margin:0}
    .card{margin-bottom:14px}
    .ch{padding:12px 14px}
    .cb{padding:14px}
    .info-row{align-items:flex-start}
    .btn{min-height:42px}
    .card .cb>.btn{width:100%;margin-right:0}
    table{min-width:560px}
}
@media(max-width:480px){
    .info-row{flex-direction:column;gap:4px}
    .info-key,.info-val{width:100%;max-width:100%;flex:none;text-align:left}
    .ch{font-size:12px}
    .cb{padding:12px}
    table{min-width:520px;font-size:11px}
    th,td{padding:8px 9px}
}
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title-name">{{ $student->full_name }}</div>
        <div class="page-title-meta">{{ $student->admission_number }} &middot; {{ $student->status_label }}</div>
    </div>
    <a href="{{ route('students.archive.index') }}" class="btn btn-g">← Back to Archive</a>
</div>

@if(session('success'))
<div class="alert-success">✓ {{ session('success') }}</div>
@endif

<div class="grid">
    <div>
        {{-- Profile --}}
        <div class="card">
            <div class="ch">👤 Profile</div>
            <div class="cb">
                <div class="info-row"><span class="info-key">Status</span><span class="info-val">{{ $student->status_label }}</span></div>
                <div class="info-row"><span class="info-key">Last Known Class</span><span class="info-val">{{ optional(optional($student->currentClassArm)->classLevel)->name }} {{ optional($student->currentClassArm)->name ?? '-' }}</span></div>
                <div class="info-row"><span class="info-key">Admission Date</span><span class="info-val">{{ optional($student->admission_date)->format('d M Y') ?? '-' }}</span></div>
                <div class="info-row"><span class="info-key">Graduation Date</span><span class="info-val">{{ optional($student->graduation_date)->format('d M Y') ?? '-' }}</span></div>
            </div>
        </div>

        {{-- Available actions --}}
        <div class="card">
            <div class="ch">⚙ Available Actions</div>
            <div class="cb">
                @if($canReactivate)
                <a href="{{ route('students.reactivate.form', $student) }}" class="btn btn-p">↺ Reactivate</a>
                @endif
                @if($canReadmit)
                <a href="{{ route('students.readmit.form', $student) }}" class="btn btn-p">🎓 Readmit</a>
                @endif
                @if($canCorrectGraduation)
                <a href="{{ route('students.graduation-correction.form', $student) }}" class="btn btn-p">✎ Correct Graduation</a>
                @endif
                @unless($canReactivate || $canReadmit || $canCorrectGraduation)
                <p style="font-size:12px;color:var(--slate-light);margin:0;overflow-wrap:anywhere">No lifecycle action is available for your permissions and this status.</p>
                @endunless
            </div>
        </div>

        {{-- Exit details --}}
        @if($exitHistory)
        <div class="card">
            <div class="ch">🚪 Exit Details</div>
            <div class="cb">
                <div class="info-row"><span class="info-key">Effective Date</span><span class="info-val">{{ optional($exitHistory->effective_date)->format('d M Y') ?? '-' }}</span></div>
                <div class="info-row"><span class="info-key">Reason</span><span class="info-val">{{ $exitHistory->reason }}</span></div>
                @if($exitHistory->destination_school)
                <div class="info-row"><span class="info-key">Destination School</span><span class="info-val">{{ $exitHistory->destination_school }}</span></div>
                @endif
                @if($exitHistory->transfer_certificate_number)
                <div class="info-row"><span class="info-key">Transfer Certificate</span><span class="info-val">{{ $exitHistory->transfer_certificate_number }}</span></div>
                @endif
                @if($exitHistory->document_path)
                <div style="margin-top:10px">
                    <a href="{{ route('students.status-history.document', $exitHistory) }}" class="btn btn-g" style="margin:0">📄 Download Document</a>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div>
        {{-- Status history --}}
        <div class="card">
            <div class="ch">📋 Status History</div>
            <div class="tbl-wrap">
            <table>
                <thead><tr><th>From</th><th>To</th><th>Effective Date</th><th>Changed By</th><th>Reason</th></tr></thead>
                <tbody>
                @forelse($student->statusHistories->sortByDesc('effective_date') as $history)
                <tr>
                    <td>{{ $statusLabels[$history->old_status] ?? $history->old_status ?? '-' }}</td>
                    <td style="font-weight:700;color:var(--indigo)">{{ $statusLabels[$history->new_status] ?? $history->new_status }}</td>
                    <td>{{ optional($history->effective_date)->format('d M Y') ?? '-' }}</td>
                    <td>{{ optional($history->changedBy)->name ?? '-' }}</td>
                    <td style="font-size:12px;color:var(--slate)">{{ $history->reason }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--slate-light)">No status history.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{-- Enrolment history --}}
        <div class="card">
            <div class="ch">🏫 Enrolment History</div>
            <div class="tbl-wrap">
            <table>
                <thead><tr><th>Class</th><th>Session</th><th>Term</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($student->enrolmentHistory as $enrolment)
                <tr>
                    <td>{{ optional(optional($enrolment->classArm)->classLevel)->name }} {{ optional($enrolment->classArm)->name ?? '-' }}</td>
                    <td>{{ optional($enrolment->session)->name ?? '-' }}</td>
                    <td>{{ optional($enrolment->term)->name ?? '-' }}</td>
                    <td>{{ optional($enrolment->start_date)->format('d M Y') ?? '-' }}</td>
                    <td>{{ optional($enrolment->end_date)->format('d M Y') ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_',' ',$enrolment->status ?? '-')) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--slate-light)">No enrolment history.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{-- Financial / CBT summary --}}
        <div class="card">
            <div class="ch">💰 Recent Financial and CBT Records</div>
            <div class="cb">
                <p style="font-size:12px;color:var(--slate-light);margin-bottom:10px;overflow-wrap:anywhere">Historical records are preserved. This section shows recent linked records only.</p>
                <div class="info-row"><span class="info-key">Invoices shown</span><span class="info-val">{{ $student->invoices->count() }}</span></div>
                <div class="info-row"><span class="info-key">CBT sessions shown</span><span class="info-val">{{ $student->cbtSessions->count() }}</span></div>
            </div>
        </div>

        {{-- Audit events --}}
        <div class="card">
            <div class="ch">🕒 Audit Events</div>
            <div class="cb">
                @forelse($audits as $audit)
                <div class="audit-row">
                    <div style="font-size:13px;font-weight:700;color:var(--midnight)">{{ $audit->action }}</div>
                    <div style="font-size:11px;color:var(--slate-light);margin-top:2px">{{ optional($audit->created_at)->format('d M Y H:i') }} by {{ optional($audit->actor)->name ?? 'System' }}</div>
                    @if($audit->reason)<div style="font-size:12px;color:var(--slate);margin-top:3px">{{ $audit->reason }}</div>@endif
                </div>
                @empty
                <p style="font-size:12px;color:var(--slate-light);margin:0">No audit events recorded for this student.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
