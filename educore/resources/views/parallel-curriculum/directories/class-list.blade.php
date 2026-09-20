@extends('layouts.app')
@section('title','Parallel Curriculum Class List')
@section('page-title','Parallel Curriculum Class List')

@push('styles')
<style>
.pc-directory{max-width:1280px;margin:0 auto}.pc-nav{display:flex;gap:6px;overflow-x:auto;margin-bottom:14px}.pc-nav a{flex:0 0 auto;padding:8px 13px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);font-size:11.5px;font-weight:700;text-decoration:none}.pc-nav a.active,.pc-nav a:hover{background:var(--midnight);border-color:var(--midnight);color:#fff}
.hero{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:17px 19px;margin-bottom:14px;background:linear-gradient(135deg,#071E45,#0B2D63);border-radius:14px;color:#fff}.hero h2{margin:0 0 5px;font-size:18px}.hero p{max-width:820px;margin:0;color:#DCE5F2;font-size:11.5px;line-height:1.55}.hero-links{display:flex;gap:7px;flex-wrap:wrap}.hero-links a{display:inline-flex;align-items:center;min-height:34px;padding:7px 10px;border-radius:8px;background:rgba(255,255,255,.12);color:#fff;text-decoration:none;font-size:10px;font-weight:800}
.panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:11px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.body{padding:14px}
.filters{display:grid;grid-template-columns:repeat(4,minmax(0,1fr)) auto;gap:9px;align-items:end}.fg{display:flex;flex-direction:column;gap:5px;min-width:0}.fl{font-size:10px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:39px;border:1px solid var(--border);border-radius:8px;background:#fff;padding:8px 10px;font:500 11.5px inherit}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:39px;padding:8px 13px;border-radius:8px;border:0;font:800 11px inherit;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.actions{display:flex;gap:7px;flex-wrap:wrap}
.report{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden}.report-title{text-align:center;padding:18px 18px 12px;border-bottom:1px solid #E7ECF2}.report-title h2{margin:0;color:var(--midnight);font-size:18px}.report-title h3{margin:4px 0 0;color:var(--slate);font-size:13px}.report-title p{margin:5px 0 0;font-size:10.5px;color:var(--slate-light)}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;padding:12px 14px;background:#F8FAFC;border-bottom:1px solid #E7ECF2}.stat{text-align:center;padding:8px;border:1px solid #E7ECF2;border-radius:9px;background:#fff}.stat strong{display:block;font-size:16px;color:var(--midnight)}.stat span{font-size:9px;color:var(--slate-light);text-transform:uppercase;font-weight:800;letter-spacing:.03em}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}.directory-table{width:100%;min-width:760px;border-collapse:collapse}.directory-table th{padding:9px 10px;background:var(--midnight);color:#fff;font-size:9.5px;text-align:left}.directory-table td{padding:9px 10px;border-bottom:1px solid #EEF2F7;font-size:10.5px;color:var(--slate);vertical-align:middle}.directory-table tr:last-child td{border-bottom:0}.directory-table .sn{width:48px;text-align:center}.name{font-size:11px;font-weight:800;color:var(--midnight)}.empty{padding:30px;text-align:center;color:var(--slate-light);font-size:11.5px}.mobile-list{display:none}.student-card{border:1px solid var(--border);border-radius:10px;padding:11px;margin-bottom:8px}.student-card strong{display:block;color:var(--midnight);font-size:12px}.student-card span{display:block;color:var(--slate);font-size:10px;margin-top:3px}.print-meta{display:none}
@media(max-width:980px){.filters{grid-template-columns:repeat(2,minmax(0,1fr))}.actions{grid-column:1/-1}}
@media(max-width:700px){.desktop-list{display:none}.mobile-list{display:block;padding:12px}.filters{grid-template-columns:1fr}.actions{display:grid;grid-template-columns:1fr 1fr}.actions .btn{width:100%}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.body{padding:12px}.hero{padding:15px}}
@media print{
    @page{size:A4 portrait;margin:10mm}
    body *{visibility:hidden!important}
    #parallel-class-print,#parallel-class-print *{visibility:visible!important}
    #parallel-class-print{position:absolute;left:0;top:0;width:100%;border:0;border-radius:0}
    #parallel-class-print .report-title{padding-top:0}
    #parallel-class-print .table-wrap{overflow:visible}
    #parallel-class-print .directory-table{min-width:0;width:100%}
    #parallel-class-print .directory-table th{background:#fff!important;color:#000!important;border:1px solid #777;font-size:9px}
    #parallel-class-print .directory-table td{border:1px solid #AAA;color:#000;font-size:9px;padding:6px}
    #parallel-class-print .mobile-list{display:none!important}
    #parallel-class-print .desktop-list{display:block!important}
    #parallel-class-print .stats{background:#fff;grid-template-columns:repeat(4,1fr)}
    #parallel-class-print .stat{border:1px solid #AAA}
    #parallel-class-print .print-meta{display:block;font-size:8.5px;color:#444;margin-top:5px}
}
</style>
@endpush

@push('styles')
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pc-directory">
    <div class="pc-nav no-print">
        <a href="{{ route('parallel-curriculum.index') }}">Parallel Workspace</a>
        <a href="{{ route('parallel-curriculum.setup') }}">Programme Setup</a>
        <a href="{{ route('parallel-curriculum.student-assignments') }}">Student Assignments</a>
        <a href="{{ route('parallel-curriculum.class-list') }}" class="active">Class List</a>
        <a href="{{ route('parallel-curriculum.teacher-list') }}">Teacher List</a>
        <a href="{{ route('parallel-curriculum.lifecycle.index') }}">Academic Lifecycle</a>
        <a href="{{ route('parallel-curriculum.operations.index') }}">Timetable & Attendance</a>
        <a href="{{ route('parallel-curriculum.results.index') }}">Parallel Results</a>
    </div>

    <div class="hero no-print">
        <div>
            <h2>Parallel Curriculum Class List</h2>
            <p>Generate the official learner register for any parallel programme, session, class level and arm without altering the learner's conventional class placement.</p>
        </div>
        <div class="hero-links">
            <a href="{{ route('parallel-curriculum.teacher-list', ['parallel_curriculum_id' => $selectedCurriculum?->id]) }}">Teacher List</a>
        </div>
    </div>

    <section class="panel no-print">
        <div class="head">
            <span>List filters</span>
            <span>{{ $enrolments->count() }} learner{{ $enrolments->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="body">
            <form method="GET" action="{{ route('parallel-curriculum.class-list') }}" class="filters">
                <div class="fg">
                    <label class="fl">Parallel programme</label>
                    <select class="fc" name="parallel_curriculum_id" onchange="this.form.submit()">
                        @forelse($curricula as $curriculum)
                            <option value="{{ $curriculum->id }}" @selected((int)$selectedCurriculum?->id === (int)$curriculum->id)>{{ $curriculum->name }}</option>
                        @empty
                            <option value="">No active programme</option>
                        @endforelse
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Academic session</label>
                    <select class="fc" name="session_id">
                        @forelse($sessions as $session)
                            <option value="{{ $session->id }}" @selected((int)$selectedSession?->id === (int)$session->id)>{{ $session->name }}{{ $session->is_current ? ' · Current' : '' }}</option>
                        @empty
                            <option value="">No academic session</option>
                        @endforelse
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Parallel class</label>
                    <select class="fc" name="class_id" onchange="this.form.submit()">
                        @forelse($classes as $class)
                            <option value="{{ $class->id }}" @selected((int)$selectedClass?->id === (int)$class->id)>{{ $class->name }}</option>
                        @empty
                            <option value="">No active class</option>
                        @endforelse
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Class arm</label>
                    <select class="fc" name="arm_id">
                        @if($arms->isNotEmpty())
                            <option value="all" @selected($allArms)>All arms</option>
                            @foreach($arms as $arm)
                                <option value="{{ $arm->id }}" @selected(!$allArms && (int)$selectedArm?->id === (int)$arm->id)>{{ $arm->name }}</option>
                            @endforeach
                        @else
                            <option value="all">No arm configured</option>
                        @endif
                    </select>
                </div>
                <div class="actions">
                    <button class="btn btn-p" type="submit">Generate List</button>
                    <button class="btn btn-s" type="button" onclick="window.print()">Print</button>
                </div>
            </form>
        </div>
    </section>

    <section class="report" id="parallel-class-print">
        <div class="report-title">
            <h2>{{ auth()->user()->tenant?->name ?: 'School' }}</h2>
            <h3>Parallel Curriculum Class List</h3>
            <p>
                {{ $selectedCurriculum?->name ?: 'No programme selected' }}
                @if($selectedSession) · {{ $selectedSession->name }} @endif
                @if($selectedClass) · {{ $selectedClass->name }} @endif
                @if($selectedArm) · Arm {{ $selectedArm->name }} @elseif($allArms && $arms->isNotEmpty()) · All Arms @endif
            </p>
            <div class="print-meta">Generated {{ now()->format('d M Y, H:i') }}</div>
        </div>

        <div class="stats">
            <div class="stat"><strong>{{ $enrolments->count() }}</strong><span>Total Learners</span></div>
            <div class="stat"><strong>{{ $maleCount }}</strong><span>Male</span></div>
            <div class="stat"><strong>{{ $femaleCount }}</strong><span>Female</span></div>
            <div class="stat"><strong>{{ max(0, $enrolments->count() - $maleCount - $femaleCount) }}</strong><span>Other / Unspecified</span></div>
        </div>

        @if($enrolments->isEmpty())
            <div class="empty">No active learner is assigned to the selected parallel class context.</div>
        @else
            <div class="desktop-list">
                <div class="table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th class="sn">S/N</th>
                                <th>Admission No.</th>
                                <th>Learner Name</th>
                                <th>Gender</th>
                                @if($allArms)<th>Parallel Arm</th>@endif
                                <th>Conventional Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrolments as $enrolment)
                                <tr>
                                    <td class="sn">{{ $loop->iteration }}</td>
                                    <td>{{ $enrolment->student?->admission_number ?: '—' }}</td>
                                    <td><span class="name">{{ $enrolment->student?->full_name ?: 'Learner' }}</span></td>
                                    <td>{{ $enrolment->student?->gender ? ucfirst($enrolment->student->gender) : '—' }}</td>
                                    @if($allArms)<td>{{ $enrolment->curriculumClassArm?->name ?: '—' }}</td>@endif
                                    <td>{{ $enrolment->student?->currentClassArm?->full_name ?: 'Not assigned' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mobile-list">
                @foreach($enrolments as $enrolment)
                    <article class="student-card">
                        <strong>{{ $loop->iteration }}. {{ $enrolment->student?->full_name ?: 'Learner' }}</strong>
                        <span>Admission No.: {{ $enrolment->student?->admission_number ?: '—' }}</span>
                        <span>Gender: {{ $enrolment->student?->gender ? ucfirst($enrolment->student->gender) : '—' }}</span>
                        @if($allArms)<span>Parallel Arm: {{ $enrolment->curriculumClassArm?->name ?: '—' }}</span>@endif
                        <span>Conventional: {{ $enrolment->student?->currentClassArm?->full_name ?: 'Not assigned' }}</span>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
