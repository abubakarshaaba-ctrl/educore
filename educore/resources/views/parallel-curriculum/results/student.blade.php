@extends('layouts.app')
@section('title','Parallel Curriculum Student Result')
@section('page-title','Parallel Curriculum Result')

@push('styles')
<style>
.pc-result{max-width:1180px;margin:0 auto}.toolbar{display:flex;gap:8px;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:12px}.actions{display:flex;gap:7px;flex-wrap:wrap}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:8px 13px;border-radius:8px;border:0;font:700 12px inherit;text-decoration:none;cursor:pointer}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}
.report-card{background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow);margin-bottom:16px}.report-header{display:grid;grid-template-columns:82px minmax(0,1fr);border-bottom:1px solid var(--border)}.brand-logo{display:flex;align-items:center;justify-content:center;padding:12px;border-right:1px solid var(--border);background:#FCFDFE}.brand-logo img{width:58px;height:58px;object-fit:contain}.logo-fallback{width:54px;height:54px;border:2px solid #17365d;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#17365d;font-size:22px;font-weight:800}.brand-copy{text-align:center;padding:12px 16px}.school-name{color:#17365d;font-size:18px;font-weight:800;text-transform:uppercase}.school-contact{margin-top:3px;color:var(--slate);font-size:11px;line-height:1.45}.document-title{margin-top:8px;padding:6px;border-top:1px solid #CBD5E1;border-bottom:1px solid #CBD5E1;color:#17365d;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
.identity{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border-bottom:1px solid var(--border)}.field{padding:10px 12px;border-right:1px solid #EEF2F7;border-bottom:1px solid #EEF2F7;min-width:0}.field:nth-child(4n){border-right:0}.label{display:block;color:var(--slate-light);font-size:9.5px;font-weight:800;text-transform:uppercase;letter-spacing:.035em}.value{display:block;margin-top:3px;color:var(--midnight);font-size:12px;font-weight:700;overflow-wrap:anywhere}
.kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));border-bottom:1px solid var(--border)}.kpi{padding:11px 7px;text-align:center;border-right:1px solid #EEF2F7}.kpi:last-child{border-right:0}.kpi-value{font-size:17px;font-weight:800;color:#17365d}.kpi-label{margin-top:3px;color:var(--slate-light);font-size:9px;font-weight:800;text-transform:uppercase}.good{color:#067647!important}.fair{color:#B54708!important}.risk{color:#B42318!important}
.section-bar{padding:8px 12px;background:#EDF3F8;border-bottom:1px solid #AEBBC9;border-top:1px solid #AEBBC9;color:#17365d;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.03em}.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}.academic{width:100%;min-width:840px;border-collapse:collapse}.academic th{padding:8px 7px;background:#F3F6F9;border:1px solid #CBD5DF;color:#243B53;font-size:9px;text-transform:uppercase;text-align:center;white-space:nowrap}.academic td{padding:8px 7px;border:1px solid #DCE3EA;text-align:center;font-size:11px;color:var(--slate)}.academic tbody tr:nth-child(even) td{background:#FAFBFD}.academic .subject{text-align:left;min-width:150px;font-weight:800;color:var(--midnight)}.academic .remark{text-align:left;min-width:110px}.academic .strong{font-weight:800;color:var(--midnight)}.academic .pass{color:#067647;font-weight:800}.academic .fail{color:#B42318;font-weight:800}
.grade-scale{display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));border-bottom:1px solid var(--border)}.grade-item{padding:8px;text-align:center;border-right:1px solid #E5EAF0;font-size:10px}.grade-item:last-child{border-right:0}.grade-item strong{display:block;color:#17365d;font-size:12px}.grade-item span{display:block;margin-top:2px;color:var(--slate)}
.development{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));border-top:1px solid #AEBBC9}.dev-panel{border-right:1px solid #AEBBC9;min-width:0}.dev-panel:last-child{border-right:0}.panel-title{padding:8px;background:#F3F6F9;border-bottom:1px solid #CBD5DF;color:#243B53;font-size:10px;font-weight:800;text-align:center;text-transform:uppercase}.domain-table,.attendance-table{width:100%;border-collapse:collapse}.domain-table th,.domain-table td,.attendance-table th,.attendance-table td{padding:7px 8px;border-bottom:1px solid #E7EBF0;font-size:10px}.domain-table th,.attendance-table th{color:var(--slate);font-size:9px;text-transform:uppercase;text-align:left;background:#FAFBFD}.domain-table td:nth-child(2),.domain-table td:nth-child(3){text-align:center}.attendance-table td{text-align:center;font-weight:800;color:#17365d}.score-pill{display:inline-flex;min-width:26px;justify-content:center;padding:2px 6px;border:1px solid #9AA7B5;border-radius:999px;font-weight:800}.score-pill.recorded{background:#17365d;color:#fff;border-color:#17365d}.rating-key{grid-column:1/-1;padding:7px;text-align:center;background:#FAFBFD;color:var(--slate-light);font-size:9.5px;border-top:1px solid #DCE3EA}
.remarks{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid #AEBBC9}.remark{border-right:1px solid #AEBBC9}.remark:last-child{border-right:0}.remark-title{padding:8px 10px;background:#F3F6F9;border-bottom:1px solid #CBD5DF;color:#243B53;font-size:10px;font-weight:800;text-transform:uppercase}.remark-body{min-height:72px;padding:11px 12px;color:var(--slate);font-size:11.5px;line-height:1.55}.remark-by{margin-top:8px;color:var(--slate-light);font-size:9.5px}
.report-footer{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));border-top:1px solid #AEBBC9}.report-footer.third{grid-template-columns:repeat(3,minmax(0,1fr))}.footer-cell{padding:10px 12px;border-right:1px solid #DCE3EA}.footer-cell:last-child{border-right:0}.generated{padding:8px;text-align:center;color:#98A2B3;font-size:9px;border-top:1px solid #EEF2F7}.note{padding:10px 12px;border-radius:8px;background:#FFFAEB;border:1px solid #FEDF89;color:#B54708;font-size:11px;margin-bottom:12px}
@media(max-width:900px){.identity{grid-template-columns:repeat(2,minmax(0,1fr))}.field:nth-child(4n){border-right:1px solid #EEF2F7}.field:nth-child(2n){border-right:0}.kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.development{grid-template-columns:1fr}.dev-panel{border-right:0;border-bottom:1px solid #AEBBC9}.remarks{grid-template-columns:1fr}.remark{border-right:0;border-bottom:1px solid #AEBBC9}}
@media(max-width:560px){.toolbar,.actions{align-items:stretch}.toolbar{flex-direction:column}.toolbar .btn,.actions,.actions .btn{width:100%}.report-header{grid-template-columns:64px minmax(0,1fr)}.brand-logo{padding:8px}.brand-logo img,.logo-fallback{width:46px;height:46px}.school-name{font-size:14px}.document-title{font-size:10px}.identity{grid-template-columns:1fr}.field{border-right:0!important}.kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.report-footer,.report-footer.third{grid-template-columns:1fr}.footer-cell{border-right:0;border-bottom:1px solid #DCE3EA}}
</style>
@endpush

@push('styles')
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
@php
    $row = $report['student_result'];
    $avg = (float) ($summary->final_average ?? 0);
    $pos = (int) ($summary->position_in_class ?? 0);
    $total = (int) ($summary->total_students_in_class ?? 0);
    $ordinal = function ($number) {
        $number = (int) $number;
        if ($number <= 0) return '—';
        $mod100 = $number % 100;
        if ($mod100 >= 11 && $mod100 <= 13) return $number.'th';
        return $number.match($number % 10) {1=>'st',2=>'nd',3=>'rd',default=>'th'};
    };
    $gradeRecord = $gradingSystem->sortByDesc('min_score')->first(
        fn($grade) => $avg >= (float)$grade->min_score && $avg <= (float)$grade->max_score
    );
    $overallGrade = $gradeRecord?->grade_letter ?? '—';
    $overallRemark = $gradeRecord?->remark ?? ($avg >= 50 ? 'Satisfactory' : 'Needs Improvement');
    $performanceClass = $avg >= 70 ? 'good' : ($avg >= 50 ? 'fair' : 'risk');
    $ratingLabels = [1=>'Poor',2=>'Fair',3=>'Good',4=>'Very Good',5=>'Excellent'];
    $logoUrl = !empty($tenant?->logo_path) ? asset('storage/'.preg_replace('#^storage/#','',ltrim($tenant->logo_path,'/'))) : null;
@endphp

<div class="pc-result">
    @include('parallel-curriculum.partials.module-navigation')

    <div class="toolbar">
        <a class="btn btn-s" href="{{ route('parallel-curriculum.results.index',['class_id'=>$report['class']->id,'term_id'=>$report['term']->id]) }}">← Back to Result Register</a>
        <div class="actions">
            @if($isCumulative ?? false)
                <a class="btn btn-p" href="{{ route('parallel-curriculum.results.student.cumulative.pdf',['class'=>$report['class']->id,'student'=>$row['student']->id,'session_id'=>$report['term']->session_id]) }}">Download Cumulative PDF</a>
            @else
                <a class="btn btn-p" href="{{ route('parallel-curriculum.results.student.pdf',['class'=>$report['class']->id,'student'=>$row['student']->id,'term_id'=>$report['term']->id]) }}">Download Term PDF</a>
            @endif
        </div>
    </div>

    @if(!$row['complete'])
        <div class="note">This result is incomplete. One or more active parallel subjects do not yet have all required assessment components.</div>
    @endif

    <article class="report-card">
        <header class="report-header">
            <div class="brand-logo">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="School logo">
                @else
                    <div class="logo-fallback">{{ strtoupper(substr($tenant?->name ?? 'E',0,1)) }}</div>
                @endif
            </div>
            <div class="brand-copy">
                <div class="school-name">{{ $tenant?->name ?? config('app.name') }}</div>
                <div class="school-contact">
                    {{ $tenant?->address ?? '' }}
                    @if(!empty($tenant?->phone)) · {{ $tenant->phone }} @endif
                    @if(!empty($tenant?->email)) · {{ $tenant->email }} @endif
                </div>
                <div class="document-title">
                    {{ $reportDocumentTitle ?? (($isCumulative ?? false) ? strtoupper($parallelProgrammeName ?? 'Parallel Curriculum').' Cumulative Student Performance Report' : strtoupper($parallelProgrammeName ?? 'Parallel Curriculum').' Student Termly Performance Report') }}
                </div>
            </div>
        </header>

        <section class="identity">
            <div class="field"><span class="label">Student Name</span><span class="value">{{ $student->full_name }}</span></div>
            <div class="field"><span class="label">Admission Number</span><span class="value">{{ $student->admission_number }}</span></div>
            <div class="field"><span class="label">Class</span><span class="value">{{ $parallelClassName }}</span></div>
            <div class="field"><span class="label">Gender</span><span class="value">{{ ucfirst($student->gender ?? '—') }}</span></div>
            <div class="field"><span class="label">Session / Term</span><span class="value">{{ $session?->name }} / {{ $term->name }}</span></div>
            <div class="field"><span class="label">Date of Birth</span><span class="value">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M Y') : '—' }}</span></div>
            <div class="field"><span class="label">Date Issued</span><span class="value">{{ now()->format('d M Y') }}</span></div>
            <div class="field"><span class="label">Number in Class</span><span class="value">{{ $total ?: '—' }}</span></div>
        </section>

        <section class="kpis">
            <div class="kpi"><div class="kpi-value">{{ number_format($avg,1) }}%</div><div class="kpi-label">{{ $isThirdTerm ? 'Annual Average' : 'Term Average' }}</div></div>
            <div class="kpi"><div class="kpi-value">{{ $ordinal($pos) }}</div><div class="kpi-label">Class Position</div></div>
            <div class="kpi"><div class="kpi-value">{{ $overallGrade }}</div><div class="kpi-label">Overall Grade</div></div>
            <div class="kpi"><div class="kpi-value {{ $performanceClass }}">{{ $overallRemark }}</div><div class="kpi-label">Performance</div></div>
            <div class="kpi"><div class="kpi-value">{{ $summary->subjects_offered ?? count($subjectRows) }}</div><div class="kpi-label">Subjects Offered</div></div>
            <div class="kpi"><div class="kpi-value">{{ number_format((float)($summary->total_score ?? 0),1) }}</div><div class="kpi-label">Total Score</div></div>
        </section>

        <div class="section-bar">{{ ($isCumulative ?? false) ? 'Cumulative Academic Performance' : $term->name.' Academic Performance' }}</div>
        <div class="table-wrap">
            <table class="academic">
                <thead>
                    <tr>
                        <th style="text-align:left">Subject</th>
                        @if($isThirdTerm)
                            <th>1st Term<br>Total</th>
                            <th>2nd Term<br>Total</th>
                        @endif
                        @foreach($assessmentTypes as $assessmentType)
                            <th>{{ strtoupper(substr($assessmentType->name,0,6)) }}<br>/{{ number_format($assessmentType->weight_percentage,0) }}</th>
                        @endforeach
                        @if($isThirdTerm)
                            <th>3rd Term<br>Total</th>
                            <th>Annual<br>Total</th>
                            <th>Avg</th>
                        @else
                            <th>Total</th>
                        @endif
                        <th>Grade</th>
                        <th>Pos</th>
                        <th>Low</th>
                        <th>High</th>
                        <th style="text-align:left">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjectRows as $subjectRow)
                        <tr>
                            <td class="subject">{{ $subjectRow['subject_name'] }}</td>
                            @if($isThirdTerm)
                                <td>{{ $subjectRow['term1_avg'] ?? '—' }}</td>
                                <td>{{ $subjectRow['term2_avg'] ?? '—' }}</td>
                            @endif
                            @foreach($assessmentTypes as $assessmentType)
                                <td>{{ $subjectRow['scores'][$assessmentType->id] ?? '—' }}</td>
                            @endforeach
                            @if($isThirdTerm)
                                <td>{{ $subjectRow['term3_avg'] ?? $subjectRow['total'] ?? '—' }}</td>
                                <td class="strong">{{ $subjectRow['annual_total'] ?? '—' }}</td>
                                <td class="strong">{{ $subjectRow['cumulative_avg'] ?? '—' }}</td>
                            @else
                                <td class="strong">{{ $subjectRow['total'] ?? '—' }}</td>
                            @endif
                            <td class="{{ $subjectRow['is_pass'] ? 'pass' : 'fail' }}">{{ $subjectRow['grade'] ?? '—' }}</td>
                            <td>{{ $subjectRow['class_position'] ?? '—' }}</td>
                            <td>{{ $subjectRow['class_lowest'] ?? '—' }}</td>
                            <td>{{ $subjectRow['class_highest'] ?? '—' }}</td>
                            <td class="remark">{{ $subjectRow['remark'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($gradingSystem->count())
            <div class="grade-scale">
                @foreach($gradingSystem->sortByDesc('min_score') as $grade)
                    <div class="grade-item">
                        <strong>{{ $grade->grade_letter }}</strong>
                        <span>{{ $grade->min_score }}-{{ $grade->max_score }} · {{ $grade->remark }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="section-bar">Behavioural Development and Attendance</div>
        <section class="development">
            <div class="dev-panel">
                <div class="panel-title">Affective Domain</div>
                <table class="domain-table">
                    <thead><tr><th>Attribute</th><th>Score</th><th>Rating</th></tr></thead>
                    <tbody>
                    @forelse($affectiveSkills as $skill)
                        @php($rating=(int)($skillRatings->where('skill_definition_id',$skill->id)->first()?->rating ?? 0))
                        <tr><td>{{ $skill->name }}</td><td><span class="score-pill {{ $rating ? 'recorded' : '' }}">{{ $rating ?: '—' }}</span></td><td>{{ $ratingLabels[$rating] ?? 'Not rated' }}</td></tr>
                    @empty
                        <tr><td colspan="3">No affective ratings recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="dev-panel">
                <div class="panel-title">Psychomotor Domain</div>
                <table class="domain-table">
                    <thead><tr><th>Skill</th><th>Score</th><th>Rating</th></tr></thead>
                    <tbody>
                    @forelse($psychomotorSkills as $skill)
                        @php($rating=(int)($skillRatings->where('skill_definition_id',$skill->id)->first()?->rating ?? 0))
                        <tr><td>{{ $skill->name }}</td><td><span class="score-pill {{ $rating ? 'recorded' : '' }}">{{ $rating ?: '—' }}</span></td><td>{{ $ratingLabels[$rating] ?? 'Not rated' }}</td></tr>
                    @empty
                        <tr><td colspan="3">No psychomotor ratings recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="dev-panel">
                <div class="panel-title">Attendance Record</div>
                <table class="attendance-table">
                    <tbody>
                        <tr><th>Days School Open</th><td>{{ $attendanceSummary['days_open'] ?? '—' }}</td></tr>
                        <tr><th>Days Present</th><td class="good">{{ $attendanceSummary['days_present'] ?? '—' }}</td></tr>
                        <tr><th>Days Absent</th><td class="risk">{{ $attendanceSummary['days_absent'] ?? '—' }}</td></tr>
                        <tr><th>Attendance Rate</th><td>{{ $attendanceSummary['rate'] ?? '—' }}{{ is_numeric($attendanceSummary['rate'] ?? null) ? '%' : '' }}</td></tr>
                        <tr><th>Class Position</th><td>{{ $ordinal($pos) }} of {{ $total ?: '—' }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="rating-key">Rating scale: 1 Poor · 2 Fair · 3 Good · 4 Very Good · 5 Excellent</div>
        </section>

        <section class="remarks">
            <div class="remark">
                <div class="remark-title">Form Teacher's Comment</div>
                <div class="remark-body">
                    {{ $summary->form_tutor_remark ?: 'No form-teacher comment has been entered for this term.' }}
                    @if($formTeacherName)<div class="remark-by">Form Teacher: {{ $formTeacherName }}</div>@endif
                </div>
            </div>
            <div class="remark">
                <div class="remark-title">Principal's Comment</div>
                <div class="remark-body">{{ $summary->principal_remark ?: '—' }}</div>
            </div>
        </section>

        <footer class="report-footer {{ $isThirdTerm ? 'third' : '' }}">
            <div class="footer-cell"><span class="label">Form Teacher</span><span class="value">{{ $formTeacherName ?: '—' }}</span></div>
            <div class="footer-cell"><span class="label">Next Term Begins</span><span class="value">{{ $term->next_term_begins ? \Carbon\Carbon::parse($term->next_term_begins)->format('d M Y') : 'To be announced' }}</span></div>
            @if($isActualThirdTerm ?? false)
                <div class="footer-cell">
                    <span class="label">Promotion Decision</span>
                    <span class="value">
                        {{ $summary->promotion_decision ? ucwords(str_replace('_',' ',$summary->promotion_decision)) : 'Pending / Not Recorded' }}
                        @if($summary->promoted_to_class)<br><small>Promoted to: {{ $summary->promoted_to_class }}</small>@endif
                    </span>
                </div>
            @endif
        </footer>
        <div class="generated">Generated by EduCore for {{ $tenant?->name }} on {{ now()->format('d F Y') }}</div>
    </article>
</div>
@endsection
