@extends('layouts.app')
@section('title', 'Edit Exam Period')
@section('page-title', 'Edit Exam Period')

@push('styles')
<style>
.fg{margin-bottom:14px}
.fl{display:block;margin-bottom:5px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--slate)}
.fc{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:#F8FAFC}
.fc:focus{outline:none;border-color:var(--indigo);background:#fff}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.chip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px}
.chip{display:flex;align-items:center;gap:7px;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;cursor:pointer}
.chip input{accent-color:var(--indigo)}
.session-row{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end;margin-bottom:10px}
.day-chip{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border:1px solid var(--border);border-radius:20px;font-size:12px;margin-right:6px;cursor:pointer}
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-title">Edit {{ $period->title }}</div>
    <div class="page-header-actions"><a href="{{ route('exams.show', $period) }}" class="btn btn-ghost">← Back</a></div>
</div>

@if($errors->any())<div class="alert-error" style="margin-bottom:16px;padding:12px 16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;color:#B91C1C">{{ $errors->first() }}</div>@endif

@if($period->status !== 'draft')
<div style="margin-bottom:16px;padding:12px 16px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;color:#92400E;font-size:13px">
    ⚠ Saving changes will clear the existing timetable and supervision plan — you'll need to regenerate them.
</div>
@endif

<form method="POST" action="{{ route('exams.update', $period) }}" id="examForm">
@csrf
@method('PUT')

<div class="card">
    <div class="ch">Exam Period Details</div>
    <div class="cb">
        <div class="two-col">
            <div class="fg">
                <label class="fl">Title</label>
                <input class="fc" type="text" name="title" value="{{ old('title', $period->title) }}" required>
            </div>
            <div class="fg">
                <label class="fl">Term</label>
                <select class="fc" name="term_id" required>
                    <option value="">Select term</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}" {{ (int) old('term_id', $period->term_id) === $t->id ? 'selected' : '' }}>
                            {{ $t->name }} — {{ optional($t->session)->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="two-col">
            <div class="fg">
                <label class="fl">Start Date</label>
                <input class="fc" type="date" name="start_date" value="{{ old('start_date', $period->start_date->toDateString()) }}" required>
            </div>
            <div class="fg">
                <label class="fl">End Date</label>
                <input class="fc" type="date" name="end_date" value="{{ old('end_date', $period->end_date->toDateString()) }}" required>
            </div>
        </div>
        <div class="fg">
            <label class="fl">Excluded Days</label>
            @php $excluded = old('excluded_weekdays', $period->excluded_weekdays ?? [0, 6]); @endphp
            <div>
                @foreach(['0'=>'Sun','1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat'] as $val => $lbl)
                <label class="day-chip">
                    <input type="checkbox" name="excluded_weekdays[]" value="{{ $val }}" {{ in_array((int) $val, array_map('intval', $excluded), true) ? 'checked' : '' }}>
                    {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="ch">Daily Exam Sessions</div>
    <div class="cb">
        <div id="sessionRows">
            @foreach($period->examSessions as $i => $s)
            <div class="session-row">
                <div><label class="fl">Session Name</label><input class="fc" name="sessions[{{ $i }}][name]" value="{{ $s->name }}" required></div>
                <div><label class="fl">Start</label><input class="fc" type="time" name="sessions[{{ $i }}][start_time]" value="{{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}" required></div>
                <div><label class="fl">End</label><input class="fc" type="time" name="sessions[{{ $i }}][end_time]" value="{{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}" required></div>
                <div>@if($loop->first)<div style="height:1px"></div>@else<button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('.session-row').remove()">Remove</button>@endif</div>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-ghost btn-sm" id="addSession">+ Add another session</button>
    </div>
</div>

<div class="card">
    <div class="ch">Classes Sitting This Exam</div>
    <div class="cb">
        <div class="chip-grid">
            @php $selectedLevels = $period->classLevels->pluck('id')->all(); @endphp
            @foreach($classLevels as $cl)
            <label class="chip">
                <input type="checkbox" name="class_level_ids[]" value="{{ $cl->id }}" {{ in_array($cl->id, $selectedLevels, true) ? 'checked' : '' }}>
                {{ $cl->name }}
            </label>
            @endforeach
        </div>
    </div>
</div>

<button type="submit" class="btn btn-primary">Save Changes</button>
</form>

<script>
let sessionCount = {{ $period->examSessions->count() }};
document.getElementById('addSession').addEventListener('click', function () {
    const row = document.createElement('div');
    row.className = 'session-row';
    row.innerHTML = `
        <div><label class="fl">Session Name</label><input class="fc" name="sessions[${sessionCount}][name]" required></div>
        <div><label class="fl">Start</label><input class="fc" type="time" name="sessions[${sessionCount}][start_time]" required></div>
        <div><label class="fl">End</label><input class="fc" type="time" name="sessions[${sessionCount}][end_time]" required></div>
        <div><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('.session-row').remove()">Remove</button></div>
    `;
    document.getElementById('sessionRows').appendChild(row);
    sessionCount++;
});
</script>
@endsection
