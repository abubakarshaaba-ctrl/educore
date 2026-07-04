@extends('layouts.app')
@section('title', 'Subjects')
@section('page-title', 'Class Management')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .two-col { display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-control { width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-sm { padding:5px 10px;font-size:12px; }
    .btn-success { background:#ECFDF5;color:var(--emerald);border:1px solid #A7F3D0; }
    .btn-warning { background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .subjects-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;padding:16px; }
    .subject-card { border:1px solid var(--border);border-radius:8px;padding:12px 14px;display:flex;align-items:center;justify-content:space-between; }
    .subject-name { font-size:13px;font-weight:600;color:var(--midnight); }
    .subject-code { font-size:11px;color:var(--slate-light);margin-top:2px; }
    .inactive { opacity:0.5; }

@media (max-width: 1024px) {
    .two-col { grid-template-columns: 1fr !important; }
    .stats-row, .stat-row { grid-template-columns: repeat(2, 1fr) !important; }
    .kpi { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 640px) {
    .two, .fr { grid-template-columns: 1fr !important; }
}
@media (max-width: 480px) {
    .fr3 { grid-template-columns: 1fr !important; }
}
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('classes.levels') }}" class="page-tab">Class Levels</a>
    <a href="{{ route('classes.arms') }}" class="page-tab">Class Arms</a>
    <a href="{{ route('classes.subjects') }}" class="page-tab active">Subjects</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="two-col">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Subjects ({{ $subjects->count() }})</span>
            <span style="font-size:12px;color:var(--slate)">{{ $subjects->where('is_active',true)->count() }} active</span>
        </div>
        <div class="subjects-grid">
            @forelse($subjects as $subject)
            <div class="subject-card {{ !$subject->is_active ? 'inactive' : '' }}">
                <div>
                    <div class="subject-name">{{ $subject->name }}</div>
                    @if($subject->code)<div class="subject-code">{{ $subject->code }}</div>@endif
                </div>
                <form method="POST" action="{{ route('classes.subjects.toggle', $subject) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm {{ $subject->is_active ? 'btn-warning' : 'btn-success' }}">
                        {{ $subject->is_active ? 'Disable' : 'Enable' }}
                    </button>
                </form>
            </div>
            @empty
            <p style="color:var(--slate-light);font-size:13px;padding:20px">No subjects yet.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Add Subject</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('classes.subjects.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Subject Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Mathematics">
                </div>
                <div class="form-group">
                    <label class="form-label">Short Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g. MTH" maxlength="10">
                </div>
                <button type="submit" class="btn btn-primary">Add Subject</button>
            </form>
        </div>
    </div>
</div>
@endsection
