@extends('layouts.app')
@section('title','Class Arm Track Assignment')
@section('page-title','Curriculum')

@push('styles')
<style>
.ctabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content}
.ctab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms}
.ctab.active,.ctab:hover{background:var(--indigo);color:white}
.level-section{margin-bottom:24px}
.level-head{font-size:14px;font-weight:800;color:var(--midnight);padding-bottom:8px;border-bottom:2px solid var(--border);margin-bottom:12px;display:flex;align-items:center;gap:10px}
.arm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px}
.arm-card{background:white;border:1px solid var(--border);border-radius:10px;padding:14px;transition:box-shadow 150ms}
.arm-card:hover{box-shadow:0 4px 12px rgba(0,0,0,0.07)}
.arm-name{font-size:13px;font-weight:700;color:var(--midnight)}
.arm-track{font-size:11px;margin-top:4px}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-science{background:#EFF6FF;color:#1D4ED8}.b-humanities{background:#F0FDF4;color:#059669}
.b-business{background:#FFFBEB;color:#D97706}.b-general{background:#F1F5F9;color:#64748B}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--indigo);color:white}.btn-sm{padding:5px 10px;font-size:11px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
select.arm-sel{font-size:12px;padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;background:#F8FAFC;font-family:inherit;margin-top:8px;width:100%}
select.arm-sel:focus{border-color:var(--indigo)}

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
<div class="ctabs">
    <a href="{{ route('curriculum.tracks') }}"     class="ctab">📋 Tracks</a>
    <a href="{{ route('curriculum.arm-tracks') }}" class="ctab active">🏫 Arm Assignments</a>
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 16px;font-size:13px;color:#1D4ED8;margin-bottom:20px">
    🏫 Assign each <strong>senior class arm</strong> to an academic track (Science, Humanities, Business).
    Junior class arms use the <em>General</em> track automatically — no assignment needed.
    After assignment, go to <a href="{{ route('curriculum.tracks') }}" style="color:var(--indigo);font-weight:600">Tracks → Level Subject Rules</a> to define subjects per track.
</div>

@foreach($levels as $level)
@php $levelArms = $arms->get($level->id, collect()); @endphp
@if($levelArms->isNotEmpty())
<div class="level-section">
    <div class="level-head">
        {{ $level->name }}
        <a href="{{ route('curriculum.level-subjects', $level) }}" style="font-size:12px;color:var(--indigo);font-weight:600;text-decoration:none">⚙️ Subject Rules →</a>
        <span style="font-size:12px;color:var(--slate-light);margin-left:auto">{{ $levelArms->count() }} arm(s)</span>
    </div>
    <div class="arm-grid">
    @foreach($levelArms as $arm)
    @php
        $tname = optional($arm->academicTrack)->name ?? 'General';
        $tslug = optional($arm->academicTrack)->slug ?? 'general';
        $bdg   = 'b-' . $tslug;
    @endphp
    <div class="arm-card">
        <div class="arm-name">{{ $arm->name }}</div>
        <div class="arm-track">
            <span class="badge {{ $bdg }}">{{ $tname }}</span>
            <span style="font-size:11px;color:var(--slate-light);margin-left:6px">{{ $armSubjectCounts[$arm->id] ?? 0 }} subject rule(s)</span>
        </div>
        <form method="POST" action="{{ route('curriculum.arm-tracks.set', $arm->id) }}" style="margin-top:8px">
            @csrf @method('PATCH')
            <select name="academic_track_id" class="arm-sel" onchange="this.form.submit()">
                <option value="">General (no track)</option>
                @foreach($tracks as $t)
                <option value="{{ $t->id }}" {{ $arm->academic_track_id == $t->id ? 'selected':'' }}>
                    {{ $t->name }} ({{ ucfirst($t->section) }})
                </option>
                @endforeach
            </select>
        </form>
        <div style="margin-top:8px">
            <a href="{{ route('curriculum.arm-teachers', $arm->id) }}" style="font-size:11px;color:var(--indigo);font-weight:600">👨‍🏫 Teacher Allocation →</a>
        </div>
    </div>
    @endforeach
    </div>
</div>
@endif
@endforeach
@endsection
