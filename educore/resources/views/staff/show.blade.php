@extends('layouts.app')
@section('title', 'Staff Profile')
@section('page-title', 'Staff Management')

@push('styles')
<style>
    .breadcrumb { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px; }
    .breadcrumb a { color:var(--indigo);text-decoration:none;font-weight:500; }
    .breadcrumb svg { width:14px;height:14px; }
    .profile-grid { display:grid;grid-template-columns:280px 1fr;gap:16px; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:14px; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#F8FAFC; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }
    .profile-hero { padding:28px 20px;text-align:center;border-bottom:1px solid var(--border); }
    .big-avatar { width:64px;height:64px;border-radius:50%;background:var(--indigo);color:white;font-size:24px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px; }
    .profile-name { font-size:16px;font-weight:700;color:var(--midnight); }
    .profile-email { font-size:12px;color:var(--slate-light);margin-top:3px; }
    .info-row { display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px; }
    .info-row:last-child { border-bottom:none; }
    .info-key { color:var(--slate);font-weight:500; }
    .info-val { color:var(--midnight);font-weight:600; }
    .role-badge { display:inline-flex;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px; }
    .role-admin        { background:var(--indigo-bg);color:var(--indigo); }
    .role-teacher      { background:#ECFDF5;color:var(--emerald); }
    .role-principal    { background:#F5F3FF;color:#7C3AED; }
    .role-vice_principal { background:#FDF4FF;color:#A21CAF; }
    .role-form_teacher { background:#ECFDF5;color:var(--emerald); }
    .role-subject_teacher { background:#FFFBEB;color:var(--amber); }
    .role-accountant   { background:#FFFBEB;color:var(--amber); }
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-label span { color:var(--crimson); }
    .form-control { width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .btn { display:inline-flex;align-items:center;gap:5px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .btn-danger { background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    @media(max-width:1024px) { .profile-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="breadcrumb">
    <a href="{{ route('staff.index') }}">Staff</a>
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    {{ $staff->name }}
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="profile-grid">
    <div>
        <div class="card">
            <div class="profile-hero">
                <div class="big-avatar">{{ strtoupper(substr($staff->name, 0, 1)) }}</div>
                <div class="profile-name">{{ $staff->name }}</div>
                <div class="profile-email">{{ $staff->email }}</div>
                <div style="margin-top:10px">
                    <span class="role-badge role-{{ $staff->roleKey() }}">{{ $staff->roleLabel() }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="info-row"><span class="info-key">Status</span>
                    <span class="info-val" style="color:{{ $staff->is_active ? 'var(--emerald)' : 'var(--slate)' }}">
                        {{ $staff->employmentStatusLabel() }}
                    </span>
                </div>
                <div class="info-row"><span class="info-key">Login</span>
                    <span class="info-val">{{ $staff->is_active ? 'Enabled' : 'Disabled' }}</span>
                </div>
                <div class="info-row"><span class="info-key">Current Position</span>
                    <span class="info-val">{{ optional($staff->currentWorkHistory)->position_title ?: '-' }}</span>
                </div>
                <div class="info-row"><span class="info-key">Joined</span><span class="info-val">{{ $staff->created_at->format('d M Y') }}</span></div>
            </div>
            <div style="padding:12px 20px;border-top:1px solid var(--border)">
                @if(auth()->user()->canManage('staff'))
                <a href="{{ route('staff.permissions', $staff) }}"
                   style="display:flex;align-items:center;justify-content:center;gap:6px;padding:9px;background:#EFF6FF;color:#2563EB;border:1px solid #BFDBFE;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;margin-bottom:8px">
                    🔐 Manage Permissions
                </a>
                @endif
                @can('staff.status.view')
                <a href="{{ route('staff.status.show', $staff) }}" class="btn btn-primary" style="width:100%;justify-content:center;margin-bottom:8px">
                    Manage Lifecycle Status
                </a>
                @endcan
                @can('staff.work-history.view')
                <a href="{{ route('staff.work-history.index', $staff) }}" class="btn btn-ghost" style="width:100%;justify-content:center">
                    Work History
                </a>
                @endcan
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Edit Details</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('staff.update', $staff) }}">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label class="form-label">Full Name <span>*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $staff->name) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span>*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $staff->email) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span>*</span></label>
                        @include('staff._role_select', ['selected' => old('role', $staff->role)])
                    </div>
                    <div style="display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('staff.index') }}" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Reset Password</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('staff.reset-password', $staff) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">New Password <span>*</span></label>
                        <input type="text" name="password" class="form-control" placeholder="Minimum 8 characters">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password <span>*</span></label>
                        <input type="text" name="password_confirmation" class="form-control" placeholder="Repeat password">
                    </div>
                    <button type="submit" class="btn btn-danger">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
