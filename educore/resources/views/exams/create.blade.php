@extends('layouts.app')
@section('title', 'New Exam Period')
@section('page-title', 'New Exam Period')

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
    <div class="page-title">New Exam Period</div>
    <div class="page-header-actions"><a href="{{ route('exams.index') }}" class="btn btn-ghost">← Back</a></div>
</div>

@if($errors->any())<div class="alert-error" style="margin-bottom:16px;padding:12px 16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;color:#B91C1C">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('exams.store') }}" id="examForm">
@csrf

<div class="card">
    <div class="ch">Exam Period Details</div>
    <div class="cb">
        <div class="two-col">
            <div class="fg">
                <label class="fl">Title</label>
                <input class="fc" type="text" name="title" placeholder="e.g. 3rd Term Examination 2026" required>
            </div>
            <div class="fg">
                <label class="fl">Term</label>
                <select class="fc" name="term_id" required>
                    <option value="">Select term</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} — {{ optional($t->session)->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="two-col">
            <div class="fg">
                <label class="fl">Start Date</label>
                <input class="fc" type="date" name="start_date" required>
            </div>
            <div class="fg">
                <label class="fl">End Date</label>
                <input class="fc" type="date" name="end_date" required>
            </div>
        </div>
        <div class="fg">
            <label class="fl">Excluded Days</label>
            <div>
                @foreach(['0'=>'Sun','1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat'] as $val => $lbl)
                <label class="day-chip">
                    <input type="checkbox" name="excluded_weekdays[]" value="{{ $val }}" {{ in_array($val, ['0','6']) ? 'checked' : '' }}>
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
            <div class="session-row">
                <div><label class="fl">Session Name</label><input class="fc" name="sessions[0][name]" value="Morning" required></div>
                <div><label class="fl">Start</label><input class="fc" type="time" name="sessions[0][start_time]" value="08:00" required></div>
                <div><label class="fl">End</label><input class="fc" type="time" name="sessions[0][end_time]" value="10:00" required></div>
                <div></div>
            </div>
            <div class="session-row">
                <div><label class="fl">Session Name</label><input class="fc" name="sessions[1][name]" value="Afternoon" required></div>
                <div><label class="fl">Start</label><input class="fc" type="time" name="sessions[1][start_time]" value="11:00" required></div>
                <div><label class="fl">End</label><input class="fc" type="time" name="sessions[1][end_time]" value="13:00" required></div>
                <div></div>
            </div>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" id="addSession">+ Add another session</button>
    </div>
</div>

<div class="card">
    <div class="ch">Classes Sitting This Exam</div>
    <div class="cb">
        <div class="chip-grid">
            @foreach($classLevels as $cl)
            <label class="chip">
                <input type="checkbox" name="class_level_ids[]" value="{{ $cl->id }}">
                {{ $cl->name }}
            </label>
            @endforeach
        </div>
    </div>
</div>

<button type="submit" class="btn btn-primary">Create Exam Period</button>
</form>

<script>
let sessionCount = 2;
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
