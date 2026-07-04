@extends('layouts.app')

@section('title', 'Archived Student')
@section('page-title', 'Archived Student')

@push('styles')
<style>
.grid{display:grid;grid-template-columns:320px 1fr;gap:18px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #F8FAFC;font-size:13px}
.info-row:last-child{border-bottom:none}
.info-key{color:var(--slate-light);font-weight:500}
.info-val{color:var(--midnight);font-weight:700;text-align:right}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;margin:0 6px 6px 0}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
table{width:100%;border-collapse:collapse;font-size:12.5px}
th{padding:9px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);border-bottom:1px solid var(--border);background:#F8FAFC}
td{padding:10px 12px;border-bottom:1px solid #F8FAFC;color:var(--midnight)}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:16px}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div>
        <div style="font-size:16px;font-weight:800;color:var(--midnight)">{{ $student->full_name }}</div>
        <div style="font-size:11px;color:var(--slate-light)">{{ $student->admission_number }} &middot; {{ $student->status_label }}</div>
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
                <p style="font-size:12px;color:var(--slate-light);margin:0">No lifecycle action is available for your permissions and this status.</p>
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
            <div style="overflow-x:auto">
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
            <div style="overflow-x:auto">
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
                <p style="font-size:12px;color:var(--slate-light);margin-bottom:10px">Historical records are preserved. This section shows recent linked records only.</p>
                <div class="info-row"><span class="info-key">Invoices shown</span><span class="info-val">{{ $student->invoices->count() }}</span></div>
                <div class="info-row"><span class="info-key">CBT sessions shown</span><span class="info-val">{{ $student->cbtSessions->count() }}</span></div>
            </div>
        </div>

        {{-- Audit events --}}
        <div class="card">
            <div class="ch">🕒 Audit Events</div>
            <div class="cb">
                @forelse($audits as $audit)
                <div style="border-bottom:1px solid #F8FAFC;padding:9px 0">
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
