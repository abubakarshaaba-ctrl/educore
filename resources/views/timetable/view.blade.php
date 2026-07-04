@extends('layouts.app')
@section('title', 'Class Timetable')
@section('page-title', 'Timetable')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }

    .context-bar { background:white;border:1px solid var(--border);border-radius:10px;padding:14px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px; }
    .context-info h2 { font-size:15px;font-weight:700;color:var(--midnight); }
    .context-info p  { font-size:12px;color:var(--slate);margin-top:2px; }
    .context-actions { display:flex;gap:8px; }

    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .alert-error   { background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px; }
    .conflict-item { font-size:12px;margin-top:4px; }

    /* Timetable grid */
    .tt-outer { overflow-x:auto;margin-bottom:20px; }
    .tt-table { width:100%;border-collapse:collapse;background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);min-width:700px; }
    .tt-table thead th {
        background:var(--midnight);color:white;padding:10px 12px;
        font-size:11px;font-weight:600;text-align:center;
        text-transform:uppercase;letter-spacing:0.05em;
        border-right:1px solid rgba(255,255,255,0.08);
    }
    .tt-table thead th:first-child { text-align:left;width:110px;border-right:1px solid rgba(255,255,255,0.15); }
    .tt-table tbody td {
        border-bottom:1px solid var(--border);border-right:1px solid var(--border);
        vertical-align:top;padding:0;
    }
    .tt-table tbody td.time-col {
        padding:10px 12px;font-size:11px;font-weight:700;color:var(--slate-light);
        text-transform:uppercase;letter-spacing:0.04em;background:#F8FAFC;
        text-align:center;vertical-align:middle;white-space:nowrap;
        border-right:1px solid var(--border);
    }
    .tt-table tbody tr:last-child td { border-bottom:none; }
    .break-row td { background:#FFFBEB !important;padding:8px 12px !important;
        font-size:11px;font-weight:600;color:var(--amber);text-align:center;
        vertical-align:middle !important;
    }

    .period-cell { padding:6px 8px;min-height:54px; }
    .period-item { background:var(--indigo-bg);border:1px solid #BFDBFE;border-radius:7px;padding:8px 10px;position:relative;margin-bottom:4px; }
    .period-subject { font-size:12px;font-weight:700;color:var(--indigo);line-height:1.3; }
    .period-teacher { font-size:11px;color:var(--slate);margin-top:2px; }
    .period-del { position:absolute;top:4px;right:5px;background:none;border:none;color:#CBD5E1;cursor:pointer;font-size:13px;line-height:1;padding:0;transition:color 150ms; }
    .period-del:hover { color:var(--crimson); }
    .empty-cell { display:flex;align-items:center;justify-content:center;min-height:54px; }
    .add-btn { font-size:11px;color:var(--slate-light);background:none;border:1.5px dashed #CBD5E1;border-radius:6px;cursor:pointer;padding:5px 10px;transition:all 150ms; }
    .add-btn:hover { border-color:var(--indigo);color:var(--indigo); }

    /* Add period form */
    .add-card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    .add-card-header { padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:600;color:var(--midnight); }
    .add-card-body { padding:18px; }
    .form-group { margin-bottom:12px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-label span { color:var(--crimson); }
    .form-control { width:100%;padding:8px 10px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);background:white; }
    .form-row { display:grid;grid-template-columns:1fr 1fr;gap:8px; }
    .btn { display:inline-flex;align-items:center;gap:5px;padding:9px 14px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .btn-sm { padding:6px 12px;font-size:12px; }
    .btn-generate { background:linear-gradient(135deg,#059669,#047857);color:white; }
</style>
@endpush

@section('content')
<div class="page-tabs">
    @if(auth()->user()->canManage('timetable'))
    <a href="{{ route('timetable.configure') }}" class="page-tab">1. School Hours</a>
    <a href="{{ route('timetable.frequency') }}" class="page-tab">2. Subject Frequency</a>
    <a href="{{ route('timetable.index') }}" class="page-tab active">3. View / Generate</a>
    <a href="{{ route('timetable.teacher') }}" class="page-tab">Teacher View</a>
    @else
    @if(auth()->user()->hasFormTeacherDuty())
    <a href="{{ route('timetable.view', ['class_arm_id' => request('class_arm_id'), 'session_id' => request('session_id')]) }}" class="page-tab active">📋 My Class Timetable</a>
    @endif
    @if(auth()->user()->hasSubjectTeacherDuty())
    <a href="{{ route('timetable.teacher', ['teacher_id' => auth()->id(), 'session_id' => request('session_id')]) }}" class="page-tab">📚 My Subject Schedule</a>
    @endif
    @endif
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

@if(session('tt_conflicts') && count(session('tt_conflicts')) > 0)
<div class="alert-error">
    <strong>⚠️ {{ count(session('tt_conflicts')) }} conflict(s) detected:</strong>
    @foreach(session('tt_conflicts') as $c)
        <div class="conflict-item">• {{ $c }}</div>
    @endforeach
</div>
@endif

<div class="context-bar">
    <div class="context-info">
        <h2>{{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ $session->name }}</h2>
        <p>
            @if($config)
                {{ $config->periods_per_day }} periods/day · {{ $config->period_duration }} mins each
                @if(count($config->breaks ?? []))· {{ count($config->breaks) }} break(s)@endif
            @else
                No school hours configured
            @endif
            · {{ $periods->flatten()->count() }} periods placed
        </p>
    </div>
    <div class="context-actions">
        @if(auth()->user()->canManage('timetable'))
        <form method="POST" action="{{ route('timetable.generate') }}" style="display:inline"
              onsubmit="return confirm('Regenerate timetable for this class?')">
            @csrf
            <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
            <input type="hidden" name="session_id" value="{{ $session->id }}">
            <input type="hidden" name="overwrite" value="1">
            <button type="submit" class="btn btn-generate btn-sm">⚡ Regenerate</button>
        </form>
        @endif
        <a href="{{ route('timetable.index') }}" class="btn btn-ghost btn-sm">← Back</a>
    </div>
</div>

{{-- Timetable grid --}}
<div class="tt-outer">
    <div class="tbl"><table class="tt-table">
        <thead>
            <tr>
                <th>Time</th>
                @foreach($days as $day)<th>{{ ucfirst($day) }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($allSlots as $slot)
                @if($slot['is_break'])
                <tr class="break-row">
                    <td>{{ substr($slot['start'],0,5) }} – {{ substr($slot['end'],0,5) }}</td>
                    @foreach($days as $day)
                    <td>☕ {{ $slot['label'] }}</td>
                    @endforeach
                </tr>
                @else
                <tr>
                    <td class="time-col">
                        P{{ $slot['period'] }}<br>
                        <span style="font-weight:400">{{ substr($slot['start'],0,5) }}</span><br>
                        <span style="font-weight:400">{{ substr($slot['end'],0,5) }}</span>
                    </td>
                    @foreach($days as $day)
                    @php
                        $match = $periods->get($day, collect())
                            ->first(fn($p) => $p->start_time === $slot['start']);
                    @endphp
                    <td>
                        @if($match)
                        <div class="period-cell">
                            <div class="period-item">
                                <div class="period-subject">{{ $match->subject->name }}</div>
                                @if($match->teacher)<div class="period-teacher">{{ $match->teacher->name }}</div>@endif
                                @if($match->venue)<div class="period-teacher">📍 {{ $match->venue }}</div>@endif
                                @if(auth()->user()->canManage('timetable'))
                                <form method="POST" action="{{ route('timetable.destroy', $match) }}"
                                      style="display:inline" onsubmit="return confirm('Remove this period?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="period-del" title="Remove">✕</button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @else
                        <div class="empty-cell">
                            @if(auth()->user()->canManage('timetable'))
                            <button type="button" class="add-btn"
                                onclick="openAddForm('{{ $day }}','{{ $slot['start'] }}','{{ $slot['end'] }}')">
                                + Add
                            </button>
                            @endif
                        </div>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endif
            @endforeach

            @if(empty($allSlots))
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--slate-light)">
                No school hours configured. <a href="{{ route('timetable.configure') }}" style="color:var(--indigo)">Set up school hours →</a>
            </td></tr>
            @endif
        </tbody>
    </table></div>
</div>

{{-- Manual add period panel (admin only) --}}
@if(auth()->user()->canManage('timetable'))
<div class="add-card" id="addPeriodCard" style="max-width:480px;display:none">
    <div class="add-card-header">Manually Add Period</div>
    <div class="add-card-body">
        <form method="POST" action="{{ route('timetable.store') }}">
            @csrf
            <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
            <input type="hidden" name="session_id"   value="{{ $session->id }}">
            <div class="form-group">
                <label class="form-label">Day <span>*</span></label>
                <select name="day_of_week" id="addDay" class="form-control" required>
                    @foreach($days as $day)<option value="{{ $day }}">{{ ucfirst($day) }}</option>@endforeach
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Start <span>*</span></label>
                    <input type="time" name="start_time" id="addStart" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End <span>*</span></label>
                    <input type="time" name="end_time" id="addEnd" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Subject <span>*</span></label>
                <select name="subject_id" class="form-control" required>
                    <option value="">Select subject</option>
                    @foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Teacher</label>
                <select name="teacher_id" class="form-control">
                    <option value="">None</option>
                    @foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Venue</label>
                <input type="text" name="venue" class="form-control" placeholder="e.g. Lab 1">
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Add Period</button>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addPeriodCard').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
@if(auth()->user()->canManage('timetable'))
function openAddForm(day, start, end) {
    document.getElementById('addDay').value = day;
    document.getElementById('addStart').value = start;
    document.getElementById('addEnd').value = end;
    const card = document.getElementById('addPeriodCard');
    card.style.display = 'block';
    card.scrollIntoView({ behavior:'smooth', block:'center' });
}
@endif
</script>
@endpush
@endsection
