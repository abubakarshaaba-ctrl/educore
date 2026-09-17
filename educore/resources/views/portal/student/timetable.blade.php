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
@endsection
