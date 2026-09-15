@extends('layouts.app')
@section('title', 'Assessment Templates')
@section('page-title', 'Assessment Templates')

@push('styles')
<style>
.at-page{display:flex;flex-direction:column;gap:16px}.at-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap}.at-head h2{margin:0;font-size:18px!important;color:var(--midnight)}.at-head p{margin:4px 0 0;color:var(--slate-light);font-size:12px;max-width:820px}.at-tabs{display:flex;gap:4px;padding:4px;background:#fff;border:1px solid var(--border);border-radius:10px;overflow-x:auto}.at-tab{border:0;background:transparent;color:var(--slate);font:inherit;font-size:12px;font-weight:800;padding:9px 14px;border-radius:7px;cursor:pointer;white-space:nowrap}.at-tab.active{background:var(--brand-gold);color:var(--brand-navy)}.at-panel{display:none}.at-panel.active{display:block}.at-btn{min-height:40px;display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid var(--border);border-radius:8px;padding:0 14px;background:#fff;color:var(--midnight);font:inherit;font-size:12px;font-weight:800;text-decoration:none;cursor:pointer}.at-btn-primary{background:var(--brand-gold);border-color:var(--brand-gold);color:var(--brand-navy)}.at-btn-danger{background:#FEF2F2;border-color:#FECACA;color:#991B1B}.at-btn:disabled{opacity:.45;cursor:not-allowed}.at-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden}.at-section-head{background:var(--brand-navy);color:#fff;padding:12px 16px;border-bottom:2px solid var(--brand-gold);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}.at-section-head strong{font-size:13px}.at-section-head small{display:block;color:#CBD5E1;font-size:11px;margin-top:2px}.at-body{padding:16px}.at-field label{display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:var(--slate-light);margin-bottom:5px}.at-field input,.at-field select,.at-field textarea{width:100%;box-sizing:border-box;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:12px}.at-field input,.at-field select{height:40px;padding:0 10px}.at-field textarea{min-height:72px;padding:9px 10px}.at-filter{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end}.at-table-wrap{overflow-x:auto}.at-table{width:100%;border-collapse:collapse;min-width:820px}.at-table th{height:38px;background:#F8FAFC;color:var(--slate-light);font-size:10px;text-transform:uppercase;letter-spacing:.04em;padding:8px 12px;text-align:left;border-bottom:1px solid var(--border)}.at-table td{height:44px;padding:9px 12px;border-bottom:1px solid var(--border);font-size:12px;vertical-align:middle}.at-table tr.selected{background:var(--brand-gold-light)}.at-name{font-weight:800;color:var(--midnight)}.at-sub{font-size:11px;color:var(--slate-light);margin-top:2px;line-height:1.45}.at-badge{display:inline-flex;align-items:center;border-radius:999px;padding:3px 8px;font-size:10px;font-weight:900}.at-active{background:#ECFDF5;color:#047857}.at-draft{background:#F1F5F9;color:#64748B}.at-locked{background:#FEF2F2;color:#991B1B}.at-editable{background:#EFF6FF;color:#1D4ED8}.at-weight{display:inline-flex;border-radius:999px;background:var(--brand-gold-light);color:var(--brand-gold-dark);padding:5px 9px;font-size:11px;font-weight:900}.at-alert{border-radius:9px;padding:10px 13px;font-size:12px;line-height:1.5}.at-alert-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46}.at-alert-error{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B}.at-alert-warn{background:#FFFBEB;border:1px solid #FDE68A;color:#92400E}.at-alert-info{background:#EFF6FF;border:1px solid #BFDBFE;color:#1E3A8A}.at-component{border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:10px}.at-component.exam{border-left:3px solid var(--brand-gold)}.at-component-top{display:flex;justify-content:space-between;gap:10px;align-items:center}.at-component-grid{display:grid;grid-template-columns:1.6fr .7fr 1fr auto;gap:8px;align-items:end;margin-top:10px}.at-builder{border:1px dashed #CBD5E1;border-radius:10px;padding:14px;margin-top:14px;background:#FCFDFE}.at-builder-grid{display:grid;grid-template-columns:1.6fr .7fr 1fr;gap:8px}.at-total{display:flex;justify-content:space-between;align-items:center;padding-top:12px;margin-top:12px;border-top:1px solid var(--border);font-weight:900}.at-layout{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(300px,.8fr);gap:16px}.at-checks{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:7px;margin-top:8px}.at-check{display:flex;align-items:center;gap:7px;border:1px solid var(--border);border-radius:8px;padding:8px 10px;background:#F8FAFC;font-size:11px}.at-check input{width:auto}.at-danger-zone{border:1px solid #FECACA;border-radius:10px;padding:12px;background:#FFF7F7}.at-flow{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.at-flow-step{border:1px solid #D7E1EF;border-radius:9px;padding:10px;background:#fff}.at-flow-step strong{display:block;color:var(--brand-navy);font-size:11px;margin-bottom:3px}.at-flow-step span{font-size:10px;line-height:1.4;color:var(--slate)}.at-modal-backdrop{position:fixed;inset:0;background:rgba(7,30,69,.45);display:none;align-items:center;justify-content:center;z-index:1000;padding:16px}.at-modal-backdrop.open{display:flex}.at-modal{width:min(620px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.24)}.at-modal-head{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border)}.at-modal-body{padding:16px 18px}.at-empty{text-align:center;padding:28px 16px;color:var(--slate-light);font-size:12px}@media(max-width:900px){.at-layout{grid-template-columns:1fr}.at-filter,.at-component-grid,.at-builder-grid{grid-template-columns:1fr 1fr}.at-flow{grid-template-columns:1fr 1fr}}@media(max-width:640px){.at-filter,.at-component-grid,.at-builder-grid,.at-flow{grid-template-columns:1fr}.at-head .at-btn{width:100%}}
</style>
@endpush

@section('content')
@php
    $visibleTemplates = $templates->filter(function($template){
        $q = strtolower(trim((string) request('q')));
        $status = request('status');
        $section = request('section');
        if($q && !str_contains(strtolower($template->name.' '.$template->description), $q)) return false;
        if($status && $template->status !== $status) return false;
        if($section && !$template->assignments->contains(fn($a)=>$a->classLevel?->section === $section)) return false;
        return true;
    });
    $hasRecordedScores = $selectedTemplate?->hasRecordedScores() ?? false;
    $structureLocked = $hasRecordedScores;
    $selectedTotal = $selectedTemplate ? round((float)$selectedTemplate->total_weight, 2) : 0;
    $hasAssignments = $selectedTemplate?->assignments->where('is_active', true)->isNotEmpty() ?? false;
@endphp

<div class="at-page">
    <div class="at-head">
        <div>
            <h2>Assessment Templates</h2>
            <p>Configure only the score-sheet structure and contribution weights. CBT question marks, objective scoring and theory marking are configured and processed inside the CBT module.</p>
        </div>
        <button class="at-btn at-btn-primary" type="button" onclick="openModal('newTemplateModal')">+ New Template</button>
    </div>

    @if(session('success'))<div class="at-alert at-alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="at-alert at-alert-error"><strong>Please correct the following:</strong><ul style="margin:6px 0 0;padding-left:18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="at-tabs" role="tablist">
        <button class="at-tab" data-tab="templates" type="button">Templates</button>
        <button class="at-tab" data-tab="components" type="button">Components</button>
        <button class="at-tab" data-tab="assignment" type="button">Assignment & Usage</button>
        <button class="at-tab" data-tab="settings" type="button">Settings</button>
        <button class="at-tab" data-tab="guide" type="button">Scoring Guide</button>
    </div>

    <section class="at-panel" id="tab-templates">
        <div class="at-card">
            <div class="at-body">
                <form class="at-filter" method="GET" action="{{ route('scores.assessment-types') }}">
                    <div class="at-field"><label>Search</label><input name="q" value="{{ request('q') }}" placeholder="Template name"></div>
                    <div class="at-field"><label>Class Section</label><select name="section"><option value="">All Classes</option>@foreach($templateClassLevels->pluck('section')->filter()->unique()->sort() as $section)<option value="{{ $section }}" @selected(request('section')===$section)>{{ str($section)->title() }}</option>@endforeach</select></div>
                    <div class="at-field"><label>Status</label><select name="status"><option value="">All Status</option><option value="active" @selected(request('status')==='active')>Active</option><option value="draft" @selected(request('status')==='draft')>Draft</option></select></div>
                    <button class="at-btn at-btn-primary" type="submit">Apply Filters</button>
                </form>
            </div>
        </div>
        <div class="at-card" style="margin-top:16px">
            <div class="at-section-head"><div><strong>School Templates</strong><small>{{ $visibleTemplates->count() }} shown</small></div></div>
            <div class="at-table-wrap"><table class="at-table"><thead><tr><th>Template</th><th>Components</th><th>Total</th><th>Assigned To</th><th>Status</th><th>Editing</th><th>Action</th></tr></thead><tbody>
                @forelse($visibleTemplates as $template)
                    @php $assigned=$template->assignments->where('is_active',true)->pluck('classLevel.name')->filter()->unique()->implode(', '); $templateHasScores=$template->hasRecordedScores(); @endphp
                    <tr class="{{ $selectedTemplate?->id === $template->id ? 'selected' : '' }}">
                        <td><div class="at-name">{{ $template->name }}</div><div class="at-sub">{{ $template->description ?: 'School-defined assessment structure' }}</div></td>
                        <td>{{ $template->components->count() }}</td><td><span class="at-weight">{{ rtrim(rtrim(number_format($template->total_weight,2),'0'),'.') }}%</span></td><td>{{ $assigned ?: 'Not assigned' }}</td>
                        <td><span class="at-badge {{ $template->status==='active'?'at-active':'at-draft' }}">{{ ucfirst($template->status) }}</span></td>
                        <td><span class="at-badge {{ $templateHasScores?'at-locked':'at-editable' }}">{{ $templateHasScores?'Scores recorded':'Editable' }}</span></td>
                        <td><a class="at-btn" href="{{ route('scores.assessment-types',['selected'=>$template->id]) }}#components">Manage</a></td>
                    </tr>
                @empty<tr><td colspan="7"><div class="at-empty">No assessment templates yet.</div></td></tr>@endforelse
            </tbody></table></div>
        </div>
    </section>

    <section class="at-panel" id="tab-components">
        @if($selectedTemplate)
        <div class="at-card">
            <div class="at-section-head"><div><strong>{{ $selectedTemplate->name }} — Components</strong><small>These become the columns on the score sheet.</small></div><span class="at-weight">{{ rtrim(rtrim(number_format($selectedTotal,2),'0'),'.') }} / 100%</span></div>
            <div class="at-body">
                @if($structureLocked)<div class="at-alert at-alert-warn" style="margin-bottom:12px"><strong>Structure locked.</strong> Scores have been recorded on this template. Duplicate it to create a different structure.</div>
                @elseif($hasAssignments)<div class="at-alert at-alert-info" style="margin-bottom:12px">This template is assigned but still editable because no student score has been recorded yet.</div>
                @elseif($selectedTotal < 100)<div class="at-alert at-alert-warn" style="margin-bottom:12px">Add components until the template totals exactly 100%.</div>@endif

                @forelse($selectedTemplate->components as $component)
                    @php $isExamComponent=$component->isExam(); @endphp
                    <div class="at-component {{ $isExamComponent?'exam':'' }}">
                        <div class="at-component-top"><div><div class="at-name">{{ $component->name }}</div><div class="at-sub">{{ str($component->component_type)->replace('_',' ')->title() }} · score-sheet contribution only</div></div><span class="at-weight">{{ rtrim(rtrim(number_format($component->weight_percentage,2),'0'),'.') }}%</span></div>
                        @unless($structureLocked)
                        <form method="POST" action="{{ route('assessment-templates.components.update',[$selectedTemplate,$component]) }}" class="at-component-grid">@csrf @method('PUT')
                            <input type="hidden" name="entry_mode" value="manual">
                            <div class="at-field"><label>Component Name</label><input name="name" value="{{ $component->name }}" required></div>
                            <div class="at-field"><label>Weight %</label><input type="number" step="0.01" min="0.01" max="100" name="weight_percentage" value="{{ $component->weight_percentage }}" required></div>
                            <div class="at-field"><label>Type</label><select name="component_type">@foreach(['coursework'=>'Coursework','test'=>'Test','practical'=>'Practical','exam'=>'Exam','final_exam'=>'Final Exam'] as $value=>$label)<option value="{{ $value }}" @selected($component->component_type===$value)>{{ $label }}</option>@endforeach</select></div>
                            <button class="at-btn" type="submit">Save</button>
                        </form>
                        <form method="POST" action="{{ route('assessment-templates.components.destroy',[$selectedTemplate,$component]) }}" style="margin-top:8px" onsubmit="return confirm('Remove this component from the template?')">@csrf @method('DELETE')<button class="at-btn at-btn-danger" type="submit">Remove Component</button></form>
                        @endunless
                    </div>
                @empty<div class="at-empty">No components yet.</div>@endforelse

                @unless($structureLocked)
                <div class="at-builder"><div class="at-name">Add Assessment Component</div><div class="at-sub">Example: Test 1 = 5%, Test 2 = 5%, CA 1 = 10%, CA 2 = 10%, Exam = 70%.</div>
                    <form method="POST" action="{{ route('assessment-templates.components.store',$selectedTemplate) }}" style="margin-top:10px">@csrf
                        <input type="hidden" name="entry_mode" value="manual">
                        <div class="at-builder-grid"><div class="at-field"><label>Component Name</label><input name="name" required placeholder="e.g. CA 1"></div><div class="at-field"><label>Weight %</label><input type="number" step="0.01" min="0.01" max="100" name="weight_percentage" required placeholder="10"></div><div class="at-field"><label>Type</label><select name="component_type"><option value="coursework">Coursework</option><option value="test">Test</option><option value="practical">Practical</option><option value="exam">Exam</option><option value="final_exam">Final Exam</option></select></div></div>
                        <div style="display:flex;justify-content:flex-end;margin-top:10px"><button class="at-btn at-btn-primary" type="submit">+ Add Component</button></div>
                    </form>
                </div>
                @endunless
                <div class="at-total"><span>Template Total</span><span class="at-weight">{{ rtrim(rtrim(number_format($selectedTotal,2),'0'),'.') }}%</span></div>
            </div>
        </div>
        @else<div class="at-card"><div class="at-empty">Create or select a template first.</div></div>@endif
    </section>

    <section class="at-panel" id="tab-assignment">
        @if($selectedTemplate)
        <div class="at-layout">
            <div class="at-card"><div class="at-section-head"><div><strong>Assign Template</strong><small>Apply the 100% structure to class levels for an academic session.</small></div></div><div class="at-body">
                @if($selectedTemplate->status==='active' && abs($selectedTotal-100)<0.001)<button class="at-btn at-btn-primary" type="button" onclick="openModal('assignTemplateModal')">Assign Template</button>@else<div class="at-alert at-alert-warn">Complete the template to exactly 100% and set it to Active first.</div>@endif
            </div></div>
            <div class="at-card"><div class="at-section-head"><div><strong>Current Usage</strong><small>Existing class-level assignments.</small></div></div><div class="at-body">@forelse($selectedTemplate->assignments->where('is_active',true) as $assignment)<div style="padding:8px 0;border-bottom:1px solid var(--border)"><div class="at-name">{{ $assignment->classLevel?->name ?? 'Class level' }}</div><div class="at-sub">{{ $assignment->session?->name ?? 'Session' }}</div></div>@empty<div class="at-empty">Not assigned yet.</div>@endforelse</div></div>
        </div>
        @endif
    </section>

    <section class="at-panel" id="tab-settings">
        @if($selectedTemplate)
        <div class="at-layout">
            <div class="at-card"><div class="at-section-head"><div><strong>Template Settings</strong><small>Name, description and status.</small></div></div><div class="at-body"><form method="POST" action="{{ route('assessment-templates.update',$selectedTemplate) }}">@csrf @method('PUT')<div class="at-field"><label>Name</label><input name="name" value="{{ $selectedTemplate->name }}" required></div><div class="at-field" style="margin-top:10px"><label>Description</label><textarea name="description">{{ $selectedTemplate->description }}</textarea></div><div class="at-field" style="margin-top:10px"><label>Status</label><select name="status"><option value="draft" @selected($selectedTemplate->status==='draft')>Draft</option><option value="active" @selected($selectedTemplate->status==='active')>Active</option></select></div><button class="at-btn at-btn-primary" type="submit" style="width:100%;margin-top:12px">Save Template</button></form><form method="POST" action="{{ route('assessment-templates.duplicate',$selectedTemplate) }}" style="margin-top:10px">@csrf<button class="at-btn" type="submit" style="width:100%">Duplicate Template</button></form></div></div>
            <div class="at-card"><div class="at-section-head"><div><strong>Delete Template</strong><small>Disabled once student scores exist.</small></div></div><div class="at-body"><div class="at-danger-zone">@if($hasRecordedScores)<div class="at-name" style="color:#991B1B">Delete disabled</div><div class="at-sub" style="margin:6px 0 10px;color:#7F1D1D">Student scores have already been recorded using this template.</div><button class="at-btn at-btn-danger" type="button" disabled>Delete Template</button>@else<div class="at-name" style="color:#991B1B">No recorded scores</div><div class="at-sub" style="margin:6px 0 10px;color:#7F1D1D">This template can be deleted safely.</div><form method="POST" action="{{ route('assessment-templates.destroy',$selectedTemplate) }}" onsubmit="return confirm('Delete this assessment template?')">@csrf @method('DELETE')<button class="at-btn at-btn-danger" type="submit">Delete Template</button></form>@endif</div></div></div>
        </div>
        @endif
    </section>

    <section class="at-panel" id="tab-guide">
        <div class="at-card"><div class="at-section-head"><div><strong>How CBT feeds the Exam column</strong><small>Template weights and CBT raw marks are deliberately separated.</small></div></div><div class="at-body">
            <div class="at-alert at-alert-info"><strong>Assessment Template:</strong> defines only the score-sheet columns and their weights. <strong>CBT Module:</strong> defines the actual question marks, automatically scores objective questions, accepts teacher-entered theory marks, aggregates the raw examination result and converts it to the Exam weight before synchronization.</div>
            <div class="at-flow" style="margin-top:12px"><div class="at-flow-step"><strong>1. Template</strong><span>Example: Test 1 5%, Test 2 5%, CA 1 10%, CA 2 10%, Exam 70%.</span></div><div class="at-flow-step"><strong>2. Objective</strong><span>Student submits CBT. Objective question marks are scored automatically.</span></div><div class="at-flow-step"><strong>3. Theory</strong><span>Teacher marks theory and enters each theory score on the CBT results/marking interface.</span></div><div class="at-flow-step"><strong>4. Sync</strong><span>(Objective + Theory) ÷ total obtainable × Exam weight is written automatically to the Exam column and locked as CBT-derived.</span></div></div>
        </div></div>
    </section>
</div>

<div class="at-modal-backdrop" id="newTemplateModal" onclick="if(event.target===this)closeModal('newTemplateModal')"><div class="at-modal"><div class="at-modal-head"><strong>New Assessment Template</strong><button class="at-btn" type="button" onclick="closeModal('newTemplateModal')">×</button></div><form method="POST" action="{{ route('assessment-templates.store') }}">@csrf<div class="at-modal-body"><div class="at-field"><label>Template Name</label><input name="name" required placeholder="e.g. CA 30 / Exam 70"></div><div class="at-field" style="margin-top:10px"><label>Description</label><textarea name="description"></textarea></div><input type="hidden" name="status" value="draft"><div class="at-alert at-alert-info" style="margin-top:12px">Create the template, then add components until the total reaches 100%.</div><div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px"><button class="at-btn" type="button" onclick="closeModal('newTemplateModal')">Cancel</button><button class="at-btn at-btn-primary" type="submit">Create Template</button></div></div></form></div></div>

@if($selectedTemplate)
<div class="at-modal-backdrop" id="assignTemplateModal" onclick="if(event.target===this)closeModal('assignTemplateModal')"><div class="at-modal"><div class="at-modal-head"><strong>Assign {{ $selectedTemplate->name }}</strong><button class="at-btn" type="button" onclick="closeModal('assignTemplateModal')">×</button></div><form method="POST" action="{{ route('assessment-templates.assign',$selectedTemplate) }}">@csrf<div class="at-modal-body"><div class="at-field"><label>Academic Session</label><select name="session_id" required><option value="">Select session</option>@foreach($templateSessions as $session)<option value="{{ $session->id }}">{{ $session->name }}{{ $session->is_current ? ' (Current)' : '' }}</option>@endforeach</select></div><div class="at-field" style="margin-top:12px"><label>Class Levels</label><div class="at-checks">@foreach($templateClassLevels as $level)<label class="at-check"><input type="checkbox" name="class_level_ids[]" value="{{ $level->id }}"> <span>{{ $level->name }}</span></label>@endforeach</div></div><div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px"><button class="at-btn" type="button" onclick="closeModal('assignTemplateModal')">Cancel</button><button class="at-btn at-btn-primary" type="submit">Assign Template</button></div></div></form></div></div>
@endif
@endsection

@push('scripts')
<script>
function openModal(id){document.getElementById(id)?.classList.add('open')}
function closeModal(id){document.getElementById(id)?.classList.remove('open')}
function activateAssessmentTab(name){
    const valid=['templates','components','assignment','settings','guide'];
    if(!valid.includes(name)) name='templates';
    document.querySelectorAll('.at-tab').forEach(btn=>btn.classList.toggle('active',btn.dataset.tab===name));
    document.querySelectorAll('.at-panel').forEach(panel=>panel.classList.toggle('active',panel.id==='tab-'+name));
    sessionStorage.setItem('assessmentTemplateTab',name);
}
document.querySelectorAll('.at-tab').forEach(btn=>btn.addEventListener('click',()=>{activateAssessmentTab(btn.dataset.tab);history.replaceState(null,'','#'+btn.dataset.tab)}));
const initial=(location.hash||'').replace('#','') || sessionStorage.getItem('assessmentTemplateTab') || 'templates';
activateAssessmentTab(initial);
</script>
@endpush
