@extends('layouts.app')
@section('title', 'Promotion Rules')
@section('page-title', 'Promotion Engine')

@push('styles')
<style>
.rules-intro{margin-bottom:16px;padding:16px 18px;border:1px solid #E2E8F0;border-radius:13px;background:#fff}.rules-intro h2{margin:0 0 4px;color:#071E45;font-size:17px}.rules-intro p{margin:0;color:#64748B;font-size:11px;line-height:1.5}.bulk-rule{margin-bottom:16px;background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden}.bulk-head{padding:13px 16px;background:#071E45;color:#fff}.bulk-head h3{margin:0;font-size:13px}.bulk-head p{margin:4px 0 0;color:#D7E0ED;font-size:10px}.bulk-body{padding:15px}.bulk-grid{display:grid;grid-template-columns:minmax(270px,1.1fr) minmax(220px,.9fr);gap:14px}.criteria-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.level-picker{border:1px solid #D6DDE8;border-radius:9px;overflow:hidden;background:#FAFBFC}.picker-tools{display:flex;justify-content:space-between;align-items:center;padding:8px 9px;border-bottom:1px solid #E6EAF0;background:#F8FAFC}.picker-tools span{font-size:9px;color:#64748B;font-weight:800}.picker-actions{display:flex;gap:5px}.picker-actions button{border:0;background:transparent;color:#0B5CAD;font-size:9px;font-weight:800;cursor:pointer}.picker-body{max-height:220px;overflow:auto;padding:8px}.picker-section{margin-bottom:8px}.picker-section-title{font-size:9px;color:#64748B;text-transform:uppercase;font-weight:900;margin:2px 2px 5px}.level-check{display:flex;align-items:center;gap:7px;padding:5px 6px;border-radius:6px;font-size:10.5px;color:#334155;cursor:pointer}.level-check:hover{background:#EFF4FA}.selection-note{font-size:9.5px;color:#64748B;margin-top:6px;line-height:1.4}
.rules-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:14px}.rule-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;box-shadow:0 2px 10px rgba(15,23,42,.035);overflow:hidden}.rule-header{padding:13px 15px;border-bottom:1px solid #E8EDF4;background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;gap:10px}.rule-title{font-size:13px;font-weight:800;color:#071E45}.rule-body{padding:15px}.form-group{margin-bottom:12px}.form-label{display:block;font-size:10px;font-weight:800;color:#475569;margin-bottom:5px}.form-control{width:100%;padding:8px 10px;font-size:12px;font-family:inherit;border:1px solid #D6DDE8;border-radius:8px;background:#fff;outline:none}.form-control:focus{border-color:#D79A21;box-shadow:0 0 0 3px rgba(215,154,33,.11)}.compulsory-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px;max-height:150px;overflow-y:auto;border:1px solid #E2E8F0;border-radius:8px;padding:9px;background:#FAFBFC}.check-label{display:flex;align-items:flex-start;gap:6px;font-size:10.5px;color:#334155;cursor:pointer}.save-rule{width:100%;border:0;border-radius:8px;background:#071E45;color:#fff;padding:9px 13px;font:800 11px inherit;cursor:pointer}.save-rule:hover{background:#0B2D63}.badge{display:inline-flex;font-size:9px;font-weight:800;padding:3px 8px;border-radius:20px}.badge-success{background:#ECFDF3;color:#067647}.badge-warning{background:#FFF8E8;color:#8A5B00}.alert-s,.alert-e{border-radius:9px;padding:11px 14px;font-size:11px;margin-bottom:14px}.alert-s{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.alert-e{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.alert-e ul{margin:5px 0 0 16px;padding:0}.current-rule{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:11px}.rule-metric{padding:9px;border-radius:8px;background:#F8FAFC}.rule-metric strong{display:block;font-size:14px;color:#071E45}.rule-metric span{display:block;margin-top:2px;font-size:9px;color:#64748B;text-transform:uppercase;font-weight:700}.subject-list{font-size:10px;color:#475569;line-height:1.45;margin:7px 0 12px}.edit-note{font-size:9.5px;color:#64748B;margin-bottom:10px;line-height:1.45}
@media(max-width:850px){.bulk-grid{grid-template-columns:1fr}}@media(max-width:640px){.rules-grid{grid-template-columns:1fr}.compulsory-grid,.criteria-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@include('classes.partials.promotion-tabs')

@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e"><strong>Promotion rule could not be saved.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@php
    $sectionLabels = [
        'creche'=>'Creche','nursery'=>'Nursery','primary'=>'Primary',
        'junior_secondary'=>'Junior Secondary','senior_secondary'=>'Senior Secondary','general'=>'General',
    ];
    $groupedLevels = $levels->groupBy(fn($level)=>$level->section ?: 'general');
    $selectedLevelIds = collect(old('class_level_ids', old('class_level_id') ? [old('class_level_id')] : []))->map(fn($id)=>(string)$id)->all();
    $oldCompulsory = collect(old('compulsory_subject_ids', []))->map(fn($id)=>(string)$id)->all();
@endphp

<div class="rules-intro">
    <h2>Promotion rules</h2>
    <p>Define promotion criteria once and apply them to one or multiple class levels. Existing rules on explicitly selected levels are updated; duplicate/conflicting rule records are blocked instead of silently overwritten.</p>
</div>

<div class="bulk-rule">
    <div class="bulk-head"><h3>Apply rule to multiple class levels</h3><p>Select the levels that share the same promotion standard, then define the criteria once.</p></div>
    <div class="bulk-body">
        <form method="POST" action="{{ route('classes.promotion.save') }}">
            @csrf
            <div class="bulk-grid">
                <div>
                    <div class="form-label">Class levels *</div>
                    <div class="level-picker">
                        <div class="picker-tools"><span id="ruleSelectionCount">0 selected</span><div class="picker-actions"><button type="button" onclick="toggleRuleLevels(true)">Select all</button><button type="button" onclick="toggleRuleLevels(false)">Clear</button></div></div>
                        <div class="picker-body">
                            @foreach($groupedLevels as $section=>$sectionLevels)
                                <div class="picker-section"><div class="picker-section-title">{{ $sectionLabels[$section] ?? ucwords(str_replace('_',' ',$section)) }}</div>
                                    @foreach($sectionLevels as $level)
                                        <label class="level-check"><input class="rule-level-check" type="checkbox" name="class_level_ids[]" value="{{ $level->id }}" {{ in_array((string)$level->id,$selectedLevelIds,true) ? 'checked' : '' }} onchange="updateRuleSelectionCount()"><span>{{ $level->name }}</span></label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="selection-note">Only the selected class levels are changed. Other configured rules remain untouched.</div>
                </div>
                <div>
                    <div class="criteria-grid">
                        <div class="form-group"><label class="form-label">Minimum average (%)</label><input type="number" name="min_required_average" class="form-control" value="{{ old('min_required_average',40) }}" min="0" max="100" step="0.01"></div>
                        <div class="form-group"><label class="form-label">Maximum failed subjects</label><input type="number" name="max_failed_subjects_allowed" class="form-control" value="{{ old('max_failed_subjects_allowed',3) }}" min="0"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Compulsory subjects that must be passed</label>
                        <div class="compulsory-grid">
                            @foreach($subjects as $subject)
                                <label class="check-label"><input type="checkbox" name="compulsory_subject_ids[]" value="{{ $subject->id }}" {{ in_array((string)$subject->id,$oldCompulsory,true) ? 'checked' : '' }}><span>{{ $subject->name }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="save-rule">Apply promotion rule</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="rules-grid">
    @foreach($levels as $level)
        @php
            $rule = $level->promotionRule;
            $compulsoryNames = $rule ? $subjects->whereIn('id',$rule->compulsory_subject_ids ?? [])->pluck('name') : collect();
        @endphp
        <div class="rule-card">
            <div class="rule-header"><span class="rule-title">{{ $level->name }}</span><span class="badge {{ $rule ? 'badge-success' : 'badge-warning' }}">{{ $rule ? 'Configured' : 'Not configured' }}</span></div>
            <div class="rule-body">
                @if($rule)
                    <div class="current-rule">
                        <div class="rule-metric"><strong>{{ $rule->min_required_average ?? '—' }}{{ $rule->min_required_average !== null ? '%' : '' }}</strong><span>Minimum average</span></div>
                        <div class="rule-metric"><strong>{{ $rule->max_failed_subjects_allowed ?? '—' }}</strong><span>Max failed subjects</span></div>
                    </div>
                    <div class="subject-list"><strong>Compulsory passes:</strong> {{ $compulsoryNames->isNotEmpty() ? $compulsoryNames->implode(', ') : 'None specified' }}</div>
                    <div class="edit-note">To change this rule alone, use the form below. To apply one rule across several levels, use the multi-class form above.</div>
                @else
                    <div class="edit-note">No promotion criteria have been configured for this class level.</div>
                @endif

                <form method="POST" action="{{ route('classes.promotion.save') }}">
                    @csrf
                    <input type="hidden" name="class_level_id" value="{{ $level->id }}">
                    <div class="criteria-grid">
                        <div class="form-group"><label class="form-label">Minimum average (%)</label><input type="number" name="min_required_average" class="form-control" value="{{ optional($rule)->min_required_average ?? 40 }}" min="0" max="100" step="0.01"></div>
                        <div class="form-group"><label class="form-label">Maximum failed subjects</label><input type="number" name="max_failed_subjects_allowed" class="form-control" value="{{ optional($rule)->max_failed_subjects_allowed ?? 3 }}" min="0"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Compulsory subjects</label>
                        <div class="compulsory-grid">
                            @foreach($subjects as $subject)
                                <label class="check-label"><input type="checkbox" name="compulsory_subject_ids[]" value="{{ $subject->id }}" {{ in_array($subject->id, optional($rule)->compulsory_subject_ids ?? []) ? 'checked' : '' }}><span>{{ $subject->name }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="save-rule">Save {{ $level->name }} rule</button>
                </form>
            </div>
        </div>
    @endforeach
</div>

<script>
function updateRuleSelectionCount(){const count=document.querySelectorAll('.rule-level-check:checked').length;document.getElementById('ruleSelectionCount').textContent=count+' selected';}
function toggleRuleLevels(checked){document.querySelectorAll('.rule-level-check').forEach(cb=>cb.checked=checked);updateRuleSelectionCount();}
updateRuleSelectionCount();
</script>
@endsection
