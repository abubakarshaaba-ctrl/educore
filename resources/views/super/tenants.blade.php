@extends('layouts.super')
@section('title', 'Manage Schools')
@section('page-title', 'Schools Management')

@push('styles')
<style>
    .page-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:20px; }
    .page-header h1 { font-size:20px;font-weight:700;color:var(--midnight);letter-spacing:-0.02em; }
    .filters { background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end; }
    .filter-group { display:flex;flex-direction:column;gap:5px; }
    .filter-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .filter-control { padding:8px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;min-width:180px; }
    .btn { display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .btn-sm { padding:5px 10px;font-size:11px; }
    .btn-success { background:var(--emerald);color:white; }
    .btn-warning { background:var(--amber);color:white; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .badge { display:inline-flex;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px; }
    .badge-active    { background:#ECFDF5;color:var(--emerald); }
    .badge-suspended { background:#FEF2F2;color:var(--crimson); }
    .badge-expired   { background:#FFFBEB;color:var(--amber); }
    .badge-pending   { background:#EFF6FF;color:var(--indigo); }
    .action-group { display:flex;gap:6px;flex-wrap:wrap; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="page-header">
    <h1>All Schools ({{ $tenants->total() }})</h1>
    <a href="{{ route('super.tenants.create') }}" class="btn btn-primary">+ Provision New School</a>
</div>

<form method="GET">
    <div class="filters">
        <div class="filter-group">
            <span class="filter-label">Search</span>
            <input type="text" name="search" class="filter-control" placeholder="School name..." value="{{ request('search') }}">
        </div>
        <div class="filter-group">
            <span class="filter-label">Status</span>
            <select name="status" class="filter-control">
                <option value="">All</option>
                <option value="active"               {{ request('status') === 'active'               ? 'selected' : '' }}>Active</option>
                <option value="pending"              {{ request('status') === 'pending'              ? 'selected' : '' }}>Pending</option>
                <option value="suspended"            {{ request('status') === 'suspended'            ? 'selected' : '' }}>Suspended</option>
                <option value="subscription_expired" {{ request('status') === 'subscription_expired' ? 'selected' : '' }}>Expired</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>

<div class="card">
    <div class="tbl"><table>
        <thead>
            <tr><th>School</th><th>Students</th><th>Users</th><th>Status</th><th>Expires</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach($tenants as $tenant)
            <tr>
                <td>
                    <strong>{{ $tenant->name }}</strong>
                    <div style="font-size:11px;color:var(--slate-light)">{{ $tenant->email }}</div>
                </td>
                <td>{{ $tenant->students_count }}</td>
                <td>{{ $tenant->users_count }}</td>
                <td>
                    <span class="badge badge-{{ $tenant->status === 'active' ? 'active' : ($tenant->status === 'suspended' ? 'suspended' : ($tenant->status === 'pending' ? 'pending' : 'expired')) }}">
                        {{ ucfirst(str_replace('_',' ',$tenant->status)) }}
                    </span>
                </td>
                <td style="font-size:12px;color:{{ optional($tenant->subscription_expires_at)->isPast() ? 'var(--crimson)' : 'var(--slate)' }}">
                    {{ optional($tenant->subscription_expires_at)->format('d M Y') ?? '—' }}
                </td>
                <td>
                    <div class="action-group">
                        <a href="{{ route('super.tenant.edit', $tenant) }}" class="btn btn-sm btn-ghost">Edit</a>
                        <form method="POST" action="{{ route('super.impersonate', $tenant) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">Login As</button>
                        </form>
                        <form method="POST" action="{{ route('super.tenant.toggle', $tenant) }}" style="display:inline">
                            @csrf @method('PATCH')
                            @if($tenant->status === 'active')
                                <input type="hidden" name="status" value="suspended">
                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Suspend this school?')">Suspend</button>
                            @else
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="btn btn-sm btn-success">Activate</button>
                            @endif
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
</div>
{{ $tenants->links() }}
@endsection
