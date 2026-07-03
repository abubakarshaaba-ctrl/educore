@extends('layouts.app')
@section('title', 'Skill Ratings')
@section('page-title', 'Psychomotor & Affective Skills')

@push('styles')
<style>
    .selector-card { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .pg-split { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
    @media(max-width:900px) { .pg-split { grid-template-columns:1fr; } }
    .selector-title { font-size: 17px; font-weight: 700; color: var(--midnight); margin-bottom: 6px; letter-spacing: -0.02em; }
    .selector-sub { font-size: 13px; color: var(--slate); margin-bottom: 24px; line-height: 1.5; }

    .skills-preview { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px; }
    .skill-group { background: #F8FAFC; border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; }
    .skill-group-title { font-size: 11px; font-weight: 700; color: var(--indigo); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 10px; }
    .skill-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--midnight); padding: 4px 0; }
    .skill-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--indigo); flex-shrink: 0; }
    .skill-group.affective .skill-group-title { color: var(--emerald); }
    .skill-group.affective .skill-dot { background: var(--emerald); }

    .rating-scale { background: var(--indigo-bg); border: 1px solid #BFDBFE; border-radius: 8px; padding: 12px 16px; }
    .rating-scale-title { font-size: 11px; font-weight: 700; color: var(--indigo); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .rating-scale-items { display: flex; gap: 10px; flex-wrap: wrap; }
    .rating-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 6px; }
    .r5 { background: #ECFDF5; color: #059669; }
    .r4 { background: #EFF6FF; color: #2563EB; }
    .r3 { background: #FFFBEB; color: #D97706; }
    .r2 { background: #FFF7ED; color: #EA580C; }
    .r1 { background: #FEF2F2; color: #DC2626; }

    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .form-label { font-size: 11px; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: 0.05em; }
    .form-label span { color: var(--crimson); }
    .form-control { padding: 10px 12px; font-size: 13px; font-family: inherit; border: 1px solid var(--border); border-radius: 8px; background: #F8FAFC; outline: none; transition: border-color 200ms; width: 100%; }
    .form-control:focus { border-color: var(--indigo); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); background: white; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 11px 22px; font-size: 13px; font-weight: 600; font-family: inherit; border-radius: 8px; border: none; cursor: pointer; transition: background 150ms; width: 100%; }
    .btn-primary { background: var(--indigo); color: white; }
    .btn-primary:hover { background: #1D4ED8; }
</style>
@endpush

@section('content')
<div class="pg-split">

{{-- Left: info panel --}}
<div class="selector-card">
    <div class="selector-title">Psychomotor & Affective Skills</div>
    <div class="selector-sub">Rate each student on practical skills and character traits. Select the class and term to open the rating sheet.</div>

    <div class="skills-preview">
        <div class="skill-group">
            <div class="skill-group-title">Psychomotor Skills</div>
            @foreach(['Handwriting','Drawing & Painting','Sports & Games','Verbal Fluency','Handling of Tools'] as $s)
            <div class="skill-item"><span class="skill-dot"></span>{{ $s }}</div>
            @endforeach
        </div>
        <div class="skill-group affective">
            <div class="skill-group-title">Affective Skills</div>
            @foreach(['Punctuality','Attentiveness','Neatness','Honesty','Relationship with Others'] as $s)
            <div class="skill-item"><span class="skill-dot"></span>{{ $s }}</div>
            @endforeach
        </div>
    </div>

    <div class="rating-scale">
        <div class="rating-scale-title">Rating Scale</div>
        <div class="rating-scale-items">
            <span class="rating-badge r5">5 — Excellent</span>
            <span class="rating-badge r4">4 — Very Good</span>
            <span class="rating-badge r3">3 — Good</span>
            <span class="rating-badge r2">2 — Fair</span>
            <span class="rating-badge r1">1 — Poor</span>
        </div>
    </div>
</div>

{{-- Right: selector form --}}
<div style="position:sticky;top:calc(var(--header-h) + 16px)">
    <div class="selector-card">
        <form method="GET" action="{{ route('skills.sheet') }}">
            <div class="form-group">
                <label class="form-label">Class <span>*</span></label>
                @if($classArms->isEmpty())
                <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:10px 12px;font-size:12px;color:#92400E">
                    ⚠️ You are not assigned as form tutor of any class yet. Contact your administrator to assign you.
                </div>
                @else
                <select name="class_arm_id" class="form-control" required>
                    <option value="">Select class</option>
                    @foreach($classArms as $arm)
                        <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label">Term / Session <span>*</span></label>
                <select name="term_id" class="form-control" required>
                    <option value="">Select term</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>{{ $term->name }} — {{ $term->session->name ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Open Rating Sheet</button>
        </form>
    </div>
</div>

</div>
@endsection
