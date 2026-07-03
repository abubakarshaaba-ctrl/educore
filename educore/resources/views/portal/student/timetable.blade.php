@extends('layouts.portal')
@section('title','My Timetable')
@section('content')
<h2 style="font-size:17px;font-weight:800;margin-bottom:18px">⏰ Class Timetable
    @if($arm)<span style="font-size:13px;font-weight:500;color:var(--muted)"> — {{ $arm->full_name }}</span>@endif
</h2>

@if($timetable->isEmpty())
<div class="card"><div class="empty"><div class="empty-icon">📅</div><div>No timetable set yet.</div></div></div>
@else
@foreach($days as $day)
@php $periods = $timetable->get($day, collect()); @endphp
@if($periods->isNotEmpty())
<div class="card" style="margin-bottom:12px">
    <div class="ch">{{ $day }}</div>
    <div style="overflow-x:auto">
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
@endsection
