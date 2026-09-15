@extends('layouts.app')
@section('title', 'Assessment Template')
@section('page-title', 'Assessment Template')

@push('styles')
<style>
.at-page{display:flex;flex-direction:column;gap:16px}.at-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}.at-head h2{margin:0;color:var(--midnight);font-size:20px!important}.at-head p{margin:4px 0 0;color:var(--slate-light);font-size:12px}.at-actions{display:flex;gap:8px;flex-wrap:wrap}.at-btn{min-height:40px;display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid var(--border);border-radius:8px;padding:0 14px;font-weight:700;font-size:12px;text-decoration:none;cursor:pointer;background:white;color:var(--midnight)}.at-btn-primary{background:var(--brand-gold);border-color:var(--brand-gold);color:var(--brand-navy)}.at-btn-danger{color:#991B1B;background:#FEF2F2;border-color:#FECACA}.at-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}.at-filter{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;padding:14px 16px;align-items:end}.at-field label{display:block;margin-bottom:5px;color:var(--slate-light);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.at-field input,.at-field select,.at-field textarea{width:100%;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;padding:0 11px;font:inherit;box-sizing:border-box}.at-field input,.at-field select{height:40px}.at-field textarea{min-height:76px;padding-top:9px}.at-table-head{padding:10px 16px;color:var(--slate-light);font-size:12px}.at-table-wrap{overflow-x:auto}.at-table{width:calc(100% - 28px)!important;margin:0 14px 14px!important;min-width:800px}.at-table thead th{background:var(--brand-navy);color:white!important;font-size:10px!important;text-transform:none!important;letter-spacing:0!important}.at-table tbody tr.selected{background:var(--brand-gold-light)}.at-name{font-weight:800;color:var(--midnight)}.at-sub{font-size:11px;color:var(--slate-light);margin-top:2px}.at-components{font-size:12px;color:var(--slate)}.at-badge{display:inline-flex;align-items:center;border-radius:999px;padding:3px 8px;font-size:10px;font-weight:800}.at-active{background:#ECFDF5;color:#047857}.at-draft{background:#F1F5F9;color:#64748B}.at-row-actions{display:flex;gap:6px;flex-wrap:wrap}.at-bottom{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(280px,.9fr);gap:16px}.at-section-head{background:var(--brand-navy);color:white;padding:12px 16px;border-bottom:2px solid var(--brand-gold)}.at-section-head strong{font-size:13px}.at-section-head span{display:block;font-size:11px;color:#CBD5E1;margin-top:2px}.at-body{padding:14px 16px}.at-preview-title{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}.at-preview-title h3{margin:0}.at-component-row{display:grid;grid-template-columns:1fr 90px;gap:12px;align-items:center;padding:11px 0;border-top:1px solid var(--border)}.at-component-main{display:flex;align-items:center;gap:10px}.at-component-icon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--brand-navy-soft);color:var(--brand-navy);font-weight:900;font-size:11px;flex:none}.at-weight{text-align:center;border-radius:999px;background:var(--brand-gold-light);color:var(--brand-gold-dark);font-weight:900;padding:5px 8px}.at-total{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border);padding-top:10px;margin-top:3px;font-weight:900}.at-usage{display:flex;flex-direction:column;gap:10px}.at-usage-item{background:#F8FAFC;border:1px solid var(--border);border-radius:9px;padding:10px 12px}.at-usage-item strong{display:block;font-size:11px;color:var(--midnight)}.at-usage-item span{font-size:11px;color:var(--slate-light)}.at-empty{text-align:center;padding:34px 18px;color:var(--slate-light)}.at-modal-backdrop{position:fixed;inset:0;background:rgba(7,30,69,.45);display:none;align-items:center;justify-content:center;z-index:1000;padding:16px}.at-modal-backdrop.open{display:flex}.at-modal{width:min(760px,100%);max-height:92vh;overflow:auto;background:white;border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.25)}.at-modal-head{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border)}.at-modal-head h3{margin:0}.at-modal-body{padding:16px 18px}.at-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}.at-component-editor{border:1px solid var(--border);border-radius:10px;padding:12px;margin-top:12px}.at-component-editor-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}.at-component-edit-row{display:grid;grid-template-columns:1.5fr .7fr 1fr 1fr .7fr .7fr auto;gap:8px;align-items:end;margin-bottom:8px}.at-component-edit-row .at-field{min-width:0}.at-checks{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:7px;margin-top:8px}.at-check{display:flex;align-items:center;gap:7px;background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:8px 10px;font-size:11px}.at-check input{width:auto;height:auto}.at-errors{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;border-radius:9px;padding:10px 13px}.at-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;border-radius:9px;padding:10px 13px}@media(max-width:900px){.at-bottom{grid-template-columns:1fr}.at-filter{grid-template-columns:1fr 1fr}.at-component-edit-row{grid-template-columns:1fr 1fr}.at-component-edit-row .remove-component{grid-column:span 2}}@media(max-width:640px){.at-filter,.at-grid2{grid-template-columns:1fr}.at-head{align-items:stretch}.at-actions>*{flex:1}.at-component-edit-row{grid-template-columns:1fr}.at-component-edit-row .remove-component{grid-column:auto}.at-table{margin-left:10px!important;margin-right:10px!important;width:calc(100% - 20px)!important}}
</style>
@endpush

@section('content')
<div class="at-page">
    <div class="at-head">
        <div>
            <h2>Assessment Template</h2>
            <p>Configure reusable scoring structures once, then assign them to class levels and academic sessions.</p>
        </div>
        <div class="at-actions">
            @if($selectedTemplate)
                <button class="at-btn" type="button" onclick="openModal('assignTemplateModal')">Assign Template</button>
            @endif
            <button class="at-btn at-btn-primary" type="button" onclick="openModal('newTemplateModal')">+ New Template</button>
        </div>
    </div>

    @if(session('success'))<div class="at-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="at-errors" role="alert"><strong>Please correct the following:</strong><ul style="margin:6px 0 0;padding-left:18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="at-card">
        <form class="at-filter" method="GET" action="{{ route('scores.assessment-types') }}">
            <div class="at-field"><label>Search</label><input name="q" value="{{ request('q') }}" placeholder="Search template name..."></div>
            <div class="at-field"><label>Class Section</label><select name="section"><option value="">All Classes</option>@foreach($templateClassLevels->pluck('section')->filter()->unique()->sort() as $section)<option value="{{ $section }}" @selected(request('section')===$section)>{{ str($section)->title() }}</option>@endforeach</select></div>
            <div class="at-field"><label>Status</label><select name="status"><option value="">All Status</option><option value="active" @selected(request('status')==='active')>Active</option><option value="draft" @selected(request('status')==='draft')>Draft</option></select></div>
            <button class="at-btn at-btn-primary" type="submit">Apply Filters</button>
        </form>
    </div>

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
    @endphp

    <div class="at-card">
        <div class="at-table-head">Showing <strong>{{ $visibleTemplates->count() }}</strong> of {{ $templates->count() }} templates</div>
        <div class="at-table-wrap">
            <table class="at-table">
                <thead><tr><th>Template Name</th><th>Components</th><th>Total Weight</th><th>Assigned To</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($visibleTemplates as $template)
                    @php
                        $componentText = $template->components->map(fn($c)=>$c->name.' '.rtrim(rtrim(number_format($c->weight_percentage,2),'0'),'.').'%')->implode(' + ');
                        $assigned = $template->assignments->where('is_active',true)->pluck('classLevel.name')->filter()->unique()->implode(', ');
                    @endphp
                    <tr class="{{ $selectedTemplate?->id === $template->id ? 'selected' : '' }}">
                        <td><div class="at-name">{{ $template->name }}</div><div class="at-sub">{{ $template->description ?: 'Reusable assessment structure' }}</div></td>
                        <td class="at-components">{{ $componentText ?: 'No components yet' }}</td>
                        <td><strong>{{ rtrim(rtrim(number_format($template->total_weight,2),'0'),'.') }}%</strong></td>
                        <td>{{ $assigned ?: 'Not assigned' }}</td>
                        <td><span class="at-badge {{ $template->status==='active'?'at-active':'at-draft' }}">{{ ucfirst($template->status) }}</span></td>
                        <td><div class="at-row-actions"><a class="at-btn" href="{{ route('scores.assessment-types',['selected'=>$template->id]) }}">View</a><button class="at-btn" type="button" onclick="selectAndAssign({{ $template->id }})">Assign</button></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="at-empty"><strong>No assessment templates yet.</strong><br>Create one reusable template instead of configuring assessment types term by term.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="at-bottom">
        <div class="at-card">
            <div class="at-section-head"><strong>Template Preview</strong><span>Structure and weight distribution for the selected template.</span></div>
            <div class="at-body">
                @if($selectedTemplate)
                    <div class="at-preview-title"><div><h3>{{ $selectedTemplate->name }} <span class="at-badge {{ $selectedTemplate->status==='active'?'at-active':'at-draft' }}">{{ ucfirst($selectedTemplate->status) }}</span></h3><div class="at-sub">{{ $selectedTemplate->description }}</div></div></div>
                    @foreach($selectedTemplate->components as $component)
                        <div class="at-component-row">
                            <div class="at-component-main"><div class="at-component-icon">{{ strtoupper(substr($component->name,0,2)) }}</div><div><div class="at-name">{{ $component->name }}</div><div class="at-sub">{{ str($component->component_type)->replace('_',' ')->title() }} · {{ str($component->entry_mode)->replace('_',' ')->title() }}</div></div></div>
                            <div class="at-weight">{{ rtrim(rtrim(number_format($component->weight_percentage,2),'0'),'.') }}%</div>
                        </div>
                    @endforeach
                    <div class="at-total"><span>Total</span><span class="at-weight">{{ rtrim(rtrim(number_format($selectedTemplate->total_weight,2),'0'),'.') }}%</span></div>
                @else
                    <div class="at-empty">Create a template to preview its assessment components.</div>
                @endif
            </div>
        </div>
        <div class="at-usage">
            <div class="at-card">
                <div class="at-section-head"><strong>Template Usage</strong><span>Classes and sessions using this template.</span></div>
                <div class="at-body">
                    @if($selectedTemplate && $selectedTemplate->assignments->isNotEmpty())
                        @foreach($selectedTemplate->assignments->sortByDesc('id')->take(8) as $assignment)
                            <div class="at-usage-item"><strong>{{ $assignment->classLevel?->name ?? 'Class level' }}</strong><span>{{ $assignment->session?->name ?? 'Session' }} · {{ $assignment->is_active ? 'Active' : 'Inactive' }}</span></div>
                        @endforeach
                    @else<div class="at-empty" style="padding:16px 8px">Not assigned yet.</div>@endif
                </div>
            </div>
            <div class="at-card"><div class="at-body"><div class="at-name">About Assessment Templates</div><div class="at-sub" style="line-height:1.55;margin-top:5px">Templates define reusable scoring structures. Once assigned to class levels, EduCore generates the compatible runtime assessment rows automatically for every term in the selected session.</div></div></div>
        </div>
    </div>
</div>

<div class="at-modal-backdrop" id="newTemplateModal" onclick="if(event.target===this)closeModal('newTemplateModal')">
    <div class="at-modal">
        <div class="at-modal-head"><h3>New Assessment Template</h3><button class="at-btn" type="button" onclick="closeModal('newTemplateModal')">×</button></div>
        <form method="POST" action="{{ route('assessment-templates.store') }}">@csrf
            <div class="at-modal-body">
                <div class="at-grid2"><div class="at-field"><label>Template Name</label><input name="name" required placeholder="e.g. Secondary Standard"></div><div class="at-field"><label>Status</label><select name="status"><option value="active">Active</option><option value="draft">Draft</option></select></div></div>
                <div class="at-field" style="margin-top:12px"><label>Description</label><input name="description" placeholder="e.g. Standard scoring for senior secondary classes"></div>
                <div class="at-component-editor">
                    <div class="at-component-editor-head"><div><strong>Assessment Components</strong><div class="at-sub">Weights must total exactly 100%.</div></div><button class="at-btn" type="button" onclick="addComponentRow()">+ Add Component</button></div>
                    <div id="componentRows"></div>
                    <div class="at-total"><span>Total Weight</span><span id="componentTotal" class="at-weight">0%</span></div>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px"><button class="at-btn" type="button" onclick="closeModal('newTemplateModal')">Cancel</button><button class="at-btn at-btn-primary" type="submit">Create Template</button></div>
            </div>
        </form>
    </div>
</div>

@if($selectedTemplate)
<div class="at-modal-backdrop" id="assignTemplateModal" onclick="if(event.target===this)closeModal('assignTemplateModal')">
    <div class="at-modal" style="max-width:620px">
        <div class="at-modal-head"><h3>Assign {{ $selectedTemplate->name }}</h3><button class="at-btn" type="button" onclick="closeModal('assignTemplateModal')">×</button></div>
        <form method="POST" action="{{ route('assessment-templates.assign',$selectedTemplate) }}">@csrf
            <div class="at-modal-body">
                <div class="at-field"><label>Academic Session</label><select name="session_id" required><option value="">Select session</option>@foreach($templateSessions as $session)<option value="{{ $session->id }}">{{ $session->name }}{{ $session->is_current ? ' (Current)' : '' }}</option>@endforeach</select></div>
                <div class="at-field" style="margin-top:12px"><label>Class Levels</label><div class="at-checks">@foreach($templateClassLevels as $level)<label class="at-check"><input type="checkbox" name="class_level_ids[]" value="{{ $level->id }}"> <span>{{ $level->name }}</span></label>@endforeach</div></div>
                <div class="at-sub" style="margin-top:10px">Assigning a template synchronizes compatible assessment rows for every term in the selected session. Existing scores remain untouched.</div>
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px"><button class="at-btn" type="button" onclick="closeModal('assignTemplateModal')">Cancel</button><button class="at-btn at-btn-primary" type="submit">Assign Template</button></div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
let componentIndex=0;
function openModal(id){document.getElementById(id)?.classList.add('open')}
function closeModal(id){document.getElementById(id)?.classList.remove('open')}
function componentRow(index){return `<div class="at-component-edit-row" data-component-row><div class="at-field"><label>Name</label><input required name="components[${index}][name]" placeholder="Continuous Assessment"></div><div class="at-field"><label>Weight %</label><input required type="number" min="0.01" max="100" step="0.01" name="components[${index}][weight_percentage]" data-weight oninput="updateComponentTotal()"></div><div class="at-field"><label>Type</label><select name="components[${index}][component_type]"><option value="coursework">Coursework</option><option value="test">Test</option><option value="practical">Practical</option><option value="exam">Exam</option><option value="objective_exam">Objective Exam</option><option value="theory_exam">Theory Exam</option><option value="final_exam">Final Exam</option></select></div><div class="at-field"><label>Entry Mode</label><select name="components[${index}][entry_mode]"><option value="manual">Manual</option><option value="cbt_objective">CBT Objective</option><option value="cbt_aggregate">CBT Aggregate</option><option value="theory_manual">Theory Manual</option></select></div><div class="at-field"><label>Obj. Max</label><input type="number" min="0.01" step="0.01" name="components[${index}][objective_max]" placeholder="Optional"></div><div class="at-field"><label>Theory Max</label><input type="number" min="0.01" step="0.01" name="components[${index}][theory_max]" placeholder="Optional"></div><button class="at-btn at-btn-danger remove-component" type="button" onclick="this.closest('[data-component-row]').remove();updateComponentTotal()">Remove</button></div>`}
function addComponentRow(){document.getElementById('componentRows').insertAdjacentHTML('beforeend',componentRow(componentIndex++));updateComponentTotal()}
function updateComponentTotal(){let t=0;document.querySelectorAll('[data-weight]').forEach(i=>t+=parseFloat(i.value||0));document.getElementById('componentTotal').textContent=(Math.round(t*100)/100)+'%'}
function selectAndAssign(id){window.location='{{ route('scores.assessment-types') }}?selected='+id+'&assign=1'}
addComponentRow();addComponentRow();
@if(request('assign') && $selectedTemplate) document.addEventListener('DOMContentLoaded',()=>openModal('assignTemplateModal')); @endif
</script>
@endpush
