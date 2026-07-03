@extends('layouts.app')
@section('title', 'Edit Student')
@section('page-title', 'Students')

@push('styles')
<style>
    .form-page { width:100%; }
    .breadcrumb { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px; }
    .breadcrumb a { color:var(--indigo);text-decoration:none;font-weight:500; }
    .breadcrumb svg { width:14px;height:14px; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:16px; }
    .card-header { padding:14px 24px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:24px; }
    .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
    .form-group { display:flex;flex-direction:column;gap:6px; }
    .form-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .form-label span { color:var(--crimson); }
    .form-control { padding:10px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms;width:100%; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .is-invalid { border-color:var(--crimson) !important; }
    .invalid-feedback { font-size:12px;color:var(--crimson);margin-top:2px; }
    .alert-error { background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    @media(max-width:768px) { .form-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="form-page">
    <div class="breadcrumb">
        <a href="{{ route('students.index') }}">Students</a>
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        <a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a>
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        Edit
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('students.update', $student) }}">
        @csrf @method('PUT')

        <div class="card">
            <div class="card-header">Personal Information</div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Last Name <span>*</span></label>
                        <input type="text" name="last_name" class="form-control {{ $errors->has('last_name') ? 'is-invalid' : '' }}"
                               value="{{ old('last_name', $student->last_name) }}">
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">First Name <span>*</span></label>
                        <input type="text" name="first_name" class="form-control {{ $errors->has('first_name') ? 'is-invalid' : '' }}"
                               value="{{ old('first_name', $student->first_name) }}">
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Other Name</label>
                        <input type="text" name="other_name" class="form-control"
                               value="{{ old('other_name', $student->other_name) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender <span>*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="male"   {{ old('gender', $student->gender) === 'male'   ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $student->gender) === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control"
                               value="{{ old('date_of_birth', optional($student->date_of_birth)->format('Y-m-d')) }}">
                    </div>
                    @include('partials.nigeria-location',['uid'=>'student_edit','stateField'=>'state_of_origin','lgaField'=>'lga_of_origin','selectedState'=>old('state_of_origin',$student->state_of_origin??''),'selectedLga'=>old('lga_of_origin',$student->lga_of_origin??''),'showDistrict'=>false,'labelClass'=>'form-label','inputClass'=>'form-control','wrapClass'=>'form-group','stateLabel'=>'State of Origin','lgaLabel'=>'LGA of Origin'])
                    <div class="form-group">
                        <label class="form-label">Religion</label>
                        <select name="religion" class="form-control">
                            <option value="">Select</option>
                            <option value="Christianity" {{ old('religion', $student->religion) === 'Christianity' ? 'selected' : '' }}>Christianity</option>
                            <option value="Islam"        {{ old('religion', $student->religion) === 'Islam'        ? 'selected' : '' }}>Islam</option>
                            <option value="Other"        {{ old('religion', $student->religion) === 'Other'        ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lifecycle Status</label>
                        <div class="form-control" style="background:#f8fafc">{{ $student->status_label }}</div>
                        @can('student.status.change')
                            <small><a href="{{ route('students.status.show', $student) }}">Change status through lifecycle workflow</a></small>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Class Assignment</div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Current Class</label>
                        <select name="current_class_arm_id" class="form-control">
                            <option value="">None</option>
                            @foreach($classArms as $arm)
                                <option value="{{ $arm->id }}"
                                    {{ old('current_class_arm_id', $student->current_class_arm_id) == $arm->id ? 'selected' : '' }}>
                                    {{ $arm->classLevel->name }} {{ $arm->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Admission Number</label>
                        <input type="text" name="admission_number" class="form-control"
                               value="{{ old('admission_number', $student->admission_number) }}">
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('students.show', $student) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
