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
    .guardian-help { font-size:12px;color:#64748B;line-height:1.5;margin:0 0 14px; }
    .guardian-search { margin-bottom:10px; }
    .guardian-list { border:1px solid var(--border);border-radius:10px;max-height:290px;overflow:auto;background:#F8FAFC; }
    .guardian-row { display:grid;grid-template-columns:minmax(0,1fr) 110px;gap:14px;align-items:center;padding:11px 13px;border-bottom:1px solid var(--border);background:white; }
    .guardian-row:last-child { border-bottom:none; }
    .guardian-check { display:flex;gap:10px;align-items:flex-start;cursor:pointer;min-width:0; }
    .guardian-check input { margin-top:3px;flex:0 0 auto; }
    .guardian-name { display:block;font-size:13px;font-weight:700;color:var(--midnight); }
    .guardian-meta { display:block;font-size:11px;color:#64748B;margin-top:2px;line-height:1.4; }
    .guardian-primary { display:flex;align-items:center;justify-content:flex-end;gap:6px;font-size:11px;color:#475569;white-space:nowrap; }
    .guardian-empty { padding:16px;font-size:12px;color:#64748B;text-align:center; }
    .guardian-new { margin-top:18px;padding-top:18px;border-top:1px solid var(--border); }
    .guardian-new-title { font-size:13px;font-weight:700;color:var(--midnight);margin-bottom:5px; }
    .guardian-primary-new { display:flex;align-items:center;gap:8px;margin-top:10px;font-size:12px;color:#475569; }
    .section-actions { display:flex;gap:12px;margin-top:16px; }
    @media(max-width:768px) {
        .form-grid { grid-template-columns:1fr; }
        .guardian-row { grid-template-columns:1fr; }
        .guardian-primary { justify-content:flex-start;padding-left:26px; }
    }
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

        <div style="display:flex;gap:12px;margin-bottom:22px">
            <button type="submit" class="btn btn-primary">Save Student Changes</button>
            <a href="{{ route('students.show', $student) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>

    @php
        $availableGuardians = \App\Models\Guardian::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with(['students' => fn($query) => $query->select('students.id','students.first_name','students.last_name','students.admission_number')])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $linkedGuardianIds = $student->guardians->pluck('id')->map(fn($id) => (int) $id)->all();
        $selectedGuardianIds = collect(old('guardian_ids', $linkedGuardianIds))->map(fn($id) => (int) $id)->all();
        $currentPrimaryId = optional($student->guardians->first(fn($guardian) => (bool) $guardian->pivot->is_primary_contact))->id;
        $selectedPrimaryId = (int) old('primary_guardian_id', $currentPrimaryId ?: 0);
    @endphp

    <form method="POST" action="{{ route('guardians.store', $student) }}" id="guardianManagementForm">
        @csrf
        <div class="card">
            <div class="card-header">Parents & Guardians</div>
            <div class="card-body">
                <p class="guardian-help">
                    Link this registered student to multiple parents or guardians. Select any existing parent already registered in this school, or add a new parent below. One linked guardian is retained as the primary contact.
                </p>

                <div class="form-group guardian-search">
                    <label class="form-label" for="guardianSearch">Find Existing Parent</label>
                    <input type="search" id="guardianSearch" class="form-control" placeholder="Search by name, phone, email or existing child">
                </div>

                <div class="guardian-list" id="guardianList">
                    @forelse($availableGuardians as $guardian)
                        @php
                            $children = $guardian->students
                                ->map(fn($child) => trim($child->first_name.' '.$child->last_name).($child->admission_number ? ' ('.$child->admission_number.')' : ''))
                                ->implode(', ');
                            $searchText = strtolower(trim($guardian->full_name.' '.$guardian->phone.' '.$guardian->email.' '.$children));
                        @endphp
                        <div class="guardian-row" data-guardian-row data-search="{{ $searchText }}">
                            <label class="guardian-check">
                                <input type="checkbox" name="guardian_ids[]" value="{{ $guardian->id }}"
                                       {{ in_array((int) $guardian->id, $selectedGuardianIds, true) ? 'checked' : '' }}>
                                <span>
                                    <span class="guardian-name">{{ $guardian->full_name }}</span>
                                    <span class="guardian-meta">
                                        {{ $guardian->phone ?: 'No phone' }}{{ $guardian->email ? ' · '.$guardian->email : '' }}
                                        · {{ ucfirst($guardian->relationship ?: 'guardian') }}
                                        · {{ $guardian->user_id ? 'Portal account active' : 'No portal account yet' }}
                                        @if($children)<br>Existing child{{ $guardian->students->count() === 1 ? '' : 'ren' }}: {{ $children }}@endif
                                    </span>
                                </span>
                            </label>
                            <label class="guardian-primary">
                                <input type="radio" name="primary_guardian_id" value="{{ $guardian->id }}"
                                       {{ $selectedPrimaryId === (int) $guardian->id ? 'checked' : '' }}>
                                Primary contact
                            </label>
                        </div>
                    @empty
                        <div class="guardian-empty">No existing parent or guardian is registered in this school yet.</div>
                    @endforelse
                </div>
                @error('guardian_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('guardian_ids.*')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="guardian-new">
                    <div class="guardian-new-title">Add a New Parent / Guardian</div>
                    <p class="guardian-help">Leave these fields blank if you only want to link existing parents.</p>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="new_guardian_first_name" class="form-control" value="{{ old('new_guardian_first_name') }}">
                            @error('new_guardian_first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="new_guardian_last_name" class="form-control" value="{{ old('new_guardian_last_name') }}">
                            @error('new_guardian_last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="new_guardian_phone" class="form-control" value="{{ old('new_guardian_phone') }}">
                            @error('new_guardian_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="new_guardian_email" class="form-control" value="{{ old('new_guardian_email') }}">
                            @error('new_guardian_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Relationship</label>
                            <select name="new_guardian_relationship" class="form-control">
                                <option value="">Select relationship</option>
                                @foreach(['father'=>'Father','mother'=>'Mother','guardian'=>'Guardian','other'=>'Other'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('new_guardian_relationship') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('new_guardian_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="new_guardian_occupation" class="form-control" value="{{ old('new_guardian_occupation') }}">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Address</label>
                            <input type="text" name="new_guardian_address" class="form-control" value="{{ old('new_guardian_address') }}">
                        </div>
                    </div>
                    <label class="guardian-primary-new">
                        <input type="checkbox" name="new_guardian_primary" value="1" {{ old('new_guardian_primary') ? 'checked' : '' }}>
                        Make this newly added parent/guardian the primary contact
                    </label>
                </div>

                <div class="section-actions">
                    <button type="submit" class="btn btn-primary">Save Parent / Guardian Links</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('guardianSearch');
    const rows = Array.from(document.querySelectorAll('[data-guardian-row]'));

    if (search) {
        search.addEventListener('input', function () {
            const needle = this.value.trim().toLowerCase();
            rows.forEach(row => {
                row.style.display = !needle || row.dataset.search.includes(needle) ? '' : 'none';
            });
        });
    }

    rows.forEach(row => {
        const checkbox = row.querySelector('input[type="checkbox"][name="guardian_ids[]"]');
        const primary = row.querySelector('input[type="radio"][name="primary_guardian_id"]');
        if (primary && checkbox) {
            primary.addEventListener('change', function () {
                if (this.checked) checkbox.checked = true;
            });
            checkbox.addEventListener('change', function () {
                if (!this.checked && primary.checked) primary.checked = false;
            });
        }
    });
});
</script>
@endpush