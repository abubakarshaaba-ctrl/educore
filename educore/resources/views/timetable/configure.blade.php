@extends('layouts.app')
@section('title', 'Timetable Setup')
@section('page-title', 'Timetable')

@push('styles')
<style>
.page-tabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content}
.page-tab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none}.page-tab.active{background:var(--indigo);color:white}
.setup-grid{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:20px;align-items:start}.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.card-header{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC}.card-title{font-size:14px;font-weight:700;color:var(--midnight)}.card-body{padding:20px}
.form-group{margin-bottom:14px}.form-label{display:block;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}.form-control{width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}.form-control:focus{border-color:var(--indigo);background:white}.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.break-row{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:end;margin-bottom:8px;background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:10px}.break-label{font-size:11px;font-weight:600;color:var(--slate);margin-bottom:4px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;text-decoration:none}.btn-primary{background:var(--indigo);color:white;width:100%}.btn-ghost{background:white;color:var(--slate);border:1px solid var(--border)}.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA;padding:8px 10px}.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px}.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px}
.preview-box{background:#F8FAFC;border:1px solid var(--border);border-radius:10px;padding:14px}.preview-slot{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid var(--border);font-size:12px}.preview-slot:last-child{border-bottom:none}.existing-card{border:1px solid var(--border);border-radius:9px;padding:12px;margin-bottom:8px}.existing-session{font-size:13px;font-weight:700;color:var(--midnight)}.existing-detail{font-size:12px;color:var(--slate);margin-top:3px}
@media(max-width:900px){.setup-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}.break-row{grid-template-columns:1fr 1fr}.page-tabs{width:100%;overflow-x:auto}.page-tab{white-space:nowrap}}
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('timetable.configure') }}" class="page-tab active">1. School Hours</a>
    <a href="{{ route('timetable.frequency') }}" class="page-tab">2. Subject Frequency</a>
    <a href="{{ route('timetable.index') }}" class="page-tab">3. View / Generate</a>
    <a href="{{ route('timetable.teacher') }}" class="page-tab">Teacher View</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="setup-grid">
    <div>
        <div class="card">
            <div class="card-header"><div class="card-title">Configure School Hours & Periods</div></div>
            <div class="card-body">
                @if($sessions->isEmpty())
                    <div class="alert-error">No academic session exists yet. Create an academic session before configuring the timetable.</div>
                @else
                <form method="POST" action="{{ route('timetable.config.save') }}" id="configForm">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Academic Session</label>
                        <select name="session_id" id="sessionConfig" class="form-control" required>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" {{ (int)$selectedSessionId === (int)$session->id ? 'selected' : '' }}>{{ $session->name }}{{ $session->is_current ? ' (Current)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">School Start</label><input type="time" name="school_start" class="form-control" value="{{ old('school_start', $selectedConfig ? substr((string)$selectedConfig->school_start,0,5) : '07:30') }}" required></div>
                        <div class="form-group"><label class="form-label">School End</label><input type="time" name="school_end" class="form-control" value="{{ old('school_end', $selectedConfig ? substr((string)$selectedConfig->school_end,0,5) : '14:30') }}" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Periods Per Day</label><input type="number" min="1" max="12" name="periods_per_day" class="form-control" value="{{ old('periods_per_day', $selectedConfig->periods_per_day ?? 8) }}" required></div>
                        <div class="form-group"><label class="form-label">Period Duration (minutes)</label><input type="number" min="20" max="120" name="period_duration" class="form-control" value="{{ old('period_duration', $selectedConfig->period_duration ?? 40) }}" required></div>
                    </div>

                    @php
                        $initialBreaks = old('breaks', $selectedConfig->breaks ?? [
                            ['after_period'=>3,'duration'=>20,'label'=>'Short Break'],
                            ['after_period'=>6,'duration'=>30,'label'=>'Long Break'],
                        ]);
                    @endphp
                    <div class="form-group">
                        <label class="form-label">Break Periods</label>
                        <div id="breaksContainer">
                            @foreach($initialBreaks as $index => $break)
                            <div class="break-row">
                                <div><div class="break-label">After Period #</div><input type="number" min="1" name="breaks[{{ $index }}][after_period]" class="form-control" value="{{ $break['after_period'] ?? '' }}"></div>
                                <div><div class="break-label">Duration</div><input type="number" min="5" max="60" name="breaks[{{ $index }}][duration]" class="form-control" value="{{ $break['duration'] ?? '' }}"></div>
                                <div><div class="break-label">Label</div><input type="text" maxlength="50" name="breaks[{{ $index }}][label]" class="form-control" value="{{ $break['label'] ?? 'Break' }}"></div>
                                <button type="button" class="btn btn-danger" onclick="this.closest('.break-row').remove(); updatePreview()">×</button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-ghost" onclick="addBreak()">+ Add Break</button>
                    </div>
                    <button type="submit" class="btn btn-primary">Save School Hours</button>
                </form>
                @endif
            </div>
        </div>

        @if($configs->count())
        <div class="card">
            <div class="card-header"><div class="card-title">Saved Configurations</div></div>
            <div class="card-body">
                @foreach($configs as $cfg)
                <div class="existing-card">
                    <div class="existing-session">{{ optional($cfg->session)->name ?? 'Unknown session' }}</div>
                    <div class="existing-detail">{{ substr((string)$cfg->school_start,0,5) }} – {{ substr((string)$cfg->school_end,0,5) }} · {{ $cfg->periods_per_day }} periods/day · {{ $cfg->period_duration }} mins · {{ count($cfg->breaks ?? []) }} break(s)</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">Live Slot Preview</div></div>
        <div class="card-body"><div class="preview-box" id="previewSlots">Preview will appear here.</div></div>
    </div>
