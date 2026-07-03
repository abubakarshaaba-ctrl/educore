@extends('layouts.app')
@section('title', 'Timetable Setup')
@section('page-title', 'Timetable')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }

    .setup-grid { display:grid;grid-template-columns:1fr 420px;gap:20px;align-items:start; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:14px; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#F8FAFC; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-label span { color:var(--crimson); }
    .form-control { width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .form-row { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
    .form-row-3 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px; }
    .is-invalid { border-color:var(--crimson) !important; }
    .invalid-feedback { font-size:12px;color:var(--crimson);margin-top:3px; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--slate);border:1px solid var(--border);font-size:12px;padding:6px 12px; }
    .btn-danger { background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA;font-size:12px;padding:5px 10px; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .alert-error { background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px; }

    .break-row { display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:center;margin-bottom:8px;background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:8px 10px; }
    .break-label { font-size:12px;font-weight:600;color:var(--slate);margin-bottom:3px; }

    .preview-box { background:#F8FAFC;border:1px solid var(--border);border-radius:10px;padding:16px; }
    .preview-title { font-size:12px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px; }
    .preview-slot { display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border);font-size:12px; }
    .preview-slot:last-child { border-bottom:none; }
    .slot-num { width:24px;height:24px;border-radius:50%;background:var(--indigo);color:white;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
    .slot-break { width:24px;height:24px;border-radius:50%;background:var(--amber);color:white;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
    .slot-time { font-weight:600;color:var(--midnight);min-width:110px; }
    .slot-lbl { color:var(--slate); }

    .existing-card { border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:10px; }
    .existing-session { font-size:13px;font-weight:700;color:var(--midnight); }
    .existing-detail { font-size:12px;color:var(--slate);margin-top:4px; }

    @media(max-width:1024px) { .setup-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('timetable.configure') }}" class="page-tab active">1. School Hours</a>
    <a href="{{ route('timetable.frequency') }}" class="page-tab">2. Subject Frequency</a>
    <a href="{{ route('timetable.index') }}" class="page-tab">3. View / Generate</a>
    <a href="{{ route('timetable.teacher') }}" class="page-tab">Teacher View</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="setup-grid">
    <div>
        {{-- Configuration form --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Configure School Hours & Periods</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('timetable.config.save') }}" id="configForm">
                    @csrf

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

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">School Start Time <span>*</span></label>
                            <input type="time" name="school_start" class="form-control" value="{{ old('school_start', '07:30') }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">School End Time <span>*</span></label>
                            <input type="time" name="school_end" class="form-control" value="{{ old('school_end', '14:30') }}" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Periods Per Day <span>*</span></label>
                            <input type="number" name="periods_per_day" id="periodsPerDay" class="form-control" value="{{ old('periods_per_day', 8) }}" min="1" max="12" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Period Duration (mins) <span>*</span></label>
                            <input type="number" name="period_duration" id="periodDuration" class="form-control" value="{{ old('period_duration', 40) }}" min="20" max="120" required>
                        </div>
                    </div>

                    {{-- Breaks --}}
                    <div class="form-group">
                        <label class="form-label">Break Periods</label>
                        <div id="breaksContainer">
                            <div class="break-row" data-break="0">
                                <div>
                                    <div class="break-label">After Period #</div>
                                    <input type="number" name="breaks[0][after_period]" class="form-control" placeholder="e.g. 3" min="1" value="{{ old('breaks.0.after_period', 3) }}">
                                </div>
                                <div>
                                    <div class="break-label">Duration (mins)</div>
                                    <input type="number" name="breaks[0][duration]" class="form-control" placeholder="e.g. 20" min="5" value="{{ old('breaks.0.duration', 20) }}">
                                </div>
                                <div>
                                    <div class="break-label">Label</div>
                                    <input type="text" name="breaks[0][label]" class="form-control" placeholder="e.g. Short Break" value="{{ old('breaks.0.label', 'Short Break') }}">
                                </div>
                                <div style="padding-top:18px">
                                    <button type="button" class="btn btn-danger" onclick="removeBreak(this)">✕</button>
                                </div>
                            </div>
                            <div class="break-row" data-break="1">
                                <div>
                                    <div class="break-label">After Period #</div>
                                    <input type="number" name="breaks[1][after_period]" class="form-control" placeholder="e.g. 6" min="1" value="{{ old('breaks.1.after_period', 6) }}">
                                </div>
                                <div>
                                    <div class="break-label">Duration (mins)</div>
                                    <input type="number" name="breaks[1][duration]" class="form-control" placeholder="e.g. 30" min="5" value="{{ old('breaks.1.duration', 30) }}">
                                </div>
                                <div>
                                    <div class="break-label">Label</div>
                                    <input type="text" name="breaks[1][label]" class="form-control" placeholder="e.g. Long Break" value="{{ old('breaks.1.label', 'Long Break') }}">
                                </div>
                                <div style="padding-top:18px">
                                    <button type="button" class="btn btn-danger" onclick="removeBreak(this)">✕</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-ghost" style="margin-top:8px" onclick="addBreak()">+ Add Break</button>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                </form>
            </div>
        </div>

        {{-- Existing configs --}}
        @if($configs->count())
        <div class="card">
            <div class="card-header"><span class="card-title">Saved Configurations</span></div>
            <div class="card-body">
                @foreach($configs as $cfg)
                <div class="existing-card">
                    <div class="existing-session">{{ $cfg->session->name ?? '—' }}</div>
                    <div class="existing-detail">
                        {{ $cfg->school_start }} – {{ $cfg->school_end }} ·
                        {{ $cfg->periods_per_day }} periods/day ·
                        {{ $cfg->period_duration }} mins each ·
                        {{ count($cfg->breaks ?? []) }} break(s)
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Live preview --}}
    <div class="card">
        <div class="card-header"><span class="card-title">Live Slot Preview</span></div>
        <div class="card-body">
            <p style="font-size:12px;color:var(--slate);margin-bottom:14px">Update the form to see computed period slots.</p>
            <div class="preview-box" id="previewBox">
                <div class="preview-title">Period Slots</div>
                <div id="previewSlots">
                    <div style="font-size:13px;color:var(--slate-light);text-align:center;padding:20px">Fill in the form to preview slots</div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let breakCount = 2;

function addBreak() {
    const container = document.getElementById('breaksContainer');
    const div = document.createElement('div');
    div.className = 'break-row';
    div.innerHTML = `
        <div><div class="break-label">After Period #</div>
        <input type="number" name="breaks[${breakCount}][after_period]" class="form-control" placeholder="e.g. 4" min="1"></div>
        <div><div class="break-label">Duration (mins)</div>
        <input type="number" name="breaks[${breakCount}][duration]" class="form-control" placeholder="e.g. 20" min="5"></div>
        <div><div class="break-label">Label</div>
        <input type="text" name="breaks[${breakCount}][label]" class="form-control" placeholder="e.g. Break"></div>
        <div style="padding-top:18px"><button type="button" class="btn btn-danger" onclick="removeBreak(this)">✕</button></div>`;
    container.appendChild(div);
    breakCount++;
    updatePreview();
}

function removeBreak(btn) {
    btn.closest('.break-row').remove();
    updatePreview();
}

function updatePreview() {
    const start  = document.querySelector('[name="school_start"]')?.value;
    const periods = parseInt(document.querySelector('[name="periods_per_day"]')?.value || 0);
    const dur    = parseInt(document.querySelector('[name="period_duration"]')?.value || 0);
    const breakRows = document.querySelectorAll('.break-row');

    if (!start || !periods || !dur) return;

    const breaks = [];
    breakRows.forEach(row => {
        const ap = row.querySelector('[name*="after_period"]')?.value;
        const d  = row.querySelector('[name*="duration"]')?.value;
        const l  = row.querySelector('[name*="label"]')?.value;
        if (ap && d) breaks.push({ after_period: parseInt(ap), duration: parseInt(d), label: l || 'Break' });
    });

    // Compute slots
    let slots = [];
    let [h, m] = start.split(':').map(Number);
    let mins = h * 60 + m;

    for (let i = 1; i <= periods; i++) {
        const s = `${String(Math.floor(mins/60)).padStart(2,'0')}:${String(mins%60).padStart(2,'0')}`;
        mins += dur;
        const e = `${String(Math.floor(mins/60)).padStart(2,'0')}:${String(mins%60).padStart(2,'0')}`;
        slots.push({ period: i, start: s, end: e, is_break: false, label: `Period ${i}` });

        const brk = breaks.find(b => b.after_period === i);
        if (brk) {
            const bs = e;
            mins += brk.duration;
            const be = `${String(Math.floor(mins/60)).padStart(2,'0')}:${String(mins%60).padStart(2,'0')}`;
            slots.push({ period: null, start: bs, end: be, is_break: true, label: brk.label });
        }
    }

    const container = document.getElementById('previewSlots');
    container.innerHTML = slots.map(s => `
        <div class="preview-slot">
            <div class="${s.is_break ? 'slot-break' : 'slot-num'}">${s.is_break ? '☕' : s.period}</div>
            <div class="slot-time">${s.start} – ${s.end}</div>
            <div class="slot-lbl">${s.label}</div>
        </div>
    `).join('');
}

// Attach live preview listeners
document.querySelectorAll('[name="school_start"],[name="periods_per_day"],[name="period_duration"]')
    .forEach(el => el.addEventListener('input', updatePreview));
document.getElementById('breaksContainer').addEventListener('input', updatePreview);
updatePreview();
</script>
@endpush
@endsection
