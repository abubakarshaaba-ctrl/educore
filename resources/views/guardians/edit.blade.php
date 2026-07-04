@extends('layouts.app')
@section('title', 'Edit Guardian — ' . $guardian->full_name)
@section('page-title', 'Edit Guardian')

@push('styles')
<style>
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
.breadcrumb svg{width:14px;height:14px}
.form-card{background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);max-width:680px}
.form-header{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.form-header-icon{width:40px;height:40px;background:#EEF2FF;border-radius:10px;display:flex;align-items:center;justify-content:center}
.form-header-icon svg{width:20px;height:20px;color:var(--indigo)}
.form-header-title{font-size:15px;font-weight:700;color:var(--midnight)}
.form-header-sub{font-size:12px;color:var(--slate-light);margin-top:2px}
.form-body{padding:24px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-grid.full{grid-template-columns:1fr}
.form-group{display:flex;flex-direction:column;gap:5px}
.form-label{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.form-label span{color:var(--crimson)}
.form-control{width:100%;padding:9px 11px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms,background 200ms}
.form-control:focus{border-color:var(--indigo);background:white}
.form-control.error{border-color:var(--crimson)}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px}
.linked-students{background:#F0FDF4;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#166534;margin-bottom:18px}
.linked-students strong{font-weight:600}
.form-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms}
.btn-primary{background:var(--indigo);color:white}
.btn-primary:hover{background:#1D4ED8}
.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.btn-ghost:hover{background:#F8FAFC}
</style>
@endpush

@section('content')
<div class="breadcrumb">
    <a href="{{ route('students.index') }}">Students</a>
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    @if($guardian->students->first())
        <a href="{{ route('students.show', $guardian->students->first()) }}">{{ $guardian->students->first()->full_name }}</a>
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    @endif
    Edit Guardian
</div>

@if($errors->any())
<div class="alert-error" style="max-width:680px">{{ $errors->first() }}</div>
@endif

<div class="form-card">
    <div class="form-header">
        <div class="form-header-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div class="form-header-title">Edit Guardian / Parent</div>
            <div class="form-header-sub">{{ $guardian->full_name }}</div>
        </div>
    </div>
    <div class="form-body">
        @if($guardian->students->count())
        <div class="linked-students">
            <strong>Linked to:</strong>
            {{ $guardian->students->map(fn($s) => $s->full_name)->join(', ') }}
        </div>
        @endif

        <form method="POST" action="{{ route('guardians.update', $guardian) }}">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">First Name <span>*</span></label>
                    <input type="text" name="first_name" class="form-control {{ $errors->has('first_name') ? 'error' : '' }}"
                        value="{{ old('first_name', $guardian->first_name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name <span>*</span></label>
                    <input type="text" name="last_name" class="form-control {{ $errors->has('last_name') ? 'error' : '' }}"
                        value="{{ old('last_name', $guardian->last_name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone <span>*</span></label>
                    <input type="text" name="phone" class="form-control {{ $errors->has('phone') ? 'error' : '' }}"
                        value="{{ old('phone', $guardian->phone) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'error' : '' }}"
                        value="{{ old('email', $guardian->email) }}" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label class="form-label">Relationship <span>*</span></label>
                    <select name="relationship" class="form-control" required>
                        @foreach(['father' => 'Father', 'mother' => 'Mother', 'guardian' => 'Guardian', 'other' => 'Other'] as $val => $label)
                        <option value="{{ $val }}" {{ old('relationship', $guardian->relationship) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="occupation" class="form-control"
                        value="{{ old('occupation', $guardian->occupation) }}" placeholder="Optional">
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Home Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Optional">{{ old('address', $guardian->address) }}</textarea>
                </div>
            </div>
            <div class="form-footer" style="margin:0 -24px -24px;margin-top:20px">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                @if($guardian->students->first())
                <a href="{{ route('students.show', $guardian->students->first()) }}" class="btn btn-ghost">Cancel</a>
                @else
                <a href="{{ route('students.index') }}" class="btn btn-ghost">Cancel</a>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
