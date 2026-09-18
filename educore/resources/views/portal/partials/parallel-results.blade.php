@if(isset($parallelResults) && $parallelResults->isNotEmpty())
@once
<style>
.parallel-portal-results{margin-top:20px}
.parallel-portal-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.parallel-portal-heading h3{margin:0;font-size:17px;font-weight:800;color:var(--midnight,#071E45);line-height:1.3}
.parallel-portal-heading p{margin:3px 0 0;font-size:12.5px;color:var(--muted,#64748B);line-height:1.5}
.parallel-result-card{background:#fff;border:1px solid var(--border,#E2E8F0);border-radius:12px;overflow:hidden;margin-bottom:14px;box-shadow:0 1px 3px rgba(15,23,42,.05)}
.parallel-result-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 16px;background:#F8FAFC;border-bottom:1px solid var(--border,#E2E8F0)}
.parallel-result-title{min-width:0}
.parallel-result-title strong{display:block;font-size:14px;line-height:1.4;color:var(--midnight,#071E45);overflow-wrap:anywhere}
.parallel-result-title span{display:block;margin-top:3px;font-size:12px;line-height:1.45;color:var(--muted,#64748B)}
.parallel-result-head-actions{display:flex;align-items:center;justify-content:flex-end;gap:7px;flex-wrap:wrap}
.parallel-result-download{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:7px 11px;border-radius:8px;background:var(--midnight,#071E45);color:#fff!important;text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap}
.parallel-result-download:hover{opacity:.92}
.parallel-result-badge{display:inline-flex;align-items:center;justify-content:center;min-height:28px;padding:4px 9px;border-radius:999px;background:#ECFDF3;color:#067647;font-size:11px;font-weight:800;white-space:nowrap}
.parallel-result-body{padding:15px}
.parallel-result-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:14px}
.parallel-result-kpi{padding:12px;border:1px solid var(--border,#E2E8F0);border-radius:10px;background:#fff;text-align:center;min-width:0}
.parallel-result-kpi strong{display:block;font-size:18px;line-height:1.25;color:var(--midnight,#071E45);overflow-wrap:anywhere}
.parallel-result-kpi span{display:block;margin-top:3px;font-size:11.5px;line-height:1.35;color:var(--muted,#64748B)}
.parallel-result-table-wrap{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--border,#E2E8F0);border-radius:10px}
.parallel-result-table{width:100%;min-width:760px;border-collapse:collapse}
.parallel-result-table th{padding:10px 11px;background:var(--midnight,#071E45);color:#fff;font-size:12px;font-weight:700;text-align:left;white-space:nowrap}
.parallel-result-table td{padding:10px 11px;border-bottom:1px solid #EEF2F7;font-size:12.5px;line-height:1.45;color:#475569;vertical-align:top}
.parallel-result-table tr:last-child td{border-bottom:0}
.parallel-result-table .parallel-subject-name{font-weight:800;color:var(--midnight,#071E45)}
.parallel-assessments{display:flex;gap:5px 8px;flex-wrap:wrap;min-width:220px}
.parallel-assessment-chip{display:inline-flex;padding:3px 7px;border-radius:999px;background:#F1F5F9;color:#475569;font-size:11px;line-height:1.35;white-space:nowrap}
.parallel-grade{display:inline-flex;align-items:center;justify-content:center;min-width:32px;padding:3px 7px;border-radius:999px;background:#EEF2FF;color:#3730A3;font-size:11px;font-weight:800}
.parallel-grade.fail{background:#FEF2F2;color:#B42318}
.parallel-result-mobile{display:none}
.parallel-mobile-subject{padding:12px;border:1px solid var(--border,#E2E8F0);border-radius:10px;margin-bottom:9px;background:#fff}
.parallel-mobile-subject:last-child{margin-bottom:0}
.parallel-mobile-subject-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
.parallel-mobile-subject-name{font-size:13px;font-weight:800;color:var(--midnight,#071E45);line-height:1.4}
.parallel-mobile-subject-total{font-size:14px;font-weight:800;color:var(--midnight,#071E45);white-space:nowrap}
.parallel-mobile-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:9px}
.parallel-mobile-meta div{padding:8px 9px;border-radius:8px;background:#F8FAFC;min-width:0}
.parallel-mobile-meta span{display:block;font-size:10.5px;color:var(--muted,#64748B);margin-bottom:2px}
.parallel-mobile-meta strong{display:block;font-size:12px;color:var(--midnight,#071E45);overflow-wrap:anywhere}
.parallel-mobile-assessments{margin-top:9px;display:flex;gap:5px;flex-wrap:wrap}
@media(max-width:760px){
    .parallel-result-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}
    .parallel-result-head,.parallel-result-body{padding:13px}
    .parallel-result-head-actions{width:100%;justify-content:flex-start}
    .parallel-result-download{min-height:42px}
}
@media(max-width:640px){
    .parallel-result-table-wrap{display:none}
    .parallel-result-mobile{display:block}
    .parallel-portal-heading h3{font-size:16px}
}
@media(max-width:380px){
    .parallel-result-kpis{grid-template-columns:1fr 1fr;gap:8px}
    .parallel-result-kpi{padding:10px 8px}
    .parallel-result-kpi strong{font-size:16px}
    .parallel-mobile-meta{grid-template-columns:1fr}
}
</style>
@endonce

<section class="parallel-portal-results">
    <div class="parallel-portal-heading">
        <div>
            <h3>Other Curriculum Results</h3>
            <p>Published results from additional curriculum programmes offered by the school.</p>
        </div>
    </div>

    @foreach($parallelResults as $parallelResult)
        <article class="parallel-result-card">
            <div class="parallel-result-head">
                <div class="parallel-result-title">
                    <strong>{{ $parallelResult['curriculum'] ?: 'Parallel Curriculum' }} · {{ $parallelResult['class_name'] ?: 'Class' }}@if(!empty($parallelResult['class_arm_name'])) · Arm {{ $parallelResult['class_arm_name'] }}@endif</strong>
                    <span>{{ $parallelResult['term'] ?: 'Term' }} · {{ $parallelResult['session'] ?: 'Academic Session' }}</span>
                </div>
                <div class="parallel-result-head-actions">
                    <span class="parallel-result-badge">Published</span>
                    @if(!empty($parallelResult['pdf_url']))
                        <a class="parallel-result-download" href="{{ $parallelResult['pdf_url'] }}">Download PDF</a>
                    @endif
                </div>
            </div>

            <div class="parallel-result-body">
                <div class="parallel-result-kpis">
                    <div class="parallel-result-kpi">
                        <strong>{{ $parallelResult['average'] === null ? '—' : number_format((float) $parallelResult['average'], 1).'%' }}</strong>
                        <span>Average</span>
                    </div>
                    <div class="parallel-result-kpi">
                        <strong>{{ $parallelResult['position'] ?: '—' }}@if($parallelResult['class_size'])<small style="font-size:11px;font-weight:700">/{{ $parallelResult['class_size'] }}</small>@endif</strong>
                        <span>Position</span>
                    </div>
                    <div class="parallel-result-kpi">
                        <strong>{{ $parallelResult['subjects_offered'] ?? '—' }}</strong>
                        <span>Subjects</span>
                    </div>
                    <div class="parallel-result-kpi">
                        <strong>{{ $parallelResult['subjects_failed'] ?? 0 }}</strong>
                        <span>Failed</span>
                    </div>
                </div>

                <div class="parallel-result-table-wrap">
                    <table class="parallel-result-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Assessments</th>
                                <th>Total</th>
                                <th>Grade</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($parallelResult['subjects'] ?? []) as $subject)
                                <tr>
                                    <td class="parallel-subject-name">{{ $subject['subject'] ?? 'Subject' }}</td>
                                    <td>
                                        <div class="parallel-assessments">
                                            @forelse(($subject['assessments'] ?? []) as $assessment)
                                                <span class="parallel-assessment-chip">
                                                    {{ $assessment['name'] ?? 'Assessment' }}:
                                                    {{ $assessment['score'] === null ? '—' : number_format((float) $assessment['score'], 1) }}/{{ number_format((float) ($assessment['maximum'] ?? 0), 0) }}
                                                </span>
                                            @empty
                                                <span>—</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td><strong>{{ $subject['total'] === null ? '—' : number_format((float) $subject['total'], 1).'%' }}</strong></td>
                                    <td><span class="parallel-grade {{ ($subject['is_pass'] ?? true) ? '' : 'fail' }}">{{ $subject['grade'] ?? '—' }}</span></td>
                                    <td>{{ $subject['remark'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No subject rows are available for this published result.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="parallel-result-mobile">
                    @forelse(($parallelResult['subjects'] ?? []) as $subject)
                        <div class="parallel-mobile-subject">
                            <div class="parallel-mobile-subject-head">
                                <div class="parallel-mobile-subject-name">{{ $subject['subject'] ?? 'Subject' }}</div>
                                <div class="parallel-mobile-subject-total">{{ $subject['total'] === null ? '—' : number_format((float) $subject['total'], 1).'%' }}</div>
                            </div>
                            <div class="parallel-mobile-meta">
                                <div><span>Grade</span><strong>{{ $subject['grade'] ?? '—' }}</strong></div>
                                <div><span>Remark</span><strong>{{ $subject['remark'] ?? '—' }}</strong></div>
                            </div>
                            <div class="parallel-mobile-assessments">
                                @forelse(($subject['assessments'] ?? []) as $assessment)
                                    <span class="parallel-assessment-chip">
                                        {{ $assessment['name'] ?? 'Assessment' }}:
                                        {{ $assessment['score'] === null ? '—' : number_format((float) $assessment['score'], 1) }}/{{ number_format((float) ($assessment['maximum'] ?? 0), 0) }}
                                    </span>
                                @empty
                                    <span class="parallel-assessment-chip">No assessment breakdown</span>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div class="parallel-mobile-subject">No subject rows are available for this published result.</div>
                    @endforelse
                </div>
            </div>
        </article>
    @endforeach
</section>
@endif
