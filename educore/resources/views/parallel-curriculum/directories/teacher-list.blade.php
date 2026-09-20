@extends('layouts.app')
@section('title','Parallel Curriculum Teacher List')
@section('page-title','Parallel Curriculum Teacher List')

@push('styles')
<style>
.pc-directory{max-width:1280px;margin:0 auto}.pc-nav{display:flex;gap:6px;overflow-x:auto;margin-bottom:14px}.pc-nav a{flex:0 0 auto;padding:8px 13px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);font-size:11.5px;font-weight:700;text-decoration:none}.pc-nav a.active,.pc-nav a:hover{background:var(--midnight);border-color:var(--midnight);color:#fff}
.hero{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:17px 19px;margin-bottom:14px;background:linear-gradient(135deg,#071E45,#0B2D63);border-radius:14px;color:#fff}.hero h2{margin:0 0 5px;font-size:18px}.hero p{max-width:820px;margin:0;color:#DCE5F2;font-size:11.5px;line-height:1.55}.hero-links{display:flex;gap:7px;flex-wrap:wrap}.hero-links a{display:inline-flex;align-items:center;min-height:34px;padding:7px 10px;border-radius:8px;background:rgba(255,255,255,.12);color:#fff;text-decoration:none;font-size:10px;font-weight:800}
.panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:11px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.body{padding:14px}.filters{display:grid;grid-template-columns:minmax(220px,1fr) minmax(220px,1fr) auto;gap:9px;align-items:end}.fg{display:flex;flex-direction:column;gap:5px;min-width:0}.fl{font-size:10px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:39px;border:1px solid var(--border);border-radius:8px;background:#fff;padding:8px 10px;font:500 11.5px inherit}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:39px;padding:8px 13px;border-radius:8px;border:0;font:800 11px inherit;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.actions{display:flex;gap:7px;flex-wrap:wrap}
.report{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden}.report-title{text-align:center;padding:18px 18px 12px;border-bottom:1px solid #E7ECF2}.report-title h2{margin:0;color:var(--midnight);font-size:18px}.report-title h3{margin:4px 0 0;color:var(--slate);font-size:13px}.report-title p{margin:5px 0 0;font-size:10.5px;color:var(--slate-light)}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;padding:12px 14px;background:#F8FAFC;border-bottom:1px solid #E7ECF2}.stat{text-align:center;padding:8px;border:1px solid #E7ECF2;border-radius:9px;background:#fff}.stat strong{display:block;font-size:16px;color:var(--midnight)}.stat span{font-size:9px;color:var(--slate-light);text-transform:uppercase;font-weight:800;letter-spacing:.03em}.stat.warn strong{color:#B54708}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}.directory-table{width:100%;min-width:980px;border-collapse:collapse}.directory-table th{padding:9px 10px;background:var(--midnight);color:#fff;font-size:9.5px;text-align:left}.directory-table td{padding:9px 10px;border-bottom:1px solid #EEF2F7;font-size:10.5px;color:var(--slate);vertical-align:top}.directory-table tr:last-child td{border-bottom:0}.directory-table .sn{width:48px;text-align:center}.name{font-size:11px;font-weight:800;color:var(--midnight)}.badge{display:inline-flex;padding:3px 7px;border-radius:999px;background:#ECFDF3;color:#067647;font-size:9px;font-weight:800}.badge.inactive{background:#FEF3F2;color:#B42318}.assignment-type{display:inline-flex;margin:0 4px 4px 0;padding:3px 6px;border-radius:999px;background:#EFF6FF;color:#1D4ED8;font-size:8.5px;font-weight:800}.empty{padding:30px;text-align:center;color:var(--slate-light);font-size:11.5px}.mobile-list{display:none}.teacher-card{border:1px solid var(--border);border-radius:10px;padding:11px;margin-bottom:8px}.teacher-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}.teacher-card strong{color:var(--midnight);font-size:12px}.teacher-card span.meta{display:block;color:var(--slate);font-size:10px;margin-top:4px;line-height:1.5}.print-meta{display:none}
@media(max-width:900px){.filters{grid-template-columns:1fr 1fr}.actions{grid-column:1/-1}}
@media(max-width:700px){.desktop-list{display:none}.mobile-list{display:block;padding:12px}.filters{grid-template-columns:1fr}.actions{display:grid;grid-template-columns:1fr 1fr}.actions .btn{width:100%}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.body{padding:12px}.hero{padding:15px}}
@media print{
    @page{size:A4 landscape;margin:9mm}
    body *{visibility:hidden!important}
    #parallel-teacher-print,#parallel-teacher-print *{visibility:visible!important}
    #parallel-teacher-print{position:absolute;left:0;top:0;width:100%;border:0;border-radius:0}
    #parallel-teacher-print .report-title{padding-top:0}
    #parallel-teacher-print .table-wrap{overflow:visible}
    #parallel-teacher-print .directory-table{min-width:0;width:100%}
    #parallel-teacher-print .directory-table th{background:#fff!important;color:#000!important;border:1px solid #777;font-size:8.5px}
    #parallel-teacher-print .directory-table td{border:1px solid #AAA;color:#000;font-size:8.5px;padding:5px}
    #parallel-teacher-print .mobile-list{display:none!important}
    #parallel-teacher-print .desktop-list{display:block!important}
    #parallel-teacher-print .stats{background:#fff;grid-template-columns:repeat(4,1fr)}
    #parallel-teacher-print .stat{border:1px solid #AAA}
    #parallel-teacher-print .print-meta{display:block;font-size:8.5px;color:#444;margin-top:5px}
}
</style>
@endpush

@push('styles')
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pc-directory">
    @include('parallel-curriculum.partials.module-navigation')

    <div class="hero no-print">
        <div>
            <h2>Parallel Curriculum Teacher List</h2>
            <p>View the effective teaching roster after applying each class arm's teaching model, class/form-teacher assignment, arm-specific subject overrides and default subject-teacher assignments.</p>
        </div>
        <div class="hero-links">
            <a href="{{ route('parallel-curriculum.class-list', ['parallel_curriculum_id' => $selectedCurriculum?->id]) }}">Class List</a>
        </div>
    </div>

    <section class="panel no-print">
        <div class="head">
            <span>List filters</span>
            <span>{{ $teacherRows->count() }} teacher{{ $teacherRows->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="body">
            <form method="GET" action="{{ route('parallel-curriculum.teacher-list') }}" class="filters">
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
                    <label class="fl">Parallel class</label>
                    <select class="fc" name="class_id">
                        <option value="">All classes</option>
                        @foreach($availableClasses as $class)
                            <option value="{{ $class->id }}" @selected((int)$selectedClassId === (int)$class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="actions">
                    <button class="btn btn-p" type="submit">Generate List</button>
                    <button class="btn btn-s" type="button" onclick="window.print()">Print</button>
                </div>
            </form>
        </div>
    </section>

    <section class="report" id="parallel-teacher-print">
        <div class="report-title">
            <h2>{{ $schoolName }}</h2>
            <h3>Parallel Curriculum Teacher List</h3>
            <p>
                {{ $selectedCurriculum?->name ?: 'No programme selected' }}
                @if($selectedClassId)
                    · {{ $availableClasses->firstWhere('id',$selectedClassId)?->name }}
                @else
                    · All Classes
                @endif
            </p>
            <div class="print-meta">Generated {{ now()->format('d M Y, H:i') }}</div>
        </div>

        <div class="stats">
            <div class="stat"><strong>{{ $teacherRows->count() }}</strong><span>Teachers</span></div>
            <div class="stat"><strong>{{ $classTeacherCount }}</strong><span>Class/Form Teachers</span></div>
            <div class="stat"><strong>{{ $subjectTeacherCount }}</strong><span>Subject Teachers</span></div>
            <div class="stat {{ $unassignedSlots > 0 ? 'warn' : '' }}"><strong>{{ $unassignedSlots }}</strong><span>Unassigned Teaching Slots</span></div>
        </div>

        @if($teacherRows->isEmpty())
            <div class="empty">No teacher assignment resolves for the selected parallel programme context.</div>
        @else
            <div class="desktop-list">
                <div class="table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th class="sn">S/N</th>
                                <th>Staff ID</th>
                                <th>Teacher</th>
                                <th>Portal Role</th>
                                <th>Assignment Type</th>
                                <th>Class / Arm</th>
                                <th>Subject(s)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teacherRows as $row)
                                @php($teacher=$row['teacher'])
                                <tr>
                                    <td class="sn">{{ $loop->iteration }}</td>
                                    <td>{{ $teacher->staff_id ?: '—' }}</td>
                                    <td>
                                        <span class="name">{{ $teacher->name }}</span>
                                        @if($teacher->phone)<div>{{ $teacher->phone }}</div>@endif
                                    </td>
                                    <td>{{ $row['role_label'] }}</td>
                                    <td>
                                        @foreach($row['assignment_types'] as $type)
                                            <span class="assignment-type">{{ $type }}</span>
                                        @endforeach
                                    </td>
                                    <td>{{ $row['class_arms']->join(', ') ?: '—' }}</td>
                                    <td>{{ $row['subjects']->join(', ') ?: '—' }}</td>
                                    <td><span class="badge {{ $row['is_active'] ? '' : 'inactive' }}">{{ $row['is_active'] ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mobile-list">
                @foreach($teacherRows as $row)
                    @php($teacher=$row['teacher'])
                    <article class="teacher-card">
                        <div class="teacher-card-top">
                            <div>
                                <strong>{{ $loop->iteration }}. {{ $teacher->name }}</strong>
                                <span class="meta">{{ $teacher->staff_id ?: 'No Staff ID' }} · {{ $row['role_label'] }}</span>
                            </div>
                            <span class="badge {{ $row['is_active'] ? '' : 'inactive' }}">{{ $row['is_active'] ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <span class="meta">Type: {{ $row['assignment_types']->join(', ') }}</span>
                        <span class="meta">Class/Arm: {{ $row['class_arms']->join(', ') ?: '—' }}</span>
                        <span class="meta">Subjects: {{ $row['subjects']->join(', ') ?: '—' }}</span>
                        @if($teacher->phone)<span class="meta">Phone: {{ $teacher->phone }}</span>@endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
