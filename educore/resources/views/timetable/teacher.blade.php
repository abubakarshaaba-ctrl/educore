@extends('layouts.app')
@section('title', 'Teacher Timetable')
@section('page-title', 'Timetable')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content;max-width:100%;flex-wrap:wrap; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .filter-card { background:white;border:1px solid var(--border);border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;min-width:0; }
    .filter-group { display:flex;flex-direction:column;gap:5px;min-width:0;flex:1 1 210px; }
    .filter-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .filter-control { box-sizing:border-box;width:100%;min-width:0;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none; }
    .btn { display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms;min-height:40px; }
    .btn-primary { background:var(--indigo);color:white; }
    .tt-outer { width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch; }
    .tt-table { width:100%;border-collapse:collapse;background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);min-width:700px; }
    .tt-table thead th { background:var(--midnight);color:white;padding:10px 12px;font-size:11px;font-weight:600;text-align:center;text-transform:uppercase;letter-spacing:0.05em;border-right:1px solid rgba(255,255,255,0.08); }
    .tt-table thead th:first-child { text-align:left;width:110px; }
    .tt-table tbody td { border-bottom:1px solid var(--border);border-right:1px solid var(--border);vertical-align:top;padding:6px 8px;min-height:54px;overflow-wrap:anywhere; }
    .tt-table tbody td.time-col { font-size:11px;font-weight:700;color:var(--slate-light);text-transform:uppercase;background:#F8FAFC;text-align:center;vertical-align:middle;white-space:nowrap; }
    .tt-table tbody tr:last-child td { border-bottom:none; }
    .break-row td { background:#FFFBEB !important;padding:8px 12px !important;font-size:11px;font-weight:600;color:var(--amber);text-align:center; }
    .period-item { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:7px;padding:7px 10px;margin-bottom:4px; }
    .period-subject { font-size:12px;font-weight:700;color:var(--emerald);overflow-wrap:anywhere; }
    .period-class { font-size:11px;color:var(--slate);margin-top:2px;overflow-wrap:anywhere; }
    .closed-cell{background:#F8FAFC!important;color:#94A3B8!important;text-align:center!important;vertical-align:middle!important;font-size:10px!important;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
    .empty-state { text-align:center;padding:50px 20px;color:var(--slate-light); }
    .empty-state h3 { font-size:15px;font-weight:600;color:var(--slate);margin-bottom:6px; }
    @media(max-width:700px){
        .page-tabs{width:100%;overflow-x:auto;flex-wrap:nowrap;-webkit-overflow-scrolling:touch;scrollbar-width:none}.page-tabs::-webkit-scrollbar{display:none}.page-tab{white-space:nowrap;flex:0 0 auto}
        .filter-card{padding:14px;align-items:stretch}
        .filter-group{flex:1 1 100%}
        .filter-card>.btn{width:100%}
        .tt-table{min-width:660px}
    }
    @media(max-width:420px){
        .page-tab{padding:7px 11px;font-size:11px}
        .filter-card{padding:12px;border-radius:8px}
        .tt-table thead th{padding:8px 9px;font-size:9px}
        .tt-table tbody td{padding:6px;font-size:11px}
        .period-item{padding:6px}
        .empty-state{padding:36px 14px}
    }
</style>
@endpush

@section('content')
<div class="page-tabs">
    @if(auth()->user()->canManage('timetable'))
    <a href="{{ route('timetable.configure') }}" class="page-tab">1. School Hours</a>
    <a href="{{ route('timetable.frequency') }}" class="page-tab">2. Subject Frequency</a>
    <a href="{{ route('timetable.index') }}" class="page-tab">3. View / Generate</a>
    <a href="{{ route('timetable.teacher') }}" class="page-tab active">Teacher View</a>
    @else
    @if(auth()->user()->hasFormTeacherDuty())
    @php
        $myArm = \App\Models\ClassArm::where('tenant_id', auth()->user()->tenant_id)
            ->where('form_tutor_id', auth()->id())->first();
        $defaultSession = \App\Models\AcademicSession::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_current', true)->value('id');
    @endphp
    @if($myArm && request('session_id', $defaultSession))
    <a href="{{ route('timetable.view', ['class_arm_id' => $myArm->id, 'session_id' => request('session_id', $defaultSession)]) }}" class="page-tab">📋 My Class Timetable</a>
    @endif
    @endif
    @if(auth()->user()->hasSubjectTeacherDuty())
    <a href="{{ route('timetable.teacher', ['teacher_id' => auth()->id(), 'session_id' => request('session_id')]) }}" class="page-tab active">📚 My Subject Schedule</a>
    @endif
    @endif
    <a href="{{ route('exam-timetable.index') }}" class="page-tab">Exam Timetable</a>
    <a href="{{ route('exam-timetable.my-supervision') }}" class="page-tab">My Supervision</a>
</div>

<form method="GET">
    <div class="filter-card">
        <div class="filter-group">
            <span class="filter-label">Teacher</span>
            @if(count($teachers) === 1 && optional($teachers->first())->id === auth()->id())
            <input type="hidden" name="teacher_id" value="{{ auth()->id() }}">
            <div style="font-size:13px;font-weight:600;color:var(--midnight);padding:8px 0;overflow-wrap:anywhere">{{ auth()->user()->name }}</div>
            @else
            <select name="teacher_id" class="filter-control" required>
                <option value="">Select teacher</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </select>
            @endif
        </div>
        <div class="filter-group">
            <span class="filter-label">Session</span>
            <select name="session_id" class="filter-control" required>
                <option value="">Select session</option>
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ (request('session_id') == $s->id || (!request('session_id') && $s->is_current)) ? 'selected' : '' }}>{{ $s->name }}{{ $s->is_current ? ' (Current)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">View Timetable</button>
    </div>
</form>

@if(isset($teacher))
@php
    $teacherTimetableConfig = \App\Models\TimetableConfig::where('tenant_id', auth()->user()->tenant_id)
        ->where('session_id', $session->id)->first();
@endphp
<div class="tt-outer">
    <table class="tt-table">
        <thead><tr><th>Time</th>@foreach($days as $day)<th>{{ ucfirst($day) }}@if($teacherTimetableConfig)<br><span style="font-size:9px;font-weight:400;opacity:.75">{{ $teacherTimetableConfig->startingTimeFor($day) }}–{{ $teacherTimetableConfig->closingTimeFor($day) }}</span>@endif</th>@endforeach</tr></thead>
        <tbody>
            @foreach($allSlots as $slot)
                @if($slot['is_break'])
                <tr class="break-row"><td>{{ substr($slot['start'],0,5) }} – {{ substr($slot['end'],0,5) }}</td>@foreach($days as $day)@php $openForBreak = collect($daySlots[$day] ?? [])->contains(fn($daySlot) => $daySlot['is_break'] && $daySlot['start'] === $slot['start'] && $daySlot['end'] === $slot['end']); @endphp<td class="{{ $openForBreak ? '' : 'closed-cell' }}">{{ $openForBreak ? '☕ '.$slot['label'] : 'Closed' }}</td>@endforeach</tr>
                @else
                <tr>
                    <td class="time-col"><span style="font-weight:700">{{ substr($slot['start'],0,5) }}</span><br><span style="font-weight:400">{{ substr($slot['end'],0,5) }}</span></td>
                    @foreach($days as $day)
                    @php
                        $openForPeriod = collect($daySlots[$day] ?? [])->contains(
                            fn($daySlot) => ! $daySlot['is_break']
                                && $daySlot['start'] === $slot['start']
                                && $daySlot['end'] === $slot['end']
                        );
                        $match = $periods->get($day, collect())->first(
                            fn($p) => substr((string) $p->start_time, 0, 5) === substr((string) $slot['start'], 0, 5)
                                && substr((string) $p->end_time, 0, 5) === substr((string) $slot['end'], 0, 5)
                        );
                    @endphp
                    <td class="{{ $openForPeriod ? '' : 'closed-cell' }}">@if(!$openForPeriod)Closed@elseif($match)<div class="period-item"><div class="period-subject">{{ optional($match->subject)->name ?? 'Unassigned subject' }}</div><div class="period-class">{{ optional(optional($match->classArm)->classLevel)->name ?? 'Unassigned level' }} {{ optional($match->classArm)->name ?? '' }}</div></div>@endif</td>
                    @endforeach
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="empty-state"><h3>Select a teacher and session</h3><p>The teacher's full weekly schedule will appear here.</p></div>
@endif
@endsection