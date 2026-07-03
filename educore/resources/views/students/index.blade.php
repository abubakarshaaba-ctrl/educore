@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Students')

@push('styles')
<style>
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .page-header h1 {
        font-size: 20px;
        font-weight: 700;
        color: var(--midnight);
        letter-spacing: -0.02em;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 600;
        font-family: inherit;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: background 150ms;
    }

    .btn svg { width: 15px; height: 15px; }
    .btn-primary { background: var(--indigo); color: white; }
    .btn-primary:hover { background: #1D4ED8; }
    .btn-ghost { background: white; color: var(--midnight); border: 1px solid var(--border); }
    .btn-ghost:hover { background: #F8FAFC; }

    .filters {
        background: white;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-group { display: flex; flex-direction: column; gap: 6px; }
    .filter-group label { font-size: 11px; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: 0.05em; }

    .filter-input, .filter-select {
        padding: 8px 12px;
        font-size: 13px;
        font-family: inherit;
        border: 1px solid var(--border);
        border-radius: 7px;
        color: var(--midnight);
        background: #F8FAFC;
        outline: none;
        transition: border-color 150ms;
        min-width: 200px;
    }

    .filter-input:focus, .filter-select:focus { border-color: var(--indigo); background: white; }

    .filter-select { min-width: 160px; }

    .alert-success {
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 13px;
        color: var(--emerald);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .student-table-wrap {
        background: white;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .table-meta {
        padding: 14px 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .table-meta-count {
        font-size: 13px;
        color: var(--slate);
    }

    .table-meta-count strong { color: var(--midnight); }

    table { width: 100%; border-collapse: collapse; }

    thead th {
        font-size: 11px;
        font-weight: 600;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 11px 16px;
        text-align: left;
        background: #F8FAFC;
        border-bottom: 1px solid var(--border);
    }

    tbody td {
        padding: 13px 16px;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
        color: var(--midnight);
        vertical-align: middle;
    }

    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #F8FAFC; }

    .student-name-cell { display: flex; align-items: center; gap: 10px; }

    .student-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: var(--indigo-bg);
        color: var(--indigo);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .student-name { font-weight: 600; }
    .student-adm  { font-size: 11px; color: var(--slate-light); margin-top: 1px; }

    .badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
    .badge-success { background: #ECFDF5; color: var(--emerald); }
    .badge-warning { background: #FFFBEB; color: var(--amber); }
    .badge-error   { background: #FEF2F2; color: var(--crimson); }
    .badge-info    { background: var(--indigo-bg); color: var(--indigo); }

    .action-link {
        font-size: 12px;
        font-weight: 600;
        color: var(--indigo);
        text-decoration: none;
    }

    .action-link:hover { text-decoration: underline; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--slate-light);
    }

    .empty-state h3 { font-size: 15px; font-weight: 600; color: var(--slate); margin-bottom: 6px; }
    .empty-state p  { font-size: 13px; margin-bottom: 20px; }

    .pagination-wrap {
        padding: 14px 20px;
        border-top: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
        color: var(--slate);
    }

    .pagination { display: flex; gap: 4px; }
    .pagination a, .pagination span {
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        color: var(--midnight);
        border: 1px solid var(--border);
    }
    .pagination span[aria-current] { background: var(--indigo); color: white; border-color: var(--indigo); }
</style>
@endpush

@section('content')

<div class="page-header">
    <h1>Students</h1>
    <div style="display:flex;gap:8px;align-items:center">
        @can('student.archive.view')
        <a href="{{ route('students.archive.index') }}" class="btn btn-ghost">
            Student Archive
        </a>
        @endcan
        <a href="{{ route('students.bulk-upload.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;background:#059669;color:white;border-radius:8px;text-decoration:none">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
            Bulk Upload
        </a>
        @can('students.admit')
        <a href="{{ route('students.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Admit Student
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert-success">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('students.index') }}">
    <div class="filters">
        <div class="filter-group">
            <label>Search</label>
            <input type="text" name="search" class="filter-input" placeholder="Name or admission no..." value="{{ request('search') }}">
        </div>
        <div class="filter-group">
            <label>Class</label>
            <select name="class_arm_id" class="filter-select">
                <option value="">All Classes</option>
                @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}" {{ request('class_arm_id') == $arm->id ? 'selected' : '' }}>
                        {{ $arm->classLevel->name }} {{ $arm->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label>Status</label>
            <select name="status" class="filter-select">
                <option value="">Active Students</option>
                <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="applicant" {{ request('status') === 'applicant' ? 'selected' : '' }}>Applicant</option>
                <option value="withdrawn" {{ request('status') === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                <option value="left" {{ request('status') === 'left' ? 'selected' : '' }}>Left</option>
                <option value="transferred_out" {{ request('status') === 'transferred_out' ? 'selected' : '' }}>Transferred Out</option>
                <option value="graduated" {{ request('status') === 'graduated' ? 'selected' : '' }}>Graduated</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        @if(request()->hasAny(['search','class_arm_id','status']))
            <a href="{{ route('students.index') }}" class="btn btn-ghost">Clear</a>
        @endif
    </div>
</form>

{{-- Table --}}
<div class="student-table-wrap">
    <div class="table-meta">
        <span class="table-meta-count">
            Showing <strong>{{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }}</strong>
            of <strong>{{ $students->total() }}</strong> students
        </span>
    </div>

    @if($students->count())
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Gender</th>
                <th>Admission Date</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            <tr>
                <td>
                    <div class="student-name-cell">
                        <div class="student-avatar">{{ strtoupper(substr($student->first_name, 0, 1)) }}</div>
                        <div>
                            <div class="student-name">{{ $student->full_name }}</div>
                            <div class="student-adm">{{ $student->admission_number }}</div>
                        </div>
                    </div>
                </td>
                <td>{{ optional($student->currentClassArm)->classLevel->name }} {{ optional($student->currentClassArm)->name ?? '—' }}</td>
                <td>{{ ucfirst($student->gender ?? '—') }}</td>
                <td>{{ optional($student->admission_date)->format('d M Y') ?? '—' }}</td>
                <td>
                    @if($student->status === $studentStatuses['active'])
                        <span class="badge badge-success">Active</span>
                    @elseif($student->status === $studentStatuses['suspended'])
                        <span class="badge badge-warning">Suspended</span>
                    @elseif($student->isArchivedLifecycleStatus())
                        <span class="badge badge-error">{{ $student->status_label }}</span>
                    @else
                        <span class="badge badge-info">{{ $student->status_label }}</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('students.show', $student) }}" class="action-link">View →</a>
                    @can('student.status.view')
                        <a href="{{ route('students.status.show', $student) }}" class="action-link" style="margin-left:8px">Status</a>
                    @endcan
                        <a href="{{ route('reports.index', ['class_arm_id' => $student->current_class_arm_id]) }}" 
                           style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;font-size:11px;font-weight:600;background:#EFF6FF;color:#2563EB;border:1px solid #BFDBFE;border-radius:6px;text-decoration:none">
                            📋 Report
                        </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>

    @if($students->hasPages())
    <div class="pagination-wrap">
        <span>Page {{ $students->currentPage() }} of {{ $students->lastPage() }}</span>
        <div class="pagination">
            {{ $students->links('pagination::simple-tailwind') }}
        </div>
    </div>
    @endif

    @else
    <div class="empty-state">
        <h3>No students found</h3>
        <p>{{ request()->hasAny(['search','class_arm_id','status']) ? 'Try adjusting your filters.' : 'Get started by admitting your first student.' }}</p>
        @can('students.admit')
            <a href="{{ route('students.create') }}" class="btn btn-primary">Admit First Student</a>
        <a href="{{ route('students.bulk-upload.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;background:#059669;color:white;border-radius:8px;text-decoration:none">⬆ Bulk Upload</a>
        @endcan
    </div>
    @endif
</div>

@endsection
