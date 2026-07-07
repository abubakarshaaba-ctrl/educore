@extends('layouts.app')
@section('title','Add Staff')
@section('page-title','Staff Management')

@push('styles')
<style>
.form-page{width:100%}
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
.card{background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden}
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
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms}
.btn-primary{background:var(--indigo);color:white}.btn-primary:hover{background:#1D4ED8}
.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.hint{font-size:11px;color:var(--slate-light);margin-top:3px}
@media(max-width:768px){.form-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="pg-2col-sm">
<div class="form-page">
    <div class="breadcrumb">
        <a href="{{ route('staff.index') }}">Staff</a>
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        Add New Staff
    </div>

    @if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

    <div class="card">
        <div class="card-header">New Staff Account</div>
        <div class="card-body">
            <form method="POST" action="{{ route('staff.store') }}">
                @csrf
                <div class="form-grid" style="margin-bottom:16px">
                    <div class="form-group">
                        <label class="form-label">Full Name <span>*</span></label>
                        <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid':'' }}"
                               value="{{ old('name') }}" placeholder="Full legal name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address <span>*</span></label>
                        <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid':'' }}"
                               value="{{ old('email') }}" placeholder="staff@school.ng" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Staff ID</label>
                        <input type="text" name="staff_id" class="form-control" value="{{ old('staff_id') }}"
                               placeholder="Auto-generated if blank (e.g. STF1001)">
                        <div class="hint">Used for login. Leave blank to auto-assign.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="08012345678">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Role <span>*</span></label>
                        @include('staff._role_select', ['selected' => old('role', '')])
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employment Start Date <span>*</span></label>
                        <input type="date" name="employment_started_at" class="form-control {{ $errors->has('employment_started_at') ? 'is-invalid':'' }}"
                               value="{{ old('employment_started_at') }}" max="{{ now()->toDateString() }}" required>
                        @error('employment_started_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position Title <span>*</span></label>
                        <input type="text" name="position_title" class="form-control {{ $errors->has('position_title') ? 'is-invalid':'' }}"
                               value="{{ old('position_title') }}" placeholder="e.g. Mathematics Teacher" required>
                        @error('position_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department_name" class="form-control" value="{{ old('department_name') }}" placeholder="e.g. Academics">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employment Type</label>
                        <input type="text" name="employment_type" class="form-control" value="{{ old('employment_type') }}" placeholder="e.g. Full-time">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Functional Role</label>
                        <input type="text" name="functional_role" class="form-control" value="{{ old('functional_role') }}" placeholder="e.g. Class Teacher">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grade Level</label>
                        <input type="text" name="grade_level" class="form-control" value="{{ old('grade_level') }}" placeholder="Optional">
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Appointment Type</label>
                        <input type="text" name="appointment_type" class="form-control" value="{{ old('appointment_type') }}" placeholder="e.g. Initial appointment">
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Password <span>*</span></label>
                        <input type="text" name="password" class="form-control"
                               value="{{ old('password', 'Staff@2025!') }}" placeholder="Minimum 8 characters" required>
                        <div class="hint">Staff should change this password after first login.</div>
                    </div>
                </div>
                <div style="display:flex;gap:12px">
                    <button type="submit" class="btn btn-primary">✓ Create Staff Account</button>
                    <a href="{{ route('staff.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>{{-- /form side --}}
<div>{{-- right tips panel --}}
<div style="background:white;border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:14px">
    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--slate);margin-bottom:12px">Staff Account Tips</div>
    @foreach([
        ['Role','Assign the correct role to control module access permissions'],
        ['Staff ID','Leave blank to auto-generate (e.g. STF1001)'],
        ['Password','Staff should change this after first login'],
        ['Email','Used for notifications and login recovery'],
    ] as [$h,$t])
    <div style="margin-bottom:10px">
        <div style="font-size:11.5px;font-weight:700;color:var(--midnight);margin-bottom:2px">{{ $h }}</div>
        <div style="font-size:12px;color:var(--slate-light);line-height:1.4">{{ $t }}</div>
    </div>
    @endforeach
</div>
<div style="background:var(--indigo-bg);border:1px solid #BFDBFE;border-radius:10px;padding:14px 16px">
    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--indigo);margin-bottom:6px">Bulk Upload</div>
    <div style="font-size:12px;color:var(--midnight);line-height:1.5;margin-bottom:10px">Need to add many staff at once? Use the bulk upload feature.</div>
    <a href="{{ route('staff.bulk-upload.index') }}" style="display:block;padding:8px 12px;background:var(--indigo);color:white;border-radius:7px;text-decoration:none;font-size:12px;font-weight:700;text-align:center">Bulk Upload Staff</a>
</div>
</div>{{-- /tips panel --}}
</div>{{-- /grid --}}
@endsection
