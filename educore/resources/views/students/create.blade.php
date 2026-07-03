@extends('layouts.app')

@section('title', 'Admit Student')
@section('page-title', 'Admit Student')

@push('styles')
<style>
    .form-page { width: 100%; }

    .breadcrumb {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; color: var(--slate-light);
        margin-bottom: 20px;
    }
    .breadcrumb a { color: var(--indigo); text-decoration: none; font-weight: 500; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb svg { width: 14px; height: 14px; }

    .form-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 16px;
        overflow: hidden;
    }

    .form-card-header {
        padding: 16px 24px;
        border-bottom: 1px solid var(--border);
        background: #F8FAFC;
        display: flex; align-items: center; gap: 10px;
    }

    .form-card-header-icon {
        width: 32px; height: 32px;
        background: var(--indigo-bg);
        color: var(--indigo);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
    }

    .form-card-header-icon svg { width: 16px; height: 16px; }

    .form-card-title { font-size: 14px; font-weight: 600; color: var(--midnight); }

    .form-card-body { padding: 24px; }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    .col-span-2 { grid-column: span 2; }

    .form-group { display: flex; flex-direction: column; gap: 6px; }

    .form-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .form-label span { color: var(--crimson); margin-left: 2px; }

    .form-control {
        padding: 10px 12px;
        font-size: 13px;
        font-family: inherit;
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--midnight);
        background: #F8FAFC;
        outline: none;
        transition: border-color 200ms, box-shadow 200ms;
        width: 100%;
    }

    .form-control:focus {
        border-color: var(--indigo);
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        background: white;
    }

    .form-control.is-invalid { border-color: var(--crimson); }

    .invalid-feedback {
        font-size: 12px;
        color: var(--crimson);
        margin-top: 2px;
    }

    .form-actions {
        display: flex; align-items: center; gap: 12px;
        padding-top: 8px;
    }

    .btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 20px;
        font-size: 13px; font-weight: 600; font-family: inherit;
        border-radius: 8px; border: none; cursor: pointer;
        text-decoration: none; transition: background 150ms;
    }

    .btn-primary { background: var(--indigo); color: white; }
    .btn-primary:hover { background: #1D4ED8; }
    .btn-ghost { background: white; color: var(--midnight); border: 1px solid var(--border); }
    .btn-ghost:hover { background: #F8FAFC; }

    @media (max-width: 768px) {
        .form-grid, .form-grid-3 { grid-template-columns: 1fr; }
        .col-span-2 { grid-column: span 1; }
    }
</style>
@endpush

@section('content')
<div class="form-page">

    <div class="breadcrumb">
        <a href="{{ route('students.index') }}">Students</a>
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        Admit New Student
    </div>

    <form method="POST" action="{{ route('students.store') }}">
        @csrf

        {{-- PERSONAL INFORMATION --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-header-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <span class="form-card-title">Personal Information</span>
            </div>
            <div class="form-card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">First Name <span>*</span></label>
                        <input type="text" name="first_name" class="form-control {{ $errors->has('first_name') ? 'is-invalid' : '' }}" value="{{ old('first_name') }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name <span>*</span></label>
                        <input type="text" name="last_name" class="form-control {{ $errors->has('last_name') ? 'is-invalid' : '' }}" value="{{ old('last_name') }}" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender <span>*</span></label>
                        <select name="gender" class="form-control {{ $errors->has('gender') ? 'is-invalid' : '' }}" required>
                            <option value="">Select gender</option>
                            <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth <span>*</span></label>
                        <input type="date" name="date_of_birth" class="form-control {{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}" value="{{ old('date_of_birth') }}" required>
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Religion</label>
                        <select name="religion" class="form-control">
                            <option value="">Select</option>
                            <option value="Islam"       {{ old('religion') === 'Islam'       ? 'selected' : '' }}>Islam</option>
                            <option value="Christianity"{{ old('religion') === 'Christianity' ? 'selected' : '' }}>Christianity</option>
                            <option value="Other"       {{ old('religion') === 'Other'       ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    @include('partials.nigeria-location', ['uid'=>'student_create','stateField'=>'state_of_origin','lgaField'=>'lga_of_origin','selectedState'=>old('state_of_origin',''),'selectedLga'=>old('lga_of_origin',''),'showDistrict'=>false,'labelClass'=>'form-label','inputClass'=>'form-control','wrapClass'=>'form-group','stateLabel'=>'State of Origin','lgaLabel'=>'LGA of Origin'])
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">Unknown</option>
                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                                <option value="{{ $bg }}" {{ old('blood_group') === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Genotype</label>
                        <select name="genotype" class="form-control">
                            <option value="">Unknown</option>
                            @foreach(['AA','AS','AC','SS','SC'] as $gt)
                                <option value="{{ $gt }}" {{ old('genotype') === $gt ? 'selected' : '' }}>{{ $gt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ADMISSION DETAILS --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-header-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                </div>
                <span class="form-card-title">Admission Details</span>
            </div>
            <div class="form-card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Class <span>*</span></label>
                        <select name="current_class_arm_id" class="form-control {{ $errors->has('current_class_arm_id') ? 'is-invalid' : '' }}" required>
                            <option value="">Select class</option>
                            @foreach($classLevels as $level)
                                <optgroup label="{{ $level->name }}">
                                    @foreach($level->classArms as $arm)
                                        <option value="{{ $arm->id }}" {{ old('current_class_arm_id') == $arm->id ? 'selected' : '' }}>
                                            {{ $level->name }} {{ $arm->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('current_class_arm_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Admission Date <span>*</span></label>
                        <input type="date" name="admission_date" class="form-control {{ $errors->has('admission_date') ? 'is-invalid' : '' }}" value="{{ old('admission_date', date('Y-m-d')) }}" required>
                        @error('admission_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- GUARDIAN INFORMATION --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-header-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                </div>
                <span class="form-card-title">Guardian / Parent Information</span>
            </div>
            <div class="form-card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">First Name <span>*</span></label>
                        <input type="text" name="guardian_first_name" class="form-control {{ $errors->has('guardian_first_name') ? 'is-invalid' : '' }}" value="{{ old('guardian_first_name') }}" required>
                        @error('guardian_first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name <span>*</span></label>
                        <input type="text" name="guardian_last_name" class="form-control {{ $errors->has('guardian_last_name') ? 'is-invalid' : '' }}" value="{{ old('guardian_last_name') }}" required>
                        @error('guardian_last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number <span>*</span></label>
                        <input type="text" name="guardian_phone" class="form-control {{ $errors->has('guardian_phone') ? 'is-invalid' : '' }}" value="{{ old('guardian_phone') }}" placeholder="08012345678" required>
                        @error('guardian_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="guardian_email" class="form-control" value="{{ old('guardian_email') }}" placeholder="parent@email.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relationship <span>*</span></label>
                        <select name="guardian_relationship" class="form-control {{ $errors->has('guardian_relationship') ? 'is-invalid' : '' }}" required>
                            <option value="">Select</option>
                            <option value="father"   {{ old('guardian_relationship') === 'father'   ? 'selected' : '' }}>Father</option>
                            <option value="mother"   {{ old('guardian_relationship') === 'mother'   ? 'selected' : '' }}>Mother</option>
                            <option value="guardian" {{ old('guardian_relationship') === 'guardian' ? 'selected' : '' }}>Guardian</option>
                            <option value="other"    {{ old('guardian_relationship') === 'other'    ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('guardian_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Admit Student
            </button>
            <a href="{{ route('students.index') }}" class="btn btn-ghost">Cancel</a>
        </div>

    </form>
</div>
@endsection
