@extends('layouts.app')
@section('title', 'Timetable')
@section('page-title', 'Timetable')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none; }
    .page-tabs::-webkit-scrollbar{display:none}
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms;white-space:nowrap;flex:0 0 auto; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .selector-card { background:white;border:1px solid var(--border);border-radius:12px;padding:28px;box-shadow:0 1px 3px rgba(0,0,0,0.05);min-width:0; }
    .pg-split { display:grid;grid-template-columns:minmax(0,1fr) minmax(240px,280px);gap:20px;align-items:start; }
    .pg-split>*{min-width:0}
    .selector-title { font-size:17px;font-weight:700;color:var(--midnight);margin-bottom:6px;letter-spacing:-0.02em;overflow-wrap:anywhere; }
    .selector-sub { font-size:13px;color:var(--slate);margin-bottom:24px;line-height:1.5;overflow-wrap:anywhere; }
    .form-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:20px; }
    .form-group { display:flex;flex-direction:column;gap:6px;min-width:0; }
    .form-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .form-label span { color:var(--crimson); }
    .form-control { box-sizing:border-box;width:100%;min-width:0;padding:10px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .btn { display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:11px 22px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:background 150ms;min-height:42px; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .tips-card{position:sticky;top:calc(var(--header-h) + 16px)}

@media (max-width: 1024px) {
    .pg-split { grid-template-columns: 1fr; }
    .tips-card{position:static}
    .two-col { grid-template-columns: 1fr !important; }
    .stats-row, .stat-row { grid-template-columns: repeat(2, 1fr) !important; }
    .kpi { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 768px) {
    .page-tabs{width:100%;margin-bottom:14px}
    .selector-card{padding:20px}
    .form-grid{grid-template-columns:1fr;gap:12px}
    .selector-card .btn{width:100%}
}
@media (max-width: 640px) {
    .two, .fr { grid-template-columns: 1fr !important; }
}
@media (max-width: 480px) {
    .fr3 { grid-template-columns: 1fr !important; }
    .selector-card{padding:14px}
    .selector-title{font-size:15px}
    .selector-sub{font-size:12px;margin-bottom:18px}
    .page-tab{padding:7px 11px;font-size:11px}
}
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('timetable.index') }}" class="page-tab active">Class Timetable</a>
    <a href="{{ route('timetable.teacher') }}" class="page-tab">Teacher Timetable</a>
</div>

<div class="pg-split">
<div class="selector-card">
    <div class="selector-title">Class Timetable</div>
    <div class="selector-sub">Select a class and session to view or edit the weekly timetable.</div>
    <form method="GET" action="{{ route('timetable.view') }}">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Class <span>*</span></label>
                <select name="class_arm_id" class="form-control" required>
                    <option value="">Select class</option>
                    @foreach($classArms as $arm)
                        <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                    @endforeach
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
        </div>
        <button type="submit" class="btn btn-primary">Open Timetable</button>
    </form>
</div>

<div>
    <div class="card tips-card">
        <div class="ch">Timetable Tips</div>
        <div class="cb" style="font-size:13px;color:var(--slate);line-height:1.7">
            <p style="margin-bottom:10px">Select a class and session to view or build that class's weekly timetable.</p>
            <p style="margin-bottom:10px"><strong style="color:var(--midnight)">Teacher Timetable</strong> tab shows all periods assigned to a specific teacher across all classes.</p>
            <p>Periods are configured under <strong style="color:var(--midnight)">Timetable → Configure</strong> before assigning subjects to slots.</p>
        </div>
    </div>
</div>
</div>
@endsection
