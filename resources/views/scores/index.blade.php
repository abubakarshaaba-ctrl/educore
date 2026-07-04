@extends('layouts.app')
@section('title', 'Score Entry')
@section('page-title', 'Score Entry')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px; }
    .page-tab { padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active,.page-tab:hover { background:var(--indigo);border-color:var(--indigo);color:white; }
    
    .selector-card { background:white;border:1px solid var(--border);border-radius:12px;padding:28px;box-shadow:0 1px 3px rgba(0,0,0,0.05); }
    .pg-split { display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start; }
    @media(max-width:900px) { .pg-split { grid-template-columns:1fr; } }
    .selector-title { font-size:17px;font-weight:700;color:var(--midnight);margin-bottom:6px;letter-spacing:-0.02em; }
    .selector-sub { font-size:13px;color:var(--slate);margin-bottom:24px; }
    .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px; }
    .form-group { display:flex;flex-direction:column;gap:6px; }
    .form-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .form-label span { color:var(--crimson); }
    .form-control { padding:10px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:11px 22px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .info-box { background:var(--indigo-bg);border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--indigo);margin-top:20px; }
    @media(max-width:640px){.form-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

{{-- ── Score Entry Progress ─────────────────────────────────────── --}}
@if(!empty($progress) && $currentTerm)
<div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:13px;font-weight:700">📊 Score Entry Progress — {{ $currentTerm->name }}</span>
        <span style="font-size:12px;color:var(--slate-light)">{{ collect($progress)->where('pct',100)->count() }}/{{ count($progress) }} classes complete</span>
    </div>
    <div style="padding:16px 18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px">
        @foreach($progress as $armId => $p)
        <div style="border:1px solid var(--border);border-radius:10px;padding:12px;background:{{ $p['pct']>=100?'#F0FDF4':($p['pct']>=50?'#FFFBEB':'white') }}">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <span style="font-size:13px;font-weight:700">{{ $p['arm']->classLevel->name ?? '' }} {{ $p['arm']->name }}</span>
                <span style="font-size:12px;font-weight:800;color:{{ $p['pct']>=100?'#059669':($p['pct']>=50?'#D97706':'#DC2626') }}">{{ $p['pct'] }}%</span>
            </div>
            <div style="background:#E2E8F0;border-radius:4px;height:6px;overflow:hidden;margin-bottom:8px">
                <div style="height:100%;width:{{ $p['pct'] }}%;background:{{ $p['pct']>=100?'#059669':($p['pct']>=50?'#D97706':'#DC2626') }};border-radius:4px;transition:width 600ms"></div>
            </div>
            <div style="font-size:11px;color:var(--slate-light)">
                {{ $p['entered'] }} of {{ $p['expected'] }} scores entered
                · {{ $p['students'] }} students · {{ $p['subjects'] }} subjects
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="page-tabs">
    <a href="{{ route('scores.index') }}"          class="page-tab active">Score Entry</a>
    @if(auth()->user()->canAccessModule('scores.view') || auth()->user()->canAccessExactModule('scores'))
    <a href="{{ route('scores.broadsheet') }}"     class="page-tab">Broadsheet</a>
    @endif
    @if(auth()->user()->canAccessExactModule('scores'))
    <a href="{{ route('scores.assessment-types') }}" class="page-tab">Assessment Types</a>
    @endif
</div>

<div class="pg-split">
<div class="selector-card">
    <div class="selector-title">Open Score Entry Sheet</div>
    <div class="selector-sub">Select a class, subject and term — all assessment columns load on one sheet.</div>

    @if($classArms->isEmpty() && !auth()->user()->isAdmin() && !auth()->user()->isSuperAdmin())
    <div style="background:#FFFBEB;border:1px solid #FCD34D;color:#92400E;padding:12px 16px;border-radius:8px;margin-bottom:14px;font-size:13px">
        ⚠️ You haven't been assigned to teach any subject in any class yet, so there's nothing to select here.
        Ask your administrator to assign you to a class and subject under Classes → Assign Subject.
    </div>
    @endif

    <form method="GET" action="{{ route('scores.entry') }}">
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
                <label class="form-label">Subject <span>*</span></label>
                <select name="subject_id" class="form-control" required>
                    <option value="">Select subject</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Term <span>*</span></label>
                <select name="term_id" class="form-control" required>
                    <option value="">Select term</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>
                            {{ $term->name }} — {{ $term->session->name ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Open Score Sheet</button>
    </form>

    <div class="info-box">
        ℹ️ All assessment types (CA1, CA2, Exam, etc.) for the selected term will appear as columns on the score sheet. Set up assessment types under <strong>Assessment Types</strong> tab first.
    </div>
</div>

<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card" style="position:sticky;top:calc(var(--header-h) + 16px)">
        <div class="ch">How Score Entry Works</div>
        <div class="cb" style="font-size:13px;color:var(--slate);line-height:1.7">
            <p style="margin-bottom:10px">1. <strong style="color:var(--midnight)">Select</strong> a class, subject, and term above to open the score entry sheet.</p>
            <p style="margin-bottom:10px">2. <strong style="color:var(--midnight)">Enter scores</strong> for each student across all assessment columns (CA1, CA2, Exam, etc.).</p>
            <p style="margin-bottom:10px">3. <strong style="color:var(--midnight)">Save</strong> — scores are stored per assessment type and totaled automatically.</p>
            <p>4. Once all subjects are entered, go to <strong style="color:var(--midnight)">Report Cards → Generate</strong> to compute positions.</p>
        </div>
    </div>
</div>
</div>
@endsection
