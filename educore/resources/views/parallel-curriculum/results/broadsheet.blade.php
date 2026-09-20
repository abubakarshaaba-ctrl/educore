@extends('layouts.app')
@section('title','Parallel Curriculum Broadsheet')
@section('page-title','Parallel Curriculum Broadsheet')

@push('styles')
<style>
.pcb{max-width:1420px;margin:0 auto}.hero{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:16px 18px;margin-bottom:14px;border-radius:13px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff}.hero h2{margin:0 0 5px;font-size:18px}.hero p{margin:0;color:#DCE5F2;font-size:11.5px;line-height:1.55}.panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:11px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.body{padding:14px}.filters{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px;align-items:end}.fg{display:flex;flex-direction:column;gap:5px}.fl{font-size:10px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:39px;border:1px solid var(--border);border-radius:8px;padding:8px 10px;background:#fff;font:500 11.5px inherit}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:39px;padding:8px 13px;border-radius:8px;border:0;font:800 11px inherit;text-decoration:none;cursor:pointer}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.actions{display:flex;gap:7px;flex-wrap:wrap}.context{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 14px;background:#FBFCFE;border:1px solid var(--border);border-radius:10px;margin-bottom:12px}.context h3{margin:0;color:var(--midnight);font-size:14px}.context p{margin:3px 0 0;color:var(--slate-light);font-size:10.5px}.chips{display:flex;gap:6px;flex-wrap:wrap}.chip{padding:4px 8px;border-radius:999px;background:#F2F4F7;color:#475467;font-size:9.5px;font-weight:800}.sheet{overflow:auto;border:1px solid var(--border);border-radius:10px;max-height:70vh}.sheet table{width:100%;min-width:920px;border-collapse:collapse;white-space:nowrap}.sheet th{position:sticky;top:0;z-index:3;padding:8px 7px;background:var(--midnight);color:#fff;border-right:1px solid rgba(255,255,255,.12);font-size:9px;text-align:center}.sheet th.student{left:0;z-index:5;text-align:left;min-width:190px}.sheet td{padding:8px 7px;border-right:1px solid #EEF2F7;border-bottom:1px solid #EEF2F7;text-align:center;font-size:10.5px;color:var(--slate)}.sheet td.student{position:sticky;left:0;z-index:2;background:#fff;text-align:left;min-width:190px}.sheet tbody tr:nth-child(even) td{background:#FAFBFC}.sheet tbody tr:nth-child(even) td.student{background:#FAFBFC}.student-name{font-weight:800;color:var(--midnight)}.student-adm{margin-top:2px;font-size:9px;color:var(--slate-light)}.score{font-weight:800;color:var(--midnight)}.missing{color:#CBD5E1}.summary{font-weight:800;background:#F8FAFC!important}.pos{color:#B7791F}.stats td{background:#EFF6FF!important;font-size:9px;line-height:1.45}.empty{padding:30px;text-align:center;color:var(--slate-light);font-size:11.5px}.print-head{display:none}
@media(max-width:1050px){.filters{grid-template-columns:repeat(3,minmax(0,1fr))}.actions{grid-column:1/-1}}
@media(max-width:700px){.filters{grid-template-columns:1fr 1fr}.actions{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr}.actions .btn{width:100%}.body{padding:12px}.sheet{max-height:none}.hero{padding:14px}}
@media(max-width:480px){.filters{grid-template-columns:1fr}.actions{grid-template-columns:1fr}}
@media print{
 @page{size:A4 landscape;margin:9mm}
 body *{visibility:hidden!important}
 #parallel-broadsheet-print,#parallel-broadsheet-print *{visibility:visible!important}
 #parallel-broadsheet-print{position:absolute;left:0;top:0;width:100%;border:0}
 #parallel-broadsheet-print .sheet{overflow:visible;max-height:none;border:0}
 #parallel-broadsheet-print .sheet table{min-width:0;white-space:normal}
 #parallel-broadsheet-print .sheet th{position:static;background:#071E45!important;color:#fff!important;font-size:7px;padding:4px}
 #parallel-broadsheet-print .sheet td{position:static!important;font-size:7.5px;padding:4px;background:#fff!important}
 #parallel-broadsheet-print .print-head{display:block;text-align:center;margin-bottom:8px}
 #parallel-broadsheet-print .print-head strong{display:block;font-size:13px;color:#071E45}
 #parallel-broadsheet-print .print-head span{display:block;font-size:8px;color:#475569;margin-top:3px}
}
</style>
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pcb">
    @include('parallel-curriculum.partials.module-navigation')

    <div class="hero">
        <div>
            <h2>Parallel Curriculum Broadsheet</h2>
            <p>Switch between termly and cumulative performance without leaving the workspace. Cumulative mode averages each learner's available subject results across the selected session.</p>
        </div>
    </div>

    <section class="panel">
        <div class="head"><span>Broadsheet filters</span><span>{{ ucfirst($mode) }}</span></div>
        <div class="body">
            <form method="GET" action="{{ route('parallel-curriculum.results.broadsheet') }}" class="filters" id="parallel-broadsheet-filter">
                <div class="fg">
                    <label class="fl">View</label>
                    <select class="fc" name="mode" id="broadsheet-mode">
                        <option value="termly" @selected($mode==='termly')>Termly Broadsheet</option>
                        <option value="cumulative" @selected($mode==='cumulative')>Cumulative Broadsheet</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Parallel class</label>
                    <select class="fc" name="class_id" required>
                        @foreach($classes as $item)
                            <option value="{{ $item->id }}" @selected((int)$selectedClass?->id===(int)$item->id)>{{ $item->curriculum?->name }} · {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Arm</label>
                    <select class="fc" name="arm_id">
                        <option value="">All arms</option>
                        @foreach(($selectedClass?->arms ?? collect()) as $item)
                            <option value="{{ $item->id }}" @selected((int)$selectedArm?->id===(int)$item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg period-filter" data-mode="termly">
                    <label class="fl">Academic term</label>
                    <select class="fc" name="term_id">
                        @foreach($terms as $item)
                            <option value="{{ $item->id }}" @selected((int)$selectedTerm?->id===(int)$item->id)>{{ $item->session?->name }} · {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg period-filter" data-mode="cumulative">
                    <label class="fl">Academic session</label>
                    <select class="fc" name="session_id">
                        @foreach($sessions as $item)
                            <option value="{{ $item->id }}" @selected((int)$selectedSession?->id===(int)$item->id)>{{ $item->name }}{{ $item->is_current?' · Current':'' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="actions">
                    <button class="btn btn-p" type="submit">Generate</button>
                    @if($matrix->isNotEmpty())
                        <a class="btn btn-s" target="_blank" href="{{ route('parallel-curriculum.results.broadsheet.pdf', request()->query()) }}">PDF</a>
                        <button class="btn btn-s" type="button" onclick="window.print()">Print</button>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <section class="panel" id="parallel-broadsheet-print">
        <div class="print-head">
            <strong>{{ auth()->user()->tenant?->name }}</strong>
            <span>{{ $selectedClass?->curriculum?->name }} · {{ $selectedClass?->name }}{{ $selectedArm ? ' '.$selectedArm->name : ' · All Arms' }} · {{ $mode==='cumulative' ? ($selectedSession?->name.' Cumulative') : (($selectedTerm?->name ?? '').' '.($selectedTerm?->session?->name ?? '')) }}</span>
        </div>

        @if(!$selectedClass || $matrix->isEmpty())
            <div class="empty">No result data is available for the selected parallel class and period.</div>
        @else
            <div class="body">
                <div class="context">
                    <div>
                        <h3>{{ $selectedClass->curriculum?->name }} · {{ $selectedClass->name }}{{ $selectedArm ? ' '.$selectedArm->name : '' }}</h3>
                        <p>{{ $mode==='cumulative' ? ($selectedSession?->name.' · cumulative across '.$periodTerms->count().' term(s)') : (($selectedTerm?->session?->name ?? '').' · '.($selectedTerm?->name ?? '')) }}</p>
                    </div>
                    <div class="chips">
                        <span class="chip">{{ $matrix->count() }} learners</span>
                        <span class="chip">{{ $subjects->count() }} subjects</span>
                        @if($mode==='cumulative')<span class="chip">{{ $periodTerms->count() }} terms</span>@endif
                    </div>
                </div>

                <div class="sheet">
                    <table>
                        <thead>
                            <tr>
                                <th class="student">Learner</th>
                                @if($mode==='cumulative')
                                    @foreach($periodTerms as $index=>$periodTerm)
                                        <th>{{ $index+1 }}T Avg</th>
                                    @endforeach
                                @endif
                                @foreach($subjects as $subject)
                                    <th title="{{ $subject->name }}">{{ $subject->code ?: IlluminateSupportStr::limit($subject->name,7,'') }}</th>
                                @endforeach
                                <th>Total</th><th>Avg</th><th>Pos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matrix as $row)
                                <tr>
                                    <td class="student">
                                        <div class="student-name">{{ $row['student']->full_name }}</div>
                                        <div class="student-adm">{{ $row['student']->admission_number }}</div>
                                    </td>
                                    @if($mode==='cumulative')
                                        @foreach($periodTerms as $periodTerm)
                                            @php($ta=$row['term_averages'][(int)$periodTerm->id] ?? null)
                                            <td>{{ $ta===null ? '—' : number_format($ta,1) }}</td>
                                        @endforeach
                                    @endif
                                    @foreach($subjects as $subject)
                                        @php($sd=$row['subjects'][(int)$subject->id] ?? null)
                                        <td>
                                            @if(($sd['score'] ?? null)!==null)
                                                <span class="score">{{ number_format($sd['score'],1) }}</span>
                                                @if($mode==='termly' && !empty($sd['grade']))<div style="font-size:8.5px;color:var(--slate-light)">{{ $sd['grade'] }}</div>@endif
                                            @else
                                                <span class="missing">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="summary">{{ number_format($row['total'],1) }}</td>
                                    <td class="summary">{{ $row['average']===null ? '—' : number_format($row['average'],1) }}</td>
                                    <td class="summary pos">{{ $row['position'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                            <tr class="stats">
                                <td class="student"><strong>Class Stats</strong></td>
                                @if($mode==='cumulative')
                                    @foreach($periodTerms as $periodTerm)<td>—</td>@endforeach
                                @endif
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
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
    const mode=document.getElementById('broadsheet-mode');
    const refresh=()=>{
        document.querySelectorAll('.period-filter').forEach(el=>{
            el.style.display=el.dataset.mode===mode.value?'flex':'none';
            el.querySelectorAll('select,input').forEach(input=>input.disabled=el.dataset.mode!==mode.value);
        });
    };
    mode?.addEventListener('change',refresh);
    refresh();
});
</script>
@endpush
