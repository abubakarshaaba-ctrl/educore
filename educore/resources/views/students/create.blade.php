@extends('layouts.app')

@section('title', 'Admit Student')
@section('page-title', 'Admit Student')

@php
    $existingGuardians = \App\Models\Guardian::query()
        ->where('tenant_id', auth()->user()->tenant_id)
        ->with(['students:id,first_name,last_name,admission_number', 'user:id'])
        ->withCount('students')
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->get();
@endphp

@push('styles')
<style>
    .form-page { width: 100%; max-width: 1120px; }
    .breadcrumb { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--slate-light); margin-bottom:20px; }
    .breadcrumb a { color:var(--indigo); text-decoration:none; font-weight:600; }
    .form-card { background:#fff; border:1px solid var(--border); border-radius:12px; box-shadow:0 1px 3px rgba(15,23,42,.05); margin-bottom:16px; overflow:hidden; }
    .form-card-header { padding:15px 22px; border-bottom:1px solid var(--border); background:#F8FAFC; display:flex; align-items:center; gap:10px; }
    .form-card-header-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:var(--indigo-bg); color:var(--indigo); }
    .form-card-header-icon svg { width:17px; height:17px; }
    .form-card-title { font-size:14px; font-weight:700; color:var(--midnight); }
    .form-card-body { padding:22px; }
    .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
    .form-group { display:flex; flex-direction:column; gap:6px; }
    .form-group.full { grid-column:1 / -1; }
    .form-label { font-size:11px; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.05em; }
    .form-label span { color:var(--crimson); margin-left:2px; }
    .form-control { width:100%; padding:10px 12px; font-size:13px; font-family:inherit; border:1px solid var(--border); border-radius:8px; color:var(--midnight); background:#F8FAFC; outline:none; transition:150ms ease; }
    .form-control:focus { border-color:var(--indigo); box-shadow:0 0 0 3px rgba(37,99,235,.10); background:#fff; }
    .form-control[readonly] { background:#F1F5F9; color:#475569; cursor:not-allowed; }
    .form-control.is-invalid { border-color:var(--crimson); }
    .invalid-feedback { font-size:12px; color:var(--crimson); }
    .helper { font-size:12px; color:#64748B; line-height:1.5; }
    .parent-choice { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:18px; }
    .parent-choice label { display:flex; gap:10px; align-items:flex-start; border:1px solid var(--border); border-radius:10px; padding:13px 14px; cursor:pointer; background:#fff; }
    .parent-choice label.active { border-color:var(--indigo); background:#EFF6FF; box-shadow:0 0 0 2px rgba(37,99,235,.08); }
    .parent-choice input { margin-top:2px; }
    .choice-title { font-size:13px; font-weight:700; color:var(--midnight); display:block; }
    .choice-copy { font-size:12px; color:#64748B; display:block; margin-top:2px; line-height:1.35; }
    .existing-parent-panel { margin-bottom:18px; padding:16px; border:1px solid #BFDBFE; background:#EFF6FF; border-radius:10px; }
    .existing-parent-summary { display:none; margin-top:10px; padding:10px 12px; border-radius:8px; background:#fff; border:1px solid #DBEAFE; font-size:12px; color:#475569; line-height:1.55; }
    .existing-parent-summary strong { color:#0F172A; }
    .form-actions { display:flex; align-items:center; gap:12px; padding:2px 0 18px; }
    .btn { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; font-size:13px; font-weight:700; border-radius:8px; border:none; cursor:pointer; text-decoration:none; }
    .btn-primary { background:var(--indigo); color:#fff; }
    .btn-ghost { background:#fff; color:var(--midnight); border:1px solid var(--border); }
    @media (max-width:768px) { .form-grid,.parent-choice { grid-template-columns:1fr; } .form-group.full { grid-column:auto; } }
</style>
@endpush

@section('content')
<div class="form-page">
    <div class="breadcrumb">
        <a href="{{ route('students.index') }}">Students</a>
        <span>›</span>
        <span>Admit New Student</span>
    </div>

    <form method="POST" action="{{ route('students.store') }}" id="studentAdmissionForm">
        @csrf

        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-header-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
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
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
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
                            <option value="Islam" {{ old('religion') === 'Islam' ? 'selected' : '' }}>Islam</option>
                            <option value="Christianity" {{ old('religion') === 'Christianity' ? 'selected' : '' }}>Christianity</option>
                            <option value="Other" {{ old('religion') === 'Other' ? 'selected' : '' }}>Other</option>
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

        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-header-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg></div>
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
                                        <option value="{{ $arm->id }}" {{ old('current_class_arm_id') == $arm->id ? 'selected' : '' }}>{{ $level->name }} {{ $arm->name }}</option>
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

        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-header-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
                <span class="form-card-title">Guardian / Parent Information</span>
            </div>
            <div class="form-card-body">
                <div class="parent-choice">
                    <label id="existingChoice" class="{{ old('existing_guardian_id') ? 'active' : '' }}">
                        <input type="radio" name="parent_mode" value="existing" {{ old('existing_guardian_id') ? 'checked' : '' }} {{ $existingGuardians->isEmpty() ? 'disabled' : '' }}>
                        <span><span class="choice-title">Use existing parent</span><span class="choice-copy">Choose a parent already registered in this school. Their existing portal account will be reused.</span></span>
                    </label>
                    <label id="newChoice" class="{{ old('existing_guardian_id') ? '' : 'active' }}">
                        <input type="radio" name="parent_mode" value="new" {{ old('existing_guardian_id') ? '' : 'checked' }}>
                        <span><span class="choice-title">Create new parent</span><span class="choice-copy">Use this when the student has no parent or guardian already registered in the school.</span></span>
                    </label>
                </div>

                <div id="existingParentPanel" class="existing-parent-panel" style="display:{{ old('existing_guardian_id') ? 'block' : 'none' }};">
                    <div class="form-group">
                        <label class="form-label">Existing Parent <span>*</span></label>
                        <select id="existingGuardianSelect" name="existing_guardian_id" class="form-control {{ $errors->has('existing_guardian_id') ? 'is-invalid' : '' }}">
                            <option value="">Select an existing parent</option>
                            @foreach($existingGuardians as $guardian)
                                @php
                                    $children = $guardian->students->map(fn($student) => trim($student->first_name.' '.$student->last_name).' ('.$student->admission_number.')')->implode(', ');
                                @endphp
                                <option
                                    value="{{ $guardian->id }}"
                                    data-first="{{ $guardian->first_name }}"
                                    data-last="{{ $guardian->last_name }}"
                                    data-phone="{{ $guardian->phone }}"
                                    data-email="{{ $guardian->email }}"
                                    data-relationship="{{ $guardian->relationship }}"
                                    data-children="{{ $children }}"
                                    data-portal="{{ $guardian->user_id ? 'yes' : 'no' }}"
                                    {{ old('existing_guardian_id') == $guardian->id ? 'selected' : '' }}
                                >
                                    {{ $guardian->full_name }} — {{ $guardian->phone }}{{ $guardian->email ? ' — '.$guardian->email : '' }} ({{ $guardian->students_count }} {{ Str::plural('child', $guardian->students_count) }})
                                </option>
                            @endforeach
                        </select>
                        @error('existing_guardian_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($existingGuardians->isEmpty())
                            <div class="helper">No existing parents are registered for this school yet.</div>
                        @else
                            <div class="helper">Selecting a parent links this student to the same guardian record and existing parent login.</div>
                        @endif
                        <div id="existingParentSummary" class="existing-parent-summary"></div>
                    </div>
                </div>

                <div class="form-grid" id="guardianFields">
                    <div class="form-group">
                        <label class="form-label">First Name <span>*</span></label>
                        <input id="guardianFirstName" type="text" name="guardian_first_name" class="form-control {{ $errors->has('guardian_first_name') ? 'is-invalid' : '' }}" value="{{ old('guardian_first_name') }}" required>
                        @error('guardian_first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name <span>*</span></label>
                        <input id="guardianLastName" type="text" name="guardian_last_name" class="form-control {{ $errors->has('guardian_last_name') ? 'is-invalid' : '' }}" value="{{ old('guardian_last_name') }}" required>
                        @error('guardian_last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number <span>*</span></label>
                        <input id="guardianPhone" type="text" name="guardian_phone" class="form-control {{ $errors->has('guardian_phone') ? 'is-invalid' : '' }}" value="{{ old('guardian_phone') }}" placeholder="08012345678" required>
                        @error('guardian_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input id="guardianEmail" type="email" name="guardian_email" class="form-control" value="{{ old('guardian_email') }}" placeholder="parent@email.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relationship <span>*</span></label>
                        <select id="guardianRelationship" name="guardian_relationship" class="form-control {{ $errors->has('guardian_relationship') ? 'is-invalid' : '' }}" required>
                            <option value="">Select</option>
                            <option value="father" {{ old('guardian_relationship') === 'father' ? 'selected' : '' }}>Father</option>
                            <option value="mother" {{ old('guardian_relationship') === 'mother' ? 'selected' : '' }}>Mother</option>
                            <option value="guardian" {{ old('guardian_relationship') === 'guardian' ? 'selected' : '' }}>Guardian</option>
                            <option value="other" {{ old('guardian_relationship') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('guardian_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Admit Student</button>
            <a href="{{ route('students.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="parent_mode"]');
    const existingPanel = document.getElementById('existingParentPanel');
    const select = document.getElementById('existingGuardianSelect');
    const summary = document.getElementById('existingParentSummary');
    const existingChoice = document.getElementById('existingChoice');
    const newChoice = document.getElementById('newChoice');
    const fields = {
        first: document.getElementById('guardianFirstName'),
        last: document.getElementById('guardianLastName'),
        phone: document.getElementById('guardianPhone'),
        email: document.getElementById('guardianEmail'),
        relationship: document.getElementById('guardianRelationship'),
    };

    function selectedMode() {
        return document.querySelector('input[name="parent_mode"]:checked')?.value || 'new';
    }

    function setReadOnly(readOnly) {
        fields.first.readOnly = readOnly;
        fields.last.readOnly = readOnly;
        fields.phone.readOnly = readOnly;
        fields.email.readOnly = readOnly;
        fields.relationship.disabled = readOnly;

        // Disabled selects are not submitted, so mirror relationship while reusing a parent.
        let mirror = document.getElementById('guardianRelationshipMirror');
        if (readOnly) {
            if (!mirror) {
                mirror = document.createElement('input');
                mirror.type = 'hidden';
                mirror.id = 'guardianRelationshipMirror';
                mirror.name = 'guardian_relationship';
                fields.relationship.after(mirror);
            }
            mirror.value = fields.relationship.value;
        } else if (mirror) {
            mirror.remove();
        }
    }

    function populateExisting() {
        const option = select.options[select.selectedIndex];
        if (!option || !option.value) {
            summary.style.display = 'none';
            setReadOnly(false);
            return;
        }

        fields.first.value = option.dataset.first || '';
        fields.last.value = option.dataset.last || '';
        fields.phone.value = option.dataset.phone || '';
        fields.email.value = option.dataset.email || '';
        fields.relationship.value = option.dataset.relationship || 'guardian';
        setReadOnly(true);

        const children = option.dataset.children || 'No linked students shown';
        const portal = option.dataset.portal === 'yes' ? 'Existing parent portal account will be retained.' : 'No portal account is currently linked to this guardian.';
        summary.innerHTML = '<strong>Existing children:</strong> ' + children + '<br><strong>Account:</strong> ' + portal;
        summary.style.display = 'block';
    }

    function applyMode() {
        const existing = selectedMode() === 'existing';
        existingPanel.style.display = existing ? 'block' : 'none';
        existingChoice.classList.toggle('active', existing);
        newChoice.classList.toggle('active', !existing);
        select.required = existing;

        if (existing) {
            populateExisting();
        } else {
            select.value = '';
            summary.style.display = 'none';
            setReadOnly(false);
        }
    }

    radios.forEach(radio => radio.addEventListener('change', applyMode));
    select.addEventListener('change', populateExisting);
    applyMode();
});
</script>
@endpush
