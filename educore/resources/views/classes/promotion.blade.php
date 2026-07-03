@extends('layouts.app')
@section('title', 'Promotion Rules')
@section('page-title', 'Promotion Engine')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .rules-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px; }
    .rule-card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    .rule-header { padding:14px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between; }
    .rule-title { font-size:14px;font-weight:700;color:var(--midnight); }
    .rule-body { padding:18px; }
    .form-group { margin-bottom:12px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-control { width:100%;padding:8px 10px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);background:white; }
    .compulsory-grid { display:grid;grid-template-columns:1fr 1fr;gap:6px;max-height:140px;overflow-y:auto;border:1px solid var(--border);border-radius:7px;padding:8px;background:#F8FAFC; }
    .check-label { display:flex;align-items:center;gap:6px;font-size:12px;color:var(--midnight);cursor:pointer; }
    .btn { display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;transition:background 150ms;width:100%;justify-content:center; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .badge { display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px; }
    .badge-success { background:#ECFDF5;color:var(--emerald); }
    .badge-warning { background:#FFFBEB;color:var(--amber); }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }

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
<div class="page-tabs" style="margin-bottom:20px">
    <a href="{{ route('classes.promotion') }}" class="page-tab active">Rules</a>
    <a href="{{ route('classes.grading') }}" class="page-tab">Grading Scale</a>
    <a href="{{ route('classes.promotion.preview') }}" class="page-tab">Run Promotion</a>
    <a href="{{ route('classes.promotion.history') }}" class="page-tab">History</a>
    <a href="{{ route('classes.bulk-promote.page') }}" class="page-tab">Manual Bulk</a>
</div>
@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="rules-grid">
    @foreach($levels as $level)
    @php $rule = $level->promotionRule; @endphp
    <div class="rule-card">
        <div class="rule-header">
            <span class="rule-title">{{ $level->name }}</span>
            <span class="badge {{ $rule ? 'badge-success' : 'badge-warning' }}">{{ $rule ? 'Rule Set' : 'Not Configured' }}</span>
        </div>
        <div class="rule-body">
            <form method="POST" action="{{ route('classes.promotion.save') }}">
                @csrf
                <input type="hidden" name="class_level_id" value="{{ $level->id }}">
                <div class="form-group">
                    <label class="form-label">Minimum Average (%)</label>
                    <input type="number" name="min_required_average" class="form-control" value="{{ old('min_required_average', optional($rule)->min_required_average ?? 40) }}" min="0" max="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Failed Subjects Allowed</label>
                    <input type="number" name="max_failed_subjects_allowed" class="form-control" value="{{ old('max_failed_subjects_allowed', optional($rule)->max_failed_subjects_allowed ?? 3) }}" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Compulsory Subjects (must pass)</label>
                    <div class="compulsory-grid">
                        @foreach($subjects as $subject)
                        <label class="check-label">
                            <input type="checkbox" name="compulsory_subject_ids[]" value="{{ $subject->id }}"
                                {{ in_array($subject->id, optional($rule)->compulsory_subject_ids ?? []) ? 'checked' : '' }}>
                            {{ $subject->name }}
                        </label>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Rule</button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endsection
