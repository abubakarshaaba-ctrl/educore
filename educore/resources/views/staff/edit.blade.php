@extends('layouts.app')
@section('title','Edit Staff')
@section('page-title','Staff Management')

@push('styles')
<style>
.form-page{width:100%}
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.card-header{padding:14px 24px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:600;color:var(--midnight)}
.card-body{padding:24px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-label{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em}
.form-label span{color:var(--crimson)}
.form-control{padding:10px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms;width:100%}
.form-control:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white}
.is-invalid{border-color:var(--crimson)!important}
.invalid-feedback{font-size:12px;color:var(--crimson);margin-top:2px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.hint{font-size:11px;color:var(--slate-light);margin-top:3px}
.toggle-row{display:flex;align-items:center;gap:10px}
.toggle{position:relative;display:inline-block;width:44px;height:24px}
.toggle input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;inset:0;background:#CBD5E1;border-radius:24px;transition:.3s}
.slider::before{content:"";position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.3s}
input:checked+.slider{background:var(--indigo)}
input:checked+.slider::before{transform:translateX(20px)}
@media(max-width:768px){.form-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="form-page">
    <div class="breadcrumb">
        <a href="{{ route('staff.index') }}">Staff</a>
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        Edit — {{ $staff->name }}
    </div>

    @if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif
    @if(session('success'))<div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px">✓ {{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header">Edit Staff — {{ $staff->name }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('staff.update', $staff) }}">
                @csrf @method('PUT')
                <div class="form-grid" style="margin-bottom:16px">
                    <div class="form-group">
                        <label class="form-label">Full Name <span>*</span></label>
                        <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid':'' }}"
                               value="{{ old('name', $staff->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address <span>*</span></label>
                        <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid':'' }}"
                               value="{{ old('email', $staff->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Staff ID</label>
                        <input type="text" name="staff_id" class="form-control"
                               value="{{ old('staff_id', $staff->staff_id) }}" placeholder="e.g. STF1001">
                        <div class="hint">Used for login.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $staff->phone) }}" placeholder="08012345678">
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Role <span>*</span></label>
                        @include('staff._role_select', ['selected' => old('role', $staff->role)])
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="hint">Role changes do not alter employment history. Use Work History for appointment, promotion, or reassignment records.</div>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Employment Start Date</label>
                        <div class="form-control" style="color:var(--slate);background:#F1F5F9">
                            {{ optional($staff->employment_started_at)->format('d M Y') ?? 'Not recorded' }}
                        </div>
                        <div class="hint">
                            Employment dates are controlled through lifecycle and work-history workflows.
                            @can('staff.work-history.manage')
                                <a href="{{ route('staff.work-history.index', $staff) }}" style="color:var(--indigo)">Open work history</a>
                            @endcan
                        </div>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Employment Status</label>
                        <div class="toggle-row">
                            <label class="toggle">
                                <input type="checkbox" disabled {{ $staff->is_active ? 'checked':'' }}>
                                <span class="slider"></span>
                            </label>
                            <span style="font-size:13px;color:var(--slate)">
                                {{ $staff->is_active ? 'Active — Staff can log in' : 'Inactive — Login disabled' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:12px">
                    <button type="submit" class="btn btn-primary">✓ Save Changes</button>
                    <a href="{{ route('staff.show', $staff) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
