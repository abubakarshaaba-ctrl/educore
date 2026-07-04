@extends('layouts.app')
@section('title','Grade Book')
@section('page-title','Grade Book')
@push('styles')
<style>
.filter-card{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap}
.fg{display:flex;flex-direction:column;gap:4px}
.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;min-width:180px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;text-decoration:none}
.btn-p{background:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:auto;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between;white-space:nowrap}
.gb-table{border-collapse:collapse;min-width:100%}
.gb-table th{font-size:9.5px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:7px 10px;text-align:center;background:#F8FAFC;border:1px solid var(--border);white-space:nowrap}
.gb-table th.sticky{text-align:left;position:sticky;left:0;z-index:2;background:#F8FAFC;min-width:160px}
.gb-table td{padding:7px 10px;border:1px solid var(--border);font-size:12.5px;text-align:center;vertical-align:middle}
.gb-table td.sticky{text-align:left;position:sticky;left:0;background:white;z-index:1;font-weight:600}
.gb-table tr:nth-child(even) td{background:#FAFAFA}
.gb-table tr:nth-child(even) td.sticky{background:#FAFAFA}
.score-cell{min-width:42px}
.sc-good{color:var(--emerald);font-weight:700}
.sc-warn{color:var(--amber);font-weight:600}
.sc-fail{color:var(--crimson);font-weight:700}
.sc-empty{color:#D1D5DB}
.subject-hd{writing-mode:vertical-lr;transform:rotate(180deg);font-size:9px;padding:6px 4px;max-height:80px}
</style>
@endpush
@section('content')
<form method="GET">
<div class="filter-card">
    <div class="fg"><span class="fl">Class</span>
        <select name="class_arm_id" class="fc">
            <option value="">Select class</option>
            @foreach($classArms as $arm)<option value="{{ $arm->id }}" {{ request('class_arm_id')==$arm->id?'selected':'' }}>{{ $arm->classLevel->name }} {{ $arm->name }}</option>@endforeach
        </select>
    </div>
    <div class="fg"><span class="fl">Term</span>
        <select name="term_id" class="fc">
            <option value="">Select term</option>
            @foreach($terms as $term)<option value="{{ $term->id }}" {{ request('term_id') ? (request('term_id')==$term->id?'selected':'') : ($term->is_current?'selected':'') }}>{{ $term->name }} — {{ $term->session->name }}</option>@endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-p">Load Grade Book</button>
    @if(isset($classArm))
    <a href="{{ route('scores.entry',request()->only(['class_arm_id','term_id'])) }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">Enter Scores</a>
    @endif
</div>
</form>

@if(isset($students) && isset($subjects))
<div class="card">
    <div class="ch">
        {{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ $term->name }}
        <span style="font-size:11px;font-weight:400;color:var(--slate)">{{ $students->count() }} students · {{ $subjects->count() }} subjects · {{ $assessmentTypes->count() }} assessment type(s)</span>
    </div>
    <div style="overflow-x:auto">
    <table class="gb-table">
        <thead>
            <tr>
                <th class="sticky" rowspan="2">Student</th>
                @foreach($subjects as $sub)
                    <th colspan="{{ $assessmentTypes->count() }}">{{ $sub->name }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($subjects as $sub)
                    @foreach($assessmentTypes as $at)
                        <th title="{{ $at->name }} ({{ $at->weight_percentage }}%)">{{ strtoupper(substr($at->name,0,3)) }}<br><span style="font-size:8px;font-weight:400">{{ $at->weight_percentage }}%</span></th>
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            <tr>
                <td class="sticky">{{ $student->full_name }}<br><span style="font-size:10px;color:var(--slate-light)">{{ $student->admission_number }}</span></td>
                @foreach($subjects as $sub)
                    @foreach($assessmentTypes as $at)
                        @php $sc = $scores[$student->id.'-'.$sub->id][$at->id] ?? null; $v = $sc?->score; @endphp
                        <td class="score-cell {{ $v!==null ? ($v>=70?'sc-good':($v>=50?'sc-warn':'sc-fail')) : 'sc-empty' }}">
                            {{ $v ?? '—' }}
                        </td>
                    @endforeach
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
@else
<div style="background:white;border:1px solid var(--border);border-radius:12px;padding:60px;text-align:center;color:var(--slate-light)">
    Select a class and term above to load the grade book.
</div>
@endif
@endsection