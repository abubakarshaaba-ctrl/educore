@extends('layouts.portal')
@section('title','My Timetable')

@push('styles')
<style>
.timetable-title{font-size:17px;font-weight:800;margin-bottom:18px;overflow-wrap:anywhere}
.timetable-title span{font-size:13px;font-weight:500;color:var(--muted);overflow-wrap:anywhere}
.timetable-table{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;overscroll-behavior-inline:contain}
.timetable-table table{min-width:660px}
.timetable-table th{white-space:nowrap}
.timetable-table td{overflow-wrap:anywhere;vertical-align:top}
@media(max-width:640px){
    .timetable-title{font-size:16px;line-height:1.4}
    .timetable-title span{display:block;margin-top:3px}
    .timetable-table table{min-width:600px}
}
@media(max-width:420px){
    .timetable-table table{min-width:560px;font-size:12px}
    .timetable-table th,.timetable-table td{padding:8px 10px}
}
</style>
@endpush

@section('content')
<h2 class="timetable-title">⏰ Class Timetable
    @if($arm)<span> — {{ $arm->full_name }}</span>@endif
</h2>

@if($timetable->isEmpty())
<div class="card"><div class="empty"><div class="empty-icon">📅</div><div>No timetable set yet.</div></div></div>
@else
@foreach($days as $day)
@php $periods = $timetable->get($day, collect()); @endphp
@if($periods->isNotEmpty())
<div class="card" style="margin-bottom:12px">
    <div class="ch">{{ $day }}</div>
    <div class="timetable-table">
    <table>
        <thead><tr><th>Period</th><th>Time</th><th>Subject</th><th>Teacher</th><th>Venue</th></tr></thead>
        <tbody>
        @foreach($periods->sortBy('start_time') as $p)
        <tr>
            <td style="font-weight:600">Period {{ $p->period_number }}</td>
            <td style="font-size:12px;color:var(--muted)">
                {{ $p->start_time ? \Carbon\Carbon::parse($p->start_time)->format('H:i') : '' }}
                @if($p->end_time) – {{ \Carbon\Carbon::parse($p->end_time)->format('H:i') }} @endif
            </td>
            <td style="font-weight:600">{{ optional($p->subject)->name ?? '—' }}</td>
            <td>{{ optional($p->teacher)->name ?? '—' }}</td>
            <td style="font-size:12px;color:var(--muted)">{{ $p->venue ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif
@endforeach
@endif

@if($parallelTimetables->isNotEmpty())
<h2 class="timetable-title" style="margin-top:24px">📘 Parallel Timetable</h2>
@foreach($parallelTimetables as $programme)
<div class="card" style="margin-bottom:16px">
    <div class="ch">
        {{ $programme['curriculum_name'] }}
        @if($programme['class_name'])
            <span style="font-weight:500;color:var(--muted)"> — {{ $programme['class_name'] }}{{ $programme['arm_name'] ? ' '.$programme['arm_name'] : '' }}</span>
        @endif
    </div>
    @foreach($days as $day)
        @php
            $periods = $programme['periods']->filter(
                fn($period) => strcasecmp((string)$period->day_of_week, $day) === 0
            )->sortBy('start_time')->values();
        @endphp
        @if($periods->isNotEmpty())
        <div style="padding:12px 14px 4px;font-weight:800;font-size:12px">{{ $day }}</div>
        <div class="timetable-table">
            <table>
                <thead><tr><th>Time</th><th>Subject</th><th>Teacher</th><th>Venue</th></tr></thead>
                <tbody>
                @foreach($periods as $p)
                <tr>
                    <td style="font-size:12px;color:var(--muted)">
                        {{ substr((string)$p->start_time,0,5) }}
                        @if($p->end_time) – {{ substr((string)$p->end_time,0,5) }} @endif
                    </td>
                    <td style="font-weight:600">{{ $p->subject?->name ?? '—' }}</td>
                    <td>{{ $p->teacher?->name ?? '—' }}</td>
                    <td style="font-size:12px;color:var(--muted)">{{ $p->venue ?? '—' }}</td>
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
