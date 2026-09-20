@extends('layouts.app')
@section('title','Parallel Curriculum Setup')
@section('page-title','Parallel Curriculum Setup')

@push('styles')
<style>
.pc-shell{max-width:1240px;margin:0 auto}.pc-tabs{display:flex;gap:5px;overflow-x:auto;margin-bottom:16px}.pc-tab{flex:0 0 auto;padding:8px 14px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);font-size:12px;font-weight:700;text-decoration:none}.pc-tab.active,.pc-tab:hover{background:var(--midnight);color:#fff;border-color:var(--midnight)}
.pc-hero{padding:18px 20px;border-radius:14px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff;margin-bottom:16px}.pc-hero h2{font-size:19px;margin:0 0 5px}.pc-hero p{font-size:11.5px;line-height:1.55;color:#DCE5F2;margin:0;max-width:820px}
.pc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.pc-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;min-width:0}.pc-head{padding:12px 15px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.pc-body{padding:15px}.pc-card.full{grid-column:1/-1}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:10px;min-width:0}.fl{font-size:10.5px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:39px;border:1px solid var(--border);border-radius:8px;padding:8px 10px;background:#fff;font:500 12px inherit;outline:none}.fc:focus{border-color:var(--indigo)}select[multiple].fc{min-height:155px}.form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.score-entry-grid{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:10px;align-items:end}.score-entry-grid .fg{margin-bottom:0}.score-entry-help{margin-top:10px;padding:10px 12px;border:1px solid #D0D5DD;border-radius:8px;background:#F8FAFC;color:var(--slate);font-size:10.5px;line-height:1.5}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:0;border-radius:8px;padding:9px 14px;font:700 11.5px inherit;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.btn-d{background:#FEF2F2;color:#B42318;border:1px solid #FECDCA}.hint{font-size:10.5px;color:var(--slate-light);line-height:1.45}
.workspaces{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px}.workspace{display:block;padding:13px;border:1px solid var(--border);border-radius:10px;color:inherit;background:#fff;min-width:0}.workspace:hover{border-color:var(--indigo);background:#F8FAFF}.workspace strong{display:block;color:var(--midnight);font-size:12.5px;overflow-wrap:anywhere}.workspace span{display:block;margin-top:4px;color:var(--slate);font-size:10.5px;line-height:1.45}.workspace-badge{display:inline-flex!important;width:auto;margin-top:7px!important;padding:3px 7px;border-radius:999px;background:#ECFDF3;color:#067647!important;font-size:9px!important;font-weight:800}.workspace-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.workspace-actions a{display:inline-flex;align-items:center;justify-content:center;min-height:32px;padding:6px 9px;border-radius:7px;text-decoration:none;font-size:10px;font-weight:800}.workspace-open{background:var(--indigo);color:#fff}.workspace-secondary{background:#fff;color:var(--midnight);border:1px solid var(--border)}.empty{padding:24px;text-align:center;color:var(--slate-light);font-size:12px}
.pc-feature-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.pc-feature-link{display:flex;flex-direction:column;gap:5px;min-height:112px;padding:14px;border:1px solid var(--border);border-radius:10px;background:#fff;color:inherit;text-decoration:none}.pc-feature-link:hover{border-color:var(--indigo);background:#F8FAFF;box-shadow:var(--shadow)}.pc-feature-link strong{font-size:12.5px;line-height:1.35;color:var(--midnight)}.pc-feature-link span{font-size:10.5px;line-height:1.5;color:var(--slate)}.pc-feature-link small{margin-top:auto;font-size:9.5px;font-weight:800;color:var(--indigo);text-transform:uppercase;letter-spacing:.03em}
.item{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid #EEF2F7}.item:last-child{border-bottom:0}.item-main{min-width:0}.item-main strong{display:block;color:var(--midnight);font-size:11.5px;overflow-wrap:anywhere}.item-main span{display:block;color:var(--slate-light);font-size:10px;margin-top:2px}.badge{display:inline-flex;padding:3px 7px;border-radius:999px;font-size:9.5px;font-weight:800;background:#EFF6FF;color:#1D4ED8}.badge.synced{background:#ECFDF3;color:#067647}.badge.pending{background:#FFFAEB;color:#B54708}.badge.conflict,.badge.locked{background:#FEF3F2;color:#B42318}.badge.unmapped{background:#F2F4F7;color:#475467}
.alert-s,.alert-e{border-radius:9px;padding:10px 13px;font-size:11px;margin-bottom:12px}.alert-s{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.alert-e{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.checkbox-row{display:flex;align-items:flex-start;gap:8px;font-size:11px;color:var(--slate)}.checkbox-row input{margin-top:2px}
@media(max-width:840px){.pc-grid{grid-template-columns:1fr}.pc-card.full{grid-column:auto}.pc-feature-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.score-entry-grid{grid-template-columns:1fr 1fr}}@media(max-width:560px){.form-row,.score-entry-grid{grid-template-columns:1fr}.pc-hero{padding:15px}.pc-body{padding:13px}.btn{width:100%}.item{flex-direction:column}.pc-feature-grid{grid-template-columns:1fr}.pc-feature-link{min-height:0}}
</style>
@endpush

@push('styles')
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pc-shell">
    @if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert-e" data-parallel-validation-error><strong>Could not save.</strong> {{ $errors->first() }}</div>@endif
    @if($schemaReconciliationPending)
        <div class="alert-e">
            <strong>Database update pending.</strong>
            Programme setup is running in compatibility mode because one or more newer parallel-curriculum schema components are not yet available. Deploy the latest master build and complete database migrations before making configuration changes.
        </div>
    @endif

    <div class="pc-tabs">
        <a href="{{ route('scores.index') }}" class="pc-tab">Conventional Scores</a>
        <a href="{{ route('parallel-curriculum.index') }}" class="pc-tab">Parallel Workspace</a>
        <a href="{{ route('parallel-curriculum.setup') }}" class="pc-tab active">Programme Setup</a>
        <a href="{{ route('parallel-curriculum.student-assignments') }}" class="pc-tab">Student Assignments</a>
        <a href="{{ route('parallel-curriculum.lifecycle.index') }}" class="pc-tab">Academic Lifecycle</a>
        <a href="{{ route('parallel-curriculum.operations.index') }}" class="pc-tab">Timetable & Attendance</a>
        <a href="{{ route('parallel-curriculum.results.index') }}" class="pc-tab">Parallel Results</a>
        @if(auth()->user()->canAccessModule('scores.view') || auth()->user()->canAccessExactModule('scores'))
            <a href="{{ route('scores.broadsheet') }}" class="pc-tab">Broadsheet</a>
        @endif
    </div>

    <div class="pc-hero">
        <h2>Programme Setup & Integration</h2>
        <p>Configure parallel programmes, class levels, subjects, class-subject assignments, grading bands and conventional-result mappings here. Operational score entry, timetable, attendance, student placement and lifecycle work remain in their own workspaces.</p>
    </div>

    <section class="pc-card full" style="margin-bottom:14px">
        <div class="pc-head">Configuration workspace</div>
        <div class="pc-body">
            <div class="score-entry-help" style="margin-top:0">
                Sections are collapsed by default. Open only the configuration area you are changing; this keeps the setup workflow focused while preserving all existing controls and validation.
            </div>
        </div>
    </section>

    @if($canManage)
    <div class="pc-grid" data-collapsible-root data-storage-key="parallel-config">
        <div class="pc-section-toolbar">
            <div class="pc-toolbar-copy">
                <strong>Programme configuration</strong>
                <span>Configuration sections are collapsed by default to keep this workspace focused. Open only the section you need.</span>
            </div>
            <div class="pc-toolbar-actions">
                <button class="btn btn-s" type="button" data-expand-all>Expand all</button>
                <button class="btn btn-s" type="button" data-collapse-all>Collapse all</button>
            </div>
        </div>
        <section class="pc-card" data-collapsible-item>
            <div class="pc-head">1. Create programme</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.curricula.store') }}">@csrf
                    <div class="form-row">
                        <div class="fg"><label class="fl">Programme name</label><input class="fc" name="name" required placeholder="e.g. Islamiyyah"></div>
                        <div class="fg"><label class="fl">Code</label><input class="fc" name="code" placeholder="e.g. ISL"></div>
                    </div>
                    <div class="fg"><label class="fl">Default Assessment Template</label><select class="fc" name="default_assessment_template_id" required><option value="">Select template</option>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }} · {{ number_format($template->total_weight,0) }}%</option>@endforeach</select><div class="hint">This template controls how each parallel subject is scored. A class can override it.</div></div>
                    <button class="btn btn-p">Create Programme</button>
                </form>
            </div>
        </section>

        <section class="pc-card" data-collapsible-item>
            <div class="pc-head">2. Create parallel class level</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.classes.store') }}">@csrf
                    <div class="fg"><label class="fl">Programme</label><select class="fc" name="parallel_curriculum_id" required><option value="">Select programme</option>@foreach($curricula as $curriculum)<option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>@endforeach</select></div>
                    <div class="form-row">
                        <div class="fg"><label class="fl">Class level name</label><input class="fc" name="name" required placeholder="e.g. Mutawassitah 1"></div>
                        <div class="fg"><label class="fl">Code</label><input class="fc" name="code"></div>
                    </div>
                    <div class="fg"><label class="fl">Template override (optional)</label><select class="fc" name="assessment_template_id"><option value="">Use programme default</option>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select></div>
                    <button class="btn btn-p">Create Class</button>
                </form>
            </div>
        </section>

        <section class="pc-card" data-collapsible-item>
            <div class="pc-head">3. Create programme subject</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.subjects.store') }}">@csrf
                    <div class="fg"><label class="fl">Programme</label><select class="fc" name="parallel_curriculum_id" required><option value="">Select programme</option>@foreach($curricula as $curriculum)<option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>@endforeach</select></div>
                    <div class="form-row">
                        <div class="fg"><label class="fl">Subject name</label><input class="fc" name="name" required placeholder="e.g. Qur'an, Fiqh, Hadith"></div>
                        <div class="fg"><label class="fl">Code (optional)</label><input class="fc" name="code" placeholder="e.g. QRN"></div>
                    </div>
                    <div class="hint" style="margin-bottom:10px">Programme subjects are independent and do not appear in conventional subject selectors. Create class arms separately in Academic Lifecycle.</div>
                    <button class="btn btn-p">Save Programme Subject</button>
                </form>
            </div>
        </section>

        <section class="pc-card" data-collapsible-item>
            <div class="pc-head">4. Assign subjects to parallel class</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.class-subjects.store') }}">@csrf
                    <div class="fg"><label class="fl">Parallel class</label><select class="fc" name="parallel_curriculum_class_id" required><option value="">Select class</option>@foreach($curricula as $curriculum)@foreach($curriculum->classes as $class)<option value="{{ $class->id }}">{{ $curriculum->name }} · {{ $class->name }}</option>@endforeach @endforeach</select></div>
                    <div class="form-row">
                        <div class="fg"><label class="fl">Programme subject</label><select class="fc" name="parallel_curriculum_subject_id" required><option value="">Select programme subject</option>@foreach($curricula as $curriculum)@foreach($curriculum->subjects->where('is_active',true) as $subject)<option value="{{ $subject->id }}">{{ $curriculum->name }} · {{ $subject->name }}</option>@endforeach @endforeach</select><div class="hint">This explicitly defines which subjects belong to the selected parallel class. All-subject class teachers see only these class-assigned subjects.</div></div>
                        <div class="fg"><label class="fl">Default subject teacher for all arms (optional)</label><select class="fc" name="teacher_id"><option value="">No default subject teacher</option>@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select><div class="hint">Used only when the arm is in Subject-based teachers mode. An arm-specific teacher may override this default in Academic Lifecycle.</div></div>
                    </div>
                    <button class="btn btn-p">Assign Subject</button>
                </form>
            </div>
        </section>

        <section class="pc-card" data-collapsible-item>
            <div class="pc-head">5. Assign students independently</div>
            <div class="pc-body">
                <div class="item" style="padding-top:0">
                    <div class="item-main">
                        <strong>Bulk student assignment workspace</strong>
                        <span>Filter learners by conventional class, gender, assignment status, name or admission number. Select many learners at once and place or move them into an independent parallel class.</span>
                    </div>
                    <span class="badge">{{ $enrolments->count() }} active</span>
                </div>
                <div class="hint" style="margin:10px 0 12px">Conventional classes are used only to find students. A learner's conventional placement is never changed by parallel-curriculum assignment.</div>
                <a class="btn btn-p" href="{{ route('parallel-curriculum.student-assignments') }}">Open Student Assignment Workspace</a>
            </div>
        </section>

        <section class="pc-card full" data-collapsible-item>
            <div class="pc-head">6. Map programme average to conventional results</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.integrations.store') }}" id="parallel-integration-form">@csrf
                    <div class="fg">
                        <label class="fl">Source programme</label>
                        <select class="fc" name="parallel_curriculum_id" required>
                            <option value="">Select programme</option>
                            @foreach($curricula as $curriculum)
                                <option value="{{ $curriculum->id }}" @selected((int)old('parallel_curriculum_id') === (int)$curriculum->id)>{{ $curriculum->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fg">
                        <label class="fl">Conventional class levels</label>
                        @php($oldIntegrationLevels=collect(old('destination_class_level_ids', []))->map(fn($id)=>(int)$id)->all())
                        <select class="fc" name="destination_class_level_ids[]" id="integration-class-levels" multiple required>
                            @foreach($classLevels as $level)
                                <option value="{{ $level->id }}" @selected(in_array((int)$level->id,$oldIntegrationLevels,true))>{{ $level->name }}</option>
                            @endforeach
                        </select>
                        <div class="hint">Select the conventional class levels that will receive the programme average. Each level continues to use its own Assessment Template.</div>
                    </div>

                    <div class="fg">
                        <label class="fl">Destination subject</label>
                        <select class="fc" name="destination_subject_id" id="integration-destination-subject" required disabled>
                            <option value="">Select class level(s) first</option>
                            @foreach($conventionalSubjects as $subject)
                                <option
                                    value="{{ $subject->id }}"
                                    data-compatible-levels="{{ collect($integrationSubjectCompatibility->get((int)$subject->id, []))->implode(',') }}"
                                    @selected((int)old('destination_subject_id') === (int)$subject->id)
                                >{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <div class="hint" id="integration-subject-help">
                            Only subjects offered by the master conventional curriculum in every selected class level/academic track can be used as the destination.
                        </div>
                        <div class="score-entry-help" id="integration-compatibility-warning" hidden style="margin-top:8px"></div>
                    </div>

                    <div class="form-row">
                        <div class="fg"><label class="fl">Minimum completed subjects</label><input class="fc" type="number" name="minimum_completed_subjects" value="{{ old('minimum_completed_subjects',1) }}" min="1" max="50"></div>
                        <div style="padding-top:22px">
                            <input type="hidden" name="require_all_subjects" value="0">
                            <label class="checkbox-row"><input type="checkbox" name="require_all_subjects" value="1" @checked(old('require_all_subjects','1'))><span>Require all active parallel subjects before calculating the average.</span></label>
                            <input type="hidden" name="auto_sync" value="0">
                            <label class="checkbox-row" style="margin-top:8px"><input type="checkbox" name="auto_sync" value="1" @checked(old('auto_sync','1'))><span>Automatically refresh the conventional score after parallel score entry.</span></label>
                        </div>
                    </div>
                    <button class="btn btn-p" type="submit" id="save-integration-mapping">Save Integration Mapping</button>
                </form>
            </div>
        </section>

        <section class="pc-card full" data-collapsible-item>
            <div class="pc-head">7. Configure standalone result grading scale</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.grades.store') }}">@csrf
                    <div class="form-row">
                        <div class="fg"><label class="fl">Programme</label><select class="fc" name="parallel_curriculum_id" required><option value="">Select programme</option>@foreach($curricula as $curriculum)<option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>@endforeach</select></div>
                        <div class="fg"><label class="fl">Grade</label><input class="fc" name="grade_letter" required maxlength="20" placeholder="e.g. A"></div>
                    </div>
                    <div class="form-row">
                        <div class="fg"><label class="fl">Minimum score</label><input class="fc" type="number" name="min_score" min="0" max="100" step="0.01" required placeholder="70"></div>
                        <div class="fg"><label class="fl">Maximum score</label><input class="fc" type="number" name="max_score" min="0" max="100" step="0.01" required placeholder="100"></div>
                    </div>
                    <div class="form-row">
                        <div class="fg"><label class="fl">Remark</label><input class="fc" name="remark" maxlength="100" placeholder="e.g. Excellent"></div>
                        <div style="padding-top:22px">
                            <input type="hidden" name="is_pass_grade" value="0">
                            <label class="checkbox-row"><input type="checkbox" name="is_pass_grade" value="1" checked><span>Count this as a pass grade.</span></label>
                        </div>
                    </div>
                    <div class="hint" style="margin-bottom:10px">Grade ranges must not overlap. For formal publication, the configured bands should cover every possible score from 0 to 100 without gaps.</div>
                    <button class="btn btn-p">Save Grade Band</button>
                </form>

                <div style="margin-top:14px;border-top:1px solid #EEF2F7;padding-top:10px">
                    @forelse($curricula as $curriculum)
                        <div class="item">
                            <div class="item-main">
                                <strong>{{ $curriculum->name }}</strong>
                                <span>
                                    @if($curriculum->grades->isEmpty())
                                        No grading bands configured.
                                    @else
                                        {{ $curriculum->grades->map(fn($grade) => $grade->grade_letter.' '.$grade->min_score.'–'.$grade->max_score)->join(' · ') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        @foreach($curriculum->grades as $grade)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 0 6px 12px;border-bottom:1px solid #F4F6F8">
                                <span style="font-size:10px;color:var(--slate)"><strong>{{ $grade->grade_letter }}</strong> · {{ number_format($grade->min_score,2) }}–{{ number_format($grade->max_score,2) }} · {{ $grade->remark ?: 'No remark' }} · {{ $grade->is_pass_grade ? 'Pass' : 'Fail' }}</span>
                                <form method="POST" action="{{ route('parallel-curriculum.grades.destroy',$grade) }}">@csrf @method('DELETE')<button class="btn btn-d" style="padding:4px 7px">Remove</button></form>
                            </div>
                        @endforeach
                    @empty
                        <div class="empty">Create a programme before configuring its grading scale.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="pc-card" data-collapsible-item>
            <div class="pc-head">
                <span>Current programme structure</span>
                <span class="hint">Published result structures must be unpublished before protected edits.</span>
            </div>
            <div class="pc-body">
                @forelse($curricula as $curriculum)
                    <div class="pc-structure-group">
                        <div class="item" style="padding-top:0">
                            <div class="item-main">
                                <strong>{{ $curriculum->name }} @if($curriculum->code) · {{ $curriculum->code }}@endif</strong>
                                <span>Default: {{ $curriculum->defaultAssessmentTemplate?->name ?: 'No template' }} · {{ $curriculum->subjects->where('is_active',true)->count() }} active subject(s) · {{ $curriculum->classes->where('is_active',true)->count() }} active class(es)</span>
                            </div>
                            <details class="pc-edit-details">
                                <summary>Edit programme</summary>
                                <div class="pc-edit-panel">
                                    <form method="POST" action="{{ route('parallel-curriculum.curricula.update',$curriculum) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-row">
                                            <div class="fg"><label class="fl">Programme name</label><input class="fc" name="name" value="{{ $curriculum->name }}" required></div>
                                            <div class="fg"><label class="fl">Code</label><input class="fc" name="code" value="{{ $curriculum->code }}"></div>
                                        </div>
                                        <div class="fg">
                                            <label class="fl">Default Assessment Template</label>
                                            <select class="fc" name="default_assessment_template_id" required>
                                                @foreach($templates as $template)
                                                    <option value="{{ $template->id }}" @selected((int)$curriculum->default_assessment_template_id === (int)$template->id)>{{ $template->name }} · {{ number_format($template->total_weight,0) }}%</option>
                                                @endforeach
                                            </select>
                                            <div class="hint">Template changes are blocked once parallel scores exist; programme-detail changes are blocked while programme results are published.</div>
                                        </div>
                                        <button class="btn btn-p" type="submit">Save Programme Changes</button>
                                    </form>
                                </div>
                            </details>
                        </div>

                        <div class="pc-structure-label">Programme subjects</div>
                        @forelse($curriculum->subjects->where('is_active',true) as $subject)
                            <div class="pc-structure-child">
                                <div class="pc-structure-child-head">
                                    <div class="pc-structure-child-title">
                                        <strong>{{ $subject->name }}</strong>
                                        <span>{{ $subject->code ?: 'No code' }}</span>
                                    </div>
                                    <details class="pc-edit-details">
                                        <summary>Edit subject</summary>
                                        <div class="pc-edit-panel">
                                            <form method="POST" action="{{ route('parallel-curriculum.subjects.update',$subject) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="form-row">
                                                    <div class="fg"><label class="fl">Subject name</label><input class="fc" name="name" value="{{ $subject->name }}" required></div>
                                                    <div class="fg"><label class="fl">Code</label><input class="fc" name="code" value="{{ $subject->code }}"></div>
                                                </div>
                                                <div class="hint" style="margin-bottom:10px">Renaming is blocked while a published parallel result uses this subject.</div>
                                                <button class="btn btn-p" type="submit">Save Subject Changes</button>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        @empty
                            <div class="hint">No active programme subjects yet.</div>
                        @endforelse

                        <div class="pc-structure-label">Parallel classes</div>
                        @forelse($curriculum->classes as $class)
                            <div class="pc-structure-child">
                                <div class="pc-structure-child-head">
                                    <div class="pc-structure-child-title">
                                        <strong>{{ $class->name }} @if($class->code) · {{ $class->code }}@endif</strong>
                                        <span>Template: {{ $class->assessmentTemplate?->name ?: 'Programme default' }}</span>
                                    </div>
                                    <details class="pc-edit-details">
                                        <summary>Edit class</summary>
                                        <div class="pc-edit-panel">
                                            <form method="POST" action="{{ route('parallel-curriculum.classes.update',$class) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="form-row">
                                                    <div class="fg"><label class="fl">Class name</label><input class="fc" name="name" value="{{ $class->name }}" required></div>
                                                    <div class="fg"><label class="fl">Code</label><input class="fc" name="code" value="{{ $class->code }}"></div>
                                                </div>
                                                <div class="fg">
                                                    <label class="fl">Assessment Template</label>
                                                    <select class="fc" name="assessment_template_id">
                                                        <option value="">Use programme default</option>
                                                        @foreach($templates as $template)
                                                            <option value="{{ $template->id }}" @selected((int)$class->assessment_template_id === (int)$template->id)>{{ $template->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="hint">Template changes are blocked after scores are recorded; all class-detail edits are blocked while this class result is published.</div>
                                                </div>
                                                <button class="btn btn-p" type="submit">Save Class Changes</button>
                                            </form>
                                        </div>
                                    </details>
                                </div>

                                @forelse($class->subjectAssignments->where('is_active',true) as $assignment)
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:7px;flex-wrap:wrap">
                                        <span class="hint">{{ $assignment->subject?->name }} @if($assignment->teacher) · {{ $assignment->teacher->name }}@endif</span>
                                        <form method="POST" action="{{ route('parallel-curriculum.class-subjects.destroy',$assignment) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-d" type="submit">Remove</button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="hint" style="margin-top:6px">No subjects assigned to this class yet.</div>
                                @endforelse
                            </div>
                        @empty
                            <div class="hint">No parallel classes yet.</div>
                        @endforelse
                    </div>
                @empty
                    <div class="empty">Create the first programme above.</div>
                @endforelse
            </div>
        </section>

        <section class="pc-card" data-collapsible-item>
            <div class="pc-head">Conventional result mappings</div>
            <div class="pc-body">
                @forelse($integrations as $rule)
                    <div class="item">
                        <div class="item-main">
                            <strong>{{ $rule->curriculum?->name }} → {{ $rule->destinationSubject?->name }}</strong>
                            <span>{{ $rule->destinationClassLevel?->name }} · {{ $rule->require_all_subjects ? 'All subjects required' : 'Minimum '.$rule->minimum_completed_subjects }} · {{ $rule->auto_sync ? 'Auto sync' : 'Manual sync' }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                            <span class="badge {{ $rule->is_active ? 'synced' : 'unmapped' }}">{{ $rule->is_active ? 'Active' : 'Inactive' }}</span>
                            @if($rule->is_active)
                                <form method="POST" action="{{ route('parallel-curriculum.integrations.destroy',$rule) }}" onsubmit="return confirm('Remove this conventional result mapping? Unpublished derived scores will be cleared; already-published results will be preserved.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-d" type="submit">Remove</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty">No integration rules configured yet.</div>
                @endforelse
            </div>
        </section>

        <section class="pc-card full" data-collapsible-item>
            <div class="pc-head">Current student parallel-class assignments @if($currentSession) · {{ $currentSession->name }}@endif</div>
            <div class="pc-body">
                @forelse($enrolments->take(12) as $enrolment)
                    <div class="item">
                        <div class="item-main"><strong>{{ $enrolment->student?->full_name }} · {{ $enrolment->curriculumClass?->name }}</strong><span>{{ $enrolment->curriculum?->name }} · Conventional: {{ $enrolment->student?->currentClassArm?->full_name ?: 'Not assigned' }}</span></div>
                        <form method="POST" action="{{ route('parallel-curriculum.enrolments.destroy',$enrolment) }}">@csrf @method('DELETE')<button class="btn btn-d">Remove</button></form>
                    </div>
                @empty
                    <div class="empty">No student has been assigned to a parallel class for the current session.</div>
                @endforelse
                @if($enrolments->count() > 12)
                    <div class="score-entry-help" style="margin-top:10px">
                        Showing 12 of {{ $enrolments->count() }} active assignments here to keep the dashboard compact.
                        <a href="{{ route('parallel-curriculum.student-assignments') }}">Open the Student Assignment Workspace</a> to view and manage the full list.
                    </div>
                @endif
            </div>
        </section>

        <section class="pc-card full" data-collapsible-item>
            <div class="pc-head">Composite synchronization @if($currentTerm) · {{ $currentTerm->name }}@endif</div>
            <div class="pc-body">
                @if($currentTerm && $curricula->isNotEmpty())
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                        @foreach($curricula as $curriculum)
                            <form method="POST" action="{{ route('parallel-curriculum.sync') }}">@csrf
                                <input type="hidden" name="parallel_curriculum_id" value="{{ $curriculum->id }}">
                                <input type="hidden" name="term_id" value="{{ $currentTerm->id }}">
                                <button class="btn btn-s">Sync {{ $curriculum->name }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif
                @forelse($recentComposites as $composite)
                    <div class="item">
                        <div class="item-main"><strong>{{ $composite->student?->full_name }} · {{ $composite->curriculum?->name }}</strong><span>{{ $composite->completed_subject_count }}/{{ $composite->subject_count }} subjects · Average: {{ $composite->average_score === null ? '—' : number_format($composite->average_score,2) }} · {{ $composite->sync_message }}</span></div>
                        <a class="badge {{ $composite->sync_status }}" href="{{ route('parallel-curriculum.breakdown',$composite) }}">{{ ucfirst($composite->sync_status) }}</a>
                    </div>
                @empty
                    <div class="empty">Composite records will appear after parallel scores are entered or synchronized.</div>
                @endforelse
            </div>
        </section>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function(){
    const form = document.getElementById('parallel-score-entry-form');
    if (!form) return;

    const arm = document.getElementById('parallel-score-arm');
    const subject = document.getElementById('parallel-score-subject');
    const classId = document.getElementById('parallel-score-class-id');
    const armId = document.getElementById('parallel-score-arm-id');
    const term = document.getElementById('parallel-score-term');
    const open = document.getElementById('parallel-score-open');

    function refreshSubjects() {
        const selectedArm = arm?.selectedOptions?.[0];
        const workspaceKey = arm?.value || '';

        classId.value = selectedArm?.dataset?.classId || '';
        armId.value = selectedArm?.dataset?.armId || '';

        let firstVisible = null;
        Array.from(subject?.options || []).forEach((option, index) => {
            if (index === 0) return;
            const visible = !!workspaceKey && option.dataset.workspaceKey === workspaceKey;
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible && !firstVisible) firstVisible = option;
        });

        subject.disabled = !firstVisible;
        subject.value = firstVisible ? firstVisible.value : '';
        if (subject.options.length) {
            subject.options[0].textContent = firstVisible
                ? 'Select subject'
                : (workspaceKey ? 'No assigned subject for this arm' : 'Select class arm first');
        }

        refreshButton();
    }

    function refreshButton() {
        if (!open) return;
        open.disabled = !classId.value || !subject.value || !term?.value;
    }

    arm?.addEventListener('change', refreshSubjects);
    subject?.addEventListener('change', refreshButton);
    term?.addEventListener('change', refreshButton);

    if (arm && !arm.disabled && arm.options.length > 1) {
        arm.selectedIndex = 1;
    }
    refreshSubjects();

    form.addEventListener('submit', function(event){
        refreshButton();
        if (open?.disabled) event.preventDefault();
    });
})();

(function(){
    const form = document.getElementById('parallel-integration-form');
    if (!form) return;

    const levels = document.getElementById('integration-class-levels');
    const subject = document.getElementById('integration-destination-subject');
    const warning = document.getElementById('integration-compatibility-warning');
    const save = document.getElementById('save-integration-mapping');

    function selectedLevelIds() {
        return Array.from(levels?.selectedOptions || []).map(option => String(option.value));
    }

    function optionCompatible(option, selectedIds) {
        if (!option.value || selectedIds.length === 0) return false;

        const compatible = String(option.dataset.compatibleLevels || '')
            .split(',')
            .map(value => value.trim())
            .filter(Boolean);

        return selectedIds.every(id => compatible.includes(id));
    }

    function refreshDestinationSubjects() {
        const selectedIds = selectedLevelIds();
        const currentValue = subject?.value || '';
        let compatibleCount = 0;
        let currentStillValid = false;

        Array.from(subject?.options || []).forEach((option, index) => {
            if (index === 0) return;

            const compatible = optionCompatible(option, selectedIds);
            option.hidden = !compatible;
            option.disabled = !compatible;

            if (compatible) compatibleCount++;
            if (compatible && option.value === currentValue) currentStillValid = true;
        });

        if (subject?.options?.length) {
            subject.options[0].textContent = selectedIds.length === 0
                ? 'Select class level(s) first'
                : (compatibleCount > 0
                    ? 'Select compatible conventional subject'
                    : 'No compatible destination subject');
        }

        if (subject) {
            subject.disabled = selectedIds.length === 0 || compatibleCount === 0;
            if (!currentStillValid) subject.value = '';
        }

        if (warning) {
            if (selectedIds.length > 0 && compatibleCount === 0) {
                warning.hidden = false;
                warning.textContent =
                    'No conventional subject is offered across every selected class level/academic track. Configure the intended destination subject in the conventional master curriculum for those levels/tracks, or select fewer class levels.';
            } else {
                warning.hidden = true;
                warning.textContent = '';
            }
        }

        if (save) {
            save.disabled = selectedIds.length === 0 || compatibleCount === 0 || !subject?.value;
        }
    }

    levels?.addEventListener('change', refreshDestinationSubjects);
    subject?.addEventListener('change', refreshDestinationSubjects);

    form.addEventListener('submit', function(event){
        refreshDestinationSubjects();
        if (save?.disabled) {
            event.preventDefault();
        }
    });

    refreshDestinationSubjects();
})();
</script>
@include('parallel-curriculum.partials.progressive-disclosure')
<script>
(function () {
    const input = document.getElementById('parallel-workspace-filter');
    const list = document.getElementById('parallel-workspace-list');
    const count = document.getElementById('parallel-workspace-filter-count');
    if (!input || !list || !count) return;

    const cards = Array.from(list.querySelectorAll('[data-workspace-card]'));
    const refresh = () => {
        const query = input.value.trim().toLowerCase();
        let visible = 0;

        cards.forEach((card) => {
            const matches = !query || card.textContent.toLowerCase().includes(query);
            card.hidden = !matches;
            if (matches) visible++;
        });

        count.textContent = visible + ' of ' + cards.length + ' workspace(s)';
    };

    input.addEventListener('input', refresh);
    refresh();
})();
</script>
@endpush