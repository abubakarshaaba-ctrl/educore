@extends('layouts.app')
@section('title', 'Edit Subject')
@section('page-title', 'Subject Management')

@push('styles')
<style>
    .form-page { width:100%; }
    .pg-split { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
    @media(max-width:900px) { .pg-split { grid-template-columns:1fr; } }
    .breadcrumb { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px; }
    .breadcrumb a { color:var(--indigo);text-decoration:none;font-weight:500; }
    .breadcrumb svg { width:14px;height:14px; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    .card-header { padding:14px 24px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:24px; }
    .form-group { margin-bottom:16px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px; }
    .form-control { width:100%;padding:10px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .checkbox-row { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--midnight); }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
</style>
@endpush

@section('content')
<div class="form-page">
<div class="pg-split">
<div>
    <div class="breadcrumb">
        <a href="{{ route('subjects.index') }}">Subjects</a>
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        <a href="{{ route('subjects.show', $subject) }}">{{ $subject->name }}</a>
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        Edit
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header">Edit — {{ $subject->name }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('subjects.update', $subject) }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">Subject Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $subject->name) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Short Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $subject->code) }}" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="checkbox-row">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $subject->is_active) ? 'checked' : '' }}>
                        Active
                    </label>
                </div>
                <div style="display:flex;gap:12px;margin-top:8px">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('subjects.show', $subject) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Info panel --}}
<div>
    <div class="card" style="position:sticky;top:calc(var(--header-h) + 16px)">
        <div class="ch">Subject Notes</div>
        <div class="cb" style="font-size:13px;color:var(--slate);line-height:1.7">
            <p style="margin-bottom:10px">Changes take effect immediately across score entry, broadsheets, and report cards.</p>
            <p style="margin-bottom:10px"><strong style="color:var(--midnight)">Deactivating</strong> a subject hides it from new score entry but preserves all existing scores and historical records.</p>
            <p>The short code is used in compact table headers — keep it under 5 characters.</p>
        </div>
    </div>
</div>
</div>
</div>
@endsection
