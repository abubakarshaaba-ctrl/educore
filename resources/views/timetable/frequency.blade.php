@extends('layouts.app')
@section('title', 'Subject Frequency')
@section('page-title', 'Timetable')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }

    .selector-card { background:white;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.05); }
    .selector-grid { display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:flex-end; }
    .form-group { display:flex;flex-direction:column;gap:5px; }
    .form-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .form-label span { color:var(--crimson); }
    .form-control { padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms;width:100%; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }

    .freq-card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    .freq-header { padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between; }
    .freq-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .freq-meta { font-size:12px;color:var(--slate); }

    .info-banner { background:var(--indigo-bg);border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--indigo);margin-bottom:16px;display:flex;align-items:center;gap:8px; }

    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 20px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:12px 20px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .subject-name { font-weight:600; }
    .teacher-name { font-size:12px;color:var(--slate-light);margin-top:2px; }

    .freq-input { width:70px;padding:7px 10px;font-size:14px;font-weight:700;text-align:center;border:2px solid var(--border);border-radius:8px;background:white;outline:none;transition:border-color 150ms;color:var(--midnight); }
    .freq-input:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
    .freq-stepper { display:flex;align-items:center;gap:6px; }
    .step-btn { width:28px;height:28px;border-radius:50%;border:1.5px solid var(--border);background:white;cursor:pointer;font-size:16px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:center;transition:all 150ms; }
    .step-btn:hover { border-color:var(--indigo);color:var(--indigo);background:var(--indigo-bg); }
    .per-week { font-size:11px;color:var(--slate-light);margin-left:4px; }

    .total-bar { display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);background:#F8FAFC; }
    .total-info { font-size:13px;color:var(--slate); }
    .total-count { font-size:15px;font-weight:700;color:var(--midnight); }
    .total-warn { color:var(--crimson); }
    .total-ok { color:var(--emerald); }

    .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }

    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .empty-state { text-align:center;padding:50px;color:var(--slate-light); }
    .empty-state h3 { font-size:15px;font-weight:600;color:var(--slate);margin-bottom:6px; }

    @media(max-width:768px) { .selector-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('timetable.configure') }}" class="page-tab">1. School Hours</a>
    <a href="{{ route('timetable.frequency') }}" class="page-tab active">2. Subject Frequency</a>
    <a href="{{ route('timetable.index') }}" class="page-tab">3. View / Generate</a>
    <a href="{{ route('timetable.teacher') }}" class="page-tab">Teacher View</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

