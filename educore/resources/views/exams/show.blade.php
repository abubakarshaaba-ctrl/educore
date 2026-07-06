@extends('layouts.app')
@section('title', $period->title)
@section('page-title', 'Exam Timetable')

@push('styles')
<style>
.step-bar{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.step{padding:8px 14px;border-radius:8px;font-size:12.5px;font-weight:700;background:#F1F5F9;color:var(--slate)}
.step.done{background:#ECFDF5;color:#047857}
.step.active{background:var(--indigo);color:#fff}
table.tt{width:100%;border-collapse:collapse;font-size:12.5px}
table.tt th{padding:8px 10px;background:#F8FAFC;border:1px solid var(--border);text-align:left;font-size:10.5px;text-transform:uppercase;color:var(--slate)}
table.tt td{padding:8px 10px;border:1px solid var(--border)}
.day-block{margin-bottom:18px}
.day-title{font-weight:800;color:var(--midnight);margin-bottom:6px;font-size:13.5px}
.chip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;max-height:320px;overflow-y:auto;padding:4px}
.chip{display:flex;align-items:center;gap:7px;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:12.5px}
.chip input{accent-color:var(--indigo)}
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">{{ $period->title }}</div>
        <div style="font-size:12px;color:var(--slate-light);margin-top:2px">
            {{ optional($period->term)->name }} · {{ $period->start_date->format('d M') }} – {{ $period->end_date->format('d M Y') }}
        </div>
    </div>
    <div class="page-header-actions"><a href="{{ route('exams.index') }}" class="btn btn-ghost">← All Periods</a></div>
</div>

@if(session('success'))<div class="alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="margin-bottom:16px;padding:12px 16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;color:#B91C1C">{{ $errors->first() }}</div>@endif

<div class="step-bar">
    <span class="step {{ $period->status !== 'draft' ? 'done' : 'active' }}">1. Timetable</span>
    <span class="step {{ in_array($period->status, ['supervision_planned','published']) ? 'done' : ($period->status === 'timetabled' ? 'active' : '') }}">2. Staff Pool</span>
    <span class="step {{ $period->status === 'published' ? 'done' : ($period->status === 'supervision_planned' ? 'active' : '') }}">3. Supervision</span>
    <span class="step {{ $period->status === 'published' ? 'done' : '' }}">4. Publish</span>
</div>

{{-- Step 1: Timetable --}}
<div class="card">
    <div class="ch" style="display:flex;justify-content:space-between;align-items:center">
        <span>Exam Timetable — {{ $period->classLevels->pluck('name')->join(', ') }}</span>
        <form method="POST" action="{{ route('exams.timetable.generate', $period) }}" onsubmit="return confirm('This will (re)build the timetable. Continue?')">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">{{ $period->entries->isEmpty() ? 'Generate Timetable' : 'Regenerate Timetable' }}</button>
        </form>
    </div>
    <div class="cb">
        @forelse($entriesByDate as $date => $dayEntries)
        <div class="day-block">
            <div class="day-title">{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}</div>
            <table class="tt">
                <thead><tr><th>Session</th><th>Class</th><th>Subject</th><th>Supervisor</th></tr></thead>
                <tbody>
                @foreach($dayEntries as $e)
                <tr>
                    <td>{{ $e->examSession->name }} ({{ \Carbon\Carbon::parse($e->examSession->start_time)->format('g:ia') }}–{{ \Carbon\Carbon::parse($e->examSession->end_time)->format('g:ia') }})</td>
                    <td>{{ $e->classLevel->name }}</td>
                    <td>{{ $e->subject->name }}</td>
                    <td>{{ $e->supervisors->map(fn($s) => $s->user->name)->join(', ') ?: '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @empty
        <div style="padding:30px;text-align:center;color:var(--slate-light)">No timetable generated yet. Click "Generate Timetable" above.</div>
        @endforelse
    </div>
</div>

{{-- Step 2: Staff Pool --}}
<div class="card">
    <div class="ch">Supervision Staff Pool</div>
    <div class="cb">
        <form method="POST" action="{{ route('exams.staff-pool.save', $period) }}">
            @csrf
            <div class="chip-grid">
                @foreach($staff as $s)
                <label class="chip">
                    <input type="checkbox" name="user_ids[]" value="{{ $s->id }}"
                        {{ $period->staffPool->contains('id', $s->id) ? 'checked' : '' }}>
                    {{ $s->name }} <span style="color:var(--slate-light)">({{ $s->roleLabel() }})</span>
                </label>
                @endforeach
            </div>
            <button type="submit" class="btn btn-ghost btn-sm" style="margin-top:12px">Save Staff Pool</button>
        </form>
    </div>
</div>

{{-- Step 3: Supervision --}}
<div class="card">
    <div class="ch" style="display:flex;justify-content:space-between;align-items:center">
        <span>Supervision Plan</span>
        <form method="POST" action="{{ route('exams.supervision.generate', $period) }}" onsubmit="return confirm('This will (re)assign supervisors. Continue?')">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm" {{ $period->entries->isEmpty() ? 'disabled' : '' }}>Generate Supervision Plan</button>
        </form>
    </div>
    <div class="cb">
        <p style="font-size:13px;color:var(--slate-light);margin:0">
            Distributes one supervisor per sitting from the staff pool above — balanced load, no clashes,
            and avoids assigning a teacher to invigilate their own subject where possible.
        </p>
    </div>
</div>

{{-- Step 4: Publish --}}
<div class="card">
    <div class="ch">Publish</div>
    <div class="cb" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <p style="font-size:13px;color:var(--slate-light);margin:0">
            Publishing makes each staff member's personal supervision schedule visible on the EduCore app
            ("My Exam Duties") and sends them a push notification.
        </p>
        <form method="POST" action="{{ route('exams.publish', $period) }}">
            @csrf
            <button type="submit" class="btn btn-primary" {{ $period->status !== 'supervision_planned' && $period->status !== 'published' ? 'disabled' : '' }}>
                {{ $period->status === 'published' ? 'Re-publish' : 'Publish to Staff' }}
            </button>
        </form>
    </div>
</div>
@endsection
