@extends('layouts.app')
@section('title', 'Subject Detail')
@section('page-title', 'Subject Management')

@push('styles')
<style>
    .breadcrumb { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px; }
    .breadcrumb a { color:var(--indigo);text-decoration:none;font-weight:500; }
    .breadcrumb svg { width:14px;height:14px; }
    .detail-grid { display:grid;grid-template-columns:1fr 360px;gap:16px; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:14px; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }
    .subject-hero { padding:24px 20px;text-align:center;border-bottom:1px solid var(--border); }
    .subject-icon { width:56px;height:56px;border-radius:12px;background:var(--indigo);color:white;font-size:20px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px; }
    .subject-name { font-size:18px;font-weight:700;color:var(--midnight); }
    .subject-code { font-size:12px;color:var(--slate-light);margin-top:3px; }
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
    .form-label span { color:var(--crimson); }
    .form-control { width:100%;padding:8px 10px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);background:white; }
    .btn { display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-danger { background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA;font-size:11px;padding:4px 10px; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .alert-error { background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px; }
    .badge { display:inline-flex;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px; }
    .badge-success { background:#ECFDF5;color:var(--emerald); }
    .badge-error   { background:#F1F5F9;color:var(--slate); }
    .empty-state { text-align:center;padding:30px;color:var(--slate-light);font-size:13px; }
    @media(max-width:1024px) { .detail-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="breadcrumb">
    <a href="{{ route('subjects.index') }}">Subjects</a>
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    {{ $subject->name }}
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="detail-grid">
    <div>
        {{-- Subject info --}}
        <div class="card">
            <div class="subject-hero">
                <div class="subject-icon">{{ strtoupper(substr($subject->name, 0, 2)) }}</div>
                <div class="subject-name">{{ $subject->name }}</div>
                @if($subject->code)<div class="subject-code">Code: {{ $subject->code }}</div>@endif
                <div style="margin-top:10px">
                    <span class="badge {{ $subject->is_active ? 'badge-success' : 'badge-error' }}">{{ $subject->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="info-row"><span class="info-key">Classes Assigned</span><span class="info-val">{{ $assignments->count() }}</span></div>
                <div class="info-row"><span class="info-key">Total Score Entries</span><span class="info-val">{{ $subject->scores()->count() }}</span></div>
            </div>
            <div style="padding:14px 20px;border-top:1px solid var(--border)">
                <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-ghost" style="width:100%;justify-content:center">Edit Subject</a>
            </div>
        </div>

        {{-- Class assignments --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Class Assignments ({{ $assignments->count() }})</span></div>
            @if($assignments->count())
            <div class="tbl"><table>
                <thead><tr><th>Class</th><th>Session</th><th>Teacher</th><th></th></tr></thead>
                <tbody>
                    @foreach($assignments as $asgn)
                    <tr>
                        <td><strong>{{ $asgn->classArm->classLevel->name }} {{ $asgn->classArm->name }}</strong></td>
                        <td style="font-size:12px;color:var(--slate)">{{ $asgn->session->name ?? '—' }}</td>
                        <td>{{ optional($asgn->teacher)->name ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('classes.subjects.toggle', $asgn) }}" onsubmit="return confirm('Remove this assignment?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>
            @else
            <div class="empty-state">Not assigned to any class yet.</div>
            @endif
        </div>
    </div>

    {{-- Assign to class form --}}
    <div class="card">
        <div class="card-header"><span class="card-title">Assign to Class</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('classes.assign-subject') }}">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <div class="form-group">
                    <label class="form-label">Assign to <span>*</span></label>
                    <select name="target" class="form-control" required>
                        <option value="">Select class level or class</option>
                        <optgroup label="Whole level (all arms)">
                            @foreach($classLevels as $level)
                                <option value="level:{{ $level->id }}">{{ $level->name }} — all arms</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Specific class">
                            @foreach($classArms as $arm)
                                <option value="arm:{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Session <span>*</span></label>
                    <select name="session_id" class="form-control" required>
                        <option value="">Select session</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}" {{ $session->is_current ? 'selected' : '' }}>
                                {{ $session->name }}{{ $session->is_current ? ' (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
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
                <button type="submit" class="btn btn-primary">Assign to Class</button>
            </form>
        </div>
    </div>
</div>
@endsection
