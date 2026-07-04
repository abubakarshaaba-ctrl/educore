@extends('layouts.app')
@section('title', 'Staff Management')
@section('page-title', 'Staff Management')

@push('styles')
<style>
    .page-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:20px; }
    .page-header h1 { font-size:20px;font-weight:700;color:var(--midnight);letter-spacing:-0.02em; }
    .filters { background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end; }
    .filter-group { display:flex;flex-direction:column;gap:5px; }
    .filter-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .filter-control { padding:8px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;min-width:180px; }
    .filter-control:focus { border-color:var(--indigo); }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .btn-sm { padding:5px 10px;font-size:12px; }
    .btn-indigo { background:var(--indigo-bg);color:var(--indigo); }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .staff-avatar { width:32px;height:32px;border-radius:50%;background:var(--indigo);color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0; }
    .staff-cell { display:flex;align-items:center;gap:10px; }
    .staff-name { font-weight:600; }
    .staff-email { font-size:11px;color:var(--slate-light); }
    .role-badge { display:inline-flex;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px; }
    .role-admin                 { background:#EFF6FF;color:#2563EB; }
    .role-admission_officer     { background:#F5F3FF;color:#7C3AED; }
    .role-principal             { background:#F0FDF4;color:#059669; }
    .role-vice_principal        { background:#F0FDF4;color:#059669; }
    .role-form_teacher          { background:#F5F3FF;color:#7C3AED; }
    .role-asst_form_teacher     { background:#F5F3FF;color:#7C3AED; }
    .role-subject_teacher       { background:#F5F3FF;color:#7C3AED; }
    .role-form_subject_teacher  { background:#F5F3FF;color:#7C3AED; }
    .role-accountant            { background:#FFFBEB;color:#D97706; }
    .role-health_officer        { background:#FEF2F2;color:#DC2626; }
    .role-librarian             { background:#ECFEFF;color:#0891B2; }
    .role-transport_officer     { background:#F7FEE7;color:#65A30D; }
    .role-communication_officer { background:#FDF2F8;color:#DB2777; }
    .role-driver                { background:#F7FEE7;color:#65A30D; }
    .role-bus_assistant         { background:#F7FEE7;color:#65A30D; }
    .role-teacher               { background:#F5F3FF;color:#7C3AED; }
    .status-dot { width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px; }
    .dot-active { background:var(--emerald); }
    .dot-inactive { background:#CBD5E1; }
    .empty-state { text-align:center;padding:50px;color:var(--slate-light); }
    .empty-state h3 { font-size:15px;font-weight:600;color:var(--slate);margin-bottom:6px; }
</style>
@endpush

@section('content')

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="page-header">
    <h1>Staff ({{ $staff->total() }})</h1>
    <div style="display:flex;gap:8px;align-items:center">
        @can('staff.archive.view')
        <a href="{{ route('staff.archive.index') }}" class="btn btn-ghost">
            Staff Archive
        </a>
        @endcan
        <a href="{{ route('staff.bulk-upload.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;background:#059669;color:white;border-radius:8px;text-decoration:none">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
            Bulk Upload
        </a>
        <a href="{{ route('staff.create') }}" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Add Staff
        </a>
    </div>
</div>

<form method="GET">
    <div class="filters">
        <div class="filter-group">
            <span class="filter-label">Search</span>
            <input type="text" name="search" class="filter-control" placeholder="Name or email..." value="{{ request('search') }}">
        </div>
        <div class="filter-group">
            <span class="filter-label">Role</span>
            <select name="role" class="filter-control">
                <option value="">All Roles</option>
                <optgroup label="Administration">
                    <option value="admin"             {{ request('role')==='admin'             ?'selected':'' }}>Admin</option>
                    <option value="principal"         {{ request('role')==='principal'         ?'selected':'' }}>Principal</option>
                    <option value="vice_principal"    {{ request('role')==='vice_principal'    ?'selected':'' }}>Vice Principal</option>
                    <option value="admission_officer" {{ request('role')==='admission_officer' ?'selected':'' }}>Admission Officer</option>
                </optgroup>
                <optgroup label="Teaching">
                    <option value="form_teacher"        {{ request('role')==='form_teacher'        ?'selected':'' }}>Form Teacher</option>
                    <option value="asst_form_teacher"   {{ request('role')==='asst_form_teacher'   ?'selected':'' }}>Asst. Form Teacher</option>
                    <option value="subject_teacher"     {{ request('role')==='subject_teacher'     ?'selected':'' }}>Subject Teacher</option>
                    <option value="form_subject_teacher"{{ request('role')==='form_subject_teacher'?'selected':'' }}>Form & Subject Teacher</option>
                </optgroup>
                <optgroup label="Finance & Support">
                    <option value="accountant"            {{ request('role')==='accountant'            ?'selected':'' }}>Accountant</option>
                    <option value="health_officer"        {{ request('role')==='health_officer'        ?'selected':'' }}>Health Officer</option>
                    <option value="librarian"             {{ request('role')==='librarian'             ?'selected':'' }}>Librarian</option>
                    <option value="transport_officer"     {{ request('role')==='transport_officer'     ?'selected':'' }}>Transport Officer</option>
                    <option value="communication_officer" {{ request('role')==='communication_officer' ?'selected':'' }}>Communication Officer</option>
                </optgroup>
                <optgroup label="Transport">
                    <option value="driver"        {{ request('role')==='driver'        ?'selected':'' }}>Driver</option>
                    <option value="bus_assistant" {{ request('role')==='bus_assistant' ?'selected':'' }}>Bus Assistant</option>
                </optgroup>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        @if(request()->hasAny(['search','role']))
            <a href="{{ route('staff.index') }}" class="btn btn-ghost">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    @if($staff->count())
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>Staff Member</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($staff as $member)
            <tr>
                <td>
                    <div class="staff-cell">
                        <div class="staff-avatar">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                        <div>
                            <div class="staff-name">{{ $member->name }}</div>
                            <div class="staff-email">{{ $member->email }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="role-badge role-{{ $member->role }}">{{ $member->roleLabel() }}</span>
                </td>
                <td>
                    <span class="status-dot {{ $member->is_active ? 'dot-active' : 'dot-inactive' }}"></span>
                    {{ $member->employmentStatusLabel() }}
                </td>
                <td style="font-size:12px;color:var(--slate)">{{ $member->created_at->format('d M Y') }}</td>
                <td>
                    <a href="{{ route('staff.show', $member) }}" class="btn btn-sm btn-indigo">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
    @else
    <div class="empty-state">
        <h3>No staff found</h3>
        <p>Add your first staff member to get started.</p>
    </div>
    @endif
</div>

{{-- ── Role Access Matrix ─────────────────────────────────────────── --}}
@if(auth()->user()->isAdmin() || auth()->user()->isPrincipal())
<div style="margin-top:28px;padding-bottom:10px">
    <div style="font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px">
        🔐 Staff Role Access Matrix
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
    @foreach($roleLabels as $role => $label)
    @if(!in_array($role, ['student','parent']))
    @php
        $access = $roleAccess[$role] ?? [];
        $isFull = in_array('*', $access);
        $modules = $isFull ? ['Full system access'] : array_slice($access, 0, 5);
        $colorMap = [
            'admin'                 => ['#2563EB','#EFF6FF'],
            'admission_officer'     => ['#7C3AED','#F5F3FF'],
            'principal'             => ['#059669','#F0FDF4'],
            'vice_principal'        => ['#059669','#F0FDF4'],
            'form_teacher'          => ['#7C3AED','#F5F3FF'],
            'asst_form_teacher'     => ['#7C3AED','#F5F3FF'],
            'subject_teacher'       => ['#7C3AED','#F5F3FF'],
            'form_subject_teacher'  => ['#6D28D9','#F5F3FF'],
            'accountant'            => ['#D97706','#FFFBEB'],
            'health_officer'        => ['#DC2626','#FEF2F2'],
            'librarian'             => ['#0891B2','#ECFEFF'],
            'transport_officer'     => ['#65A30D','#F7FEE7'],
            'communication_officer' => ['#DB2777','#FDF2F8'],
            'driver'                => ['#78716C','#F5F5F4'],
            'bus_assistant'         => ['#78716C','#F5F5F4'],
        ];
        [$clr,$bg] = $colorMap[$role] ?? ['#64748B','#F1F5F9'];
    @endphp
    <div style="background:white;border:1px solid var(--border);border-radius:10px;overflow:hidden">
        <div style="background:{{ $bg }};padding:9px 12px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
            <div style="width:8px;height:8px;border-radius:50%;background:{{ $clr }};flex-shrink:0"></div>
            <span style="font-size:12px;font-weight:700;color:{{ $clr }}">{{ $label }}</span>
        </div>
        <div style="padding:8px 12px">
            @foreach($modules as $mod)
            <div style="font-size:11px;color:var(--slate);padding:2px 0;text-transform:capitalize">• {{ $isFull ? $mod : str_replace('_',' ',$mod) }}</div>
            @endforeach
            @if(!$isFull && count($access) > 5)
            <div style="font-size:10px;color:var(--slate-light);margin-top:2px">+{{ count($access)-5 }} more</div>
            @endif
        </div>
    </div>
    @endif
    @endforeach
    </div>
</div>
@endif
@endsection
