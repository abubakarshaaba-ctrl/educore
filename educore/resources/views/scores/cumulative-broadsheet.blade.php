@extends('layouts.app')
@section('title','Cumulative Broadsheet')
@section('page-title','Cumulative Broadsheet')

@push('styles')
<style>
.cb{max-width:1420px;margin:0 auto}.tabs{display:flex;gap:7px;overflow-x:auto;margin-bottom:12px}.tab{flex:0 0 auto;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);text-decoration:none;font-size:11px;font-weight:800}.tab.active{background:var(--midnight);border-color:var(--midnight);color:#fff}.hero{padding:16px 18px;margin-bottom:14px;border-radius:13px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff}.hero h2{margin:0 0 5px;font-size:18px}.hero p{margin:0;color:#DCE5F2;font-size:11.5px;line-height:1.55}.panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.head{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:11px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.body{padding:14px}.filters{display:grid;grid-template-columns:minmax(220px,1fr) minmax(220px,1fr) auto;gap:9px;align-items:end}.fg{display:flex;flex-direction:column;gap:5px}.fl{font-size:10px;font-weight:800;color:var(--slate)}.fc{min-height:39px;width:100%;border:1px solid var(--border);border-radius:8px;background:#fff;padding:8px 10px;font:500 11.5px inherit}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:39px;padding:8px 13px;border-radius:8px;border:0;font:800 11px inherit;text-decoration:none;cursor:pointer}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.actions{display:flex;gap:7px;flex-wrap:wrap}.context{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 14px;background:#FBFCFE;border:1px solid var(--border);border-radius:10px;margin-bottom:12px}.context h3{margin:0;font-size:14px;color:var(--midnight)}.context p{margin:3px 0 0;font-size:10.5px;color:var(--slate-light)}.sheet{overflow:auto;border:1px solid var(--border);border-radius:10px;max-height:70vh}.sheet table{width:100%;min-width:940px;border-collapse:collapse;white-space:nowrap}.sheet th{position:sticky;top:0;z-index:3;padding:8px 7px;background:var(--midnight);color:#fff;font-size:9px;text-align:center}.sheet th.student{left:0;z-index:5;text-align:left;min-width:190px}.sheet td{padding:8px 7px;border-right:1px solid #EEF2F7;border-bottom:1px solid #EEF2F7;text-align:center;font-size:10.5px;color:var(--slate)}.sheet td.student{position:sticky;left:0;z-index:2;background:#fff;text-align:left}.sheet tbody tr:nth-child(even) td{background:#FAFBFC}.sheet tbody tr:nth-child(even) td.student{background:#FAFBFC}.name{font-weight:800;color:var(--midnight)}.adm{font-size:9px;color:var(--slate-light);margin-top:2px}.score{font-weight:800;color:var(--midnight)}.sum{font-weight:800;background:#F8FAFC!important}.stats td{background:#EFF6FF!important;font-size:9px}.empty{padding:30px;text-align:center;color:var(--slate-light);font-size:11.5px}.print-head{display:none}
@media(max-width:760px){.filters{grid-template-columns:1fr}.actions{display:grid;grid-template-columns:1fr 1fr}.actions .btn{width:100%}.sheet{max-height:none}.body{padding:12px}}
@media print{@page{size:A4 landscape;margin:9mm}body *{visibility:hidden!important}#cumulative-print,#cumulative-print *{visibility:visible!important}#cumulative-print{position:absolute;left:0;top:0;width:100%;border:0}.sheet{overflow:visible;max-height:none;border:0}.sheet table{min-width:0;white-space:normal}.sheet th{position:static;background:#071E45!important;color:#fff!important;font-size:7px;padding:4px}.sheet td{position:static!important;background:#fff!important;font-size:7.5px;padding:4px}.print-head{display:block;text-align:center;margin-bottom:8px}.print-head strong{display:block;font-size:13px;color:#071E45}.print-head span{font-size:8px;color:#475569}}
</style>
@endpush

@section('content')
<div class="cb">
    <div class="tabs">
        <a class="tab" href="{{ route('scores.broadsheet') }}">Termly Broadsheet</a>
        <a class="tab active" href="{{ route('scores.cumulative-broadsheet') }}">Cumulative Broadsheet</a>
        <a class="tab" href="{{ route('parallel-curriculum.results.broadsheet') }}">Parallel Broadsheet</a>
    </div>

    <div class="hero">
        <h2>Conventional Curriculum Cumulative Broadsheet</h2>
        <p>View each learner's session-wide subject averages, term averages, overall cumulative average and position in one clear register.</p>
    </div>

    <section class="panel">
        <div class="head"><span>Broadsheet filters</span><span>Session cumulative</span></div>
        <div class="body">
            <form method="GET" action="{{ route('scores.cumulative-broadsheet') }}" class="filters">
                <div class="fg">
                    <label class="fl">Class</label>
                    <select class="fc" name="class_arm_id" required>
                        <option value="">Select class</option>
                        @foreach($classArms as $arm)
                            <option value="{{ $arm->id }}" @selected((int)request('class_arm_id')===(int)$arm->id)>{{ $arm->classLevel?->name }} {{ $arm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Academic session</label>
                    <select class="fc" name="session_id" required>
                        <option value="">Select session</option>
                        @foreach($sessions as $item)
                            <option value="{{ $item->id }}" @selected((int)request('session_id')===(int)$item->id)>{{ $item->name }}{{ $item->is_current?' · Current':'' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="actions">
                    <button class="btn btn-p" type="submit">Generate</button>
                    @if(isset($matrix) && $matrix->isNotEmpty())
                        <a class="btn btn-s" target="_blank" href="{{ route('scores.cumulative-broadsheet.pdf',['class_arm_id'=>$classArm->id,'session_id'=>$session->id]) }}">PDF</a>
                        <button class="btn btn-s" type="button" onclick="window.print()">Print</button>
                    @endif
                </div>
            </form>
        </div>
    </section>

    @if(isset($matrix))
    <section class="panel" id="cumulative-print">
        <div class="print-head">
            <strong>{{ auth()->user()->tenant?->name }}</strong>
            <span>Cumulative Broadsheet · {{ $classArm->classLevel?->name }} {{ $classArm->name }} · {{ $session->name }}</span>
        </div>
        @if($matrix->isEmpty())
            <div class="empty">No cumulative score data is available for this class and session.</div>
        @else
            <div class="body">
                <div class="context">
                    <div><h3>{{ $classArm->classLevel?->name }} {{ $classArm->name }}</h3><p>{{ $session->name }} · {{ $terms->count() }} term(s) · {{ $subjects->count() }} subjects</p></div>
                </div>
                <div class="sheet">
                    <table>
                        <thead><tr>
                            <th class="student">Learner</th>
                            @foreach($terms as $index=>$term)<th>{{ $index+1 }}T Avg</th>@endforeach
                            @foreach($subjects as $subject)<th title="{{ $subject->name }}">{{ $subject->code ?: IlluminateSupportStr::limit($subject->name,7,'') }}</th>@endforeach
                            <th>Total</th><th>Avg</th><th>Pos</th>
                        </tr></thead>
                        <tbody>
                        @foreach($matrix as $row)
                            <tr>
                                <td class="student"><div class="name">{{ $row['student']->full_name }}</div><div class="adm">{{ $row['student']->admission_number }}</div></td>
                                @foreach($terms as $term)
                                    @php($ta=$row['term_averages'][(int)$term->id] ?? null)
                                    <td>{{ $ta===null?'—':number_format($ta,1) }}</td>
                                @endforeach
                                @foreach($subjects as $subject)
                                    @php($sd=$row['subjects'][(int)$subject->id] ?? null)
                                    <td>@if(($sd['average'] ?? null)!==null)<span class="score">{{ number_format($sd['average'],1) }}</span><div style="font-size:8px;color:var(--slate-light)">{{ $sd['grade'] }}</div>@else — @endif</td>
                                @endforeach
                                <td class="sum">{{ number_format($row['total'],1) }}</td>
                                <td class="sum">{{ number_format($row['average'],1) }}</td>
                                <td class="sum">{{ $row['position'] }}</td>
                            </tr>
                        @endforeach
                        <tr class="stats">
                            <td class="student"><strong>Class Stats</strong></td>
                            @foreach($terms as $term)<td>—</td>@endforeach
                            @foreach($subjects as $subject)
                                @php($st=$subjectStats->get((int)$subject->id,[]))
                                <td>H {{ $st['highest'] ?? '—' }}<br>L {{ $st['lowest'] ?? '—' }}<br>A {{ $st['avg'] ?? '—' }}</td>
                            @endforeach
                            <td colspan="3"></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
    @else
        <section class="panel"><div class="empty">Select a class and academic session to generate the cumulative broadsheet.</div></section>
    @endif
</div>
@endsection
