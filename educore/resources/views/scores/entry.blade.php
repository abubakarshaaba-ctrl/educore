@extends('layouts.app')
@section('title', 'Score Entry Sheet')
@section('page-title', 'Score Entry')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }

    .context-bar { background:white;border:1px solid var(--border);border-radius:10px;padding:14px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px; }
    .context-info h2 { font-size:15px;font-weight:700;color:var(--midnight); }
    .context-info p { font-size:12px;color:var(--slate);margin-top:2px; }

    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .alert-warning { background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--amber);margin-bottom:16px; }

    /* Score sheet table */
    .sheet-wrap { overflow-x:auto; }
    .sheet-table { width:100%;border-collapse:collapse;background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);min-width:600px; }

    .sheet-table thead tr:first-child th {
        background:var(--midnight);color:white;padding:10px 14px;
        font-size:11px;font-weight:600;text-align:center;
        text-transform:uppercase;letter-spacing:0.05em;
        border-right:1px solid rgba(255,255,255,0.08);
    }
    .sheet-table thead tr:first-child th.student-col { text-align:left;min-width:200px; }
    .sheet-table thead tr:second-child th,
    .sheet-table thead tr:nth-child(2) th {
        background:#F8FAFC;font-size:10px;font-weight:600;color:var(--slate-light);
        text-align:center;padding:6px 8px;border-bottom:2px solid var(--border);
        border-right:1px solid var(--border);
    }

    .sheet-table tbody td {
        padding:8px 12px;border-bottom:1px solid var(--border);
        border-right:1px solid var(--border);font-size:13px;
        vertical-align:middle;
    }
    .sheet-table tbody td.student-col { text-align:left; }
    .sheet-table tbody td.score-col { text-align:center;padding:6px 8px; }
    .sheet-table tbody td.total-col {
        text-align:center;font-weight:700;background:#F8FAFC;
        border-left:2px solid var(--border);
    }
    .sheet-table tbody tr:last-child td { border-bottom:none; }
    .sheet-table tbody tr:hover td { background:#FAFBFF; }
    .sheet-table tbody tr:hover td.total-col { background:#EFF6FF; }

    .student-name { font-weight:600;color:var(--midnight); }
    .student-adm  { font-size:11px;color:var(--slate-light);margin-top:2px; }

    /* Score input */
    .score-input {
        width:62px;padding:6px 8px;font-size:13px;font-weight:600;
        text-align:center;border:1.5px solid var(--border);border-radius:7px;
        background:white;outline:none;transition:border-color 150ms,box-shadow 150ms;
        font-family:inherit;color:var(--midnight);
    }
    .score-input:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
    .score-input.has-value { background:#ECFDF5;border-color:#A7F3D0;color:var(--emerald); }
    .score-input.over-limit { background:#FEF2F2;border-color:#FECACA;color:var(--crimson); }

    .max-label { font-size:10px;color:var(--slate-light);margin-top:2px;display:block;text-align:center; }

    /* Split (objective + theory) cell */
    .split-cell { display:flex;flex-direction:column;gap:4px;align-items:center; }
    .split-obj { font-size:10.5px;font-weight:700;color:var(--slate);background:#F1F5F9;border-radius:6px;padding:3px 7px;white-space:nowrap; }
    .split-obj.missing { color:var(--crimson);background:#FEF2F2; }
    .split-theory-input { width:56px;padding:5px 6px;font-size:12.5px;font-weight:600;text-align:center;border:1.5px solid var(--border);border-radius:6px;background:white;outline:none;font-family:inherit;color:var(--midnight); }
    .split-theory-input:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
    .split-theory-input.has-value { background:#ECFDF5;border-color:#A7F3D0;color:var(--emerald); }
    .split-total { font-size:10.5px;color:var(--slate-light); }

    .total-val { font-size:15px; }
    .total-green { color:var(--emerald); }
    .total-amber { color:var(--amber); }
    .total-red   { color:var(--crimson); }

    .sheet-footer { padding:14px 20px;border-top:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap; }
    .footer-info { font-size:12px;color:var(--slate); }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border);padding:9px 16px; }

    .no-assessments { text-align:center;padding:50px;color:var(--slate-light); }
    .no-assessments h3 { font-size:15px;font-weight:600;color:var(--slate);margin-bottom:8px; }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('scores.index') }}"            class="page-tab">Score Entry</a>
    <a href="{{ route('scores.broadsheet') }}"       class="page-tab">Broadsheet</a>
    <a href="{{ route('scores.assessment-types') }}" class="page-tab">Assessment Types</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="context-bar">
    <div class="context-info">
        <h2>{{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ $subject->name }} — {{ $term->name }}</h2>
        <p>{{ $students->count() }} students · {{ $assessmentTypes->count() }} assessment column(s) · Enter scores and save</p>
    </div>
    <a href="{{ route('scores.index') }}" class="btn btn-ghost">← Back</a>
</div>

@if($assessmentTypes->isEmpty())
<div class="sheet-table">
    <div class="no-assessments">
        <h3>No assessment types set up for this term</h3>
        <p>Set up CA1, CA2, Exam, etc. under Assessment Types before entering scores.</p>
        <a href="{{ route('scores.assessment-types') }}" class="btn btn-primary" style="margin-top:14px">Set Up Assessment Types</a>
    </div>
</div>
@else

@php $maxTotal = $assessmentTypes->sum('weight_percentage'); @endphp

<form method="POST" action="{{ route('scores.save') }}">
    @csrf
    <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
    <input type="hidden" name="subject_id"   value="{{ $subject->id }}">
    <input type="hidden" name="term_id"      value="{{ $term->id }}">

    <div class="sheet-wrap">
        <div class="tbl"><table class="sheet-table">
            <thead>
                <tr>
                    <th class="student-col" rowspan="2" style="vertical-align:middle;text-align:left">
                        Student
                    </th>
                    @foreach($assessmentTypes as $at)
                    <th>
                        {{ $at->name }}
                        @if($at->is_exam)<span style="font-size:9px;opacity:0.7"> EXAM</span>@endif
                        @if($at->isSplit())<span style="font-size:9px;opacity:0.7"> (OBJ+THEORY)</span>@endif
                    </th>
                    @endforeach
                    <th style="background:#1E3A5F">Total / {{ $maxTotal }}</th>
                </tr>
                <tr>
                    @foreach($assessmentTypes as $at)
                    <th style="background:#F8FAFC;color:var(--slate);font-size:10px;padding:4px 8px;border-bottom:1px solid var(--border);border-right:1px solid var(--border)">
                        @if($at->isSplit())
                            Obj {{ $at->objective_max }} + Theory {{ $at->theory_max }}
                            @if($objectiveExamMissing[$at->id] ?? false)
                                <br><span style="color:var(--crimson)">No CBT tagged</span>
                            @endif
                        @else
                            Max: {{ $at->weight_percentage }}
                        @endif
                    </th>
                    @endforeach
                    <th style="background:#EFF6FF;border-bottom:1px solid var(--border);border-right:1px solid var(--border)"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $i => $student)
                <tr>
                    <td class="student-col">
                        <div class="student-name">{{ $student->full_name }}</div>
                        <div class="student-adm">{{ $student->admission_number }}</div>
                    </td>

                    @foreach($assessmentTypes as $at)
                    @if($at->isSplit())
                        @php
                            $obj = $objectiveScores[$student->id][$at->id] ?? null;
                            $theoryVal = $existingTheory[$student->id][$at->id] ?? '';
                        @endphp
                        <td class="score-col">
                            <div class="split-cell">
                                <span class="split-obj {{ $obj === null ? 'missing' : '' }}">
                                    Obj: {{ $obj === null ? '—' : $obj }}/{{ $at->objective_max }}
                                </span>
                                <input
                                    type="number"
                                    name="scores[{{ $student->id }}][{{ $at->id }}]"
                                    class="split-theory-input {{ $theoryVal !== '' ? 'has-value' : '' }}"
                                    value="{{ $theoryVal !== '' ? $theoryVal : '' }}"
                                    min="0"
                                    max="{{ $at->theory_max }}"
                                    step="0.5"
                                    placeholder="Theory"
                                    title="Theory score (max {{ $at->theory_max }})"
                                    data-max="{{ $at->theory_max }}"
                                    data-objective="{{ $obj ?? 0 }}"
                                    data-split-cap="{{ $at->weight_percentage }}"
                                    data-student="{{ $student->id }}"
                                    oninput="updateTotal({{ $student->id }}); validateInput(this); updateSplitTotal({{ $student->id }}, {{ $at->id }})"
                                >
                                <span class="split-total" id="split_total_{{ $student->id }}_{{ $at->id }}">
                                    = {{ ($obj !== null && $theoryVal !== '') ? min($obj + (float) $theoryVal, $at->weight_percentage) : '—' }}
                                </span>
                            </div>
                        </td>
                    @else
                    @php $val = $existingScores[$student->id][$at->id] ?? ''; @endphp
                    <td class="score-col">
                        <input
                            type="number"
                            name="scores[{{ $student->id }}][{{ $at->id }}]"
                            class="score-input {{ $val !== '' ? 'has-value' : '' }}"
                            value="{{ $val !== '' ? $val : '' }}"
                            min="0"
                            max="{{ $at->weight_percentage }}"
                            step="0.5"
                            placeholder="—"
                            data-max="{{ $at->weight_percentage }}"
                            data-student="{{ $student->id }}"
                            oninput="updateTotal({{ $student->id }}); validateInput(this)"
                        >
                    </td>
                    @endif
                    @endforeach

                    <td class="total-col">
                        @php $t = $studentTotals[$student->id] ?? 0; @endphp
                        <span class="total-val {{ $t >= $maxTotal * 0.5 ? 'total-green' : ($t >= $maxTotal * 0.3 ? 'total-amber' : 'total-red') }}"
                              id="total_{{ $student->id }}">
                            {{ $t > 0 ? $t : '—' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>

    <div class="sheet-footer">
        <div class="footer-info">
            Total possible score: <strong>{{ $maxTotal }}</strong> ·
            Scores are saved per-assessment and auto-summed
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('scores.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                Save All Scores
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
// Assessment type max values keyed by ID
const atMaxValues = {
    @foreach($assessmentTypes as $at)
    {{ $at->id }}: {{ $at->weight_percentage }},
    @endforeach
};

// Student IDs and their assessment type IDs
const studentIds = @json($students->pluck('id'));
const atIds = @json($assessmentTypes->pluck('id'));

function updateTotal(studentId) {
    let total = 0;
    atIds.forEach(atId => {
        const input = document.querySelector(
            `input[name="scores[${studentId}][${atId}]"]`
        );
        if (!input) return;
        const val = parseFloat(input.value || 0) || 0;
        const objective = parseFloat(input.dataset.objective || 0) || 0;
        const cap = parseFloat(input.dataset.splitCap || atMaxValues[atId]);
        // Split cells (data-objective present) contribute objective+theory,
        // capped at the assessment's total weight; plain cells contribute
        // their single value as before.
        total += input.dataset.objective !== undefined
            ? Math.min(val + objective, cap)
            : val;
    });

    const el = document.getElementById('total_' + studentId);
    if (!el) return;

    const maxTotal = {{ $maxTotal }};
    el.textContent = total > 0 ? total.toFixed(total % 1 === 0 ? 0 : 1) : '—';
    el.className = 'total-val ' + (
        total >= maxTotal * 0.5 ? 'total-green' :
        total >= maxTotal * 0.3 ? 'total-amber' : 'total-red'
    );
}

function updateSplitTotal(studentId, atId) {
    const input = document.querySelector(`input[name="scores[${studentId}][${atId}]"]`);
    const el = document.getElementById(`split_total_${studentId}_${atId}`);
    if (!input || !el) return;

    const objective = parseFloat(input.dataset.objective || 0) || 0;
    const cap = parseFloat(input.dataset.splitCap);
    if (input.value === '') {
        el.textContent = '—';
        return;
    }
    const theory = parseFloat(input.value) || 0;
    const total = Math.min(objective + theory, cap);
    el.textContent = '= ' + total.toFixed(total % 1 === 0 ? 0 : 1);
}

function validateInput(input) {
    const max = parseFloat(input.dataset.max);
    const val = parseFloat(input.value);

    input.classList.remove('has-value', 'over-limit');
    if (input.value === '') return;

    if (val > max) {
        input.classList.add('over-limit');
        input.title = `Max allowed: ${max}`;
    } else {
        input.classList.add('has-value');
        input.title = '';
    }
}

// Tab key navigation — move to next input
document.querySelectorAll('.score-input, .split-theory-input').forEach((input, idx, all) => {
    input.addEventListener('keydown', e => {
        if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            const next = all[idx + 1];
            if (next) next.focus();
        }
    });
});
</script>
@endpush

@endif
@endsection
