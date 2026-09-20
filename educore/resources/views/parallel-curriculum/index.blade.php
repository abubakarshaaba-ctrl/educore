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
    @if($errors->any())<div class="alert-e" data-parallel-validation-error><strong>Could not save.</strong> {{ $errors->first() }}</div>@endif
    @if($canManage && $schemaReconciliationPending)
        <div class="alert-e">
            <strong>Database update pending.</strong>
            The parallel-curriculum workspace is being rendered in compatibility mode because one or more newer schema components are not yet available. Deploy the latest master build and allow the database migrations to complete before making configuration changes.
        </div>
    @endif

    @include('parallel-curriculum.partials.module-navigation')

    <div class="pc-hero">
        <h2>Parallel Curriculum Integration</h2>
        <p>Run a second curriculum independently from conventional classes, then calculate each learner's programme average and distribute it into a locked conventional destination subject according to that class level's Assessment Template.</p>
    </div>

    <section class="pc-card full" style="margin-bottom:14px" id="parallel-score-entry">
        <div class="pc-head">Open Parallel Score Entry Sheet</div>
        <div class="pc-body">
            @if($showQuickWorkspaceSelector)
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
            @else
                <div class="score-entry-help" style="margin-top:0">
                    This account currently has {{ $workspaces->count() }} parallel score workspaces. The large dropdown selector is hidden to keep this page fast and responsive. Use <strong>Assigned parallel score workspaces</strong> below to search by programme, class, arm, subject or teacher, then open the required score sheet directly.
                </div>
                <a class="btn btn-p" href="#parallel-workspaces" style="margin-top:10px">Find a Score Workspace</a>
            @endif
        </div>
    </section>

    @if($canManage || $canViewOperations)
    <section class="pc-card full" style="margin-bottom:14px">
        <div class="pc-head">Parallel curriculum operations</div>
        <div class="pc-body">
            <div class="pc-feature-grid">
                @if($canManage)
                <a class="pc-feature-link" href="{{ route('parallel-curriculum.setup') }}">
                    <strong>Programme setup & integration</strong>
                    <span>Create programmes, levels and subjects, assign subjects to classes, configure standalone grading and map programme averages into compatible conventional subjects.</span>
                    <small>Programme Setup</small>
                </a>
                <a class="pc-feature-link" href="{{ route('parallel-curriculum.lifecycle.index') }}#teaching-assignment-model">
                    <strong>Subject & class-teacher assignment</strong>
                    <span>Choose one class teacher for every subject in an arm, or assign teachers subject-by-subject. The same teacher may teach the same subject across several classes/arms when timetable times do not clash.</span>
                    <small>Academic Lifecycle</small>
                </a>
                <a class="pc-feature-link" href="{{ route('parallel-curriculum.class-list') }}">
                    <strong>Parallel class list</strong>
                    <span>Generate a session-based learner register by programme, parallel class and class arm, with conventional placement shown only as a reference.</span>
                    <small>Class List</small>
                </a>
                <a class="pc-feature-link" href="{{ route('parallel-curriculum.teacher-list') }}">
                    <strong>Parallel teacher list</strong>
                    <span>Review the effective teaching roster after class-teacher mode, default subject teachers and arm-specific subject overrides are resolved.</span>
                    <small>Teacher List</small>
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

    <div class="pc-card full" style="margin-bottom:14px" id="parallel-workspaces">
        <div class="pc-head">{{ $canManage ? 'Available' : 'Assigned' }} parallel score workspaces @if($currentTerm) · {{ $currentTerm->name }}@endif</div>
        <div class="pc-body">
            @if($workspaces->isEmpty())
                <div class="empty">{{ $canManage ? 'No parallel score workspace is configured yet.' : 'No parallel score workspace is assigned to this account yet.' }}</div>
            @else
                <form method="GET" action="{{ route('parallel-curriculum.index') }}" class="pc-workspace-filter" id="parallel-workspace-filter-form">
                    <input
                        type="search"
                        name="workspace_q"
                        id="parallel-workspace-filter"
                        value="{{ $workspaceSearch }}"
                        placeholder="Filter by programme, class, arm, subject or teacher"
                        autocomplete="off"
                    >
                    <div class="pc-filter-actions">
                        <button class="btn btn-s" type="submit">Filter</button>
                        @if($workspaceSearch !== '')
                            <a class="btn btn-s" href="{{ route('parallel-curriculum.index') }}">Clear</a>
                        @endif
                    </div>
                    <span class="pc-filter-count">
                        {{ $workspacePaginator?->total() ?? 0 }} matching · {{ $workspaces->count() }} total
                    </span>
                </form>

                @if(($workspacePaginator?->count() ?? 0) === 0)
                    <div class="empty">No score workspace matches “{{ $workspaceSearch }}”.</div>
                @else
                    <div class="workspaces" id="parallel-workspace-list">
                        @foreach($workspacePaginator as $workspace)
                            <article class="workspace" data-workspace-card>
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

                    @if($workspacePaginator?->hasPages())
                        <div class="pagination-wrap" style="margin-top:14px">
                            {{ $workspacePaginator->links() }}
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </div>

    @if($canManage)
    <section class="pc-card full" style="margin-bottom:14px">
        <div class="pc-head">Administration</div>
        <div class="pc-body">
            <div class="score-entry-help" style="margin-top:0">
                Programme structure, grading, conventional-result mapping and configuration controls now live in the dedicated
                <a href="{{ route('parallel-curriculum.setup') }}"><strong>Programme Setup</strong></a> workspace.
                This page remains focused on score entry, assigned workspaces and operational navigation.
            </div>
        </div>
    </section>
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