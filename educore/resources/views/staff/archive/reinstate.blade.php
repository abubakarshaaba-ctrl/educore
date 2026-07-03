@extends('layouts.app')
@section('title', 'Reinstate Staff')
@section('page-title', 'Reinstate Staff')

@push('styles')
<style>
.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;width:100%}
.card-header{padding:14px 18px;background:#F8FAFC;border-bottom:1px solid var(--border);font-weight:700;color:var(--midnight)}
.card-body{padding:18px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate)}
.control{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:13px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}
.btn-primary{background:var(--indigo);color:#fff;border-color:var(--indigo)}
.btn-ghost{background:#fff;color:var(--midnight)}
.alert{padding:12px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:var(--crimson)}
.alert-info{background:#EFF6FF;border:1px solid #BFDBFE;color:#1D4ED8}
@media(max-width:768px){.grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div style="margin-bottom:18px">
    <a href="{{ route('staff.archive.show', $staff) }}" style="font-size:13px;color:var(--indigo);text-decoration:none">Back to archived profile</a>
    <h1 style="font-size:20px;font-weight:800;color:var(--midnight);margin-top:6px">Reinstate {{ $staff->name }}</h1>
    <div style="font-size:13px;color:var(--slate)">Current status: {{ $staff->employmentStatusLabel() }}</div>
</div>

@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card">
    <div class="card-header">New Employment Period</div>
    <div class="card-body">
        <div class="alert alert-info">
            Reinstatement takes effect immediately. It enables login and creates a new work-history period. Old work-history rows are not reopened.
        </div>
        <form method="POST" action="{{ route('staff.reinstate', $staff) }}" enctype="multipart/form-data">
            @csrf
            <div class="grid">
                <div class="field">
                    <label class="label">Effective Date</label>
                    <input type="date" name="effective_date" class="control" value="{{ old('effective_date', now()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label class="label">Position Title</label>
                    <input type="text" name="position_title" class="control" value="{{ old('position_title') }}" required>
                </div>
                <div class="field">
                    <label class="label">Department</label>
                    <input type="text" name="department_name" class="control" value="{{ old('department_name') }}">
                </div>
                <div class="field">
                    <label class="label">Employment Type</label>
                    <input type="text" name="employment_type" class="control" value="{{ old('employment_type') }}">
                </div>
                <div class="field">
                    <label class="label">Functional Role</label>
                    <input type="text" name="functional_role" class="control" value="{{ old('functional_role') }}">
                </div>
                <div class="field">
                    <label class="label">Grade Level</label>
                    <input type="text" name="grade_level" class="control" value="{{ old('grade_level') }}">
                </div>
            </div>
            <div class="field">
                <label class="label">Appointment Type</label>
                <input type="text" name="appointment_type" class="control" value="{{ old('appointment_type') }}">
            </div>
            <div class="field">
                <label class="label">Reason</label>
                <textarea name="reason" class="control" rows="4" required>{{ old('reason') }}</textarea>
            </div>
            <div class="field">
                <label class="label">Supporting Document</label>
                <input type="file" name="document" class="control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            </div>
            <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px;color:var(--slate);margin-bottom:14px">
                <input type="checkbox" name="confirmation" value="1" required>
                I understand this immediately reactivates the staff account and creates a new employment period.
            </label>
            <button type="submit" class="btn btn-primary">Reinstate Staff</button>
            <a href="{{ route('staff.archive.show', $staff) }}" class="btn btn-ghost">Cancel</a>
        </form>
    </div>
</div>
@endsection
