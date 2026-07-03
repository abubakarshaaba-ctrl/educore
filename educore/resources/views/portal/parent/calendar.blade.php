@extends('layouts.portal')
@section('title','School Calendar')
@section('content')
<h2 style="font-size:17px;font-weight:800;margin-bottom:18px">📆 School Calendar</h2>
@php $grouped = $events->groupBy(fn($e) => \Carbon\Carbon::parse($e->date)->format('F Y')); @endphp
@forelse($grouped as $month => $evs)
<div class="card" style="margin-bottom:14px">
    <div class="ch">{{ $month }}</div>
    @foreach($evs as $ev)
    <div style="display:flex;gap:14px;padding:12px 18px;border-bottom:1px solid var(--border);align-items:flex-start">
        <div style="text-align:center;min-width:44px;background:#EFF6FF;border-radius:8px;padding:6px">
            <div style="font-size:18px;font-weight:800;color:#2563EB">{{ \Carbon\Carbon::parse($ev->date)->format('d') }}</div>
            <div style="font-size:9px;color:#64748B;text-transform:uppercase">{{ \Carbon\Carbon::parse($ev->date)->format('D') }}</div>
        </div>
        <div>
            <div style="font-weight:700;font-size:13px">{{ $ev->title }}</div>
            @if($ev->description)<div style="font-size:12px;color:var(--muted);margin-top:3px">{{ $ev->description }}</div>@endif
        </div>
    </div>
    @endforeach
</div>
@empty
<div class="card"><div class="empty"><div class="empty-icon">📆</div><div>No events on the calendar.</div></div></div>
@endforelse
@endsection
