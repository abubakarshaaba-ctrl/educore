@extends('layouts.app')
@section('title','Parallel Curriculum Student Result')
@section('page-title','Parallel Curriculum Result')

@push('styles')
<style>
.pc-result{max-width:1060px;margin:0 auto}.toolbar{display:flex;gap:8px;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:12px}.actions{display:flex;gap:7px;flex-wrap:wrap}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 13px;border-radius:8px;border:0;font:700 11px inherit;text-decoration:none;cursor:pointer}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:13px}.head{padding:12px 15px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.body{padding:15px}
.identity{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.field{padding:9px 10px;border:1px solid #EEF2F7;border-radius:9px;background:#FBFCFE}.field span{display:block;font-size:9px;color:var(--slate-light);margin-bottom:3px}.field strong{display:block;font-size:11px;color:var(--midnight);overflow-wrap:anywhere}
.badge{display:inline-flex;padding:3px 7px;border-radius:999px;font-size:9px;font-weight:800;background:#F2F4F7;color:#475467}.badge.ok{background:#ECFDF3;color:#067647}.badge.warn{background:#FFFAEB;color:#B54708}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--border);border-radius:10px}.report{width:100%;min-width:760px;border-collapse:collapse}.report th{padding:8px 9px;background:var(--midnight);color:#fff;font-size:9px;text-align:center}.report th:first-child,.report td:first-child{text-align:left}.report td{padding:8px 9px;border-right:1px solid #EEF2F7;border-bottom:1px solid #EEF2F7;text-align:center;font-size:10.5px;color:var(--slate)}.report tr:last-child td{border-bottom:0}.report .subject{font-weight:800;color:var(--midnight)}
.summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px}.metric{padding:11px;border:1px solid var(--border);border-radius:9px;text-align:center}.metric strong{display:block;font-size:17px;color:var(--midnight)}.metric span{font-size:9px;color:var(--slate-light)}
.note{padding:9px 11px;border-radius:8px;background:#FFFAEB;border:1px solid #FEDF89;color:#B54708;font-size:10.5px;margin-bottom:12px}
@media(max-width:760px){.identity{grid-template-columns:1fr 1fr}.summary{grid-template-columns:repeat(2,1fr)}.body{padding:12px}.actions .btn{flex:1}}
@media(max-width:460px){.identity{grid-template-columns:1fr}.summary{grid-template-columns:1fr 1fr}}
</style>
@endpush

@push('styles')
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
@php($row=$report['student_result'])
<div class="pc-result">
    <div class="toolbar">
        <a class="btn btn-s" href="{{ route('parallel-curriculum.results.index',['class_id'=>$report['class']->id,'term_id'=>$report['term']->id]) }}">← Back to Result Register</a>
        <div class="actions">
            <a class="btn btn-p" href="{{ route('parallel-curriculum.results.student.pdf',['class'=>$report['class']->id,'student'=>$row['student']->id,'term_id'=>$report['term']->id]) }}">Download PDF</a>
        </div>
    </div>

    @if(!$row['complete'])
        <div class="note">This result is incomplete. One or more active subjects do not yet have all required assessment components.</div>
    @endif

    <section class="card">
        <div class="head">
            {{ $report['curriculum']?->name }} Result
            @if($report['is_published'])
                <span class="badge ok" style="margin-left:6px">Published</span>
            @else
                <span class="badge warn" style="margin-left:6px">Draft</span>
            @endif
        </div>
        <div class="body">
            <div class="identity">
                <div class="field"><span>Student</span><strong>{{ $row['student']->full_name }}</strong></div>
                <div class="field"><span>Admission No.</span><strong>{{ $row['student']->admission_number }}</strong></div>
                <div class="field"><span>Parallel Class</span><strong>{{ $report['class']->name }}{{ $row['enrolment']->curriculumClassArm ? ' · Arm '.$row['enrolment']->curriculumClassArm->name : '' }}</strong></div>
                <div class="field"><span>Academic Period</span><strong>{{ $report['term']->session?->name }} · {{ $report['term']->name }}</strong></div>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="head">Subject Performance</div>
        <div class="body">
            <div class="table-wrap">
                <table class="report">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            @foreach($report['components'] as $component)
                                <th>{{ $component->name }}<br><span style="opacity:.7">/{{ number_format($component->weight_percentage,0) }}</span></th>
                            @endforeach
                            <th>Total %</th>
                            <th>Grade</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($row['subjects'] as $subject)
                            <tr>
                                <td class="subject">{{ $subject['subject'] }}</td>
                                @foreach($report['components'] as $component)
                                    @php($componentRow=collect($subject['components'])->firstWhere('id',(int)$component->id))
                                    <td>{{ $componentRow && $componentRow['score'] !== null ? number_format($componentRow['score'],1) : '—' }}</td>
                                @endforeach
                                <td>{{ $subject['percentage'] === null ? '—' : number_format($subject['percentage'],1) }}</td>
                                <td>{{ $subject['grade'] ?: '—' }}</td>
                                <td>{{ $subject['remark'] ?: ($subject['complete'] ? '—' : 'Incomplete') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="head">Performance Summary</div>
        <div class="body">
            <div class="summary">
                <div class="metric"><strong>{{ $row['completed_subject_count'] }}/{{ $row['subject_count'] }}</strong><span>Subjects Complete</span></div>
                <div class="metric"><strong>{{ number_format($row['grand_total'],1) }}</strong><span>Total / {{ number_format($row['maximum_total'],0) }}</span></div>
                <div class="metric"><strong>{{ $row['average'] === null ? '—' : number_format($row['average'],2).'%' }}</strong><span>Average</span></div>
                <div class="metric"><strong>{{ $row['position'] ?: '—' }}</strong><span>Class Position</span></div>
                <div class="metric"><strong>{{ $row['failed_subjects'] }}</strong><span>Failed Subjects</span></div>
            </div>
        </div>
    </section>
    <section class="card">
        <div class="head">Form Teacher Comment</div>
        <div class="body">
            <div style="font-size:11.5px;line-height:1.65;color:var(--slate)">
                {{ $row['form_teacher_comment'] ?: 'No form-teacher comment has been entered for this term.' }}
            </div>
            @if(!empty($row['form_teacher']))
                <div style="margin-top:8px;font-size:9.5px;color:var(--slate-light)">
                    Form Teacher: {{ $row['form_teacher']->name }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
