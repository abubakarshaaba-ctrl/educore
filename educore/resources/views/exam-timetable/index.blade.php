@extends('layouts.app')
@section('title','Exam Timetable')
@section('page-title','Exam Timetable')

@push('styles')
<style>
.et-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.et-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:16px}.et-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.et-table{width:100%;border-collapse:collapse}.et-table th,.et-table td{padding:10px;border-bottom:1px solid #edf1f5;font-size:12px;text-align:left;vertical-align:top}.et-table th{font-size:10px;text-transform:uppercase;color:var(--slate-light)}.pill{display:inline-flex;padding:3px 8px;border-radius:999px;background:#f1f5f9;margin:2px;font-size:10px}.muted{color:var(--slate-light)}.form-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.form-row input,.form-row select,.form-row textarea{width:100%;padding:9px 10px;border:1px solid var(--border);border-radius:8px;background:#fff}.day-title{font-size:14px;font-weight:700;color:var(--midnight);margin:16px 0 8px}@media(max-width:900px){.form-row,.et-grid{grid-template-columns:1fr 1fr}}@media(max-width:560px){.form-row,.et-grid{grid-template-columns:1fr}.et-card{padding:12px}.responsive-table{overflow-x:auto}}
</style>
@endpush

@section('content')
<div class="et-head" style="margin-bottom:16px">
    <div><h2 style="margin:0">Exam Timetable</h2><div class="muted">{{ $currentTerm?->name ?? 'Select a term' }} · examination schedule and supervision assignments</div></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn btn-secondary" href="{{ route('timetable.index') }}">Class Timetable</a>
        <a class="btn btn-primary" href="{{ route('exam-timetable.my-supervision') }}">My Supervision Schedule</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="et-card">
<form method="GET" class="form-row">
    <div><label>Term</label><select name="term_id"><option value="">Current term</option>@foreach($terms as $term)<option value="{{ $term->id }}" @selected((int)$termId===$term->id)>{{ $term->session?->name }} · {{ $term->name }}</option>@endforeach</select></div>
    <div><label>Class</label><select name="class_arm_id"><option value="">All classes</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(request('class_arm_id')==$class->id)>{{ $class->classLevel?->name }} {{ $class->name }}</option>@endforeach</select></div>
    <div><label>Date</label><input type="date" name="date" value="{{ request('date') }}"></div>
    <div style="display:flex;align-items:end"><button class="btn btn-primary" type="submit">Filter</button></div>
</form>
</div>

@if($canManage)
<div class="et-card">
<h3 style="margin-top:0">Add Exam</h3>
<form method="POST" action="{{ route('exam-timetable.store') }}">@csrf
<div class="form-row">
    <div><label>Term / Session</label><select name="term_id" required>@foreach($terms as $term)<option value="{{ $term->id }}" @selected((int)$termId===$term->id)>{{ $term->session?->name }} · {{ $term->name }}</option>@endforeach</select></div>
    <div><label>Class</label><select name="class_arm_id" required>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->classLevel?->name }} {{ $class->name }}</option>@endforeach</select></div>
    <div><label>Subject</label><select name="subject_id" required>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
    <div><label>Date</label><input type="date" name="exam_date" required></div>
    <div><label>Start</label><input type="time" name="start_time" required></div>
    <div><label>End</label><input type="time" name="end_time" required></div>
    <div><label>Venue</label><input name="venue" maxlength="120"></div>
    <div><label>Exam title/type</label><input name="title" placeholder="e.g. First Term Examination"></div>
    <div style="grid-column:span 2"><label>Notes</label><input name="notes"></div>
    <div style="display:flex;align-items:end"><button class="btn btn-primary" type="submit">Add to Timetable</button></div>
</div>
</form>
</div>
@endif

@forelse($schedules as $date => $rows)
<div class="day-title">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</div>
<div class="et-card responsive-table">
<table class="et-table"><thead><tr><th>Time</th><th>Class</th><th>Subject</th><th>Venue</th><th>Supervision</th>@if($canManage)<th>Assign</th><th></th>@endif</tr></thead><tbody>
@foreach($rows as $row)
<tr>
<td><strong>{{ \Carbon\Carbon::parse($row->start_time)->format('g:i A') }}</strong><br><span class="muted">to {{ \Carbon\Carbon::parse($row->end_time)->format('g:i A') }}</span></td>
<td>{{ $row->classArm?->classLevel?->name }} {{ $row->classArm?->name }}</td>
<td><strong>{{ $row->subject?->name }}</strong>@if($row->title)<br><span class="muted">{{ $row->title }}</span>@endif</td>
<td>{{ $row->venue ?: '—' }}</td>
<td>@forelse($row->supervisions as $duty)<span class="pill">{{ $duty->staff?->name }} · {{ $duty->role }}@if($canManage)<form style="display:inline" method="POST" action="{{ route('exam-timetable.supervisors.destroy',[$row,$duty]) }}">@csrf @method('DELETE') <button type="submit" style="border:0;background:transparent;cursor:pointer">×</button></form>@endif</span>@empty<span class="muted">Not assigned</span>@endforelse</td>
@if($canManage)<td><form method="POST" action="{{ route('exam-timetable.supervisors.store',$row) }}">@csrf <div style="display:flex;gap:5px"><select name="staff_id" required style="max-width:170px"><option value="">Select staff</option>@foreach($staff as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select><input name="role" value="Invigilator" style="max-width:105px"><button class="btn btn-secondary" type="submit">Assign</button></div></form></td><td><form method="POST" action="{{ route('exam-timetable.destroy',$row) }}" onsubmit="return confirm('Delete this exam entry?')">@csrf @method('DELETE')<button class="btn btn-secondary" type="submit">Delete</button></form></td>@endif
</tr>
@endforeach
</tbody></table>
</div>
@empty
<div class="et-card" style="text-align:center;padding:36px"><strong>No examination timetable has been entered for this selection.</strong><div class="muted" style="margin-top:5px">Academic management can add examination dates, times, venues and supervisors here.</div></div>
@endforelse
@endsection
