@extends('layouts.app')
@section('title', 'Staff Work History')
@section('page-title', 'Staff Work History')

@push('styles')
<style>
.grid{display:grid;grid-template-columns:1fr 360px;gap:16px}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-header{padding:14px 18px;background:#F8FAFC;border-bottom:1px solid var(--border);font-weight:700;color:var(--midnight)}
.card-body{padding:18px}
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
.label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate)}
.control{width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:13px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}
.btn-primary{background:var(--indigo);color:#fff;border-color:var(--indigo)}
.btn-ghost{background:#fff;color:var(--midnight)}
.timeline{display:grid;gap:10px}
.timeline-item{border:1px solid var(--border);border-radius:10px;padding:12px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:var(--crimson);border-radius:8px;padding:12px 14px;margin-bottom:14px}
@media(max-width:980px){.grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:18px">
    <div>
        <a href="{{ route('staff.show', $staff) }}" style="font-size:13px;color:var(--indigo);text-decoration:none">Back to staff profile</a>
        <h1 style="font-size:20px;font-weight:800;color:var(--midnight);margin-top:6px">{{ $staff->name }} Work History</h1>
        <div style="font-size:13px;color:var(--slate)">Current status: {{ $staff->employmentStatusLabel() }}</div>
    </div>
</div>

@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif
@if(session('success'))<div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 14px;color:#047857;margin-bottom:14px">{{ session('success') }}</div>@endif

<div class="grid">
    <div class="card">
        <div class="card-header">Employment Timeline</div>
        <div class="card-body">
            <div class="timeline">
                @forelse($histories as $history)
                <div class="timeline-item">
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <strong>{{ $history->position_title ?: ucfirst(str_replace('_', ' ', $history->change_type)) }}</strong>
                        <a href="{{ route('staff.work-history.show', $history) }}" class="btn btn-ghost">View</a>
                    </div>
                    <div style="font-size:12px;color:var(--slate);margin-top:4px">
                        {{ optional($history->start_date)->format('d M Y') }} - {{ optional($history->end_date)->format('d M Y') ?: 'Current' }}
                    </div>
                    <div style="font-size:13px;color:var(--slate);margin-top:6px">{{ $history->department_name ?: 'No department recorded' }}</div>
                </div>
                @empty
                <div style="font-size:13px;color:var(--slate)">No work-history records exist for this staff member.</div>
                @endforelse
            </div>
        </div>
    </div>

    @can('staff.work-history.manage')
    <div class="card">
        <div class="card-header">Record Work-History Change</div>
        <div class="card-body">
            <form method="POST" action="{{ route('staff.work-history.store', $staff) }}" enctype="multipart/form-data">
                @csrf
                <div class="field">
                    <label class="label">Change Type</label>
                    <select name="change_type" class="control" required>
                        <option value="">Select...</option>
                        @foreach($changeTypes as $type)
                            <option value="{{ $type }}" @selected(old('change_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label">Start Date</label>
                    <input type="date" name="start_date" class="control" value="{{ old('start_date', now()->toDateString()) }}" required>
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
                <div class="field">
                    <label class="label">Appointment Type</label>
                    <input type="text" name="appointment_type" class="control" value="{{ old('appointment_type') }}">
                </div>
                <div class="field">
                    <label class="label">Reason</label>
                    <textarea name="reason" class="control" rows="3">{{ old('reason') }}</textarea>
                </div>
                <div class="field">
                    <label class="label">Document</label>
                    <input type="file" name="document" class="control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                </div>
                <button type="submit" class="btn btn-primary">Record Change</button>
            </form>
        </div>
    </div>
    @endcan
</div>
@endsection
