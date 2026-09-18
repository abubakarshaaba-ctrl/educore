@extends('layouts.app')
@section('title','Parallel Curriculum Student Assignments')
@section('page-title','Parallel Curriculum')

@push('styles')
<style>
.pc-assign{max-width:1280px;margin:0 auto}.pc-nav{display:flex;gap:6px;overflow-x:auto;margin-bottom:14px}.pc-nav a{flex:0 0 auto;padding:8px 13px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);font-size:11.5px;font-weight:700;text-decoration:none}.pc-nav a.active,.pc-nav a:hover{background:var(--midnight);border-color:var(--midnight);color:#fff}
.pc-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:17px 19px;margin-bottom:14px;background:linear-gradient(135deg,#071E45,#0B2D63);border-radius:14px;color:#fff}.pc-hero h2{margin:0 0 5px;font-size:18px}.pc-hero p{max-width:800px;margin:0;color:#DCE5F2;font-size:11.5px;line-height:1.55}.pc-hero .tag{display:inline-flex;padding:5px 9px;border-radius:999px;background:rgba(255,255,255,.12);font-size:10px;font-weight:800}
.pc-panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.pc-head{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;padding:11px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.pc-body{padding:14px}
.filters{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:9px;align-items:end}.fg{display:flex;flex-direction:column;gap:5px;min-width:0}.fl{font-size:10px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:39px;border:1px solid var(--border);border-radius:8px;background:#fff;padding:8px 10px;font:500 11.5px inherit;outline:none}.fc:focus{border-color:var(--indigo)}.search{grid-column:span 2}.actions{display:flex;gap:7px;flex-wrap:wrap}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:38px;border-radius:8px;padding:8px 13px;border:0;font:700 11px inherit;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.btn-d{background:#FEF2F2;color:#B42318;border:1px solid #FECDCA}.hint{font-size:10.5px;color:var(--slate-light);line-height:1.45}
.alert-s,.alert-e{border-radius:9px;padding:10px 13px;font-size:11px;margin-bottom:12px}.alert-s{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.alert-e{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}
.assign-bar{display:grid;grid-template-columns:minmax(220px,1fr) auto;gap:10px;align-items:end;padding:12px;border:1px solid #DCE5F2;background:#F8FAFF;border-radius:10px;margin-bottom:12px}.destination-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}.assign-meta{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:7px}.metric{display:inline-flex;padding:4px 8px;border-radius:999px;background:#EEF2FF;color:#3730A3;font-size:9.5px;font-weight:800}.metric.green{background:#ECFDF3;color:#067647}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--border);border-radius:10px}.students{width:100%;min-width:780px;border-collapse:collapse}.students th{padding:9px 10px;background:var(--midnight);color:#fff;font-size:9.5px;text-align:left}.students td{padding:9px 10px;border-bottom:1px solid #EEF2F7;font-size:10.5px;color:var(--slate);vertical-align:middle}.students tr:last-child td{border-bottom:0}.students tr:hover td{background:#FAFCFF}.students .name{font-weight:800;color:var(--midnight);font-size:11px}.badge{display:inline-flex;padding:3px 7px;border-radius:999px;background:#F2F4F7;color:#475467;font-size:9px;font-weight:800}.badge.assigned{background:#ECFDF3;color:#067647}.select-cell{width:44px;text-align:center!important}.student-check{width:17px;height:17px}.empty{padding:28px;text-align:center;color:var(--slate-light);font-size:11.5px}
.selection-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:9px}.selection-tools button{border:0;background:none;color:var(--indigo);padding:0;font:800 10.5px inherit;cursor:pointer}.selection-count{margin-left:auto;font-size:10px;color:var(--slate)}
.mobile-list{display:none}.student-card{border:1px solid var(--border);border-radius:10px;padding:11px;margin-bottom:8px}.student-card-top{display:flex;align-items:flex-start;gap:9px}.student-card-main{min-width:0;flex:1}.student-card .name{font-size:12px;font-weight:800;color:var(--midnight)}.student-card .meta{font-size:10px;color:var(--slate-light);margin-top:3px;line-height:1.45}.student-card .assignment{margin-top:8px;padding-top:8px;border-top:1px solid #EEF2F7;display:flex;justify-content:space-between;gap:8px;align-items:center}
.pagination-wrap{margin-top:12px}
@media(max-width:1050px){.filters{grid-template-columns:repeat(3,minmax(0,1fr))}.search{grid-column:span 2}}
@media(max-width:720px){.filters{grid-template-columns:1fr 1fr}.search{grid-column:1/-1}.assign-bar{grid-template-columns:1fr}.destination-grid{grid-template-columns:1fr}.assign-bar .btn{width:100%}.pc-body{padding:12px}.desktop-table{display:none}.mobile-list{display:block}.selection-count{margin-left:0;width:100%}.pc-hero{padding:15px}.actions .btn{flex:1}}
@media(max-width:460px){.filters{grid-template-columns:1fr}.search{grid-column:auto}.actions{display:grid;grid-template-columns:1fr 1fr}.actions .btn{width:100%}}
</style>
@endpush

@push('styles')
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pc-assign">
    @if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert-e"><strong>Could not update assignments.</strong> {{ $errors->first() }}</div>@endif

    <div class="pc-nav">
        <a href="{{ route('scores.index') }}">Conventional Scores</a>
        <a href="{{ route('parallel-curriculum.index') }}">Parallel Curriculum</a>
        <a href="{{ route('parallel-curriculum.student-assignments', request()->query()) }}" class="active">Student Assignments</a>
        <a href="{{ route('parallel-curriculum.lifecycle.index') }}">Academic Lifecycle</a>
        <a href="{{ route('parallel-curriculum.results.index') }}">Parallel Results</a>
    </div>

    <div class="pc-hero">
        <div>
            <h2>Bulk student assignment</h2>
            <p>Use the conventional class only to find learners. Parallel curriculum placement remains independent, so learners from the same conventional class can be assigned to different parallel classes.</p>
        </div>
        <span class="tag">Bulk · Independent · Session-based</span>
    </div>

    <section class="pc-panel">
        <div class="pc-head">
            <span>1. Find students</span>
            <span class="hint">{{ $students->total() }} matching student{{ $students->total() === 1 ? '' : 's' }}</span>
        </div>
        <div class="pc-body">
            <form method="GET" action="{{ route('parallel-curriculum.student-assignments') }}">
                <div class="filters">
                    <div class="fg">
                        <label class="fl">Parallel programme</label>
                        <select class="fc" name="parallel_curriculum_id" onchange="this.form.submit()">
                            @foreach($curricula as $curriculum)
                                <option value="{{ $curriculum->id }}" @selected((int)$curriculumId === (int)$curriculum->id)>{{ $curriculum->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fl">Academic session</label>
                        <select class="fc" name="session_id" onchange="this.form.submit()">
                            @foreach($sessions as $item)
                                <option value="{{ $item->id }}" @selected((int)$sessionId === (int)$item->id)>{{ $item->name }}{{ $item->is_current ? ' · Current' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fl">Conventional class</label>
                        <select class="fc" name="conventional_class_arm_id">
                            <option value="">All conventional classes</option>
                            @foreach($classArms as $arm)
                                <option value="{{ $arm->id }}" @selected((int)$conventionalClassArmId === (int)$arm->id)>{{ $arm->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fl">Source parallel class</label>
                        <select class="fc" name="parallel_class_id">
                            <option value="">All parallel classes</option>
                            @if($selectedCurriculum)
                                @foreach($selectedCurriculum->classes as $parallelClass)
                                    <option value="{{ $parallelClass->id }}" @selected((int)$parallelClassId === (int)$parallelClass->id)>{{ $parallelClass->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fl">Assignment status</label>
                        <select class="fc" name="assignment_status">
                            <option value="all" @selected($assignmentStatus === 'all')>All students</option>
                            <option value="unassigned" @selected($assignmentStatus === 'unassigned')>Not yet assigned</option>
                            <option value="assigned" @selected($assignmentStatus === 'assigned')>Already assigned</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fl">Gender</label>
                        <select class="fc" name="gender">
                            <option value="">All</option>
                            <option value="male" @selected(strtolower($gender) === 'male')>Male</option>
                            <option value="female" @selected(strtolower($gender) === 'female')>Female</option>
                        </select>
                    </div>
                    <div class="fg search">
                        <label class="fl">Student search</label>
                        <input class="fc" name="q" value="{{ $search }}" placeholder="Name or admission number">
                    </div>
                </div>
                <div class="actions" style="margin-top:10px">
                    <button class="btn btn-p" type="submit">Apply Filters</button>
                    <a class="btn btn-s" href="{{ route('parallel-curriculum.student-assignments', ['parallel_curriculum_id'=>$curriculumId,'session_id'=>$sessionId]) }}">Clear Filters</a>
                </div>
            </form>
        </div>
    </section>

    <section class="pc-panel">
        <div class="pc-head">
            <span>2. Select learners and destination class</span>
            @if($selectedCurriculum)<span class="hint">{{ $selectedCurriculum->name }}</span>@endif
        </div>
        <div class="pc-body">
            @if(!$session)
                <div class="alert-e">Create or select an academic session before assigning students.</div>
            @elseif(!$selectedCurriculum)
                <div class="alert-e">Create a parallel curriculum before assigning students.</div>
            @elseif($selectedCurriculum->classes->isEmpty())
                <div class="alert-e">Create at least one active class under {{ $selectedCurriculum->name }} before assigning students.</div>
            @else
                <form method="POST" action="{{ route('parallel-curriculum.enrolments.store') }}" id="bulk-assignment-form">
                    @csrf

                    <div class="assign-bar">
                        <div>
                            <div class="destination-grid">
                                <div class="fg" style="margin:0">
                                    <label class="fl">Destination session</label>
                                    <select class="fc" name="session_id" required>
                                        @foreach($sessions as $item)
                                            <option value="{{ $item->id }}" @selected((int)$item->id === (int)$session->id)>{{ $item->name }}{{ $item->is_current ? ' · Current' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="fg" style="margin:0">
                                    <label class="fl">Destination parallel class</label>
                                    <select class="fc" name="parallel_curriculum_class_id" id="destination-parallel-class" required>
                                        <option value="">Choose destination class</option>
                                        @foreach($selectedCurriculum->classes as $parallelClass)
                                            <option value="{{ $parallelClass->id }}">{{ $selectedCurriculum->name }} · {{ $parallelClass->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="fg" style="margin:0">
                                    <label class="fl">Destination class arm</label>
                                    <select class="fc" name="parallel_curriculum_class_arm_id" id="destination-parallel-arm" required disabled>
                                        <option value="">Choose class first</option>
                                        @foreach($selectedCurriculum->classes as $parallelClass)
                                            @foreach($parallelClass->arms as $arm)
                                                <option value="{{ $arm->id }}" data-class-id="{{ $parallelClass->id }}" hidden disabled>
                                                    {{ $parallelClass->name }} · {{ $arm->name }}{{ $arm->capacity ? ' · Capacity '.$arm->capacity : '' }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="assign-meta">
                                <span class="metric">{{ $students->total() }} found</span>
                                <span class="metric green"><span id="selected-count">0</span>&nbsp;selected</span>
                                <span class="hint">Same-session assignment moves an existing learner. Choosing another session creates that learner's placement for the destination session while preserving the source-session record.</span>
                            </div>
                        </div>
                        <button class="btn btn-p" type="submit" id="assign-button" disabled>Assign Selected Students</button>
                    </div>

                    <div class="selection-tools">
                        <button type="button" id="select-visible">Select all visible students</button>
                        <span>·</span>
                        <button type="button" id="clear-selection">Clear selection</button>
                        <span class="selection-count">This page shows {{ $students->count() }} of {{ $students->total() }} matching students.</span>
                    </div>

                    @if($students->isEmpty())
                        <div class="empty">No active students match the selected filters.</div>
                    @else
                        <div class="table-wrap desktop-table">
                            <table class="students">
                                <thead>
                                    <tr>
                                        <th class="select-cell"><input type="checkbox" id="master-check" aria-label="Select all visible students"></th>
                                        <th>Student</th>
                                        <th>Admission No.</th>
                                        <th>Conventional Class</th>
                                        <th>Gender</th>
                                        <th>Current {{ $selectedCurriculum->name }} Class</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                        @php($assignment = $activeAssignments->get($student->id))
                                        <tr>
                                            <td class="select-cell"><input class="student-check assignment-check" type="checkbox" name="student_ids[]" value="{{ $student->id }}"></td>
                                            <td><div class="name">{{ $student->full_name }}</div></td>
                                            <td>{{ $student->admission_number }}</td>
                                            <td>{{ $student->currentClassArm?->full_name ?: 'Not assigned' }}</td>
                                            <td>{{ $student->gender ? ucfirst($student->gender) : '—' }}</td>
                                            <td>
                                                @if($assignment)
                                                    <span class="badge assigned">{{ $assignment->curriculumClass?->name ?: 'Assigned' }}{{ $assignment->curriculumClassArm ? ' · '.$assignment->curriculumClassArm->name : '' }}</span>
                                                @else
                                                    <span class="badge">Not assigned</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mobile-list">
                            @foreach($students as $student)
                                @php($assignment = $activeAssignments->get($student->id))
                                <label class="student-card">
                                    <div class="student-card-top">
                                        <input class="student-check assignment-check" type="checkbox" name="student_ids[]" value="{{ $student->id }}">
                                        <div class="student-card-main">
                                            <div class="name">{{ $student->full_name }}</div>
                                            <div class="meta">{{ $student->admission_number }} · {{ $student->currentClassArm?->full_name ?: 'No conventional class' }} @if($student->gender) · {{ ucfirst($student->gender) }}@endif</div>
                                        </div>
                                    </div>
                                    <div class="assignment">
                                        <span class="hint">Current parallel class</span>
                                        @if($assignment)
                                            <span class="badge assigned">{{ $assignment->curriculumClass?->name ?: 'Assigned' }}{{ $assignment->curriculumClassArm ? ' · '.$assignment->curriculumClassArm->name : '' }}</span>
                                        @else
                                            <span class="badge">Not assigned</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="pagination-wrap">{{ $students->links() }}</div>
                    @endif
                </form>
            @endif
        </div>
    </section>

    <section class="pc-panel">
        <div class="pc-head">
            <span>3. Import assignments from CSV or Excel</span>
            <a class="btn btn-s" href="{{ route('parallel-curriculum.student-assignments.template') }}">Download Template</a>
        </div>
        <div class="pc-body">
            @if($selectedCurriculum && $session)
                <form method="POST" action="{{ route('parallel-curriculum.student-assignments.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="parallel_curriculum_id" value="{{ $selectedCurriculum->id }}">
                    <div class="destination-grid">
                        <div class="fg">
                            <label class="fl">Destination session</label>
                            <select class="fc" name="session_id" required>
                                @foreach($sessions as $item)
                                    <option value="{{ $item->id }}" @selected((int)$item->id === (int)$session->id)>{{ $item->name }}{{ $item->is_current ? ' · Current' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fl">Assignment file</label>
                            <input class="fc" type="file" name="assignment_file" accept=".csv,.txt,.xls,.xlsx" required>
                        </div>
                    </div>
                    <div class="hint" style="margin:3px 0 10px">
                        Required columns: <strong>admission_number</strong>, <strong>parallel_class</strong> and <strong>parallel_arm</strong>. Class and arm values may use exact names or codes. The full file, class-arm relationship and capacity are validated before any assignment is written.
                    </div>
                    <button class="btn btn-p" type="submit">Import Student Assignments</button>
                </form>
            @else
                <div class="empty">Select a parallel programme and academic session before importing assignments.</div>
            @endif
        </div>
    </section>

    <section class="pc-panel">
        <div class="pc-head">How this placement works</div>
        <div class="pc-body">
            <div class="hint">
                A learner can have one active parallel class and arm per programme per academic session. Same-session reassignment moves the learner to the selected class arm. For next-session progression, use the Promotion Engine in the Academic Lifecycle workspace; manual assignment remains available for exceptional placements. Conventional class placement is never changed.
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const checks = Array.from(document.querySelectorAll('.assignment-check'));
    const count = document.getElementById('selected-count');
    const button = document.getElementById('assign-button');
    const master = document.getElementById('master-check');
    const selectVisible = document.getElementById('select-visible');
    const clear = document.getElementById('clear-selection');
    const destinationClass = document.getElementById('destination-parallel-class');
    const destinationArm = document.getElementById('destination-parallel-arm');

    function refreshDestinationArms(){
        if(!destinationClass || !destinationArm) return;
        const classId = destinationClass.value;
        let available = 0;

        Array.from(destinationArm.options).forEach((option, index) => {
            if(index === 0) return;
            const show = !!classId && option.dataset.classId === classId;
            option.hidden = !show;
            option.disabled = !show;
            if(show) available++;
        });

        destinationArm.value = '';
        destinationArm.disabled = available === 0;
        destinationArm.options[0].textContent = classId
            ? (available ? 'Choose destination arm' : 'No active arm in this class')
            : 'Choose class first';
    }

    destinationClass?.addEventListener('change', refreshDestinationArms);
    refreshDestinationArms();

    function uniqueSelectedIds(){
        return new Set(checks.filter(check => check.checked).map(check => check.value));
    }

    function syncDuplicates(source){
        checks.filter(check => check.value === source.value).forEach(check => {
            check.checked = source.checked;
        });
    }

    function refresh(){
        const selected = uniqueSelectedIds();
        if(count) count.textContent = selected.size;
        if(button) button.disabled = selected.size === 0;
        if(master) master.checked = checks.length > 0 && selected.size === new Set(checks.map(check => check.value)).size;
    }

    checks.forEach(check => check.addEventListener('change', function(){
        syncDuplicates(this);
        refresh();
    }));

    if(master) master.addEventListener('change', function(){
        checks.forEach(check => check.checked = this.checked);
        refresh();
    });

    if(selectVisible) selectVisible.addEventListener('click', function(){
        checks.forEach(check => check.checked = true);
        refresh();
    });

    if(clear) clear.addEventListener('click', function(){
        checks.forEach(check => check.checked = false);
        if(master) master.checked = false;
        refresh();
    });

    refresh();
})();
</script>
@endpush