</div>

@push('scripts')
<script>
let breakIndex = {{ count($initialBreaks ?? []) }};
const esc = value => String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));
function addBreak(){
    const box=document.getElementById('breaksContainer'); if(!box) return;
    const i=breakIndex++;
    const row=document.createElement('div'); row.className='break-row';
    row.innerHTML=`<div><div class="break-label">After Period #</div><input type="number" min="1" name="breaks[${i}][after_period]" class="form-control"></div><div><div class="break-label">Duration</div><input type="number" min="5" max="60" name="breaks[${i}][duration]" class="form-control"></div><div><div class="break-label">Label</div><input type="text" maxlength="50" name="breaks[${i}][label]" class="form-control" value="Break"></div><button type="button" class="btn btn-danger">×</button>`;
    row.querySelector('button').addEventListener('click',()=>{row.remove();updatePreview()});
    row.querySelectorAll('input').forEach(el=>el.addEventListener('input',updatePreview)); box.appendChild(row); updatePreview();
}
function updatePreview(){
    const box=document.getElementById('previewSlots'); if(!box) return;
    const start=document.querySelector('[name="school_start"]')?.value;
    const count=parseInt(document.querySelector('[name="periods_per_day"]')?.value||'0',10);
    const duration=parseInt(document.querySelector('[name="period_duration"]')?.value||'0',10);
    if(!start||!count||!duration){box.textContent='Enter valid school hours and period settings.';return;}
    const breaks=[]; document.querySelectorAll('.break-row').forEach(row=>{const a=parseInt(row.querySelector('[name*="after_period"]')?.value||'0',10),d=parseInt(row.querySelector('[name*="duration"]')?.value||'0',10),l=row.querySelector('[name*="label"]')?.value||'Break';if(a&&d)breaks.push({a,d,l});});
    let [h,m]=start.split(':').map(Number), mins=h*60+m, html='';
    const fmt=n=>`${String(Math.floor(n/60)).padStart(2,'0')}:${String(n%60).padStart(2,'0')}`;
    for(let i=1;i<=count;i++){const s=fmt(mins);mins+=duration;const e=fmt(mins);html+=`<div class="preview-slot"><strong>Period ${i}</strong><span>${s} – ${e}</span></div>`;const b=breaks.find(x=>x.a===i);if(b){const bs=fmt(mins);mins+=b.d;html+=`<div class="preview-slot"><strong>${esc(b.l)}</strong><span>${bs} – ${fmt(mins)}</span></div>`;}}
    box.innerHTML=html||'No slots configured.';
}
document.getElementById('sessionConfig')?.addEventListener('change',e=>{const url=new URL(window.location.href);url.searchParams.set('session_id',e.target.value);window.location.href=url.toString();});
document.querySelectorAll('#configForm input').forEach(el=>el.addEventListener('input',updatePreview)); updatePreview();
</script>
@endpush
@endsection
