@extends('layouts.app')
@section('title', 'CBT Exams')
@section('page-title', 'CBT Exams')

@push('styles')
<style>
.cbt-tabs{display:flex;gap:4px;background:#fff;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:16px;width:fit-content;max-width:100%;overflow-x:auto}.cbt-tab{min-height:40px;padding:0 16px;border:0;border-radius:7px;background:transparent;color:var(--slate);font:inherit;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;cursor:pointer}.cbt-tab.active{background:var(--brand-gold);color:var(--brand-navy)}.cbt-tab:hover:not(.active){background:#F1F5F9}.cbt-panel[hidden]{display:none!important}.cbt-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden}.cbt-card-head{min-height:48px;padding:0 18px;background:var(--brand-navy);border-bottom:2px solid var(--brand-gold);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:12px}.cbt-card-head strong{font-size:13px}.cbt-card-head small{color:#CBD5E1;font-size:10px}.cbt-card-body{padding:16px}.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:12px;color:#047857;margin-bottom:14px}.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:12px;color:#B91C1C;margin-bottom:14px}.exam-list{display:grid;gap:10px}.exam-card{border:1px solid var(--border);border-radius:10px;padding:13px 15px;background:#fff}.exam-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}.exam-title{font-size:13px;font-weight:800;color:var(--midnight)}.exam-meta{display:flex;flex-wrap:wrap;gap:6px 14px;margin-top:6px;color:var(--slate);font-size:11px}.exam-actions{display:flex;flex-wrap:wrap;gap:7px;margin-top:11px}.badge{display:inline-flex;align-items:center;border-radius:999px;padding:3px 8px;font-size:10px;font-weight:800}.badge-draft{background:#F1F5F9;color:#64748B}.badge-published{background:#EFF6FF;color:#1D4ED8}.badge-active{background:#ECFDF5;color:#047857}.badge-closed{background:#FEF2F2;color:#B91C1C}.btn{min-height:36px;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:0 12px;border:1px solid transparent;border-radius:7px;font:inherit;font-size:11px;font-weight:800;text-decoration:none;cursor:pointer}.btn-primary{background:var(--brand-gold);color:var(--brand-navy);border-color:var(--brand-gold)}.btn-navy{background:var(--brand-navy);color:#fff}.btn-ghost{background:#fff;color:var(--midnight);border-color:var(--border)}.btn-success{background:#059669;color:#fff}.btn-warning{background:#D97706;color:#fff}.schedule-panel,.score-link-panel{margin-top:10px;padding:10px 12px;border:1px solid #DCE5F2;border-radius:9px;background:#F8FAFC}.score-link-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px}.score-link-head strong{font-size:11px;color:var(--midnight)}.score-link-head span{font-size:10px;color:var(--slate-light)}.score-link-form{display:grid;grid-template-columns:minmax(220px,1fr) auto;gap:8px;align-items:end}.score-link-form .form-group{margin:0}.form-shell{max-width:1040px;margin:0 auto}.form-section{border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:12px}.form-section-title{font-size:11px;font-weight:900;color:var(--midnight);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.form-grid-4{display:grid;grid-template-columns:1.1fr 1fr 1.2fr 1fr;gap:12px}.form-group{margin-bottom:12px}.form-group:last-child{margin-bottom:0}.form-label{display:block;font-size:10px;font-weight:800;color:var(--slate);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px}.form-label .required{color:#DC2626}.form-control{width:100%;height:40px;box-sizing:border-box;padding:0 10px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;color:var(--midnight);font:inherit;font-size:12px;outline:none}.form-control:focus{border-color:var(--brand-navy);box-shadow:0 0 0 3px rgba(7,30,69,.08);background:#fff}.form-control[readonly]{background:#F1F5F9;color:#475569;cursor:default}.field-note{font-size:10px;color:var(--slate-light);line-height:1.45;margin-top:4px}.auto-context-note{margin-top:10px;padding:9px 11px;border:1px solid #BFDBFE;border-radius:8px;background:#EFF6FF;color:#1E40AF;font-size:10px;line-height:1.45}.auto-context-note.warning{border-color:#FDE68A;background:#FFFBEB;color:#92400E}.target-picker{border:1px solid var(--border);border-radius:9px;background:#F8FAFC;max-height:260px;overflow:auto;padding:8px}.target-group{padding:6px 5px 9px;border-bottom:1px solid #E5EAF1}.target-group:last-child{border-bottom:0}.target-group strong{display:block;font-size:10px;color:var(--slate);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px}.target-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}.target-option{display:flex;align-items:center;gap:7px;min-height:36px;padding:0 9px;border:1px solid #DFE7F2;border-radius:7px;background:#fff;color:var(--midnight);font-size:11px;cursor:pointer}.target-option:has(input:checked){border-color:var(--brand-navy);background:#EFF6FF;color:#1D4ED8;font-weight:700}.integrity-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.empty-state{text-align:center;padding:38px 18px;color:var(--slate-light);font-size:12px}.empty-state strong{display:block;color:var(--midnight);font-size:13px;margin-bottom:4px}.create-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}.create-actions .btn{min-width:140px}.page-summary{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px}.page-summary p{margin:0;color:var(--slate-light);font-size:11px}.schedule-grid{display:grid;grid-template-columns:1fr 1fr 120px;gap:10px;align-items:end}.schedule-grid .form-group{margin-bottom:0}@media(max-width:980px){.form-grid-4{grid-template-columns:1fr 1fr}.target-options{grid-template-columns:1fr 1fr}}@media(max-width:640px){.cbt-tabs{width:100%}.form-grid,.form-grid-4,.integrity-grid,.target-options,.schedule-grid,.score-link-form{grid-template-columns:1fr}.cbt-card-body{padding:12px}.exam-top{align-items:center}.create-actions{flex-direction:column}.create-actions .btn{width:100%}}
</style>
@endpush

@section('content')
@php
    $currentTerm = $terms->firstWhere('is_current', true)
        ?? $terms->first(fn($term) => (bool) optional($term->session)->is_current)
        ?? $terms->first();
    $defaultTermId = (int) old('term_id', $currentTerm?->id ?? 0);

    foreach ($assessmentTypes as $assessmentType) {
        $assessmentType->loadMissing('classLevels', 'term.session');
    }

    $sessionIds = $terms->pluck('session_id')->filter()->unique()->values();
    $templateAssignments = \App\Models\AssessmentTemplateAssignment::withoutTenantScope()
        ->where('tenant_id', auth()->user()->tenant_id)
        ->where('is_active', true)
        ->whereIn('session_id', $sessionIds)
        ->with('template.components')
        ->get();

    $termContext = [];
    foreach ($terms as $term) {
        $termContext[(string) $term->id] = [
            'sessionId' => (int) $term->session_id,
            'sessionName' => $term->session?->name ?? 'Academic session unavailable',
            'isCurrent' => (bool) $term->is_current,
        ];
    }

    $bankLevels = $banks->mapWithKeys(fn($bank) => [(string) $bank->id => (int) $bank->class_level_id])->all();
    $armLevels = $classArms->mapWithKeys(fn($arm) => [(string) $arm->id => (int) $arm->class_level_id])->all();
    $autoAssessmentContext = [];

    foreach ($terms as $term) {
        foreach ($classLevels as $level) {
            $assignment = $templateAssignments
                ->where('session_id', $term->session_id)
                ->where('class_level_id', $level->id)
                ->first();

            if (!$assignment || !$assignment->template || !$assignment->template->isActive()) {
                continue;
            }

            $examComponent = $assignment->template->components->first(fn($component) => $component->isExam());
            if (!$examComponent) {
                continue;
            }

            $runtimeExam = $assessmentTypes
                ->where('term_id', $term->id)
                ->where('is_exam', true)
                ->first(function($type) use ($level, $examComponent) {
                    return $type->name === $examComponent->name
                        && abs((float)$type->weight_percentage - (float)$examComponent->weight_percentage) < 0.001
                        && $type->classLevels->contains('id', $level->id);
                });

            $autoAssessmentContext[$term->id.':'.$level->id] = [
                'templateId' => (int) $assignment->template->id,
                'templateName' => $assignment->template->name,
                'assessmentTypeId' => $runtimeExam?->id ? (int) $runtimeExam->id : null,
                'examName' => $examComponent->name,
                'examWeight' => (float) $examComponent->weight_percentage,
            ];
        }
    }
@endphp

<div class="cbt-tabs" role="tablist" aria-label="CBT navigation">
    <a href="{{ route('cbt.banks') }}" class="cbt-tab">Question Banks</a>
    <button type="button" class="cbt-tab active" id="examsTab" onclick="showCbtPanel('exams')">Exams</button>
    <button type="button" class="cbt-tab" id="createTab" onclick="showCbtPanel('create')">+ Create Exam</button>
    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
        <a href="{{ route('cbt.retakes') }}" class="cbt-tab">Retake Control</a>
        <a href="{{ route('cbt.lan') }}" class="cbt-tab">LAN Mode</a>
    @endif
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<section id="cbtPanelExams" class="cbt-panel">
    <div class="page-summary">
        <p>Manage existing CBT examinations. Link only the official result-bearing exam to Score Entry; mocks and practice exams can remain unlinked.</p>
        <button type="button" class="btn btn-primary" onclick="showCbtPanel('create')">+ Create Exam</button>
    </div>
    <div class="cbt-card">
        <div class="cbt-card-head"><div><strong>All Exams</strong><br><small>{{ $exams->count() }} examination(s)</small></div></div>
        <div class="cbt-card-body"><div class="exam-list">
            @forelse($exams as $exam)
                @php $eligibleExamComponents = $assessmentTypes->where('is_exam', true)->where('term_id', $exam->term_id); @endphp
                <div class="exam-card">
                    <div class="exam-top">
                        <div>
                            <div class="exam-title">{{ $exam->title }}</div>
                            <div class="exam-meta">
                                <span>{{ $exam->questionBank->subject->name ?? 'Subject unavailable' }}</span>
                                <span>{{ $exam->assignedClassNames() ?: 'No class assigned' }}</span>
                                <span>{{ $exam->duration_minutes }} mins</span>
                                <span>{{ $exam->total_questions }} questions</span>
                                <span>{{ $exam->assessmentType ? 'Score Sheet: '.$exam->assessmentType->name.' / '.$exam->assessmentType->weight_percentage : 'Score Sheet: Not linked' }}</span>
                            </div>
                        </div>
                        <span class="badge badge-{{ $exam->status }}">{{ ucfirst($exam->status) }}</span>
                    </div>
                    <div class="exam-actions">
                        <a href="{{ route('cbt.exams.builder', $exam) }}" class="btn btn-navy">Section Builder</a>
                        <a href="{{ route('cbt.results', $exam) }}" class="btn btn-ghost">View Results</a>
                        @if($exam->status === 'draft')
                            <form method="POST" action="{{ route('cbt.publish', $exam) }}">@csrf<button type="submit" class="btn btn-success">Publish</button></form>
                        @elseif(in_array($exam->status, ['published', 'active'], true))
                            <form method="POST" action="{{ route('cbt.close', $exam) }}">@csrf<button type="submit" class="btn btn-warning">Close Exam</button></form>
                        @endif
                        @if(in_array($exam->status, ['published', 'active', 'closed'], true))
                            <button type="button" class="btn btn-ghost" onclick="document.getElementById('schedule{{ $exam->id }}').toggleAttribute('hidden')">Reschedule</button>
                        @endif
                    </div>
                    <div class="score-link-panel">
                        <div class="score-link-head"><strong>Score Sheet Synchronization</strong><span>Completed CBT aggregate → configured Exam column</span></div>
                        <form method="POST" action="{{ route('cbt.exams.score-sheet-link', $exam) }}" class="score-link-form">
                            @csrf @method('PUT')
                            <div class="form-group">
                                <label class="form-label">Score Sheet Destination</label>
                                <select name="assessment_type_id" class="form-control">
                                    <option value="">Not linked — do not feed Score Entry</option>
                                    @foreach($eligibleExamComponents as $at)
                                        <option value="{{ $at->id }}" @selected((int)$exam->assessment_type_id === (int)$at->id)>{{ $at->name }} — {{ rtrim(rtrim(number_format((float)$at->weight_percentage,2),'0'),'.') }} marks</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Link & Sync</button>
                        </form>
                        <div class="field-note">Linking immediately re-synchronizes already completed and fully marked attempts.</div>
                    </div>
                    @if(in_array($exam->status, ['published', 'active', 'closed'], true))
                        <div class="schedule-panel" id="schedule{{ $exam->id }}" hidden>
                            <form method="POST" action="{{ route('cbt.exams.schedule', $exam) }}">
                                @csrf @method('PUT')
                                <div class="schedule-grid">
                                    <div class="form-group"><label class="form-label">Start</label><input type="datetime-local" name="scheduled_start" class="form-control" value="{{ $exam->scheduled_start?->format('Y-m-d\TH:i') }}" required></div>
                                    <div class="form-group"><label class="form-label">End</label><input type="datetime-local" name="scheduled_end" class="form-control" value="{{ $exam->scheduled_end?->format('Y-m-d\TH:i') }}" required></div>
                                    <div class="form-group"><label class="form-label">Minutes</label><input type="number" name="duration_minutes" class="form-control" value="{{ $exam->duration_minutes }}" min="5" max="1440" required></div>
                                </div>
                                <button type="submit" class="btn btn-success" style="margin-top:10px">Save Schedule</button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-state"><strong>No CBT exams yet</strong>Create the first examination from the Create Exam tab.</div>
            @endforelse
        </div></div>
    </div>
</section>

<section id="cbtPanelCreate" class="cbt-panel" hidden>
    <div class="cbt-card form-shell">
        <div class="cbt-card-head"><div><strong>Create Exam</strong><br><small>Current academic context and the assigned assessment template are selected automatically.</small></div></div>
        <div class="cbt-card-body">
            <form method="POST" action="{{ route('cbt.exams.store') }}" id="createExamForm">
                @csrf
                <input type="hidden" name="assessment_type_id" id="autoAssessmentTypeId" value="{{ old('assessment_type_id') }}">

                <div class="form-section">
                    <div class="form-section-title">Exam Details</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Exam Title <span class="required">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. Year 12 Biology First Term Exam" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Question Bank <span class="required">*</span></label>
                            <select name="question_bank_id" id="questionBankSelect" class="form-control" required>
                                <option value="">Select bank</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}" @selected((string) old('question_bank_id', request('bank')) === (string) $bank->id)>{{ $bank->name }} ({{ $bank->questions()->count() }} questions)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-4">
                        <div class="form-group">
                            <label class="form-label">Academic Session</label>
                            <input type="text" id="academicSessionDisplay" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Term <span class="required">*</span></label>
                            <select name="term_id" id="termSelect" class="form-control" required>
                                @foreach($terms as $term)
                                    <option value="{{ $term->id }}" @selected((int)$defaultTermId === (int)$term->id)>{{ $term->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Configured Template</label>
                            <input type="text" id="assessmentTemplateDisplay" class="form-control" value="Resolving…" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Exam Component</label>
                            <input type="text" id="examComponentDisplay" class="form-control" value="Resolving…" readonly>
                        </div>
                    </div>
                    <div class="form-group" style="max-width:220px">
                        <label class="form-label">Duration (minutes) <span class="required">*</span></label>
                        <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', 60) }}" min="5" max="1440" required>
                    </div>
                    <div id="autoContextNote" class="auto-context-note">EduCore uses the current session/term and the active assessment template assigned to the selected class level.</div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Assigned Classes</div>
                    <div class="target-picker">
                        @foreach($classArms->groupBy('class_level_id') as $levelArms)
                            <div class="target-group">
                                <strong>{{ $levelArms->first()->classLevel->name ?? 'Class level' }}</strong>
                                <div class="target-options">
                                    @foreach($levelArms as $arm)
                                        <label class="target-option"><input type="checkbox" name="class_arm_ids[]" value="{{ $arm->id }}" data-class-level-id="{{ $arm->class_level_id }}" @checked(in_array($arm->id, array_map('intval', old('class_arm_ids', []))))><span>{{ $arm->classLevel->name }} {{ $arm->name }}</span></label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="field-note">The configured template is resolved from the class level(s) selected here. All selected classes must use the same active template for the current session.</div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Schedule</div>
                    <div class="form-grid">
                        <div class="form-group"><label class="form-label">Scheduled Start</label><input type="datetime-local" name="scheduled_start" class="form-control" value="{{ old('scheduled_start') }}"></div>
                        <div class="form-group"><label class="form-label">Scheduled End</label><input type="datetime-local" name="scheduled_end" class="form-control" value="{{ old('scheduled_end') }}"></div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Exam Integrity</div>
                    <label style="display:flex;align-items:center;gap:8px;font-size:11px;color:var(--slate);margin-bottom:10px"><input type="checkbox" name="malpractice_enabled" value="1" @checked(old('malpractice_enabled', true))> Enable integrity monitoring</label>
                    <div class="integrity-grid">
                        <div class="form-group"><label class="form-label">Focus-loss Action</label><select class="form-control" name="focus_loss_policy"><option value="submit" @selected(old('focus_loss_policy','submit')==='submit')>Submit attempt</option><option value="warn" @selected(old('focus_loss_policy')==='warn')>Warn and log</option><option value="log" @selected(old('focus_loss_policy')==='log')>Log only</option></select></div>
                        <div class="form-group"><label class="form-label">Allowed Focus Losses</label><input class="form-control" type="number" name="max_focus_losses" value="{{ old('max_focus_losses',0) }}" min="0" max="20"></div>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;font-size:11px;color:var(--slate)"><input type="checkbox" name="require_fullscreen" value="1" @checked(old('require_fullscreen'))> Require full-screen mode</label>
                </div>

                <div class="create-actions">
                    <button type="button" class="btn btn-ghost" onclick="showCbtPanel('exams')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Exam</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
const cbtTermContext = @json($termContext);
const cbtBankLevels = @json($bankLevels);
const cbtAutoAssessmentContext = @json($autoAssessmentContext);

function showCbtPanel(name){
    const create = name === 'create';
    document.getElementById('cbtPanelExams').hidden = create;
    document.getElementById('cbtPanelCreate').hidden = !create;
    document.getElementById('examsTab').classList.toggle('active', !create);
    document.getElementById('createTab').classList.toggle('active', create);
    const hash = create ? '#create' : '#exams';
    if (history.replaceState) history.replaceState(null, '', hash); else location.hash = hash;
    window.scrollTo({top:0, behavior:'smooth'});
}

function selectedClassLevelIds(){
    const checked = [...document.querySelectorAll('input[name="class_arm_ids[]"]:checked')];
    if (checked.length) return [...new Set(checked.map(el => String(el.dataset.classLevelId)))];
    const bankId = document.getElementById('questionBankSelect')?.value;
    const bankLevel = bankId ? cbtBankLevels[String(bankId)] : null;
    return bankLevel ? [String(bankLevel)] : [];
}

function resolveAutomaticAssessmentContext(){
    const termId = String(document.getElementById('termSelect')?.value || '');
    const sessionField = document.getElementById('academicSessionDisplay');
    const templateField = document.getElementById('assessmentTemplateDisplay');
    const componentField = document.getElementById('examComponentDisplay');
    const hiddenAssessment = document.getElementById('autoAssessmentTypeId');
    const note = document.getElementById('autoContextNote');
    const term = cbtTermContext[termId];

    sessionField.value = term?.sessionName || 'No current academic session';
    hiddenAssessment.value = '';

    const levels = selectedClassLevelIds();
    if (!termId || !levels.length) {
        templateField.value = 'Select a question bank or class';
        componentField.value = 'Waiting for class level';
        note.classList.remove('warning');
        note.textContent = 'EduCore will select the active assessment template automatically once the class level is known.';
        return;
    }

    const configs = levels.map(levelId => cbtAutoAssessmentContext[termId + ':' + levelId]).filter(Boolean);
    if (configs.length !== levels.length) {
        templateField.value = 'No active template configured';
        componentField.value = 'Not linked';
        note.classList.add('warning');
        note.textContent = 'One or more selected class levels do not have an active assessment template for this academic session.';
        return;
    }

    const templateIds = [...new Set(configs.map(c => String(c.templateId)))];
    const assessmentIds = [...new Set(configs.map(c => c.assessmentTypeId ? String(c.assessmentTypeId) : ''))];
    if (templateIds.length !== 1) {
        templateField.value = 'Different templates selected';
        componentField.value = 'Cannot auto-link';
        note.classList.add('warning');
        note.textContent = 'The selected classes use different assessment templates. Create separate CBT exams per template/class level.';
        return;
    }

    const config = configs[0];
    templateField.value = config.templateName;
    componentField.value = config.examName + ' — ' + Number(config.examWeight).toFixed(0) + ' marks';

    if (assessmentIds.length === 1 && assessmentIds[0]) {
        hiddenAssessment.value = assessmentIds[0];
        note.classList.remove('warning');
        note.textContent = 'Current academic session, term and assessment template selected automatically. Completed CBT aggregate will feed the configured ' + config.examName + ' column.';
    } else {
        componentField.value = config.examName + ' — runtime column unavailable';
        note.classList.add('warning');
        note.textContent = 'The template is assigned, but its Exam column has not been materialized consistently for all selected classes. Re-save the template assignment before creating this official CBT exam.';
    }
}

document.addEventListener('DOMContentLoaded', function(){
    const hasErrors = @json($errors->any());
    showCbtPanel(hasErrors || location.hash === '#create' ? 'create' : 'exams');
    resolveAutomaticAssessmentContext();
    document.getElementById('termSelect')?.addEventListener('change', resolveAutomaticAssessmentContext);
    document.getElementById('questionBankSelect')?.addEventListener('change', resolveAutomaticAssessmentContext);
    document.querySelectorAll('input[name="class_arm_ids[]"]').forEach(el => el.addEventListener('change', resolveAutomaticAssessmentContext));
});
</script>
@endpush
