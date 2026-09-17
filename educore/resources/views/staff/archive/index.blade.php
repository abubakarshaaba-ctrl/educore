@extends('layouts.app')
@section('title', 'Staff Archive')
@section('page-title', 'Staff Archive')

@push('styles')
<style>
.page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px;min-width:0}.page-head>div{min-width:0}.page-head h1,.page-head div{overflow-wrap:anywhere}.page-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:16px}.summary-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:14px;min-width:0}.summary-num{font-size:22px;font-weight:800;color:var(--midnight)}.summary-label{font-size:12px;color:var(--slate);margin-top:4px;overflow-wrap:anywhere}.filters{background:#fff;border:1px solid var(--border);border-radius:10px;padding:14px;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:16px;align-items:end}.control{width:100%;min-width:0;max-width:100%;box-sizing:border-box;padding:9px 11px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:13px}.filter-actions{display:flex;gap:8px;grid-column:1/-1}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 14px;min-height:40px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}.btn-primary{background:var(--indigo);color:#fff;border-color:var(--indigo)}.btn-ghost{background:#fff;color:var(--midnight)}.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;min-width:0}.tbl{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}table{width:100%;min-width:680px;border-collapse:collapse}th{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--slate);background:#F8FAFC;text-align:left;padding:10px 14px;border-bottom:1px solid var(--border);white-space:nowrap}td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle}td:first-child{min-width:190px}td strong,td span{overflow-wrap:anywhere}.badge{display:inline-flex;max-width:100%;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:#F1F5F9;color:var(--slate);overflow-wrap:anywhere}.pagination-wrap{margin-top:14px;overflow-x:auto;-webkit-overflow-scrolling:touch}
@media(max-width:1000px){.filters{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:700px){.page-head{flex-direction:column}.page-actions{width:100%;display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.page-actions .btn{width:100%}.summary{grid-template-columns:repeat(2,minmax(0,1fr))}.filters{grid-template-columns:1fr}.filter-actions{grid-column:auto;display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.filter-actions .btn{width:100%}table{min-width:610px}th,td{padding:9px 11px;font-size:11px}}
@media(max-width:380px){.page-actions,.summary,.filter-actions{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <h1 style="font-size:20px;font-weight:800;color:var(--midnight)">Staff Archive</h1>
        <div style="font-size:13px;color:var(--slate)">Left, resigned and terminated staff records remain searchable here.</div>
    </div>
    <div class="page-actions">
        @can('staff.archive.export')
            <a href="{{ route('staff.archive.export', request()->query()) }}" class="btn btn-ghost">Export CSV</a>
        @endcan
        <a href="{{ route('staff.index') }}" class="btn btn-primary">Active Staff</a>
    </div>
</div>

<div class="summary">
    @foreach($staffArchiveStatuses as $status)
    <div class="summary-card">
        <div class="summary-num">{{ $summary[$status] ?? 0 }}</div>
        <div class="summary-label">{{ $statusLabels[$status] ?? ucfirst($status) }}</div>
    </div>
    @endforeach
</div>

<form method="GET" class="filters">
    <input type="text" name="search" class="control" placeholder="Name, email or staff ID" value="{{ request('search') }}">
    <select name="status" class="control">
        <option value="">All statuses</option>
        @foreach($staffArchiveStatuses as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
        @endforeach
    </select>
    <select name="role" class="control">
        <option value="">All roles</option>
        @foreach($staffRoles as $role)
            <option value="{{ $role }}" @selected(request('role') === $role)>{{ $roleLabels[$role] ?? ucfirst(str_replace('_', ' ', $role)) }}</option>
        @endforeach
    </select>
    <input type="date" name="exit_from" class="control" value="{{ request('exit_from') }}">
    <input type="date" name="exit_to" class="control" value="{{ request('exit_to') }}">
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Filter</button>
        <a href="{{ route('staff.archive.index') }}" class="btn btn-ghost">Clear</a>
    </div>
</form>

<div class="card">
    <div class="tbl"><table>
        <thead>
            <tr><th>Staff</th><th>Role</th><th>Status</th><th>Employment Ended</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($staff as $member)
            <tr>
                <td><strong>{{ $member->name }}</strong><br><span style="font-size:12px;color:var(--slate)">{{ $member->staff_id ?: $member->email }}</span></td>
                <td>{{ $member->roleLabel() }}</td>
                <td><span class="badge">{{ $member->employmentStatusLabel() }}</span></td>
                <td>{{ optional($member->employment_ended_at)->format('d M Y') ?: '-' }}</td>
                <td><a href="{{ route('staff.archive.show', $member) }}" class="btn btn-ghost">View</a></td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:var(--slate);padding:32px">No archived staff found.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>

<div class="pagination-wrap">{{ $staff->links() }}</div>
@endsection
