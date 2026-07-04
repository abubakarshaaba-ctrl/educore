@extends('layouts.app')
@section('title','Teacher Performance')
@section('page-title','Teacher Performance Report')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px}
.teacher-card{background:white;border:1px solid var(--border);border-radius:12px;padding:18px;display:flex;align-items:flex-start;gap:14px}
.av{width:44px;height:44px;border-radius:50%;background:var(--indigo);color:white;font-size:16px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.tname{font-size:14px;font-weight:700;color:var(--midnight)}
.trole{font-size:11px;color:var(--slate-light);text-transform:capitalize;margin-top:2px}
.tstat{font-size:22px;font-weight:800;letter-spacing:-0.02em;margin-top:6px}
.tsl{font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em}
.bar{background:#F1F5F9;border-radius:4px;height:6px;width:100%;overflow:hidden;margin-top:6px}
.bar-fill{height:6px;border-radius:4px;background:var(--indigo)}
@media(max-width:1024px){.grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if($teacherStats->isEmpty())
<div style="background:white;border:1px solid var(--border);border-radius:12px;padding:50px;text-align:center;color:var(--slate-light)">No teacher/class data available. Assign form tutors to classes first.</div>
@else
<div class="grid">
@foreach($teacherStats as $ts)
@php $a = $ts['avg_score']; $col = $a>=70?'var(--emerald)':($a>=50?'var(--amber)':' var(--crimson)'); @endphp
<div class="teacher-card">
    <div class="av">{{ strtoupper(substr($ts['teacher']->name,0,1)) }}</div>
    <div style="flex:1;min-width:0">
        <div class="tname">{{ $ts['teacher']->name }}</div>
        <div class="trole">{{ str_replace('_',' ',$ts['teacher']->role) }}</div>
        <div class="tstat" style="color:{{ $col }}">{{ $a }}</div>
        <div class="tsl">Class Avg · {{ $ts['classes'] }} class(es) · {{ $ts['students'] }} students</div>
        <div class="bar"><div class="bar-fill" style="width:{{ min($a,100) }}%;background:{{ $col }}"></div></div>
    </div>
</div>
@endforeach
</div>
@endif
@endsection