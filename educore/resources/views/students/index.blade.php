@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Students')

@push('styles')
<style>
    .student-page-actions {
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:8px;
        flex-wrap:wrap;
    }

    .student-filters {
        display:grid;
        grid-template-columns:minmax(220px,2fr) repeat(2,minmax(160px,1fr)) auto;
        gap:12px;
        align-items:end;
    }

    .student-filter-actions {
        display:flex;
        gap:8px;
        align-items:center;
        flex-wrap:wrap;
    }

    .student-table-card { overflow:hidden; min-width:0; }

    .student-table-meta {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:13px 18px;
        border-bottom:1px solid var(--brand-border, var(--border));
        background:#fff;
        color:var(--brand-gray, var(--slate));
        font-size:13px;
    }

    .student-table-meta strong { color:var(--brand-navy, var(--midnight)); }

    .student-table-card .ec-table-wrap {
        width:100%;
        max-width:100%;
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
        overscroll-behavior-inline:contain;
    }

    .student-table-card table { min-width:760px; }

    .student-name-cell {
        display:flex;
        align-items:center;
        gap:10px;
        min-width:180px;
    }

    .student-avatar {
        width:34px;
        height:34px;
        flex:0 0 34px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border-radius:50%;
        background:var(--brand-navy-soft, #EAF0F7);
        color:var(--brand-navy, #071E45);
        font-size:12px;
        font-weight:800;
    }

    .student-name { font-weight:700; color:var(--brand-text, var(--midnight)); overflow-wrap:anywhere; }
    .student-adm { margin-top:2px; font-size:11px; color:var(--brand-gray, var(--slate-light)); overflow-wrap:anywhere; }

    .student-row-actions {
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:6px;
        flex-wrap:wrap;
        min-width:190px;
    }

    .student-action {
        display:inline-flex;
        align-items:center;
        min-height:34px;
        padding:5px 9px;
        border:1px solid var(--brand-border, var(--border));
        border-radius:7px;
        background:#fff;
        color:var(--brand-navy, var(--midnight));
        font-size:11px;
        font-weight:700;
        text-decoration:none;
        white-space:nowrap;
    }

    .student-action:hover {
        background:var(--brand-navy-soft, #EAF0F7);
        border-color:var(--brand-navy, #071E45);
        text-decoration:none;
    }

    .student-action--report {
        background:var(--brand-gold-light, #FEF9EC);
        border-color:rgba(215,154,33,.38);
    }

    .student-empty {
        padding:56px 20px;
        text-align:center;
        color:var(--brand-gray, var(--slate));
    }

    .student-empty h3 {
        margin:0 0 6px;
        color:var(--brand-navy, var(--midnight));
        font-size:16px;
    }

    .student-empty p { margin:0 0 18px; font-size:13px; }

    .student-pagination {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:13px 18px;
        border-top:1px solid var(--brand-border, var(--border));
        background:#fff;
        color:var(--brand-gray, var(--slate));
        font-size:13px;
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
    }

    @media (max-width:980px) {
        .student-filters { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .student-filter-actions { grid-column:1/-1; }
    }

    @media (max-width:640px) {
        .page-header { align-items:flex-start; gap:12px; }
        .student-page-actions { width:100%; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); }
        .student-page-actions .btn { width:100%; min-width:0; justify-content:center; padding-inline:10px; font-size:11px; }
        .student-page-actions .btn:last-child:nth-child(odd) { grid-column:1/-1; }
        .student-filters { grid-template-columns:1fr; gap:10px; }
        .student-filter-actions { grid-column:auto; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); width:100%; }
        .student-filter-actions .btn { width:100%; min-width:0; justify-content:center; }
        .student-table-meta { align-items:flex-start; flex-direction:column; padding:11px 12px; font-size:11px; }
        .student-pagination { padding:10px 12px; font-size:11px; }
        .student-table-card table { min-width:700px; }
        .student-table-card table th { font-size:9px; padding:8px 10px; }
        .student-table-card table td { font-size:11px; padding:9px 10px; }
        .student-avatar { width:30px; height:30px; flex-basis:30px; }
        .student-name-cell { min-width:155px; }
        .student-row-actions { min-width:170px; }
    }

    @media (max-width:360px) {
        .student-page-actions { grid-template-columns:1fr; }
        .student-page-actions .btn:last-child:nth-child(odd) { grid-column:auto; }
        .student-filter-actions { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Students</h1>
        <div class="hint">Search, review and manage student records for the current school.</div>
    </div>
    <div class="student-page-actions">
        @can('student.archive.view')
            <a href="{{ route('students.archive.index') }}" class="btn btn-secondary">Student Archive</a>
        @endcan
        <a href="{{ route('students.bulk-upload.index') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15" aria-hidden="true"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
            Bulk Upload
        </a>
        @can('students.admit')
            <a href="{{ route('students.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Admit Student
            </a>
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="alert-success" role="status" aria-live="polite">
        {{ session('success') }}
    </div>
@endif

<form method="GET" action="{{ route('students.index') }}" class="filter-card" aria-label="Student filters">
    <div class="student-filters">
        <div class="fg">
            <label class="fl" for="student-search">Search</label>
            <input id="student-search" type="search" name="search" class="fc filter-control" placeholder="Name or admission number" value="{{ request('search') }}">
        </div>
        <div class="fg">
            <label class="fl" for="student-class">Class</label>
            <select id="student-class" name="class_arm_id" class="fc filter-control">
                <option value="">All Classes</option>
                @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}" {{ request('class_arm_id') == $arm->id ? 'selected' : '' }}>
                        {{ $arm->classLevel->name }} {{ $arm->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label class="fl" for="student-status">Status</label>
            <select id="student-status" name="status" class="fc filter-control">
                <option value="">Active Students</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="applicant" {{ request('status') === 'applicant' ? 'selected' : '' }}>Applicant</option>
                <option value="withdrawn" {{ request('status') === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                <option value="left" {{ request('status') === 'left' ? 'selected' : '' }}>Left</option>
                <option value="transferred_out" {{ request('status') === 'transferred_out' ? 'selected' : '' }}>Transferred Out</option>
                <option value="graduated" {{ request('status') === 'graduated' ? 'selected' : '' }}>Graduated</option>
            </select>
        </div>
        <div class="student-filter-actions">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            @if(request()->hasAny(['search','class_arm_id','status']))
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </div>
    </div>
</form>

<div class="ec-card student-table-card">
    <div class="student-table-meta">
        <span>
            Showing <strong>{{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }}</strong>
            of <strong>{{ $students->total() }}</strong> students
        </span>
        @if(request()->hasAny(['search','class_arm_id','status']))
            <span>Filtered view</span>
        @endif
    </div>

    @if($students->count())
        <div class="ec-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Student</th>
                        <th scope="col">Class</th>
                        <th scope="col">Gender</th>
                        <th scope="col">Admission Date</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        <tr>
                            <td>
                                <div class="student-name-cell">
                                    <div class="student-avatar" aria-hidden="true">{{ strtoupper(substr($student->first_name, 0, 1)) }}</div>
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
                                <div class="student-row-actions">
                                    <a href="{{ route('students.show', $student) }}" class="student-action">View</a>
                                    @can('student.status.view')
                                        <a href="{{ route('students.status.show', $student) }}" class="student-action">Status</a>
                                    @endcan
                                    <a href="{{ route('reports.index', ['class_arm_id' => $student->current_class_arm_id]) }}" class="student-action student-action--report">Report</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="student-pagination">
                <span>Page {{ $students->currentPage() }} of {{ $students->lastPage() }}</span>
                <div class="pagination">{{ $students->links('pagination::simple-tailwind') }}</div>
            </div>
        @endif
    @else
        <div class="student-empty">
            <h3>No students found</h3>
            <p>{{ request()->hasAny(['search','class_arm_id','status']) ? 'Try adjusting or clearing the current filters.' : 'Get started by admitting your first student.' }}</p>
            <div class="ec-actions" style="justify-content:center">
                @can('students.admit')
                    <a href="{{ route('students.create') }}" class="btn btn-primary">Admit First Student</a>
                @endcan
                <a href="{{ route('students.bulk-upload.index') }}" class="btn btn-secondary">Bulk Upload</a>
            </div>
        </div>
    @endif
</div>
@endsection