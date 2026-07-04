@extends('layouts.app')
@section('title', 'Class Detail')
@section('page-title', 'Class Management')

@push('styles')
<style>
    .breadcrumb { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px; }
    .breadcrumb a { color:var(--indigo);text-decoration:none;font-weight:500; }
    .breadcrumb svg { width:14px;height:14px; }
    .detail-grid { display:grid;grid-template-columns:1fr 340px;gap:16px; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:14px; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }
    .info-row { display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px; }
    .info-row:last-child { border-bottom:none; }
    .info-key { color:var(--slate);font-weight:500; }
    .info-val { color:var(--midnight);font-weight:600; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:11px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight); }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .form-group { margin-bottom:12px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-control { width:100%;padding:8px 10px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);background:white; }
    .btn { display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center; }
    .btn-sm { padding:5px 10px;font-size:11px; }
    .btn-indigo { background:var(--indigo-bg);color:var(--indigo); }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .alert-error { background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px; }
    .empty-state { text-align:center;padding:30px;color:var(--slate-light);font-size:13px; }
    @media(max-width:1024px) { .detail-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="breadcrumb">
    <a href="{{ route('classes.arms') }}">Class Arms</a>
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    {{ $arm->classLevel->name }} {{ $arm->name }}
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="detail-grid">
    <div>
        {{-- Class info --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Class Details</span></div>
            <div class="card-body">
                <div class="info-row"><span class="info-key">Class</span><span class="info-val">{{ $arm->classLevel->name }} {{ $arm->name }}</span></div>
                <div class="info-row"><span class="info-key">Section</span><span class="info-val">{{ ucfirst(str_replace('_',' ',$arm->classLevel->section)) }}</span></div>
                <div class="info-row"><span class="info-key">Form Tutor</span><span class="info-val">{{ optional($arm->formTutor)->name ?? 'Not assigned' }}</span></div>
                <div class="info-row"><span class="info-key">Active Students</span><span class="info-val">{{ $arm->students->count() }}</span></div>
            </div>
        </div>

        {{-- Students --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Active Students ({{ $arm->students->count() }})</span>
                <a href="{{ route('students.create') }}" class="btn btn-sm btn-indigo">+ Admit Student</a>
            </div>
            @if($arm->students->count())
            <div class="tbl"><table>
                <thead><tr><th>Name</th><th>Adm. No.</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach($arm->students->sortBy('last_name') as $student)
                    <tr>
                        <td><strong>{{ $student->full_name }}</strong></td>
                        <td style="font-size:12px;color:var(--slate-light)">{{ $student->admission_number }}</td>
                        <td>
                            <span style="font-size:11px;font-weight:600;color:var(--emerald)">
                                {{ $student->status_label }}
                            </span>
                        </td>
                        <td><a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-indigo">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>
            @else
            <div class="empty-state">No students in this class yet.</div>
            @endif
        </div>

        {{-- Subjects assigned --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Subjects Assigned</span></div>
            @if($arm->subjects->count())
            <div class="tbl"><table>
                <thead><tr><th>Subject</th><th>Teacher</th></tr></thead>
                <tbody>
                    @foreach($arm->subjects as $subject)
                    <tr>
                        <td><strong>{{ $subject->name }}</strong></td>
                        <td>{{ $staffMap[$subject->pivot->teacher_id] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>
            @else
            <div class="empty-state">No subjects assigned yet. Assign one →</div>
            @endif
        </div>
    </div>

    <div>
        {{-- Assign subject --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Assign Subject</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('classes.assign-subject') }}">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ optional($currentSession)->id ?? 1 }}">
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">Select subject</option>
                            @foreach($allSubjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apply to</label>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;margin-bottom:6px;cursor:pointer">
                            <input type="radio" name="target" value="arm:{{ $arm->id }}" checked>
                            This class only ({{ $arm->classLevel->name }} {{ $arm->name }})
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;cursor:pointer">
                            <input type="radio" name="target" value="level:{{ $arm->class_level_id }}">
                            All {{ $arm->classLevel->name }} arms
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teacher</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">None assigned</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Assign Subject</button>
                </form>
            </div>
        </div>

        {{-- Edit class name --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Edit Class Details</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('classes.arms.update', $arm) }}">
                    @csrf @method('PATCH')
                    <div class="form-group">
                        <label class="form-label">Arm Name <span>*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $arm->name) }}" placeholder="e.g. A, B, Science, Gold" required>
                        <div style="font-size:11px;color:var(--slate-light);margin-top:3px">Displayed as: {{ $arm->classLevel->name }} [name]</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Form Tutor</label>
                        <select name="form_tutor_id" class="form-control">
                            <option value="">None assigned</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ $arm->form_tutor_id == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
