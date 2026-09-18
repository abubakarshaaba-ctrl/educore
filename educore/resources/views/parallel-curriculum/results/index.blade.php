@extends('layouts.app')
@section('title','Parallel Curriculum Results')
@section('page-title','Parallel Curriculum Results')

@push('styles')
<style>
.pc-results{max-width:1280px;margin:0 auto}.pc-tabs{display:flex;gap:5px;overflow-x:auto;margin-bottom:14px}.pc-tab{flex:0 0 auto;padding:8px 13px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);font-size:11.5px;font-weight:700;text-decoration:none}.pc-tab.active,.pc-tab:hover{background:var(--midnight);color:#fff;border-color:var(--midnight)}
.panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:11px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.body{padding:14px}.filters{display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end}.fg{display:flex;flex-direction:column;gap:5px}.fl{font-size:10px;font-weight:800;color:var(--slate)}.fc{min-height:39px;width:100%;border:1px solid var(--border);border-radius:8px;padding:8px 10px;background:#fff;font:500 11.5px inherit}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:38px;padding:8px 13px;border-radius:8px;border:0;font:700 11px inherit;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.btn-d{background:#FEF2F2;color:#B42318;border:1px solid #FECDCA}.btn:disabled{opacity:.5;cursor:not-allowed}
.hero{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px;padding:16px 18px;border-radius:12px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff;margin-bottom:14px}.hero h2{margin:0 0 5px;font-size:17px}.hero p{margin:0;color:#DCE5F2;font-size:11px}.stats{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.stat{min-width:88px;padding:8px 10px;border-radius:9px;background:rgba(255,255,255,.1);text-align:center}.stat strong{display:block;font-size:16px}.stat span{font-size:9px;color:#DCE5F2}.badge{display:inline-flex;padding:3px 7px;border-radius:999px;font-size:9px;font-weight:800;background:#F2F4F7;color:#475467}.badge.ok{background:#ECFDF3;color:#067647}.badge.warn{background:#FFFAEB;color:#B54708}.badge.bad{background:#FEF3F2;color:#B42318}.alerts{display:grid;gap:8px;margin-bottom:12px}.note{padding:9px 11px;border-radius:8px;font-size:10.5px;background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE}.warning{background:#FFFAEB;color:#B54708;border-color:#FEDF89}.error{background:#FEF3F2;color:#B42318;border-color:#FECDCA}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--border);border-radius:10px}.results-table{width:100%;min-width:780px;border-collapse:collapse}.results-table th{padding:9px 10px;background:var(--midnight);color:#fff;font-size:9.5px;text-align:left}.results-table td{padding:9px 10px;border-bottom:1px solid #EEF2F7;font-size:10.5px;color:var(--slate)}.results-table tr:last-child td{border-bottom:0}.name{font-size:11px;font-weight:800;color:var(--midnight)}.actions{display:flex;gap:6px;flex-wrap:wrap}.hint{font-size:10px;color:var(--slate-light)}.empty{padding:28px;text-align:center;color:var(--slate-light);font-size:11.5px}
@media(max-width:760px){.filters{grid-template-columns:1fr}.hero{grid-template-columns:1fr}.stats{justify-content:flex-start}.body{padding:12px}.actions .btn{flex:1}}
</style>
@endpush

@push('styles')
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pc-results">
    @if(session('success'))<div class="note" style="background:#ECFDF3;color:#067647;border-color:#ABEFC6;margin-bottom:12px">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="note error" style="margin-bottom:12px">{{ $errors->first() }}</div>@endif

    <div class="pc-tabs">
        <a href="{{ route('scores.index') }}" class="pc-tab">Conventional Scores</a>
        <a href="{{ route('parallel-curriculum.index') }}" class="pc-tab">Parallel Curriculum</a>
        <a href="{{ route('parallel-curriculum.student-assignments') }}" class="pc-tab">Student Assignments</a>
        <a href="{{ route('parallel-curriculum.results.index') }}" class="pc-tab active">Parallel Results</a>
    </div>

    <section class="panel">
        <div class="head">Select parallel class and term</div>
        <div class="body">
            <form method="GET" action="{{ route('parallel-curriculum.results.index') }}" class="filters">
                <div class="fg">
                    <label class="fl">Parallel class</label>
                    <select class="fc" name="class_id" required>
                        @foreach($classes as $item)
                            <option value="{{ $item->id }}" @selected((int)$classId === (int)$item->id)>{{ $item->curriculum?->name }} · {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Academic term</label>
                    <select class="fc" name="term_id" required>
                        @foreach($terms as $item)
                            <option value="{{ $item->id }}" @selected((int)$termId === (int)$item->id)>{{ $item->session?->name }} · {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-p">Open Results</button>
            </form>
        </div>
    </section>

    @if(!$report)
        <section class="panel"><div class="empty">Create a parallel class and academic term to begin generating standalone results.</div></section>
    @else
        <div class="hero">
            <div>
                <h2>{{ $report['curriculum']?->name }} · {{ $report['class']->name }}</h2>
                <p>{{ $report['term']->session?->name }} · {{ $report['term']->name }} · Template: {{ $report['template']?->name ?: 'Not configured' }}</p>
            </div>
            <div class="stats">
                <div class="stat"><strong>{{ $report['students_count'] }}</strong><span>Students</span></div>
                <div class="stat"><strong>{{ $report['subjects_count'] }}</strong><span>Subjects</span></div>
                <div class="stat"><strong>{{ $report['complete_students_count'] }}</strong><span>Complete</span></div>
            </div>
        </div>

        <div class="alerts">
            @if($report['components']->isEmpty())
                <div class="note error">No usable Assessment Template is assigned to this parallel class.</div>
            @elseif(abs((float)$report['component_weight'] - 100.0) > 0.001)
                <div class="note error">The Assessment Template totals {{ number_format($report['component_weight'],2) }}%. It must total 100% before publication.</div>
            @endif
            @if($report['grades']->isEmpty())
                <div class="note warning">No grading scale is configured for {{ $report['curriculum']?->name }}. Add grade bands in the Parallel Curriculum workspace before publishing.</div>
            @elseif(!$report['grading_scale_complete'])
                <div class="note warning">The grading scale does not cover the full 0–100 range. Close all gaps before publishing results.</div>
            @elseif($report['ungraded_subject_results_count'] > 0)
                <div class="note warning">{{ $report['ungraded_subject_results_count'] }} completed subject result(s) do not resolve to a grade. Review the grading ranges.</div>
            @endif
            @if($report['complete_students_count'] < $report['students_count'])
                <div class="note warning">{{ $report['students_count'] - $report['complete_students_count'] }} student result(s) are incomplete. Publication remains blocked until all active subjects are complete.</div>
            @endif
        </div>

        <section class="panel">
            <div class="head">
                <span>
                    Result register
                    @if($report['is_published'])
                        <span class="badge ok" style="margin-left:6px">Published</span>
                    @else
                        <span class="badge warn" style="margin-left:6px">Draft</span>
                    @endif
                </span>
                <div class="actions">
                    @if($report['is_published'])
                        <form method="POST" action="{{ route('parallel-curriculum.results.unpublish') }}">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ $report['class']->id }}">
                            <input type="hidden" name="term_id" value="{{ $report['term']->id }}">
                            <button class="btn btn-d">Unpublish Result</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('parallel-curriculum.results.publish') }}">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ $report['class']->id }}">
                            <input type="hidden" name="term_id" value="{{ $report['term']->id }}">
                            <button class="btn btn-p" @disabled(!app(\App\Services\ParallelCurriculumResultService::class)->canPublish($report))>Publish Result</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="body">
                @if($report['results']->isEmpty())
                    <div class="empty">No active student is enrolled in this parallel class for the selected academic session.</div>
                @else
                    <div class="table-wrap">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Admission No.</th>
                                    <th>Subjects Complete</th>
                                    <th>Total</th>
                                    <th>Average</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['results'] as $row)
                                    <tr>
                                        <td><div class="name">{{ $row['student']->full_name }}</div></td>
                                        <td>{{ $row['student']->admission_number }}</td>
                                        <td>{{ $row['completed_subject_count'] }}/{{ $row['subject_count'] }}</td>
                                        <td>{{ number_format($row['grand_total'],1) }}/{{ number_format($row['maximum_total'],0) }}</td>
                                        <td>{{ $row['average'] === null ? '—' : number_format($row['average'],2).'%' }}</td>
                                        <td>{{ $row['position'] ?: '—' }}</td>
                                        <td><span class="badge {{ $row['complete'] ? 'ok' : 'warn' }}">{{ $row['complete'] ? 'Complete' : 'Incomplete' }}</span></td>
                                        <td>
                                            <div class="actions">
                                                <a class="btn btn-s" href="{{ route('parallel-curriculum.results.student',['class'=>$report['class']->id,'student'=>$row['student']->id,'term_id'=>$report['term']->id]) }}">View</a>
                                                <a class="btn btn-s" href="{{ route('parallel-curriculum.results.student.pdf',['class'=>$report['class']->id,'student'=>$row['student']->id,'term_id'=>$report['term']->id]) }}">PDF</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif
</div>
@endsection
