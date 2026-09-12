@extends('layouts.app')
@section('title','My Supervision Schedule')
@section('page-title','My Supervision Schedule')

@push('styles')
<style>
.duty-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}.duty-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:15px}.duty-card .k{font-size:10px;text-transform:uppercase;color:var(--slate-light);font-weight:700}.duty-card .v{font-size:20px;font-weight:700;color:var(--midnight);margin-top:4px}.day{font-size:14px;font-weight:700;color:var(--midnight);margin:18px 0 8px}.row-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:8px;display:grid;grid-template-columns:140px 1.4fr 1fr 1fr;gap:12px;align-items:center}.muted{color:var(--slate-light);font-size:12px}.filters{display:flex;gap:8px;align-items:end;flex-wrap:wrap;background:#fff;border:1px solid var(--border);border-radius:12px;padding:12px;margin-bottom:16px}.filters select{padding:8px;border:1px solid var(--border);border-radius:8px}@media(max-width:760px){.duty-grid{grid-template-columns:1fr}.row-card{grid-template-columns:1fr 1fr}.row-card>div:first-child{grid-column:span 2}}
</style>
@endpush

@section('content')
<div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
<div><h2 style="margin:0">My Supervision Schedule</h2><div class="muted">Only examination duties assigned to your staff account are shown.</div></div>
<a class="btn btn-secondary" href="{{ route('exam-timetable.index') }}">Exam Timetable</a>
</div>

<form method="GET" class="filters">
<div><label>Term</label><br><select name="term_id"><option value="">Current term</option>@foreach($terms as $term)<option value="{{ $term->id }}" @selected((int)$termId===$term->id)>{{ $term->session?->name }} · {{ $term->name }}</option>@endforeach</select></div>
<div><label>View</label><br><select name="scope"><option value="upcoming" @selected($scope==='upcoming')>Upcoming duties</option><option value="all" @selected($scope==='all')>All duties</option></select></div>
<button class="btn btn-primary">Apply</button>
</form>

<div class="duty-grid">
<div class="duty-card"><div class="k">Assigned duties</div><div class="v">{{ $duties->flatten()->count() }}</div></div>
<div class="duty-card"><div class="k">Next duty</div><div class="v" style="font-size:16px">{{ $nextDuty ? $nextDuty->examSchedule->exam_date->format('d M Y') : 'None' }}</div></div>
<div class="duty-card"><div class="k">Current term</div><div class="v" style="font-size:16px">{{ $currentTerm?->name ?? 'Not set' }}</div></div>
</div>

@forelse($duties as $date => $rows)
<div class="day">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</div>
@foreach($rows as $duty)
@php($exam=$duty->examSchedule)
<div class="row-card">
<div><strong>{{ \Carbon\Carbon::parse($exam->start_time)->format('g:i A') }}</strong><div class="muted">to {{ \Carbon\Carbon::parse($exam->end_time)->format('g:i A') }}</div></div>
<div><strong>{{ $exam->subject?->name }}</strong><div class="muted">{{ $exam->classArm?->classLevel?->name }} {{ $exam->classArm?->name }}</div></div>
<div><strong>{{ $exam->venue ?: 'Venue not specified' }}</strong><div class="muted">{{ $exam->title ?: 'Examination' }}</div></div>
<div><strong>{{ $duty->role }}</strong>@if($duty->notes)<div class="muted">{{ $duty->notes }}</div>@endif</div>
</div>
@endforeach
@empty
<div class="duty-card" style="text-align:center;padding:36px"><strong>No supervision duties found.</strong><div class="muted" style="margin-top:5px">When you are assigned to supervise an examination, it will appear here automatically.</div></div>
@endforelse
@endsection
