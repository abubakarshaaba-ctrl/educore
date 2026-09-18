@extends('layouts.app')
@section('title','Parallel Curriculum')
@section('page-title','Parallel Curriculum')

@push('styles')
<style>
.pc-shell{max-width:1240px;margin:0 auto}.pc-tabs{display:flex;gap:5px;overflow-x:auto;margin-bottom:16px}.pc-tab{flex:0 0 auto;padding:8px 14px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);font-size:12px;font-weight:700;text-decoration:none}.pc-tab.active,.pc-tab:hover{background:var(--midnight);color:#fff;border-color:var(--midnight)}
.pc-hero{padding:18px 20px;border-radius:14px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff;margin-bottom:16px}.pc-hero h2{font-size:19px;margin:0 0 5px}.pc-hero p{font-size:11.5px;line-height:1.55;color:#DCE5F2;margin:0;max-width:820px}
.pc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.pc-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;min-width:0}.pc-head{padding:12px 15px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.pc-body{padding:15px}.pc-card.full{grid-column:1/-1}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:10px;min-width:0}.fl{font-size:10.5px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:39px;border:1px solid var(--border);border-radius:8px;padding:8px 10px;background:#fff;font:500 12px inherit;outline:none}.fc:focus{border-color:var(--indigo)}select[multiple].fc{min-height:155px}.form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:0;border-radius:8px;padding:9px 14px;font:700 11.5px inherit;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.btn-d{background:#FEF2F2;color:#B42318;border:1px solid #FECDCA}.hint{font-size:10.5px;color:var(--slate-light);line-height:1.45}
.workspaces{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px}.workspace{display:block;padding:13px;border:1px solid var(--border);border-radius:10px;text-decoration:none;color:inherit;background:#fff}.workspace:hover{border-color:var(--indigo);background:#F8FAFF}.workspace strong{display:block;color:var(--midnight);font-size:12.5px}.workspace span{display:block;margin-top:4px;color:var(--slate);font-size:10.5px}.empty{padding:24px;text-align:center;color:var(--slate-light);font-size:12px}
.item{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid #EEF2F7}.item:last-child{border-bottom:0}.item-main{min-width:0}.item-main strong{display:block;color:var(--midnight);font-size:11.5px;overflow-wrap:anywhere}.item-main span{display:block;color:var(--slate-light);font-size:10px;margin-top:2px}.badge{display:inline-flex;padding:3px 7px;border-radius:999px;font-size:9.5px;font-weight:800;background:#EFF6FF;color:#1D4ED8}.badge.synced{background:#ECFDF3;color:#067647}.badge.pending{background:#FFFAEB;color:#B54708}.badge.conflict,.badge.locked{background:#FEF3F2;color:#B42318}.badge.unmapped{background:#F2F4F7;color:#475467}
.alert-s,.alert-e{border-radius:9px;padding:10px 13px;font-size:11px;margin-bottom:12px}.alert-s{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.alert-e{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.checkbox-row{display:flex;align-items:flex-start;gap:8px;font-size:11px;color:var(--slate)}.checkbox-row input{margin-top:2px}
@media(max-width:840px){.pc-grid{grid-template-columns:1fr}.pc-card.full{grid-column:auto}}@media(max-width:560px){.form-row{grid-template-columns:1fr}.pc-hero{padding:15px}.pc-body{padding:13px}.btn{width:100%}.item{flex-direction:column}}
</style>
@endpush

@section('content')
<div class="pc-shell">
    @if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert-e"><strong>Could not save.</strong> {{ $errors->first() }}</div>@endif

    <div class="pc-tabs">
        <a href="{{ route('scores.index') }}" class="pc-tab">Conventional Scores</a>
        <a href="{{ route('parallel-curriculum.index') }}" class="pc-tab active">Parallel Curriculum</a>
        @if(auth()->user()->canAccessModule('scores.view') || auth()->user()->canAccessExactModule('scores'))
            <a href="{{ route('scores.broadsheet') }}" class="pc-tab">Broadsheet</a>
        @endif
    </div>

    <div class="pc-hero">
        <h2>Parallel Curriculum Integration</h2>
        <p>Run a second curriculum independently from conventional classes, then calculate each learner's programme average and distribute it into a locked conventional destination subject according to that class level's Assessment Template.</p>
    </div>

    @if(!$currentSession || !$currentTerm)
        <div class="alert-e">Set a current academic session and term before entering or synchronizing parallel-curriculum results.</div>
    @endif

    <div class="pc-card full" style="margin-bottom:14px">
        <div class="pc-head">Parallel score workspaces @if($currentTerm) · {{ $currentTerm->name }}@endif</div>
        <div class="pc-body">
            @if($workspaces->isEmpty())
                <div class="empty">No parallel-curriculum score workspace is assigned to you yet.</div>
            @else
                <div class="workspaces">
                    @foreach($workspaces as $workspace)
                        @php($assignment=$workspace['assignment'])
                        <a class="workspace" href="{{ $currentTerm ? route('parallel-curriculum.score-sheet',['class_id'=>$workspace['class']->id,'subject_id'=>$assignment->parallel_curriculum_subject_id,'term_id'=>$currentTerm->id]) : '#' }}">
                            <strong>{{ $assignment->subject?->name }} · {{ $workspace['class']->name }}</strong>
                            <span>{{ $workspace['curriculum']->name }} @if($assignment->teacher) · {{ $assignment->teacher->name }}@endif</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if($canManage)
    <div class="pc-grid">
        <section class="pc-card">
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

        <section class="pc-card">
            <div class="pc-head">2. Create independent class</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.classes.store') }}">@csrf
                    <div class="fg"><label class="fl">Programme</label><select class="fc" name="parallel_curriculum_id" required><option value="">Select programme</option>@foreach($curricula as $curriculum)<option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>@endforeach</select></div>
                    <div class="form-row">
                        <div class="fg"><label class="fl">Class name</label><input class="fc" name="name" required placeholder="e.g. Mutawassitah 1B"></div>
                        <div class="fg"><label class="fl">Code</label><input class="fc" name="code"></div>
                    </div>
                    <div class="fg"><label class="fl">Template override (optional)</label><select class="fc" name="assessment_template_id"><option value="">Use programme default</option>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select></div>
                    <button class="btn btn-p">Create Class</button>
                </form>
            </div>
        </section>

        <section class="pc-card">
            <div class="pc-head">3. Create programme subject</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.subjects.store') }}">@csrf
                    <div class="fg"><label class="fl">Programme</label><select class="fc" name="parallel_curriculum_id" required><option value="">Select programme</option>@foreach($curricula as $curriculum)<option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>@endforeach</select></div>
                    <div class="form-row">
                        <div class="fg"><label class="fl">Subject name</label><input class="fc" name="name" required placeholder="e.g. Qur'an, Fiqh, Hadith"></div>
                        <div class="fg"><label class="fl">Code (optional)</label><input class="fc" name="code" placeholder="e.g. QRN"></div>
                    </div>
                    <div class="hint" style="margin-bottom:10px">Programme subjects are independent and do not appear in conventional subject selectors.</div>
                    <button class="btn btn-p">Save Programme Subject</button>
                </form>
            </div>
        </section>

        <section class="pc-card">
            <div class="pc-head">4. Assign subjects & teacher</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.class-subjects.store') }}">@csrf
                    <div class="fg"><label class="fl">Parallel class</label><select class="fc" name="parallel_curriculum_class_id" required><option value="">Select class</option>@foreach($curricula as $curriculum)@foreach($curriculum->classes as $class)<option value="{{ $class->id }}">{{ $curriculum->name }} · {{ $class->name }}</option>@endforeach @endforeach</select></div>
                    <div class="form-row">
                        <div class="fg"><label class="fl">Programme subject</label><select class="fc" name="parallel_curriculum_subject_id" required><option value="">Select programme subject</option>@foreach($curricula as $curriculum)@foreach($curriculum->subjects->where('is_active',true) as $subject)<option value="{{ $subject->id }}">{{ $curriculum->name }} · {{ $subject->name }}</option>@endforeach @endforeach</select><div class="hint">The selected subject must belong to the same programme as the class.</div></div>
                        <div class="fg"><label class="fl">Teacher (optional)</label><select class="fc" name="teacher_id"><option value="">Admin entry / unassigned</option>@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></div>
                    </div>
                    <button class="btn btn-p">Assign Subject</button>
                </form>
            </div>
        </section>

        <section class="pc-card">
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

        <section class="pc-card full">
            <div class="pc-head">6. Map programme average to conventional results</div>
            <div class="pc-body">
                <form method="POST" action="{{ route('parallel-curriculum.integrations.store') }}">@csrf
                    <div class="form-row">
                        <div class="fg"><label class="fl">Source programme</label><select class="fc" name="parallel_curriculum_id" required><option value="">Select programme</option>@foreach($curricula as $curriculum)<option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>@endforeach</select></div>
                        <div class="fg"><label class="fl">Destination subject</label><select class="fc" name="destination_subject_id" required><option value="">Select conventional subject</option>@foreach($conventionalSubjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select><div class="hint">Example: Islamiyyah Studies. Derived rows are locked against manual editing.</div></div>
                    </div>
                    <div class="fg"><label class="fl">Conventional class levels</label><select class="fc" name="destination_class_level_ids[]" multiple required>@foreach($classLevels as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach</select><div class="hint">Each selected class level continues to use its own Assessment Template when the composite is distributed.</div></div>
                    <div class="form-row">
                        <div class="fg"><label class="fl">Minimum completed subjects</label><input class="fc" type="number" name="minimum_completed_subjects" value="1" min="1" max="50"></div>
                        <div style="padding-top:22px">
                            <input type="hidden" name="require_all_subjects" value="0">
                            <label class="checkbox-row"><input type="checkbox" name="require_all_subjects" value="1" checked><span>Require all active parallel subjects before calculating the average.</span></label>
                            <input type="hidden" name="auto_sync" value="0">
                            <label class="checkbox-row" style="margin-top:8px"><input type="checkbox" name="auto_sync" value="1" checked><span>Automatically refresh the conventional score after parallel score entry.</span></label>
                        </div>
                    </div>
                    <button class="btn btn-p">Save Integration Mapping</button>
                </form>
            </div>
        </section>

        <section class="pc-card">
            <div class="pc-head">Current programme structure</div>
            <div class="pc-body">
                @forelse($curricula as $curriculum)
                    <div class="item"><div class="item-main"><strong>{{ $curriculum->name }}</strong><span>Default: {{ $curriculum->defaultAssessmentTemplate?->name ?: 'No template' }} · Subjects: {{ $curriculum->subjects->where('is_active',true)->pluck('name')->join(', ') ?: 'None yet' }}</span></div></div>
                    @foreach($curriculum->classes as $class)
                        <div style="padding:8px 0 8px 12px;border-bottom:1px solid #F1F5F9">
                            <div style="font-size:11px;font-weight:800;color:var(--midnight)">{{ $class->name }}</div>
                            @forelse($class->subjectAssignments->where('is_active',true) as $assignment)
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:5px">
                                    <span style="font-size:10px;color:var(--slate)">{{ $assignment->subject?->name }} @if($assignment->teacher) · {{ $assignment->teacher->name }}@endif</span>
                                    <form method="POST" action="{{ route('parallel-curriculum.class-subjects.destroy',$assignment) }}">@csrf @method('DELETE')<button class="btn btn-d" style="padding:4px 7px">Remove</button></form>
                                </div>
                            @empty
                                <div class="hint" style="margin-top:4px">No subjects yet.</div>
                            @endforelse
                        </div>
                    @endforeach
                @empty
                    <div class="empty">Create the first programme above.</div>
                @endforelse
            </div>
        </section>

        <section class="pc-card">
            <div class="pc-head">Conventional result mappings</div>
            <div class="pc-body">
                @forelse($integrations as $rule)
                    <div class="item"><div class="item-main"><strong>{{ $rule->curriculum?->name }} → {{ $rule->destinationSubject?->name }}</strong><span>{{ $rule->destinationClassLevel?->name }} · {{ $rule->require_all_subjects ? 'All subjects required' : 'Minimum '.$rule->minimum_completed_subjects }} · {{ $rule->auto_sync ? 'Auto sync' : 'Manual sync' }}</span></div><span class="badge">Active</span></div>
                @empty
                    <div class="empty">No integration rules configured yet.</div>
                @endforelse
            </div>
        </section>

        <section class="pc-card full">
            <div class="pc-head">Current student parallel-class assignments @if($currentSession) · {{ $currentSession->name }}@endif</div>
            <div class="pc-body">
                @forelse($enrolments as $enrolment)
                    <div class="item">
                        <div class="item-main"><strong>{{ $enrolment->student?->full_name }} · {{ $enrolment->curriculumClass?->name }}</strong><span>{{ $enrolment->curriculum?->name }} · Conventional: {{ $enrolment->student?->currentClassArm?->full_name ?: 'Not assigned' }}</span></div>
                        <form method="POST" action="{{ route('parallel-curriculum.enrolments.destroy',$enrolment) }}">@csrf @method('DELETE')<button class="btn btn-d">Remove</button></form>
                    </div>
                @empty
                    <div class="empty">No student has been assigned to a parallel class for the current session.</div>
                @endforelse
            </div>
        </section>

        <section class="pc-card full">
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
