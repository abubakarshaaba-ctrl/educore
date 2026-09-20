@extends('layouts.app')
@section('title','Parallel Curriculum')
@section('page-title','Parallel Curriculum')

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
    @if($errors->any())<div class="alert-e"><strong>Could not save.</strong> {{ $errors->first() }}</div>@endif
    @if($canManage && $schemaReconciliationPending)
        <div class="alert-e">
            <strong>Database update pending.</strong>
            The parallel-curriculum workspace is being rendered in compatibility mode because one or more newer schema components are not yet available. Deploy the latest master build and allow the database migrations to complete before making configuration changes.
        </div>
    @endif

    <div class="pc-tabs">
        <a href="{{ route('scores.index') }}" class="pc-tab">Conventional Scores</a>
        <a href="{{ route('parallel-curriculum.index') }}" class="pc-tab active">Parallel Curriculum</a>
        @if($canManage)
            <a href="{{ route('parallel-curriculum.student-assignments') }}" class="pc-tab">Student Assignments</a>
            <a href="{{ route('parallel-curriculum.lifecycle.index') }}" class="pc-tab">Academic Lifecycle</a>
        @endif
        @if($canViewOperations)
            <a href="{{ route('parallel-curriculum.operations.index') }}" class="pc-tab">Timetable & Attendance</a>
        @endif
        @if($canManage)
            <a href="{{ route('parallel-curriculum.results.index') }}" class="pc-tab">Parallel Results</a>
        @endif
        @if(auth()->user()->canAccessModule('scores.view') || auth()->user()->canAccessExactModule('scores'))
            <a href="{{ route('scores.broadsheet') }}" class="pc-tab">Broadsheet</a>
        @endif
    </div>

    <div class="pc-hero">
        <h2>Parallel Curriculum Integration</h2>
        <p>Run a second curriculum independently from conventional classes, then calculate each learner's programme average and distribute it into a locked conventional destination subject according to that class level's Assessment Template.</p>
    </div>

    <section class="pc-card full" style="margin-bottom:14px" id="parallel-score-entry">
        <div class="pc-head">Open Parallel Score Entry Sheet</div>
        <div class="pc-body">
            <form method="GET" action="{{ route('parallel-curriculum.score-sheet') }}" id="parallel-score-entry-form">
                <input type="hidden" name="class_id" id="parallel-score-class-id">
                <input type="hidden" name="arm_id" id="parallel-score-arm-id">

                <div class="score-entry-grid">
                    <div class="fg">
                        <label class="fl" for="parallel-score-arm">Parallel class arm</label>
                        <select class="fc" id="parallel-score-arm" {{ $workspaces->isEmpty() ? 'disabled' : '' }} required>
                            <option value="">Select class arm</option>
                            @foreach($workspaces->unique(fn ($workspace) => $workspace['class_id'].':'.($workspace['arm_id'] ?? 0))->values() as $workspace)
                                @php($workspaceKey=$workspace['class_id'].':'.($workspace['arm_id'] ?? 0))
                                <option
                                    value="{{ $workspaceKey }}"
                                    data-class-id="{{ $workspace['class_id'] }}"
                                    data-arm-id="{{ $workspace['arm_id'] ?? '' }}"
                                >
                                    {{ $workspace['curriculum_name'] }} · {{ $workspace['class_label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fg">
                        <label class="fl" for="parallel-score-subject">Parallel subject</label>
                        <select class="fc" id="parallel-score-subject" name="subject_id" disabled required>
                            <option value="">Select class arm first</option>
                            @foreach($workspaces as $workspace)
                                @php($workspaceKey=$workspace['class_id'].':'.($workspace['arm_id'] ?? 0))
                                <option
                                    value="{{ $workspace['subject_id'] }}"
                                    data-workspace-key="{{ $workspaceKey }}"
                                    hidden
                                    disabled
                                >
                                    {{ $workspace['subject_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fg">
                        <label class="fl" for="parallel-score-term">Term</label>
                        <select class="fc" id="parallel-score-term" name="term_id" {{ $scoreTerms->isEmpty() ? 'disabled' : '' }} required>
                            <option value="">Select term</option>
                            @foreach($scoreTerms as $termOption)
                                <option value="{{ $termOption->id }}" @selected((int)($currentTerm?->id ?? 0)===(int)$termOption->id)>
                                    {{ $termOption->name }} · {{ $currentSession?->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($workspaces->isEmpty())
                    @if($canManage)
                        <div class="score-entry-help">
                            No active parallel class-arm/subject score workspace exists yet. Create programme subjects, explicitly assign the required subjects to each parallel class, create at least one active arm, and configure the teaching assignment model.
                        </div>
                    @elseif(($parallelFormTeacherArms ?? collect())->isNotEmpty())
                        <div class="score-entry-help">
                            You are already assigned as the all-subject class teacher for
                            <strong>{{ ($parallelFormTeacherArms ?? collect())->map(fn($arm) => ($arm->curriculumClass?->name ?? 'Class').' '.$arm->name)->implode(', ') }}</strong>,
                            but those class(es) currently have no active class-subject assignment that can generate a score workspace. An academic administrator must explicitly assign the required subjects to the class under <strong>Parallel Curriculum → Assign subjects to parallel class</strong>. Once assigned, only those class subjects will appear here.
                        </div>
                    @else
                        <div class="score-entry-help">
                            This account has parallel score-entry access, but no parallel class-arm/subject assignment currently resolves to this staff ID. Conventional teaching assignments do not automatically assign parallel subjects. In subject-based mode, only the specific subjects/classes assigned to this teacher will appear here.
                        </div>
                    @endif
                @else
                    <div class="score-entry-help">
                        Only parallel class arms and subjects assigned to this account are listed here. Conventional score-entry classes and subjects remain separate.
                    </div>
                @endif

                <div style="margin-top:12px">
                    <button class="btn btn-p" type="submit" id="parallel-score-open" {{ $workspaces->isEmpty() || $scoreTerms->isEmpty() ? 'disabled' : '' }}>
                        Open Parallel Score Sheet
                    </button>
                </div>
            </form>
        </div>
    </section>

    @if($canManage || $canViewOperations)
    <section class="pc-card full" style="margin-bottom:14px">
        <div class="pc-head">Parallel curriculum operations</div>
        <div class="pc-body">
            <div class="pc-feature-grid">
                @if($canManage)
                <a class="pc-feature-link" href="{{ route('parallel-curriculum.lifecycle.index') }}#teaching-assignment-model">
                    <strong>Subject & class-teacher assignment</strong>
                    <span>Choose one class teacher for every subject in an arm, or assign teachers subject-by-subject. The same teacher may teach the same subject across several classes/arms when timetable times do not clash.</span>
                    <small>Academic Lifecycle</small>
                </a>
                <a class="pc-feature-link" href="{{ route('parallel-curriculum.operations.index') }}#working-week">
                    <strong>Days of the week</strong>
                    <span>Set an independent Monday–Sunday working week for the parallel curriculum. Each day can be enabled or disabled without changing the conventional curriculum.</span>
                    <small>Timetable & Attendance</small>
                </a>
                <a class="pc-feature-link" href="{{ route('parallel-curriculum.operations.index') }}#working-week">
                    <strong>Daily work hours</strong>
                    <span>Configure a different resumption time, closing time and late grace period for every enabled parallel-curriculum working day.</span>
                    <small>Timetable & Attendance</small>
                </a>
                @endif
                @if($canViewOperations)
                <a class="pc-feature-link" href="{{ route('parallel-curriculum.operations.index') }}#parallel-timetable">
                    <strong>Parallel timetable & learner attendance</strong>
                    <span>View the arm timetable resolved from the effective teacher assignment and manage learner attendance independently on enabled parallel working days.</span>
                    <small>Timetable & Attendance</small>
                </a>
                <a class="pc-feature-link" href="{{ route('parallel-curriculum.operations.index') }}#staff-attendance">
                    <strong>Parallel staff attendance</strong>
                    <span>Assigned parallel teachers can clock in and out against the selected day's own hours. Arrival and departure status remain separate from conventional staff attendance.</span>
                    <small>Timetable & Attendance</small>
                </a>
                @endif
            </div>
        </div>
    </section>
    @endif

    @if(!$currentSession || !$currentTerm)
        <div class="alert-e">Set a current academic session and term before entering or synchronizing parallel-curriculum results.</div>
    @endif

    <div class="pc-card full" style="margin-bottom:14px">
        <div class="pc-head">{{ $canManage ? 'Available' : 'Assigned' }} parallel score workspaces @if($currentTerm) · {{ $currentTerm->name }}@endif</div>
        <div class="pc-body">
            @if($workspaces->isEmpty())
                <div class="empty">{{ $canManage ? 'No parallel score workspace is configured yet.' : 'No parallel score workspace is assigned to this account yet.' }}</div>
            @else
                <div class="workspaces">
                    @foreach($workspaces as $workspace)
                        <article class="workspace">
                            <strong>{{ $workspace['subject_name'] }} · {{ $workspace['class_label'] }}</strong>
                            <span>
                                {{ $workspace['curriculum_name'] }}
                                @if(!empty($workspace['effective_teacher_name']))
                                    · {{ $workspace['effective_teacher_name'] }}
                                @else
                                    · Admin / unassigned
                                @endif
                                @if(!empty($workspace['arm']))
                                    · Arm-specific roster
                                @endif
                            </span>
                            @if(!empty($workspace['is_form_teacher']))
                                <span class="workspace-badge">Form Teacher · All Subjects</span>
                            @endif
                            <div class="workspace-actions">
                                <a class="workspace-open" href="{{ $workspace['score_sheet_url'] }}">Open Score Sheet</a>
                                @if(!empty($workspace['attendance_url']))
                                    <a class="workspace-secondary" href="{{ $workspace['attendance_url'] }}">Mark Attendance</a>
                                @endif
                                @if(!empty($workspace['form_teacher_comments_url']))
                                    <a class="workspace-secondary" href="{{ $workspace['form_teacher_comments_url'] }}">Form Teacher Comments</a>
                                @endif
                            </div>
                        </article>
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

        <section class="pc-card">
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

        <section class="pc-card">
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

        <section class="pc-card full">
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

        <section class="pc-card">
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

        <section class="pc-card">
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
</script>
@endpush
