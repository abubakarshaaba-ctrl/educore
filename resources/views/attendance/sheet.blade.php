@extends('layouts.app')
@section('title', 'Mark Attendance')
@section('page-title', 'Attendance')

@push('styles')
<style>
    .context-bar { background:white;border:1px solid var(--border);border-radius:10px;padding:14px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px; }
    .context-info h2 { font-size:15px;font-weight:700;color:var(--midnight); }
    .context-info p { font-size:13px;color:var(--slate);margin-top:2px; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .sheet-card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    thead th.status-th { text-align:center;min-width:90px; }
    tbody td { padding:10px 16px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#FAFBFF; }
    .student-name { font-weight:600;color:var(--midnight); }
    .student-adm  { font-size:11px;color:var(--slate-light); }

    .status-options { display:flex;gap:6px;justify-content:center; }
    .status-radio { display:none; }
    .status-label { padding:5px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;border:1.5px solid var(--border);color:var(--slate);transition:all 150ms;white-space:nowrap; }
    .status-label:hover { border-color:var(--indigo);color:var(--indigo); }
    .status-radio.present:checked + .status-label { background:#ECFDF5;border-color:#059669;color:#059669; }
    .status-radio.absent:checked  + .status-label { background:#FEF2F2;border-color:#DC2626;color:#DC2626; }
    .status-radio.late:checked    + .status-label { background:#FFFBEB;border-color:#D97706;color:#D97706; }
    .status-radio.excused:checked + .status-label { background:var(--indigo-bg);border-color:var(--indigo);color:var(--indigo); }

    .sheet-footer { padding:14px 20px;border-top:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between; }
    .bulk-actions { display:flex;gap:8px;flex-wrap:wrap; }
    .bulk-btn { padding:7px 14px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:1.5px solid var(--border);background:white;cursor:pointer;transition:all 150ms; }
    .bulk-btn:hover { border-color:var(--indigo);color:var(--indigo); }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
</style>
@endpush

@section('content')

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())
<div class="alert-error" style="background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px">
    @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
    @endforeach
</div>
@endif
@if(empty($currentTermId))
<div class="alert-error" style="background:#FFFBEB;border:1px solid #FCD34D;color:#92400E;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px">
    ⚠️ No academic term is marked as "current" — attendance cannot be saved until one is set in Academic Cycle settings.
</div>
@endif

<div class="context-bar">
    <div class="context-info">
        <h2>{{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ date('l, d F Y', strtotime($date)) }}</h2>
        <p>{{ $students->count() }} students · Mark each student's attendance status</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('attendance.index') }}" class="btn btn-ghost" style="font-size:12px;padding:7px 12px">← Back</a>
    </div>
</div>

<form method="POST" action="{{ route('attendance.save') }}">
    @csrf
    <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
    <input type="hidden" name="date" value="{{ $date }}">
    <input type="hidden" name="term_id" value="{{ $currentTermId ?? '' }}">

    <div class="sheet-card">
        <div class="tbl"><table>
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Student</th>
                    <th class="status-th">Present</th>
                    <th class="status-th">Absent</th>
                    <th class="status-th">Late</th>
                    <th class="status-th">Excused</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $i => $student)
                @php $rec = $existing->get($student->id); @endphp
                <tr>
                    <td style="color:var(--slate-light);font-size:12px">{{ $i + 1 }}</td>
                    <td>
                        <div class="student-name">{{ $student->full_name }}</div>
                        <div class="student-adm">{{ $student->admission_number }}</div>
                    </td>
                    @foreach(['present','absent','late','excused'] as $status)
                    <td style="text-align:center">
                        <input
                            type="radio"
                            class="status-radio {{ $status }}"
                            name="attendance[{{ $student->id }}]"
                            id="att_{{ $student->id }}_{{ $status }}"
                            value="{{ $status }}"
                            {{ ($rec && $rec->status === $status) || (!$rec && $status === 'present') ? 'checked' : '' }}
                        >
                        <label for="att_{{ $student->id }}_{{ $status }}" class="status-label">
                            {{ ucfirst($status) }}
                        </label>
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table></div>

        <div class="sheet-footer">
            <div class="bulk-actions">
                <span style="font-size:12px;color:var(--slate);margin-right:4px">Mark all:</span>
                @foreach(['present','absent','late','excused'] as $status)
                <button type="button" class="bulk-btn" onclick="markAll('{{ $status }}')">{{ ucfirst($status) }}</button>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                Save Attendance
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
function markAll(status) {
    document.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach(r => r.checked = true);
}
</script>
@endpush
@endsection
