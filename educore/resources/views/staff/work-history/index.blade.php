@extends('layouts.app')
@section('title', 'Staff Work History')
@section('page-title', 'Staff Work History')

@push('styles')
<style>
.work-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:18px;min-width:0}
.work-head>div{min-width:0}
.work-title{font-size:20px;font-weight:800;color:var(--midnight);margin-top:6px;overflow-wrap:anywhere}
.work-status{font-size:13px;color:var(--slate);overflow-wrap:anywhere}
.back-link{font-size:13px;color:var(--indigo);text-decoration:none}
.grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,360px);gap:16px;align-items:start}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;min-width:0}
.card-header{padding:14px 18px;background:#F8FAFC;border-bottom:1px solid var(--border);font-weight:700;color:var(--midnight);overflow-wrap:anywhere}
.card-body{padding:18px;min-width:0}
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:12px;min-width:0}
.label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate)}
.control{box-sizing:border-box;width:100%;min-width:0;max-width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:13px}
textarea.control{resize:vertical}
input[type=file].control{overflow:hidden}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 14px;min-height:40px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:700;text-decoration:none;cursor:pointer;max-width:100%}
.btn-primary{background:var(--indigo);color:#fff;border-color:var(--indigo)}
.btn-ghost{background:#fff;color:var(--midnight)}
.timeline{display:grid;gap:10px}
.timeline-item{border:1px solid var(--border);border-radius:10px;padding:12px;min-width:0}
.timeline-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;min-width:0}
.timeline-title{min-width:0;overflow-wrap:anywhere}
.timeline-meta{font-size:12px;color:var(--slate);margin-top:4px;overflow-wrap:anywhere}
.timeline-dept{font-size:13px;color:var(--slate);margin-top:6px;overflow-wrap:anywhere}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:var(--crimson);border-radius:8px;padding:12px 14px;margin-bottom:14px;overflow-wrap:anywhere}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 14px;color:#047857;margin-bottom:14px;overflow-wrap:anywhere}
@media(max-width:980px){.grid{grid-template-columns:1fr}}
@media(max-width:620px){
    .work-head{display:block}
    .work-title{font-size:18px}
    .card-header{padding:12px 14px}
    .card-body{padding:14px}
    .timeline-top{display:grid;grid-template-columns:minmax(0,1fr) auto}
    .timeline-top .btn{min-height:36px;padding:7px 11px;font-size:12px}
    .btn-primary{width:100%}
}
@media(max-width:400px){
    .timeline-top{grid-template-columns:1fr}
    .timeline-top .btn{width:100%}
}
</style>
@endpush

@section('content')
<div class="work-head">
    <div>
        <a href="{{ route('staff.show', $staff) }}" class="back-link">Back to staff profile</a>
        <h1 class="work-title">{{ $staff->name }} Work History</h1>
        <div class="work-status">Current status: {{ $staff->employmentStatusLabel() }}</div>
    </div>
</div>

@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif
@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="grid">
    <div class="card">
        <div class="card-header">Employment Timeline</div>
        <div class="card-body">
            <div class="timeline">
                @forelse($histories as $history)
                <div class="timeline-item">
                    <div class="timeline-top">
                        <strong class="timeline-title">{{ $history->position_title ?: ucfirst(str_replace('_', ' ', $history->change_type)) }}</strong>
                        <a href="{{ route('staff.work-history.show', $history) }}" class="btn btn-ghost">View</a>
                    </div>
                    <div class="timeline-meta">
                        {{ optional($history->start_date)->format('d M Y') }} - {{ optional($history->end_date)->format('d M Y') ?: 'Current' }}
                    </div>
                    <div class="timeline-dept">{{ $history->department_name ?: 'No department recorded' }}</div>
                </div>
                @empty
                <div class="timeline-dept">No work-history records exist for this staff member.</div>
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
                <div class="field"><label class="label">Department</label><input type="text" name="department_name" class="control" value="{{ old('department_name') }}"></div>
                <div class="field"><label class="label">Employment Type</label><input type="text" name="employment_type" class="control" value="{{ old('employment_type') }}"></div>
                <div class="field"><label class="label">Functional Role</label><input type="text" name="functional_role" class="control" value="{{ old('functional_role') }}"></div>
                <div class="field"><label class="label">Grade Level</label><input type="text" name="grade_level" class="control" value="{{ old('grade_level') }}"></div>
                <div class="field"><label class="label">Appointment Type</label><input type="text" name="appointment_type" class="control" value="{{ old('appointment_type') }}"></div>
                <div class="field"><label class="label">Reason</label><textarea name="reason" class="control" rows="3">{{ old('reason') }}</textarea></div>
                <div class="field"><label class="label">Document</label><input type="file" name="document" class="control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></div>
                <button type="submit" class="btn btn-primary">Record Change</button>
            </form>
        </div>
    </div>
    @endcan
</div>
@endsection
