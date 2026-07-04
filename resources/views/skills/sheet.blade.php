@extends('layouts.app')
@section('title', 'Skill Rating Sheet')
@section('page-title', 'Psychomotor & Affective Skills')

@push('styles')
<style>
    .context-bar { background: white; border: 1px solid var(--border); border-radius: 10px; padding: 14px 20px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .context-info h2 { font-size: 15px; font-weight: 700; color: var(--midnight); letter-spacing: -0.02em; }
    .context-info p { font-size: 13px; color: var(--slate); margin-top: 2px; }

    .rating-legend { display: flex; gap: 8px; flex-wrap: wrap; }
    .r-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 5px; }
    .r5b { background: #ECFDF5; color: #059669; }
    .r4b { background: #EFF6FF; color: #2563EB; }
    .r3b { background: #FFFBEB; color: #D97706; }
    .r2b { background: #FFF7ED; color: #EA580C; }
    .r1b { background: #FEF2F2; color: #DC2626; }

    .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: var(--emerald); margin-bottom: 16px; }

    .sheet-card { background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 14px; }
    .sheet-header { padding: 13px 20px; border-bottom: 2px solid var(--border); display: flex; align-items: center; gap: 10px; background: #F8FAFC; }
    .sheet-header-icon { width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; }
    .icon-psycho { background: var(--indigo-bg); color: var(--indigo); }
    .icon-affect  { background: #ECFDF5; color: var(--emerald); }
    .sheet-header-icon svg { width: 15px; height: 15px; }
    .sheet-title { font-size: 14px; font-weight: 700; color: var(--midnight); }
    .sheet-subtitle { font-size: 12px; color: var(--slate-light); margin-left: auto; }

    .ratings-table { width: 100%; border-collapse: collapse; }
    .ratings-table thead th {
        font-size: 11px; font-weight: 600; color: var(--slate-light);
        text-transform: uppercase; letter-spacing: 0.05em;
        padding: 10px 14px; text-align: left; background: #FAFBFF;
        border-bottom: 1px solid var(--border);
    }
    .ratings-table thead th.skill-th { text-align: center; min-width: 100px; }
    .ratings-table tbody td {
        padding: 10px 14px; border-bottom: 1px solid var(--border);
        font-size: 13px; vertical-align: middle;
    }
    .ratings-table tbody tr:last-child td { border-bottom: none; }
    .ratings-table tbody tr:hover td { background: #F8FAFC; }

    .student-name { font-weight: 600; color: var(--midnight); }
    .student-adm  { font-size: 11px; color: var(--slate-light); }

    .star-group { display: flex; gap: 4px; justify-content: center; }
    .star-radio { display: none; }
    .star-label {
        width: 28px; height: 28px;
        border: 1.5px solid var(--border);
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700;
        color: var(--slate-light);
        cursor: pointer;
        transition: all 150ms;
        user-select: none;
    }
    .star-label:hover { border-color: var(--indigo); color: var(--indigo); background: var(--indigo-bg); }
    .star-radio:checked + .star-label { color: white; border-color: transparent; }
    .star-radio[value="5"]:checked + .star-label { background: #059669; }
    .star-radio[value="4"]:checked + .star-label { background: #2563EB; }
    .star-radio[value="3"]:checked + .star-label { background: #D97706; }
    .star-radio[value="2"]:checked + .star-label { background: #EA580C; }
    .star-radio[value="1"]:checked + .star-label { background: #DC2626; }

    .footer-bar { padding: 16px 20px; border-top: 1px solid var(--border); background: #F8FAFC; display: flex; align-items: center; justify-content: space-between; }
    .footer-count { font-size: 13px; color: var(--slate); }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; font-size: 13px; font-weight: 600; font-family: inherit; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; transition: background 150ms; }
    .btn-primary { background: var(--indigo); color: white; }
    .btn-primary:hover { background: #1D4ED8; }
    .btn-ghost { background: white; color: var(--midnight); border: 1px solid var(--border); }
    .btn-ghost:hover { background: #F8FAFC; }
    .btn svg { width: 14px; height: 14px; }
</style>

    
@endpush

@section('content')

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="context-bar">
    <div class="context-info">
        <h2>{{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ $term->name }}</h2>
        <p>{{ $students->count() }} students · Rate each skill from 1 (Poor) to 5 (Excellent)</p>
    </div>
    <div class="rating-legend">
        <span class="r-badge r5b">5 Excellent</span>
        <span class="r-badge r4b">4 Very Good</span>
        <span class="r-badge r3b">3 Good</span>
        <span class="r-badge r2b">2 Fair</span>
        <span class="r-badge r1b">1 Poor</span>
    </div>
</div>

<form method="POST" action="{{ route('skills.save') }}">
    @csrf
    <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
    <input type="hidden" name="term_id" value="{{ $term->id }}">

    {{-- PSYCHOMOTOR SKILLS --}}
    <div class="sheet-card">
        <div class="sheet-header">
            <div class="sheet-header-icon icon-psycho">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg>
            </div>
            <span class="sheet-title">Psychomotor Skills</span>
            <span class="sheet-subtitle">Practical & physical competencies</span>
        </div>
        <div class="tbl"><table class="ratings-table">
            <thead>
                <tr>
                    <th style="width:220px">Student</th>
                    @foreach($psychomotorSkills as $skill)
                        <th class="skill-th">{{ $skill->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($students as $student)
                <tr>
                    <td>
                        <div class="student-name">{{ $student->full_name }}</div>
                        <div class="student-adm">{{ $student->admission_number }}</div>
                    </td>
                    @foreach($psychomotorSkills as $skill)
                    @php
                        $existing = $existingRatings->get($student->id)?->get($skill->id)?->rating;
                    @endphp
                    <td>
                        <div class="star-group">
                            @foreach([1,2,3,4,5] as $val)
                            <input
                                type="radio"
                                class="star-radio"
                                name="ratings[{{ $student->id }}][{{ $skill->id }}]"
                                id="r_{{ $student->id }}_{{ $skill->id }}_{{ $val }}"
                                value="{{ $val }}"
                                {{ $existing == $val ? 'checked' : '' }}
                            >
                            <label for="r_{{ $student->id }}_{{ $skill->id }}_{{ $val }}" class="star-label">{{ $val }}</label>
                            @endforeach
                        </div>
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>

    {{-- AFFECTIVE SKILLS --}}
    <div class="sheet-card">
        <div class="sheet-header">
            <div class="sheet-header-icon icon-affect">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            </div>
            <span class="sheet-title">Affective Skills</span>
            <span class="sheet-subtitle">Behavioural & character traits</span>
        </div>
        <div class="tbl"><table class="ratings-table">
            <thead>
                <tr>
                    <th style="width:220px">Student</th>
                    @foreach($affectiveSkills as $skill)
                        <th class="skill-th">{{ $skill->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($students as $student)
                <tr>
                    <td>
                        <div class="student-name">{{ $student->full_name }}</div>
                        <div class="student-adm">{{ $student->admission_number }}</div>
                    </td>
                    @foreach($affectiveSkills as $skill)
                    @php
                        $existing = $existingRatings->get($student->id)?->get($skill->id)?->rating;
                    @endphp
                    <td>
                        <div class="star-group">
                            @foreach([1,2,3,4,5] as $val)
                            <input
                                type="radio"
                                class="star-radio"
                                name="ratings[{{ $student->id }}][{{ $skill->id }}]"
                                id="r_{{ $student->id }}_{{ $skill->id }}_{{ $val }}"
                                value="{{ $val }}"
                                {{ $existing == $val ? 'checked' : '' }}
                            >
                            <label for="r_{{ $student->id }}_{{ $skill->id }}_{{ $val }}" class="star-label">{{ $val }}</label>
                            @endforeach
                        </div>
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table></div>

        <div class="footer-bar">
            <span class="footer-count">
                {{ $students->count() }} students ·
                {{ $psychomotorSkills->count() + $affectiveSkills->count() }} skills per student
            </span>
            <div style="display:flex;gap:10px">
                <a href="{{ route('skills.index') }}" class="btn btn-ghost">← Back</a>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                    Save All Ratings
                </button>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
// Quick-fill all ratings in a row
function quickFill(studentId, value) {
    document.querySelectorAll(`input[data-student="${studentId}"][value="${value}"]`).forEach(el => {
        el.checked = true;
        el.dispatchEvent(new Event('change'));
    });
    updateProgress(studentId);
}

// Update per-student progress pill
function updateProgress(studentId) {
    const total   = document.querySelectorAll(`input[data-student="${studentId}"]`).length / 5;
    const rated   = new Set([...document.querySelectorAll(`input[data-student="${studentId}"]:checked`)].map(el => el.name)).size;
    const pill    = document.getElementById(`progress-${studentId}`);
    if (pill) {
        pill.textContent = `${rated}/${total} rated`;
        pill.className   = 'progress-pill' + (rated >= total ? ' complete' : '');
    }
}

// Track changes on all radios
document.querySelectorAll('.star-radio').forEach(r => {
    r.addEventListener('change', () => updateProgress(r.dataset.student));
});

// Init progress on load
document.querySelectorAll('.progress-pill').forEach(p => {
    const sid = p.id.replace('progress-','');
    updateProgress(sid);
});
</script>
@endpush
