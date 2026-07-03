@extends('layouts.app')

@section('title', 'Student Archive')
@section('page-title', 'Student Archive')

@push('styles')
<style>
.stat{background:white;border:1px solid var(--border);border-radius:10px;padding:16px;text-align:center;flex:1;min-width:130px}
.stat .val{font-size:24px;font-weight:900;color:var(--midnight)}
.stat .lbl{font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:3px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.filters-card{padding:18px 20px}
.fg{display:flex;flex-direction:column;gap:5px}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.04em}
.fc{border:1px solid var(--border);border-radius:8px;padding:9px 12px;font:inherit;font-size:13px;min-width:160px;background:#F8FAFC}
.fc:focus{outline:none;border-color:var(--indigo);background:white}
.filters-grid{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
table{width:100%;border-collapse:collapse;font-size:12.5px}
th{padding:10px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);border-bottom:1px solid var(--border);background:#F8FAFC}
td{padding:11px 14px;border-bottom:1px solid #F8FAFC;color:var(--midnight)}
.badge{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px}
.badge-success{background:#ECFDF5;color:var(--emerald)}
.badge-warning{background:#FFFBEB;color:var(--amber)}
.badge-error{background:#FEF2F2;color:var(--crimson)}
.badge-info{background:var(--indigo-bg);color:var(--indigo)}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div style="font-size:12px;color:var(--slate-light)">
        Lifecycle archive for left, withdrawn, transferred-out and graduated students.
    </div>
    @if($canExport)
    <a href="{{ route('students.archive.export', request()->query()) }}" class="btn btn-g">
        ⬇ Export CSV
    </a>
    @endif
</div>

{{-- Stat cards --}}
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    @foreach($summary as $status => $count)
    <div class="stat">
        <div class="val">{{ $count }}</div>
        <div class="lbl">{{ $statusLabels[$status] ?? ucfirst(str_replace('_',' ',$status)) }}</div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="card filters-card" style="margin-bottom:20px">
    <form method="GET" action="{{ route('students.archive.index') }}" class="filters-grid">
        <div class="fg">
            <label class="fl">Search</label>
            <input type="text" name="search" class="fc" value="{{ $filters['search'] ?? '' }}" placeholder="Name or admission no">
        </div>
        <div class="fg">
            <label class="fl">Status</label>
            <select name="status" class="fc">
                <option value="">All Archive Statuses</option>
                @foreach($studentArchiveStatuses as $status)
                <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                    {{ $statusLabels[$status] ?? ucfirst(str_replace('_',' ',$status)) }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label class="fl">Last Class</label>
            <select name="class_arm_id" class="fc">
                <option value="">All Classes</option>
                @foreach($classArms as $arm)
                <option value="{{ $arm->id }}" {{ (string)($filters['class_arm_id'] ?? '') === (string)$arm->id ? 'selected' : '' }}>
                    {{ optional($arm->classLevel)->name }} {{ $arm->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label class="fl">Session</label>
            <select name="session_id" class="fc">
                <option value="">Any Session</option>
                @foreach($sessions as $session)
                <option value="{{ $session->id }}" {{ (string)($filters['session_id'] ?? '') === (string)$session->id ? 'selected' : '' }}>
                    {{ $session->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label class="fl">Exit From</label>
            <input type="date" name="exit_from" class="fc" value="{{ $filters['exit_from'] ?? '' }}">
        </div>
        <div class="fg">
            <label class="fl">Exit To</label>
            <input type="date" name="exit_to" class="fc" value="{{ $filters['exit_to'] ?? '' }}">
        </div>
        <button type="submit" class="btn btn-p">Filter</button>
        <a href="{{ route('students.archive.index') }}" class="btn btn-g">Reset</a>
    </form>
</div>

{{-- Results table --}}
<div class="card">
    <div class="ch">📦 Archived Students</div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>Student</th><th>Last Known Class</th><th>Status</th><th>Admission Date</th><th></th></tr>
        </thead>
        <tbody>
        @forelse($students as $student)
        <tr>
            <td>
                <div style="font-weight:700">{{ $student->full_name }}</div>
                <div style="font-size:11px;color:var(--slate-light);font-family:monospace">{{ $student->admission_number }}</div>
            </td>
            <td>{{ optional(optional($student->currentClassArm)->classLevel)->name }} {{ optional($student->currentClassArm)->name ?? '-' }}</td>
            <td>
                @php
                    $badgeClass = match(true) {
                        str_contains(strtolower($student->status_label ?? ''), 'graduat') => 'badge-success',
                        str_contains(strtolower($student->status_label ?? ''), 'transfer') => 'badge-warning',
                        str_contains(strtolower($student->status_label ?? ''), 'withdraw') => 'badge-error',
                        default => 'badge-info',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $student->status_label }}</span>
            </td>
            <td>{{ optional($student->admission_date)->format('d M Y') ?? '-' }}</td>
            <td><a href="{{ route('students.archive.show', $student) }}" class="btn btn-g" style="padding:6px 12px;font-size:11px">View</a></td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--slate-light)">No archived students found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div style="padding:14px 20px">{{ $students->links() }}</div>
</div>

@endsection
