@extends('layouts.app')
@section('title', 'Attendance Report')
@section('page-title', 'Attendance')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .filter-card { background:white;border:1px solid var(--border);border-radius:10px;padding:14px 20px;margin-bottom:20px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap; }
    .filter-group { display:flex;flex-direction:column;gap:5px; }
    .filter-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .filter-control { padding:8px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;min-width:200px; }
    .filter-control:focus { border-color:var(--indigo); }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .student-name { font-weight:600; }
    .rate-bar-wrap { width:80px;height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-left:8px; }
    .rate-bar { height:100%;border-radius:3px; }
    .badge { display:inline-flex;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px; }
    .badge-success { background:#ECFDF5;color:var(--emerald); }
    .badge-warning { background:#FFFBEB;color:var(--amber); }
    .badge-error   { background:#FEF2F2;color:var(--crimson); }
    .empty-state { text-align:center;padding:50px 20px;color:var(--slate-light); }
    .empty-state h3 { font-size:15px;font-weight:600;color:var(--slate);margin-bottom:6px; }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('attendance.index') }}" class="page-tab">Mark Attendance</a>
    <a href="{{ route('attendance.report') }}" class="page-tab active">Reports</a>
</div>

<form method="GET">
    <div class="filter-card">
        <div class="filter-group">
            <span class="filter-label">Class</span>
            <select name="class_arm_id" class="filter-control" required>
                <option value="">Select class</option>
                @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}" {{ request('class_arm_id') == $arm->id ? 'selected' : '' }}>
                        {{ $arm->classLevel->name }} {{ $arm->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Term</span>
            <select name="term_id" class="filter-control" required>
                <option value="">Select term</option>
                @foreach($terms as $term)
                    <option value="{{ $term->id }}" {{ request('term_id') ? (request('term_id') == $term->id ? 'selected' : '') : ($term->is_current ? 'selected' : '') }}>
                        {{ $term->name }} — {{ $term->session->name ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Generate Report</button>
    </div>
</form>

@if(isset($summary))
<div class="card">
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>School Days</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
                <th>Excused</th>
                <th>Attendance Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $i => $row)
            <tr>
                <td style="color:var(--slate-light);font-size:12px">{{ $i + 1 }}</td>
                <td><div class="student-name">{{ $row['student']->full_name }}</div></td>
                <td>{{ $row['school_days'] }}</td>
                <td style="color:var(--emerald);font-weight:600">{{ $row['present'] }}</td>
                <td style="color:var(--crimson);font-weight:600">{{ $row['absent'] }}</td>
                <td style="color:var(--amber);font-weight:600">{{ $row['late'] }}</td>
                <td style="color:var(--indigo);font-weight:600">{{ $row['excused'] }}</td>
                <td>
                    <span style="font-weight:700;color:{{ $row['rate'] >= 80 ? 'var(--emerald)' : ($row['rate'] >= 60 ? 'var(--amber)' : 'var(--crimson)') }}">
                        {{ $row['rate'] }}%
                    </span>
                    <span class="rate-bar-wrap">
                        <span class="rate-bar" style="width:{{ $row['rate'] }}%;background:{{ $row['rate'] >= 80 ? 'var(--emerald)' : ($row['rate'] >= 60 ? 'var(--amber)' : 'var(--crimson)') }}"></span>
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
</div>
@else
<div class="empty-state">
    <h3>Select a class and term</h3>
    <p>Choose a class and term above to generate the attendance report.</p>
</div>
@endif
@endsection
