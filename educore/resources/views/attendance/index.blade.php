@extends('layouts.app')
@section('title', 'Attendance')
@section('page-title', 'Attendance')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px; }
    .page-tab { padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active,.page-tab:hover { background:var(--indigo);border-color:var(--indigo);color:white; }
    

    .today-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px; }
    .today-card { background:white;border:1px solid var(--border);border-radius:10px;padding:16px;text-align:center; }
    .today-val { font-size:28px;font-weight:700;letter-spacing:-0.03em; }
    .today-lbl { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;margin-top:4px; }
    .green { color:var(--emerald); }
    .red   { color:var(--crimson); }
    .amber { color:var(--amber); }
    .blue  { color:var(--indigo); }

    .mark-card { background:white;border:1px solid var(--border);border-radius:12px;padding:28px;box-shadow:0 1px 3px rgba(0,0,0,0.05); }
    .mark-title { font-size:17px;font-weight:700;color:var(--midnight);margin-bottom:6px;letter-spacing:-0.02em; }
    .mark-sub { font-size:13px;color:var(--slate);margin-bottom:24px; }
    .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px; }
    .form-group { display:flex;flex-direction:column;gap:6px; }
    .form-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .form-label span { color:var(--crimson); }
    .form-control { padding:10px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:11px 22px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    @media(max-width:768px){.today-grid{grid-template-columns:1fr 1fr}}
    @media(max-width:480px){.today-grid{grid-template-columns:1fr}.today-val{font-size:22px}}
    @media(max-width:640px){.form-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

{{-- ── Attendance Metrics ─────────────────────────────────────────── --}}
@if(!empty($weeklyTrend) && $weeklyTrend->count())
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px">
    <div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden">
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC">
            <span style="font-size:13px;font-weight:700">📅 Attendance Trend — Last 7 Days</span>
        </div>
        <div style="padding:16px 18px">
            @php $maxRate = $weeklyTrend->max('rate') ?: 100; @endphp
            <div style="display:flex;align-items:flex-end;gap:8px;height:80px">
                @foreach($weeklyTrend as $day)
                <div style="display:flex;flex-direction:column;align-items:center;flex:1;gap:3px">
                    <span style="font-size:10px;font-weight:700;color:{{ $day['rate']>=75?'#059669':($day['rate']>=50?'#D97706':'#DC2626') }}">{{ $day['rate'] }}%</span>
                    <div style="width:100%;border-radius:4px 4px 0 0;background:{{ $day['rate']>=75?'#059669':($day['rate']>=50?'#D97706':'#DC2626') }};height:{{ max(4, ($day['rate']/($maxRate?:1))*55) }}px"></div>
                    <span style="font-size:10px;color:var(--slate-light);white-space:nowrap">{{ explode(' ',$day['date'])[0] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden">
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC">
            <span style="font-size:13px;font-weight:700">🏫 Today by Class</span>
        </div>
        <div style="padding:12px 18px;max-height:160px;overflow-y:auto">
            @forelse($classBreakdown ?? [] as $cls)
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <span style="font-size:11px;min-width:70px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $cls['class'] }}</span>
                <div style="flex:1;height:5px;background:#E2E8F0;border-radius:3px;overflow:hidden">
                    <div style="height:100%;width:{{ $cls['rate'] }}%;background:{{ $cls['rate']>=75?'#059669':'#D97706' }};border-radius:3px"></div>
                </div>
                <span style="font-size:11px;font-weight:700;color:{{ $cls['rate']>=75?'#059669':'#D97706' }};min-width:30px">{{ $cls['rate'] }}%</span>
            </div>
            @empty
            <div style="text-align:center;padding:20px;font-size:12px;color:var(--slate-light)">No attendance marked today yet</div>
            @endforelse
        </div>
    </div>
</div>
@endif

<div class="page-tabs">
    <a href="{{ route('attendance.index') }}" class="page-tab active">Mark Attendance</a>
    <a href="{{ route('attendance.report') }}" class="page-tab">Reports</a>
</div>

<div class="pg-2col">
<div>{{-- left --}}
{{-- Today's summary --}}
<div class="today-grid">
    <div class="today-card">
        <div class="today-val green">{{ $todaySummary['present'] ?? 0 }}</div>
        <div class="today-lbl">Present Today</div>
    </div>
    <div class="today-card">
        <div class="today-val red">{{ $todaySummary['absent'] ?? 0 }}</div>
        <div class="today-lbl">Absent Today</div>
    </div>
    <div class="today-card">
        <div class="today-val amber">{{ $todaySummary['late'] ?? 0 }}</div>
        <div class="today-lbl">Late Today</div>
    </div>
    <div class="today-card">
        <div class="today-val blue">{{ $todaySummary['excused'] ?? 0 }}</div>
        <div class="today-lbl">Excused Today</div>
    </div>
</div>

<div class="mark-card">
    <div class="mark-title">Mark Attendance</div>
    <div class="mark-sub">Select a class and date to open the attendance sheet.</div>
    <form method="GET" action="{{ route('attendance.sheet') }}">
        <div class="form-grid">
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
                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Open Attendance Sheet</button>
    </form>
</div>
</div>{{-- /left --}}
<div>{{-- right: class breakdown --}}
<div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC">
        <span style="font-size:13px;font-weight:700">Today by Class</span>
    </div>
    <div style="padding:12px 18px">
        @forelse($classBreakdown ?? [] as $cls)
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:7px">
            <span style="font-size:11.5px;min-width:80px;color:var(--slate);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $cls['class'] }}</span>
            <div style="flex:1;height:5px;background:#E2E8F0;border-radius:3px;overflow:hidden">
                <div style="height:100%;width:{{ $cls['rate'] }}%;background:{{ $cls['rate']>=75?'#059669':'#D97706' }};border-radius:3px"></div>
            </div>
            <span style="font-size:11px;font-weight:700;color:{{ $cls['rate']>=75?'#059669':'#D97706' }};min-width:32px">{{ $cls['rate'] }}%</span>
        </div>
        @empty
        <div style="text-align:center;padding:20px;font-size:12px;color:var(--slate-light)">No attendance marked today yet</div>
        @endforelse
    </div>
</div>
</div>{{-- /right --}}
</div>{{-- /grid --}}
@endsection
