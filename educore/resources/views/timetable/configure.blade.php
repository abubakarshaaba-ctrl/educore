@extends('layouts.app')
@section('title', 'Timetable Setup')
@section('page-title', 'Timetable')

@push('styles')
<style>
.page-tabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none}.page-tabs::-webkit-scrollbar{display:none}
.page-tab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;white-space:nowrap;flex:0 0 auto}.page-tab.active{background:var(--indigo);color:white}
.setup-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,380px);gap:20px;align-items:start}.setup-grid>*{min-width:0}.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.card-header{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC}.card-title{font-size:14px;font-weight:700;color:var(--midnight)}.card-body{padding:20px;min-width:0}
.form-group{margin-bottom:14px;min-width:0}.form-label{display:block;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}.form-control{box-sizing:border-box;width:100%;min-width:0;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}.form-control:focus{border-color:var(--indigo);background:white}.form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.form-row>*{min-width:0}
.break-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1.3fr) auto;gap:8px;align-items:end;margin-bottom:8px;background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:10px}.break-row>*{min-width:0}.break-label{font-size:11px;font-weight:600;color:var(--slate);margin-bottom:4px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;text-decoration:none;white-space:nowrap}.btn-primary{background:var(--indigo);color:white;width:100%}.btn-ghost{background:white;color:var(--slate);border:1px solid var(--border)}.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA;padding:8px 10px;min-width:38px}.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px}.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px}
.preview-box{background:#F8FAFC;border:1px solid var(--border);border-radius:10px;padding:14px;min-width:0}.preview-slot{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid var(--border);font-size:12px}.preview-slot:last-child{border-bottom:none}.existing-card{border:1px solid var(--border);border-radius:9px;padding:12px;margin-bottom:8px;min-width:0}.existing-session{font-size:13px;font-weight:700;color:var(--midnight)}.existing-detail{font-size:12px;color:var(--slate);margin-top:3px;overflow-wrap:anywhere;line-height:1.5}
.day-hours{margin:4px 0 16px;border:1px solid var(--border);border-radius:10px;overflow:hidden}.day-hours-head{padding:10px 12px;background:#F8FAFC;border-bottom:1px solid var(--border)}.day-hours-title{font-size:12px;font-weight:700;color:var(--midnight)}.day-hours-note{font-size:11px;color:var(--slate);margin-top:2px;line-height:1.45}.day-hours-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:0}.day-hour{padding:10px;border-right:1px solid var(--border);min-width:0}.day-hour:last-child{border-right:none}.day-name{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--slate);margin-bottom:7px}.day-time-field{margin-bottom:8px}.day-time-field:last-of-type{margin-bottom:0}.day-time-label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--slate-light);margin-bottom:3px}.day-hour .form-control{min-width:0}.day-summary{font-size:11px;color:var(--slate);margin-top:7px;overflow-wrap:anywhere;line-height:1.35}.preview-day{display:flex;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);font-size:12px}.preview-day:last-child{border-bottom:none}.preview-day strong{color:var(--midnight)}.preview-day span{text-align:right;overflow-wrap:anywhere}
@media(max-width:1100px){.setup-grid{grid-template-columns:minmax(0,1fr) minmax(280px,320px)}.day-hours-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.day-hour{border-bottom:1px solid var(--border)}}
@media(max-width:900px){.setup-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}.break-row{grid-template-columns:repeat(2,minmax(0,1fr))}.break-row .btn-danger{width:100%}.page-tabs{width:100%}.setup-grid>.card:last-child{position:static}}
@media(max-width:640px){.card-header{padding:12px 14px}.card-body{padding:14px}.day-hours-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.day-hour{border-right:1px solid var(--border);padding:9px}.break-row{grid-template-columns:1fr}.break-row .btn-danger{min-height:38px}.form-group>.btn-ghost{width:100%}.preview-day{align-items:flex-start}.preview-day span{max-width:58%}.existing-card{padding:10px}.alert-success,.alert-error{padding:10px 12px;font-size:12px}}
@media(max-width:380px){.day-hours-grid{grid-template-columns:1fr}.day-hour{border-right:0}.preview-day{flex-direction:column;gap:3px}.preview-day span{max-width:none;text-align:left}.page-tab{padding:7px 12px;font-size:12px}}
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
                        <div class="form-group"><label class="form-label">Default School Start</label><input type="time" name="school_start" class="form-control" value="{{ old('school_start', $selectedConfig ? substr((string)$selectedConfig->school_start,0,5) : '07:30') }}" required></div>
                        <div class="form-group"><label class="form-label">Default School End</label><input type="time" name="school_end" class="form-control" value="{{ old('school_end', $selectedConfig ? substr((string)$selectedConfig->school_end,0,5) : '14:30') }}" required></div>
                    </div>

                    @php
                        $schoolDays = ['monday','tuesday','wednesday','thursday','friday'];
                        $storedDayStarts = old('day_start_times', $selectedConfig->day_start_times ?? []);
                        $storedDayEnds = old('day_end_times', $selectedConfig->day_end_times ?? []);
                        $defaultStart = old('school_start', $selectedConfig ? substr((string)$selectedConfig->school_start,0,5) : '07:30');
                        $defaultEnd = old('school_end', $selectedConfig ? substr((string)$selectedConfig->school_end,0,5) : '14:30');
                    @endphp
                    <div class="day-hours">
                        <div class="day-hours-head">
                            <div class="day-hours-title">School Hours by Day</div>
                            <div class="day-hours-note">Set a different start or closing time only where needed. Blank values inherit the default school start/end above.</div>
                        </div>
                        <div class="day-hours-grid">
                            @foreach($schoolDays as $day)
                            <div class="day-hour">
                                <div class="day-name">{{ ucfirst($day) }}</div>
                                <div class="day-time-field">
                                    <div class="day-time-label">Start</div>
                                    <input type="time"
                                           name="day_start_times[{{ $day }}]"
                                           class="form-control day-start-input"
                                           data-day="{{ $day }}"
                                           value="{{ $storedDayStarts[$day] ?? '' }}"
                                           aria-label="{{ ucfirst($day) }} start time">
                                </div>
                                <div class="day-time-field">
                                    <div class="day-time-label">Closing</div>
                                    <input type="time"
                                           name="day_end_times[{{ $day }}]"
                                           class="form-control day-end-input"
                                           data-day="{{ $day }}"
                                           value="{{ $storedDayEnds[$day] ?? '' }}"
                                           aria-label="{{ ucfirst($day) }} closing time">
                                </div>
                                <div class="day-summary" id="summary-{{ $day }}">{{ $defaultStart }}–{{ $defaultEnd }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Maximum Periods Per Day</label><input type="number" min="1" max="12" name="periods_per_day" class="form-control" value="{{ old('periods_per_day', $selectedConfig->periods_per_day ?? 8) }}" required></div>
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
                    <div class="existing-detail">{{ substr((string)$cfg->school_start,0,5) }} – {{ substr((string)$cfg->school_end,0,5) }} default · up to {{ $cfg->periods_per_day }} periods/day · {{ $cfg->period_duration }} mins · {{ count($cfg->breaks ?? []) }} break(s)</div>
                    @php
                        $startOverrides = collect($cfg->day_start_times ?? []);
                        $endOverrides = collect($cfg->day_end_times ?? []);
                        $overrideDays = $startOverrides->keys()->merge($endOverrides->keys())->unique()->values();
                    @endphp
                    @if($overrideDays->isNotEmpty())
                    <div class="existing-detail">
                        Overrides:
                        {{ $overrideDays->map(fn($day) =>
                            ucfirst($day).' '.
                            ($startOverrides->has($day) ? substr((string)$startOverrides[$day],0,5) : substr((string)$cfg->school_start,0,5)).
                            '–'.
                            ($endOverrides->has($day) ? substr((string)$endOverrides[$day],0,5) : substr((string)$cfg->school_end,0,5))
                        )->join(' · ') }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">Weekly Capacity Preview</div></div>
        <div class="card-body"><div class="preview-box" id="previewSlots">Preview will appear here.</div></div>
    </div>
</div>

@push('scripts')
<script>
let breakIndex = {{ count($initialBreaks ?? []) }};
const schoolDays = ['monday','tuesday','wednesday','thursday','friday'];
const esc = value => String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));
function addBreak(){
    const box=document.getElementById('breaksContainer'); if(!box) return;
    const i=breakIndex++;
    const row=document.createElement('div'); row.className='break-row';
    row.innerHTML=`<div><div class="break-label">After Period #</div><input type="number" min="1" name="breaks[${i}][after_period]" class="form-control"></div><div><div class="break-label">Duration</div><input type="number" min="5" max="60" name="breaks[${i}][duration]" class="form-control"></div><div><div class="break-label">Label</div><input type="text" maxlength="50" name="breaks[${i}][label]" class="form-control" value="Break"></div><button type="button" class="btn btn-danger">×</button>`;
    row.querySelector('button').addEventListener('click',()=>{row.remove();updatePreview()});
    row.querySelectorAll('input').forEach(el=>el.addEventListener('input',updatePreview)); box.appendChild(row); updatePreview();
}
function computeDay(startTime,endTime){
    const count=parseInt(document.querySelector('[name="periods_per_day"]')?.value||'0',10);
    const duration=parseInt(document.querySelector('[name="period_duration"]')?.value||'0',10);
    if(!startTime||!endTime||!count||!duration) return {periods:0,start:startTime||'—',end:endTime||'—'};
    const breaks=[]; document.querySelectorAll('.break-row').forEach(row=>{const a=parseInt(row.querySelector('[name*="after_period"]')?.value||'0',10),d=parseInt(row.querySelector('[name*="duration"]')?.value||'0',10);if(a&&d)breaks.push({a,d});});
    const toMins=t=>{const [h,m]=t.split(':').map(Number);return h*60+m};
    let mins=toMins(startTime), close=toMins(endTime), periods=0;
    for(let i=1;i<=count;i++){
        const periodEnd=mins+duration;
        if(periodEnd>close) break;
        mins=periodEnd; periods++;
        const b=breaks.find(x=>x.a===i);
        if(b){if(mins+b.d>close) break;mins+=b.d;}
    }
    return {periods,start:startTime,end:endTime};
}
function updatePreview(){
    const box=document.getElementById('previewSlots'); if(!box) return;
    const defaultStart=document.querySelector('[name="school_start"]')?.value;
    const defaultEnd=document.querySelector('[name="school_end"]')?.value;
    if(!defaultStart||!defaultEnd){box.textContent='Enter valid school hours and period settings.';return;}
    let total=0, html='';
    schoolDays.forEach(day=>{
        const startInput=document.querySelector(`[name="day_start_times[${day}]"]`);
        const endInput=document.querySelector(`[name="day_end_times[${day}]"]`);
        const effectiveStart=startInput?.value||defaultStart;
        const effectiveEnd=endInput?.value||defaultEnd;
        const result=computeDay(effectiveStart,effectiveEnd); total+=result.periods;
        const label=day.charAt(0).toUpperCase()+day.slice(1);
        html+=`<div class="preview-day"><strong>${label}</strong><span>${esc(effectiveStart)}–${esc(effectiveEnd)} · ${result.periods} teaching period${result.periods===1?'':'s'}</span></div>`;
        const summary=document.getElementById(`summary-${day}`);
        if(summary){
            const inherited=!startInput?.value&&!endInput?.value;
            summary.textContent=(inherited?'Uses defaults · ':'')+effectiveStart+'–'+effectiveEnd;
        }
    });
    html+=`<div class="preview-day"><strong>Weekly capacity</strong><span>${total} teaching periods</span></div>`;
    box.innerHTML=html;
}
document.getElementById('sessionConfig')?.addEventListener('change',e=>{const url=new URL(window.location.href);url.searchParams.set('session_id',e.target.value);window.location.href=url.toString();});
document.querySelectorAll('#configForm input').forEach(el=>el.addEventListener('input',updatePreview)); updatePreview();
</script>
@endpush
@endsection
