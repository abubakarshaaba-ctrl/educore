@extends('layouts.app')
@section('title', 'Subjects')
@section('page-title', 'Subject Management')

@push('styles')
<style>
    .page-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:20px; }
    .page-header h1 { font-size:20px;font-weight:700;color:var(--midnight);letter-spacing:-0.02em; }
    .filters { background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end; }
    .filter-group { display:flex;flex-direction:column;gap:5px; }
    .filter-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .filter-control { padding:8px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;min-width:200px; }
    .filter-control:focus { border-color:var(--indigo); }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .btn-sm { padding:5px 10px;font-size:12px; }
    .btn-indigo { background:var(--indigo-bg);color:var(--indigo); }
    .btn-warning { background:#FFFBEB;color:var(--amber);border:1px solid #FDE68A; }
    .btn-success { background:#ECFDF5;color:var(--emerald);border:1px solid #A7F3D0; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .subject-name { font-weight:600; }
    .subject-code { font-size:11px;color:var(--slate-light);margin-top:2px; }
    .badge { display:inline-flex;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px; }
    .badge-success { background:#ECFDF5;color:var(--emerald); }
    .badge-error   { background:#FEF2F2;color:var(--slate); }
    .empty-state { text-align:center;padding:50px;color:var(--slate-light); }
    .empty-state h3 { font-size:15px;font-weight:600;color:var(--slate);margin-bottom:6px; }
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="page-header">
    <h1>Subjects ({{ $subjects->total() }})</h1>
    <a href="{{ route('subjects.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        Add Subject
    </a>
</div>

<form method="GET">
    <div class="filters">
        <div class="filter-group">
            <span class="filter-label">Search</span>
            <input type="text" name="search" class="filter-control" placeholder="Subject name or code..." value="{{ request('search') }}">
        </div>
        <div class="filter-group">
            <span class="filter-label">Status</span>
            <select name="status" class="filter-control">
                <option value="">All</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        @if(request()->hasAny(['search','status']))
            <a href="{{ route('subjects.index') }}" class="btn btn-ghost">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    @if($subjects->count())
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Classes Assigned</th>
                <th>Total Scores</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $subject)
            <tr>
                <td>
                    <div class="subject-name">{{ $subject->name }}</div>
                    @if($subject->code)<div class="subject-code">{{ $subject->code }}</div>@endif
                </td>
                <td>{{ $subject->class_arms_count }} class(es)</td>
                <td>{{ $subject->scores_count }} entries</td>
                <td>
                    <span class="badge {{ $subject->is_active ? 'badge-success' : 'badge-error' }}">
                        {{ $subject->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td style="display:flex;gap:6px">
                    <a href="{{ route('subjects.show', $subject) }}" class="btn btn-sm btn-indigo">View</a>
                    <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-sm btn-ghost">Edit</a>
                    <form method="POST" action="{{ route('subjects.toggle', $subject) }}" style="display:inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm {{ $subject->is_active ? 'btn-warning' : 'btn-success' }}">
                            {{ $subject->is_active ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
    @else
    <div class="empty-state">
        <h3>No subjects found</h3>
        <p>Add subjects to assign them to classes.</p>
        <a href="{{ route('subjects.create') }}" class="btn btn-primary" style="margin-top:16px">Add First Subject</a>
    </div>
    @endif
</div>
@endsection
