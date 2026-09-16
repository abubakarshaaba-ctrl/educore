@extends('layouts.app')
@section('title', 'Grade Scales')
@section('page-title', 'Promotion Engine')

@push('styles')
<style>
.grade-layout{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:18px;align-items:start}.grade-intro{margin-bottom:16px;padding:16px 18px;border:1px solid #E2E8F0;border-radius:13px;background:#fff;display:flex;justify-content:space-between;gap:18px;align-items:center}.grade-intro h2{margin:0 0 4px;color:#071E45;font-size:17px}.grade-intro p{margin:0;color:#64748B;font-size:11px;line-height:1.5}.grade-count{flex:0 0 auto;padding:7px 10px;border-radius:20px;background:#FFF8E8;color:#8A5B00;font-size:10px;font-weight:800}
.section-group{margin-bottom:14px;border:1px solid #E2E8F0;border-radius:13px;background:#fff;overflow:hidden;box-shadow:0 2px 10px rgba(15,23,42,.035)}.section-group summary{list-style:none;cursor:pointer;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;background:#F8FAFC;border-bottom:1px solid transparent}.section-group[open] summary{border-bottom-color:#E8EDF4}.section-group summary::-webkit-details-marker{display:none}.section-name{color:#071E45;font-size:13px;font-weight:800}.section-meta{color:#64748B;font-size:10px;font-weight:600}.section-chevron{color:#94A3B8;font-size:12px;transition:transform .15s}.section-group[open] .section-chevron{transform:rotate(180deg)}
.level-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:14px}.level-card{border:1px solid #E3E8EF;border-radius:11px;overflow:hidden;background:#fff}.level-head{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;background:#FCFDFE;border-bottom:1px solid #EEF1F5}.level-title{font-size:12px;font-weight:800;color:#1E293B}.level-count{font-size:9px;color:#64748B;background:#F1F5F9;border-radius:20px;padding:3px 7px;font-weight:700}.empty-grade{padding:20px 12px;text-align:center;color:#94A3B8;font-size:11px}
.grade-table-wrap{overflow-x:auto}.grade-table{width:100%;border-collapse:collapse}.grade-table th{padding:7px 9px;background:#F8FAFC;color:#7A8699;font-size:9px;text-transform:uppercase;letter-spacing:.04em;text-align:left;border-bottom:1px solid #E8EDF4;white-space:nowrap}.grade-table td{padding:8px 9px;border-bottom:1px solid #EEF2F6;color:#334155;font-size:10.5px;vertical-align:middle}.grade-table tr:last-child td{border-bottom:0}.grade-letter{font-size:12px;font-weight:900;color:#071E45}.range{white-space:nowrap}.pass{color:#067647;font-weight:800}.fail{color:#B42318;font-weight:800}.delete-btn{border:1px solid #FECDCA;background:#FFF5F4;color:#B42318;border-radius:7px;padding:3px 7px;font-size:10px;cursor:pointer}
.form-card{position:sticky;top:76px;background:#fff;border:1px solid #E2E8F0;border-radius:13px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.05)}.form-head{padding:14px 16px;background:#071E45}.form-head h3{margin:0;color:#fff;font-size:13px}.form-head p{margin:4px 0 0;color:#D8E1EE;font-size:10px;line-height:1.45}.form-body{padding:16px}.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}.fl{font-size:10px;font-weight:800;color:#475569}.fl .req{color:#B42318}.fc{width:100%;min-height:39px;padding:8px 10px;border:1px solid #D6DDE8;border-radius:8px;background:#fff;color:#1E293B;font:500 12px inherit;outline:none}.fc:focus{border-color:#D79A21;box-shadow:0 0 0 3px rgba(215,154,33,.11)}.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}.check-row{display:flex;align-items:center;gap:7px;color:#475569;font-size:11px;margin:2px 0 14px}.save-grade{width:100%;border:0;border-radius:8px;background:#071E45;color:#fff;padding:10px 14px;font:800 11px inherit;cursor:pointer}.save-grade:hover{background:#0B2D63}
.level-picker{border:1px solid #D6DDE8;border-radius:9px;overflow:hidden;background:#FAFBFC}.picker-tools{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:8px 9px;border-bottom:1px solid #E6EAF0;background:#F8FAFC}.picker-tools span{font-size:9px;color:#64748B;font-weight:700}.picker-actions{display:flex;gap:5px}.picker-actions button{border:0;background:transparent;color:#0B5CAD;font-size:9px;font-weight:800;cursor:pointer;padding:2px 4px}.picker-body{max-height:210px;overflow:auto;padding:8px}.picker-section{margin-bottom:9px}.picker-section:last-child{margin-bottom:0}.picker-section-title{font-size:9px;color:#64748B;text-transform:uppercase;font-weight:900;letter-spacing:.04em;margin:2px 2px 5px}.level-check{display:flex;align-items:center;gap:7px;padding:5px 6px;border-radius:6px;font-size:10.5px;color:#334155;cursor:pointer}.level-check:hover{background:#EFF4FA}.selection-summary{font-size:9px;color:#64748B;margin-top:5px}
.alert-s,.alert-e{border-radius:9px;padding:11px 14px;font-size:11px;margin-bottom:14px}.alert-s{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.alert-e{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.alert-e ul{margin:5px 0 0 16px;padding:0}
@media(max-width:1024px){.grade-layout{grid-template-columns:1fr}.form-card{position:static}.level-grid{grid-template-columns:1fr}}@media(max-width:640px){.grade-intro{align-items:flex-start}.grade-count{display:none}.level-grid{padding:10px}.form-row{grid-template-columns:1fr}.section-group summary{padding:12px}.grade-table th,.grade-table td{padding:7px 8px}}
</style>
@endpush

@section('content')
@include('classes.partials.promotion-tabs')

@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())
<div class="alert-e"><strong>Grade entry could not be saved.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

@php
    $sectionLabels = [
        'creche' => 'Creche',
        'nursery' => 'Nursery',
        'primary' => 'Primary',
        'junior_secondary' => 'Junior Secondary',
        'senior_secondary' => 'Senior Secondary',
        'general' => 'General',
    ];
    $groupedLevels = $levels->groupBy(fn($level) => $level->section ?: 'general');
    $selectedLevelIds = collect(old('class_level_ids', old('class_level_id') ? [old('class_level_id')] : []))->map(fn($id)=>(string)$id)->all();
@endphp

<div class="grade-intro">
    <div>
        <h2>Grade scales by class group</h2>
        <p>Create one grade band and apply it to one or several class levels at once. Existing bands with the same grade letter are updated; overlapping score bands are rejected before any class is changed.</p>
    </div>
    <div class="grade-count">{{ $levels->count() }} class levels</div>
</div>

<div class="grade-layout">
    <div>
        @forelse($groupedLevels as $section => $sectionLevels)
            @php
                $configured = $sectionLevels->filter(fn($level) => $level->gradingSystems->isNotEmpty())->count();
                $sectionLabel = $sectionLabels[$section] ?? ucwords(str_replace('_',' ',$section));
            @endphp
            <details class="section-group" {{ $loop->first ? 'open' : '' }}>
                <summary>
                    <div>
                        <div class="section-name">{{ $sectionLabel }}</div>
                        <div class="section-meta">{{ $sectionLevels->count() }} {{ Str::plural('class level',$sectionLevels->count()) }} · {{ $configured }} configured</div>
                    </div>
                    <span class="section-chevron">⌄</span>
                </summary>
                <div class="level-grid">
                    @foreach($sectionLevels as $level)
                        @php $levelGrades = $level->gradingSystems->sortByDesc('min_score'); @endphp
                        <div class="level-card">
                            <div class="level-head"><span class="level-title">{{ $level->name }}</span><span class="level-count">{{ $levelGrades->count() }} grades</span></div>
                            @if($levelGrades->isEmpty())
                                <div class="empty-grade">No grade scale defined yet.</div>
                            @else
                                <div class="grade-table-wrap"><table class="grade-table"><thead><tr><th>Grade</th><th>Range</th><th>Remark</th><th>Pass</th><th>GP</th><th></th></tr></thead><tbody>
                                @foreach($levelGrades as $g)
                                    <tr>
                                        <td class="grade-letter">{{ $g->grade_letter }}</td><td class="range">{{ $g->min_score }}–{{ $g->max_score }}</td><td>{{ $g->remark }}</td>
                                        <td><span class="{{ $g->is_pass_grade ? 'pass' : 'fail' }}">{{ $g->is_pass_grade ? 'Yes' : 'No' }}</span></td><td>{{ $g->grade_point }}</td>
                                        <td><form method="POST" action="{{ route('classes.grading.destroy',$g) }}" onsubmit="return confirm('Remove this grade entry from {{ $level->name }}?')">@csrf @method('DELETE')<button type="submit" class="delete-btn">Remove</button></form></td>
                                    </tr>
                                @endforeach
                                </tbody></table></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </details>
        @empty
            <div class="empty-grade" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px">No class levels have been configured.</div>
        @endforelse
    </div>

    <aside class="form-card">
        <div class="form-head"><h3>Add or update grade</h3><p>Select every class level that should receive the same score band.</p></div>
        <div class="form-body">
            <form method="POST" action="{{ route('classes.grading.store') }}">
                @csrf
                <div class="fg">
                    <label class="fl">Apply to class levels <span class="req">*</span></label>
                    <div class="level-picker">
                        <div class="picker-tools"><span id="gradeSelectionCount">0 selected</span><div class="picker-actions"><button type="button" onclick="toggleGradeLevels(true)">Select all</button><button type="button" onclick="toggleGradeLevels(false)">Clear</button></div></div>
                        <div class="picker-body">
                            @foreach($groupedLevels as $section => $sectionLevels)
                                <div class="picker-section">
                                    <div class="picker-section-title">{{ $sectionLabels[$section] ?? ucwords(str_replace('_',' ',$section)) }}</div>
                                    @foreach($sectionLevels as $level)
                                        <label class="level-check"><input class="grade-level-check" type="checkbox" name="class_level_ids[]" value="{{ $level->id }}" {{ in_array((string)$level->id,$selectedLevelIds,true) ? 'checked' : '' }} onchange="updateGradeSelectionCount()"><span>{{ $level->name }}</span></label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="selection-summary">A conflicting score range in any selected class blocks the entire save so configurations remain consistent.</div>
                </div>
                <div class="form-row">
                    <div class="fg"><label class="fl">Grade <span class="req">*</span></label><input type="text" name="grade_letter" class="fc" value="{{ old('grade_letter') }}" maxlength="5" placeholder="A1" required></div>
                    <div class="fg"><label class="fl">Grade point</label><input type="number" name="grade_point" class="fc" value="{{ old('grade_point',0) }}" min="0" placeholder="0"></div>
                </div>
                <div class="form-row">
                    <div class="fg"><label class="fl">Minimum score <span class="req">*</span></label><input type="number" name="min_score" class="fc" value="{{ old('min_score') }}" min="0" max="100" required></div>
                    <div class="fg"><label class="fl">Maximum score <span class="req">*</span></label><input type="number" name="max_score" class="fc" value="{{ old('max_score') }}" min="0" max="100" required></div>
                </div>
                <div class="fg"><label class="fl">Remark <span class="req">*</span></label><input type="text" name="remark" class="fc" value="{{ old('remark') }}" maxlength="100" placeholder="Excellent, Good, Pass, Fail..." required></div>
                <label class="check-row"><input type="checkbox" name="is_pass_grade" value="1" {{ old('is_pass_grade','1') ? 'checked' : '' }}> This is a passing grade</label>
                <button type="submit" class="save-grade">Save grade entry</button>
            </form>
        </div>
    </aside>
</div>

<script>
function updateGradeSelectionCount(){
    const count=document.querySelectorAll('.grade-level-check:checked').length;
    document.getElementById('gradeSelectionCount').textContent=count+' selected';
}
function toggleGradeLevels(checked){document.querySelectorAll('.grade-level-check').forEach(cb=>cb.checked=checked);updateGradeSelectionCount();}
updateGradeSelectionCount();
</script>
@endsection
