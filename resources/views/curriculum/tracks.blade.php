@extends('layouts.app')
@section('title','Academic Tracks')
@section('page-title','Curriculum')

@push('styles')
<style>
.ctabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content}
.ctab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms}
.ctab.active,.ctab:hover{background:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.two{display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start}
.track-card{background:white;border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:14px}
.track-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.t-general{background:#EFF6FF}.t-senior{background:#FFFBEB}.t-junior{background:#F0FDF4}
.track-name{font-size:14px;font-weight:700;color:var(--midnight)}
.track-meta{font-size:12px;color:var(--slate-light);margin-top:2px}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-active{background:#ECFDF5;color:#059669}.b-inactive{background:#F1F5F9;color:#64748B}.b-sys{background:#EFF6FF;color:#2563EB}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 16px;font-size:13px;color:#1D4ED8;margin-bottom:18px}

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
    <a href="{{ route('curriculum.tracks') }}"    class="ctab active">📋 Tracks</a>
    <a href="{{ route('curriculum.arm-tracks') }}" class="ctab">🏫 Arm Assignments</a>
    <a href="{{ route('curriculum.backfill') }}"  class="ctab" onclick="return confirm('Backfill from existing class arm subjects?')" style="color:var(--amber)">⚡ Backfill</a>
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div class="info-box">
    📚 <strong>Academic Tracks</strong> define the curriculum pathways available in your school.
    Junior / General classes use the <em>General</em> track. Senior classes choose between <em>Science</em>, <em>Humanities</em>, or <em>Business</em>.
    System-wide tracks (marked <span style="background:#EFF6FF;color:#2563EB;padding:1px 6px;border-radius:4px;font-size:11px;font-weight:700">System</span>) cannot be deleted.
</div>

<div class="two">
    <div>
        @foreach($tracks as $t)
        @php
            $icon = match($t->slug) { 'science'=>'🔬','humanities'=>'📖','business'=>'💼','primary'=>'🎒','general'=>'📋',default=>'📋' };
            $cls  = $t->section === 'senior' ? 't-senior' : ($t->section === 'primary' ? 't-junior' : ($t->section === 'junior' ? 't-junior' : 't-general'));
        @endphp
        <div class="track-card" style="{{ !$t->is_active ? 'opacity:.6':'' }}">
            <div class="track-icon {{ $cls }}">{{ $icon }}</div>
            <div style="flex:1">
                <div class="track-name">{{ $t->name }}</div>
                <div class="track-meta">
                    Section: {{ ucfirst($t->section) }} ·
                    {{ $t->class_arms_count ?? 0 }} class arm(s)
                    @if(!$t->tenant_id) &nbsp; <span class="badge b-sys">System</span> @endif
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center">
                <span class="badge {{ $t->is_active ? 'b-active':'b-inactive' }}">{{ $t->is_active ? 'Active':'Inactive' }}</span>
                <span style="font-size:11px;color:var(--slate-light)">{{ $trackLevelCounts[$t->id] ?? 0 }} level rule(s)</span>
                <form method="POST" action="{{ route('curriculum.tracks.toggle', $t->id) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-ghost" style="padding:5px 10px;font-size:11px">
                        {{ $t->is_active ? 'Disable':'Enable' }}
                    </button>
                </form>
                @if($t->tenant_id)
                <form method="POST" action="{{ route('curriculum.tracks.destroy', $t->id) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger" style="padding:5px 10px;font-size:11px" onclick="return confirm('Delete track?')">✕</button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="ch">➕ Create Custom Track</div>
        <div style="padding:18px">
        <form method="POST" action="{{ route('curriculum.tracks.store') }}">
            @csrf
            <div class="fg"><label class="fl">Track Name *</label>
                <input name="name" class="fc" required placeholder="e.g. Technical / Vocational"></div>
            <div class="fg"><label class="fl">Section *</label>
                <select name="section" class="fc" required>
                    <option value="primary">Primary (Basic 1–6)</option>
                    <option value="general">General / Junior</option>
                    <option value="junior">Junior Secondary</option>
                    <option value="senior">Senior Secondary</option>
                </select>
            </div>
            <button type="submit" class="btn btn-p" style="width:100%;justify-content:center">+ Create Track</button>
        </form>
        </div>
    </div>
</div>

{{-- Backfill form (hidden, triggered by tab link) --}}
<form method="POST" action="{{ route('curriculum.backfill') }}" id="backfillForm" style="display:none">@csrf</form>
@endsection
@push('scripts')
<script>
// Intercept backfill tab click
document.querySelectorAll('a[href="{{ route('curriculum.backfill') }}"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        if(confirm('Backfill class_level_subjects from existing class_arm_subjects? Safe to run multiple times.')) {
            document.getElementById('backfillForm').submit();
        }
    });
});
</script>
@endpush
