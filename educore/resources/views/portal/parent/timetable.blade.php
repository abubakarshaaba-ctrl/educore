@extends('layouts.portal')
@section('title','Timetable')

@push('styles')
<style>
.timetable-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.timetable-head h2{font-size:17px;font-weight:800}
.timetable-table{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.timetable-table table{min-width:620px}
.programme-title{font-size:13px;font-weight:800}
.programme-title span{font-weight:500;color:var(--muted)}
@media(max-width:640px){.timetable-head{align-items:stretch;flex-direction:column}.timetable-table table{min-width:560px}}
</style>
@endpush

@section('content')
@if($students->count() > 1)
<div class="child-tabs">
    @foreach($students as $s)
    <a href="?student_id={{ $s->id }}" class="child-tab {{ optional($student)->id==$s->id ? 'active':'' }}">👦 {{ $s->first_name }}</a>
    @endforeach
</div>
@endif

<div class="timetable-head">
    <h2>⏰ Timetable — {{ optional($student)->full_name }}</h2>
</div>

<div class="card">
    <div class="ch">Conventional Timetable @if($arm)<span style="font-weight:500;color:var(--muted)">— {{ $arm->full_name }}</span>@endif</div>
    @if($timetable->isEmpty())
        <div class="empty"><div class="empty-icon">📅</div><div>No conventional timetable set yet.</div></div>
    @else
        @foreach($days as $day)
            @php($periods=$timetable->get($day,collect()))
            @if($periods->isNotEmpty())
            <div style="padding:12px 14px 4px;font-weight:800;font-size:12px">{{ $day }}</div>
            <div class="timetable-table">
                <table>
                    <thead><tr><th>Time</th><th>Subject</th><th>Teacher</th><th>Venue</th></tr></thead>
                    <tbody>
                    @foreach($periods as $p)
                    <tr>
                        <td>{{ substr((string)$p->start_time,0,5) }}@if($p->end_time) – {{ substr((string)$p->end_time,0,5) }}@endif</td>
                        <td style="font-weight:600">{{ $p->subject?->name ?? '—' }}</td>
                        <td>{{ $p->teacher?->name ?? '—' }}</td>
                        <td>{{ $p->venue ?? '—' }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        @endforeach
    @endif
</div>

@if($parallelTimetables->isNotEmpty())
<h2 style="font-size:17px;font-weight:800;margin:24px 0 14px">📘 Parallel Timetable</h2>
@foreach($parallelTimetables as $programme)
<div class="card">
    <div class="ch">
        <div class="programme-title">
            {{ $programme['curriculum_name'] }}
            @if($programme['class_name'])<span>— {{ $programme['class_name'] }}{{ $programme['arm_name'] ? ' '.$programme['arm_name'] : '' }}</span>@endif
        </div>
    </div>
    @foreach($days as $day)
        @php($periods=$programme['periods']->filter(fn($p)=>strcasecmp((string)$p->day_of_week,$day)===0)->sortBy('start_time')->values())
        @if($periods->isNotEmpty())
        <div style="padding:12px 14px 4px;font-weight:800;font-size:12px">{{ $day }}</div>
        <div class="timetable-table">
            <table>
                <thead><tr><th>Time</th><th>Subject</th><th>Teacher</th><th>Venue</th></tr></thead>
                <tbody>
                @foreach($periods as $p)
                <tr>
                    <td>{{ substr((string)$p->start_time,0,5) }}@if($p->end_time) – {{ substr((string)$p->end_time,0,5) }}@endif</td>
                    <td style="font-weight:600">{{ $p->subject?->name ?? '—' }}</td>
                    <td>{{ $p->teacher?->name ?? '—' }}</td>
                    <td>{{ $p->venue ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endforeach
</div>
@endforeach
@endif
@endsection
