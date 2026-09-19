@extends('layouts.app')
@section('title','Parallel Timetable & Attendance')
@section('page-title','Parallel Timetable & Attendance')

@push('styles')
<style>
.pco{max-width:1280px;margin:0 auto}.tabs{display:flex;gap:6px;overflow-x:auto;margin-bottom:14px}.tab{flex:0 0 auto;padding:8px 13px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);font-size:11.5px;font-weight:700;text-decoration:none}.tab.active,.tab:hover{background:var(--midnight);color:#fff;border-color:var(--midnight)}
.hero{padding:17px 19px;margin-bottom:14px;border-radius:14px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff}.hero h2{margin:0 0 5px;font-size:18px}.hero p{margin:0;max-width:900px;color:#DCE5F2;font-size:11.5px;line-height:1.5}
.panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.head{padding:11px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}.head strong{font-size:12px;color:var(--midnight)}.head span{font-size:10px;color:var(--slate-light)}.body{padding:14px}
.filters{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:9px;align-items:end}.fg{display:flex;flex-direction:column;gap:5px;min-width:0}.fl{font-size:10px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:39px;border:1px solid var(--border);border-radius:8px;background:#fff;padding:8px 10px;font:500 11.5px inherit}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;border-radius:8px;padding:8px 13px;border:0;font:700 11px inherit;cursor:pointer;text-decoration:none}.p{background:var(--indigo);color:#fff}.s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.d{background:#FEF2F2;color:#B42318;border:1px solid #FECDCA}.hint{font-size:10px;color:var(--slate-light);line-height:1.45}
.grid{display:grid;grid-template-columns:1.1fr .9fr;gap:14px}.row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.row.three{grid-template-columns:repeat(3,minmax(0,1fr))}.alert{border-radius:9px;padding:10px 13px;font-size:11px;margin-bottom:12px}.ok{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.err{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}
.day{margin-bottom:10px;border:1px solid var(--border);border-radius:10px;overflow:hidden}.day:last-child{margin-bottom:0}.day-title{padding:8px 10px;background:#F8FAFC;color:var(--midnight);font-size:10.5px;font-weight:800}.period{display:grid;grid-template-columns:105px minmax(0,1fr) minmax(130px,.7fr) auto;gap:9px;align-items:center;padding:9px 10px;border-top:1px solid #EEF2F7}.period:first-of-type{border-top:0}.period strong{display:block;font-size:11px;color:var(--midnight)}.period span{display:block;font-size:9.5px;color:var(--slate-light);margin-top:2px}.empty{padding:20px;text-align:center;color:var(--slate-light);font-size:11px}
.att-summary{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:10px}.chip{padding:4px 8px;border-radius:999px;background:#F2F4F7;color:#475467;font-size:9.5px;font-weight:800}.att-table-wrap{overflow-x:auto;border:1px solid var(--border);border-radius:10px}.att{width:100%;min-width:690px;border-collapse:collapse}.att th{padding:8px 9px;background:var(--midnight);color:#fff;text-align:left;font-size:9.5px}.att td{padding:8px 9px;border-bottom:1px solid #EEF2F7;font-size:10.5px;color:var(--slate)}.att tr:last-child td{border-bottom:0}.name{font-size:11px;font-weight:800;color:var(--midnight)}
@media(max-width:1050px){.filters{grid-template-columns:repeat(3,minmax(0,1fr))}.grid{grid-template-columns:1fr}}
@media(max-width:700px){.filters,.row,.row.three{grid-template-columns:1fr 1fr}.period{grid-template-columns:90px minmax(0,1fr) auto}.period .teacher{grid-column:2/3}.hero,.body{padding:13px}}
@media(max-width:500px){.filters,.row,.row.three{grid-template-columns:1fr}.btn{width:100%}.period{grid-template-columns:1fr auto}.period .time{grid-column:1/-1}.period .teacher{grid-column:1/2}}
</style>
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pco">
@if(session('success'))<div class="alert ok">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert err"><strong>Could not complete the operation.</strong> {{ $errors->first() }}</div>@endif

<div class="tabs">
    <a class="tab" href="{{ route('scores.index') }}">Conventional Scores</a>
    <a class="tab" href="{{ route('parallel-curriculum.index') }}">Parallel Curriculum</a>
    <a class="tab" href="{{ route('parallel-curriculum.student-assignments') }}">Student Assignments</a>
    <a class="tab" href="{{ route('parallel-curriculum.lifecycle.index') }}">Academic Lifecycle</a>
    <a class="tab active" href="{{ route('parallel-curriculum.operations.index') }}">Timetable & Attendance</a>
    <a class="tab" href="{{ route('parallel-curriculum.results.index') }}">Parallel Results</a>
</div>

<div class="hero">
    <h2>Parallel Timetable & Attendance</h2>
    <p>Run the parallel programme on its own working week, day-specific resumption and closing times, timetable and staff attendance rules. Parallel learner and staff attendance remain separate from the conventional curriculum while teacher timetable clashes are still prevented across both systems.</p>
</div>

<section class="panel">
    <div class="head"><strong>Working context</strong><span>Programme · session · term · level · arm · date</span></div>
    <div class="body">
        <form method="GET" action="{{ route('parallel-curriculum.operations.index') }}" class="filters">
            <div class="fg"><label class="fl">Parallel programme</label><select class="fc" name="parallel_curriculum_id">@foreach($curricula as $item)<option value="{{ $item->id }}" @selected((int)$curriculumId===(int)$item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="fg"><label class="fl">Session</label><select class="fc" name="session_id">@foreach($sessions as $item)<option value="{{ $item->id }}" @selected((int)$sessionId===(int)$item->id)>{{ $item->name }}{{ $item->is_current?' · Current':'' }}</option>@endforeach</select></div>
            <div class="fg"><label class="fl">Term</label><select class="fc" name="term_id">@foreach($terms as $item)<option value="{{ $item->id }}" @selected((int)$termId===(int)$item->id)>{{ $item->session?->name }} · {{ $item->name }}</option>@endforeach</select></div>
            <div class="fg"><label class="fl">Parallel level</label><select class="fc" name="class_id">@foreach(($selectedCurriculum?->classes ?? collect()) as $item)<option value="{{ $item->id }}" @selected((int)$classId===(int)$item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="fg"><label class="fl">Arm</label><select class="fc" name="arm_id">@foreach(($selectedClass?->arms ?? collect()) as $item)<option value="{{ $item->id }}" @selected((int)$armId===(int)$item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="fg"><label class="fl">Attendance date</label><input class="fc" type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}"></div>
            <button class="btn p" type="submit" style="grid-column:1/-1">Load Parallel Operations</button>
        </form>
    </div>
</section>

@if($selectedCurriculum)
<section id="working-week" class="panel">
    <div class="head">
        <div>
            <strong>Parallel working days & staff attendance hours</strong><br>
            <span>Independent from the conventional curriculum · each day can have different resumption and closing times</span>
        </div>
        <span>{{ $workingDays->where('is_working',true)->count() }} active day(s)</span>
    </div>
    <div class="body">
        @if($canManageTimetable)
        <form method="POST" action="{{ route('parallel-curriculum.operations.working-days.save') }}">
            @csrf
            <input type="hidden" name="parallel_curriculum_id" value="{{ $selectedCurriculum->id }}">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:9px">
                @foreach($workingDays as $dayConfig)
                    @php($dayKey=(string)$dayConfig->day_of_week)
                    <div style="border:1px solid #E4E7EC;border-radius:10px;padding:10px;background:#FCFCFD">
                        <input type="hidden" name="days[{{ $dayKey }}][is_working]" value="0">
                        <label style="display:flex;align-items:center;gap:8px;font-size:11px;font-weight:800;color:var(--midnight);margin-bottom:9px">
                            <input type="checkbox" name="days[{{ $dayKey }}][is_working]" value="1" @checked($dayConfig->is_working)>
                            {{ ucfirst($dayKey) }} is a working day
                        </label>
                        <div class="row">
                            <div class="fg" style="margin:0">
                                <label class="fl">Resumption</label>
                                <input class="fc" type="time" name="days[{{ $dayKey }}][resumption_time]" value="{{ $dayConfig->resumption_time ? substr((string)$dayConfig->resumption_time,0,5) : '' }}">
                            </div>
                            <div class="fg" style="margin:0">
                                <label class="fl">Closing</label>
                                <input class="fc" type="time" name="days[{{ $dayKey }}][closing_time]" value="{{ $dayConfig->closing_time ? substr((string)$dayConfig->closing_time,0,5) : '' }}">
                            </div>
                        </div>
                        <div class="fg" style="margin:9px 0 0">
                            <label class="fl">Late grace period · minutes</label>
                            <input class="fc" type="number" min="0" max="180" name="days[{{ $dayKey }}][grace_minutes]" value="{{ (int)$dayConfig->grace_minutes }}">
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="hint" style="margin:10px 0">
                Timetable periods can only be created inside the enabled day's resumption–closing window. Staff arrival status uses that day's resumption plus grace period; early departure uses that day's closing time.
            </div>
            <button class="btn p" type="submit">Save Parallel Working Week</button>
        </form>
        @else
            <div style="display:flex;gap:7px;flex-wrap:wrap">
                @foreach($workingDays->where('is_working',true) as $dayConfig)
                    <span class="chip">
                        {{ ucfirst($dayConfig->day_of_week) }} ·
                        {{ substr((string)$dayConfig->resumption_time,0,5) }}–{{ substr((string)$dayConfig->closing_time,0,5) }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section id="staff-attendance" class="panel">
    @php($parallelStaffSchedule=$staffAttendance['schedule'] ?? null)
    @php($selfParallelRecord=$staffAttendance ? $staffAttendance['records']->get(auth()->id()) : null)
    <div class="head">
        <div>
            <strong>Parallel staff attendance</strong><br>
            <span>{{ $date }} · separate from conventional staff attendance</span>
        </div>
        <span>
            @if($parallelStaffSchedule && $parallelStaffSchedule->is_working)
                {{ ucfirst($staffAttendance['day_of_week']) }} ·
                {{ substr((string)$parallelStaffSchedule->resumption_time,0,5) }}–{{ substr((string)$parallelStaffSchedule->closing_time,0,5) }}
            @else
                Non-working day
            @endif
        </span>
    </div>
    <div class="body">
        @if($parallelStaffSchedule && $parallelStaffSchedule->is_working)
            <div class="att-summary">
                <span class="chip">Resumption {{ substr((string)$parallelStaffSchedule->resumption_time,0,5) }}</span>
                <span class="chip">Closing {{ substr((string)$parallelStaffSchedule->closing_time,0,5) }}</span>
                <span class="chip">Grace {{ (int)$parallelStaffSchedule->grace_minutes }} min</span>
            </div>
        @endif

        @if($canClockParallelStaff && $date===now()->toDateString() && $parallelStaffSchedule?->is_working)
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                @if(!$selfParallelRecord?->clock_in_time)
                    <form method="POST" action="{{ route('parallel-curriculum.operations.staff-attendance.clock-in') }}">
                        @csrf
                        <input type="hidden" name="parallel_curriculum_id" value="{{ $selectedCurriculum->id }}">
                        <button class="btn p" type="submit">Clock In · Parallel Curriculum</button>
                    </form>
                @elseif(!$selfParallelRecord?->clock_out_time)
                    <span class="chip">Clocked in {{ substr((string)$selfParallelRecord->clock_in_time,0,5) }} · {{ ucfirst($selfParallelRecord->status) }}</span>
                    <form method="POST" action="{{ route('parallel-curriculum.operations.staff-attendance.clock-out') }}">
                        @csrf
                        <input type="hidden" name="parallel_curriculum_id" value="{{ $selectedCurriculum->id }}">
                        <button class="btn s" type="submit">Clock Out · Parallel Curriculum</button>
                    </form>
                @else
                    <span class="chip">
                        Completed · {{ substr((string)$selfParallelRecord->clock_in_time,0,5) }}–{{ substr((string)$selfParallelRecord->clock_out_time,0,5) }}
                        · {{ ucfirst(str_replace('_',' ',$selfParallelRecord->departure_status ?? '')) }}
                    </span>
                @endif
            </div>
        @elseif($canClockParallelStaff && $date===now()->toDateString() && !$parallelStaffSchedule?->is_working)
            <div class="hint" style="margin-bottom:10px">Today is not enabled as a parallel-curriculum working day.</div>
        @endif

        @if($staffAttendance && $staffAttendance['staff']->isNotEmpty())
            <div class="att-table-wrap">
                <table class="att">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Arrival</th>
                            <th>Status</th>
                            <th>Departure</th>
                            <th>Departure status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($staffAttendance['staff'] as $person)
                        @php($staffRecord=$staffAttendance['records']->get($person->id))
                        <tr>
                            <td><span class="name">{{ $person->name }}</span></td>
                            <td>{{ $staffRecord?->clock_in_time ? substr((string)$staffRecord->clock_in_time,0,5) : '—' }}</td>
                            <td>{{ $staffRecord?->status ? ucfirst($staffRecord->status) : 'Not recorded' }}</td>
                            <td>{{ $staffRecord?->clock_out_time ? substr((string)$staffRecord->clock_out_time,0,5) : '—' }}</td>
                            <td>{{ $staffRecord?->departure_status ? ucfirst(str_replace('_',' ',$staffRecord->departure_status)) : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="hint">No active staff member is currently resolved as a teacher in this parallel curriculum.</div>
        @endif
    </div>
</section>
@endif

@if(!$selectedCurriculum || !$selectedClass || !$selectedArm)
<section class="panel"><div class="empty">Create a parallel programme, class level and active arm before configuring timetable or attendance.</div></section>
@else
<div class="grid">
    <section id="parallel-timetable" class="panel">
        <div class="head"><div><strong>Weekly parallel timetable</strong><br><span>{{ $selectedCurriculum->name }} · {{ $selectedClass->name }} {{ $selectedArm->name }}</span></div><span>{{ $periods->count() }} period(s)</span></div>
        <div class="body">
            @if($canManageTimetable)
            <form method="POST" action="{{ route('parallel-curriculum.operations.periods.store') }}" style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #EEF2F7">
                @csrf
                <input type="hidden" name="parallel_curriculum_class_id" value="{{ $selectedClass->id }}">
                <input type="hidden" name="parallel_curriculum_class_arm_id" value="{{ $selectedArm->id }}">
                <input type="hidden" name="session_id" value="{{ $sessionId }}">
                <div class="row">
                    <div class="fg"><label class="fl">Subject</label><select class="fc" name="parallel_curriculum_subject_id" required><option value="">Select subject</option>@foreach($selectedClass->subjectAssignments as $assignment)<option value="{{ $assignment->parallel_curriculum_subject_id }}">{{ $assignment->subject?->name }}</option>@endforeach</select></div>
                    <div class="fg"><label class="fl">Day</label><select class="fc" name="day_of_week" required>@forelse($workingDays->where('is_working',true) as $dayConfig)<option value="{{ $dayConfig->day_of_week }}">{{ ucfirst($dayConfig->day_of_week) }} · {{ substr((string)$dayConfig->resumption_time,0,5) }}–{{ substr((string)$dayConfig->closing_time,0,5) }}</option>@empty<option value="" disabled>No parallel working day enabled</option>@endforelse</select></div>
                </div>
                <div class="row three">
                    <div class="fg"><label class="fl">Start</label><input class="fc" type="time" name="start_time" required></div>
                    <div class="fg"><label class="fl">End</label><input class="fc" type="time" name="end_time" required></div>
                    <div class="fg"><label class="fl">Venue</label><input class="fc" name="venue" maxlength="100" placeholder="Optional"></div>
                </div>
                <div class="hint" style="margin-bottom:9px">Teacher is resolved automatically from the arm-specific override or the class-level default assignment. Teacher clashes are checked against both timetable systems.</div>
                @if($workingDays->where('is_working',true)->isEmpty())
                    <div class="alert err">Enable at least one parallel working day before adding timetable periods.</div>
                @else
                    <button class="btn p" type="submit">Add Period</button>
                @endif
            </form>
            @endif

            @foreach($workingDays as $dayConfig)
                @php($key=(string)$dayConfig->day_of_week)
                @php($dayPeriods=$periods->where('day_of_week',$key))
                <div class="day">
                    <div class="day-title">
                        {{ ucfirst($key) }} ·
                        @if($dayConfig->is_working)
                            {{ substr((string)$dayConfig->resumption_time,0,5) }}–{{ substr((string)$dayConfig->closing_time,0,5) }}
                        @else
                            Not a working day
                        @endif
                    </div>
                    @forelse($dayPeriods as $period)
                        <div class="period">
                            <div class="time"><strong>{{ substr((string)$period->start_time,0,5) }}–{{ substr((string)$period->end_time,0,5) }}</strong><span>{{ $period->venue ?: 'No venue' }}</span></div>
                            <div><strong>{{ $period->subject?->name ?: 'Subject' }}</strong><span>{{ $selectedClass->name }} {{ $selectedArm->name }}</span></div>
                            <div class="teacher"><strong>{{ $period->teacher?->name ?: 'Teacher unassigned' }}</strong><span>Effective teacher</span></div>
                            @if($canManageTimetable)<form method="POST" action="{{ route('parallel-curriculum.operations.periods.destroy',$period) }}">@csrf @method('DELETE')<button class="btn d" type="submit">Remove</button></form>@endif
                        </div>
                    @empty
                        <div class="empty">No period scheduled.</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </section>

    <section id="learner-attendance" class="panel">
        <div class="head"><div><strong>Daily parallel attendance</strong><br><span>{{ $selectedClass->name }} {{ $selectedArm->name }} · {{ $date }}</span></div><span>{{ $canMarkAttendance?'Editable':'View restricted' }}</span></div>
        <div class="body">
            @if(!$canMarkAttendance)
                <div class="alert err" style="margin:0">Attendance can be marked by authorized administrators or a teacher effectively assigned to this parallel arm.</div>
            @elseif(!$attendance)
                <div class="empty">Select a valid term and date to load attendance.</div>
            @else
                @php($existing=$attendance['records'])
                <div class="att-summary">
                    <span class="chip">{{ $attendance['enrolments']->count() }} learners</span>
                    <span class="chip">{{ $existing->where('status','present')->count() }} present</span>
                    <span class="chip">{{ $existing->where('status','absent')->count() }} absent</span>
                    <span class="chip">{{ $existing->where('status','late')->count() }} late</span>
                </div>
                @if($canExportAttendance)
                @php($selectedTerm=$terms->firstWhere('id',(int)$termId))
                <div style="padding:10px;border:1px solid #E4E7EC;border-radius:9px;background:#F8FAFC;margin-bottom:12px">
                    <div class="hint" style="margin-bottom:7px">Export attendance register for the selected arm and term. Adjust the date range if required.</div>
                    <form method="GET" action="{{ route('parallel-curriculum.operations.attendance.export') }}" class="row three">
                        <input type="hidden" name="arm_id" value="{{ $selectedArm->id }}">
                        <input type="hidden" name="term_id" value="{{ $termId }}">
                        <div class="fg"><label class="fl">From</label><input class="fc" type="date" name="from" value="{{ $selectedTerm?->start_date?->toDateString() }}"></div>
                        <div class="fg"><label class="fl">To</label><input class="fc" type="date" name="to" value="{{ $selectedTerm?->end_date?->toDateString() }}"></div>
                        <div class="fg"><label class="fl">Format</label><select class="fc" name="format"><option value="pdf">PDF</option><option value="csv">CSV</option></select></div>
                        <button class="btn s" type="submit" style="grid-column:1/-1">Export Attendance Report</button>
                    </form>
                </div>
                @endif
                @if(!($attendance['can_save'] ?? true))
                    <div class="alert err" style="margin-bottom:0">This date is not enabled as a working day for the selected parallel curriculum. Learner attendance cannot be saved for this date.</div>
                @else
                <form method="POST" action="{{ route('parallel-curriculum.operations.attendance.save') }}">
                    @csrf
                    <input type="hidden" name="parallel_curriculum_class_arm_id" value="{{ $selectedArm->id }}">
                    <input type="hidden" name="term_id" value="{{ $termId }}">
                    <input type="hidden" name="attendance_date" value="{{ $date }}">
                    <input type="hidden" name="version" value="{{ $attendance['version'] }}">
                    <div class="att-table-wrap">
                        <table class="att">
                            <thead><tr><th>Learner</th><th>Status</th><th>Remark</th></tr></thead>
                            <tbody>
                            @forelse($attendance['enrolments'] as $index=>$enrolment)
                                @php($record=$existing->get($enrolment->id))
                                <tr>
                                    <td><span class="name">{{ $enrolment->student?->full_name }}</span><br><span class="hint">{{ $enrolment->student?->admission_number }}</span><input type="hidden" name="records[{{ $index }}][enrolment_id]" value="{{ $enrolment->id }}"></td>
                                    <td><select class="fc" name="records[{{ $index }}][status]" required>@foreach(['present'=>'Present','absent'=>'Absent','late'=>'Late','excused'=>'Excused'] as $value=>$label)<option value="{{ $value }}" @selected(($record?->status ?? 'present')===$value)>{{ $label }}</option>@endforeach</select></td>
                                    <td><input class="fc" name="records[{{ $index }}][remark]" maxlength="200" value="{{ $record?->remark }}" placeholder="Optional"></td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="empty">No active learner is assigned to this arm for the selected term's session.</div></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($attendance['enrolments']->isNotEmpty())<button class="btn p" type="submit" style="margin-top:10px">Save Parallel Attendance</button>@endif
                </form>
                @endif
            @endif
        </div>
    </section>
</div>

@endif
</div>
@endsection