{{-- Class/Session selector --}}
<div class="selector-card">
    <form method="GET" action="{{ route('timetable.frequency.load') }}">
        <div class="selector-grid">
            <div class="form-group">
                <label class="form-label">Class <span>*</span></label>
                <select name="class_arm_id" class="form-control" required>
                    <option value="">Select class</option>
                    @foreach($classArms as $arm)
                        <option value="{{ $arm->id }}" {{ isset($classArm) && $classArm->id == $arm->id ? 'selected' : '' }}>
                            {{ $arm->classLevel->name }} {{ $arm->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Session <span>*</span></label>
                <select name="session_id" class="form-control" required>
                    <option value="">Select session</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ isset($session) && $session->id == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}{{ $s->is_current ? ' (Current)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Load Subjects</button>
            </div>
        </div>
    </form>
</div>

@if(isset($assignments))

@if($assignments->isEmpty())
<div class="freq-card">
    <div class="empty-state">
        <h3>No subjects assigned</h3>
        <p>Assign subjects to {{ $classArm->classLevel->name }} {{ $classArm->name }} first via the Subjects module.</p>
        <a href="{{ route('subjects.index') }}" class="btn btn-primary" style="margin-top:14px">Go to Subjects</a>
    </div>
</div>
@else

@php
    $totalPeriods = $assignments->sum(function($a) use ($frequencies) {
        return $frequencies->get($a->subject_id)?->periods_per_week ?? 2;
    });
    $availableSlots = isset($config) ? $config->periods_per_day * 5 : null;
@endphp

@if(isset($config))
<div class="info-banner">
    ℹ️ School config: <strong>{{ $config->periods_per_day }} periods/day × 5 days = {{ $config->periods_per_day * 5 }} total slots/week</strong>
    · Period duration: <strong>{{ $config->period_duration }} mins</strong>
</div>
@else
<div class="info-banner" style="background:#FFFBEB;border-color:#FDE68A;color:var(--amber)">
    ⚠️ School hours not configured yet.
    <a href="{{ route('timetable.configure') }}" style="color:var(--indigo);font-weight:600;margin-left:6px">Set up school hours →</a>
</div>
@endif

<form method="POST" action="{{ route('timetable.frequency.save') }}">
    @csrf
    <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
    <input type="hidden" name="session_id" value="{{ $session->id }}">

    <div class="freq-card">
        <div class="freq-header">
            <div>
                <div class="freq-title">{{ $classArm->classLevel->name }} {{ $classArm->name }} — Subject Frequencies</div>
                <div class="freq-meta">Set how many times each subject appears on the timetable per week</div>
            </div>
        </div>

        <div class="tbl"><table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Periods / Week</th>
                    <th>Total Mins / Week</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assignments as $asgn)
                @php $freq = $frequencies->get($asgn->subject_id)?->periods_per_week ?? 2; @endphp
                <tr>
                    <td>
                        <div class="subject-name">{{ $asgn->subject->name }}</div>
                        @if($asgn->subject->code)<div class="teacher-name">{{ $asgn->subject->code }}</div>@endif
                    </td>
                    <td>
                        @if($asgn->teacher)
                            <div style="font-size:13px">{{ $asgn->teacher->name }}</div>
                        @else
                            <span style="font-size:12px;color:var(--slate-light)">No teacher assigned</span>
                        @endif
                    </td>
                    <td>
                        <div class="freq-stepper">
                            <button type="button" class="step-btn" onclick="stepFreq({{ $asgn->subject_id }}, -1)">−</button>
                            <input
                                type="number"
                                name="frequencies[{{ $asgn->subject_id }}]"
                                id="freq_{{ $asgn->subject_id }}"
                                class="freq-input"
                                value="{{ $freq }}"
                                min="1" max="10"
                                onchange="updateTotal()"
                            >
                            <button type="button" class="step-btn" onclick="stepFreq({{ $asgn->subject_id }}, 1)">+</button>
                            <span class="per-week">per week</span>
                        </div>
                    </td>
                    <td id="mins_{{ $asgn->subject_id }}" style="font-size:13px;font-weight:600;color:var(--slate)">
                        {{ isset($config) ? ($freq * $config->period_duration) . ' mins' : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table></div>

        <div class="total-bar">
            <div class="total-info">
                Total periods needed per week:
                <span class="total-count" id="totalCount">{{ $totalPeriods }}</span>
                @if($availableSlots)
                    / {{ $availableSlots }} available
                @endif
            </div>
            <div id="totalStatus" style="font-size:12px;font-weight:600"></div>
            <button type="submit" class="btn btn-primary">Save Frequencies</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
const availableSlots = {{ $availableSlots ?? 'null' }};
const periodDuration = {{ isset($config) ? $config->period_duration : 0 }};
const subjectIds = @json($assignments->pluck('subject_id'));

function stepFreq(subjectId, delta) {
    const input = document.getElementById('freq_' + subjectId);
    const newVal = Math.max(1, Math.min(10, parseInt(input.value || 1) + delta));
    input.value = newVal;
    updateTotal();
}

function updateTotal() {
    let total = 0;
    subjectIds.forEach(id => {
        const val = parseInt(document.getElementById('freq_' + id)?.value || 0);
        total += val;
        if (periodDuration > 0) {
            const minsEl = document.getElementById('mins_' + id);
            if (minsEl) minsEl.textContent = (val * periodDuration) + ' mins';
        }
    });

    document.getElementById('totalCount').textContent = total;

    const statusEl = document.getElementById('totalStatus');
    if (availableSlots) {
        if (total > availableSlots) {
            statusEl.textContent = `⚠️ Exceeds available slots by ${total - availableSlots}`;
            statusEl.style.color = 'var(--crimson)';
        } else if (total === availableSlots) {
            statusEl.textContent = '✓ Timetable will be fully packed';
            statusEl.style.color = 'var(--emerald)';
        } else {
            statusEl.textContent = `${availableSlots - total} slots will remain empty`;
            statusEl.style.color = 'var(--slate)';
        }
    }
}
updateTotal();
</script>
@endpush

@endif
@endif
@endsection
