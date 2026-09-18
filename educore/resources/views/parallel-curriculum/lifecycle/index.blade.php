@extends('layouts.app')
@section('title','Parallel Academic Lifecycle')
@section('page-title','Parallel Academic Lifecycle')

@push('styles')
<style>
.pcl{max-width:1280px;margin:0 auto}.tabs{display:flex;gap:6px;overflow-x:auto;margin-bottom:16px}.tab{flex:0 0 auto;padding:8px 13px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);font-size:12px;font-weight:700;text-decoration:none}.tab.active,.tab:hover{background:var(--midnight);color:#fff}.hero{padding:18px 20px;border-radius:14px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff;margin-bottom:16px}.hero h2{margin:0 0 5px;font-size:19px}.hero p{margin:0;color:#DCE5F2;font-size:11.5px;line-height:1.5;max-width:900px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;min-width:0}.card.full{grid-column:1/-1}.head{padding:12px 15px;background:#F8FAFC;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}.head strong{font-size:12.5px;color:var(--midnight)}.head span{font-size:10.5px;color:var(--slate-light)}.body{padding:15px}.filter{display:grid;grid-template-columns:1.2fr 1fr auto;gap:10px;align-items:end;margin-bottom:14px}.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:10px;min-width:0}.fl{font-size:10.5px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:40px;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:#fff;font:500 12px inherit}select[multiple].fc{min-height:150px}.row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.row.three{grid-template-columns:repeat(3,minmax(0,1fr))}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 13px;border-radius:8px;border:0;font:700 11.5px inherit;cursor:pointer;text-decoration:none}.p{background:var(--indigo);color:#fff}.s{background:#fff;color:var(--midnight);border:1px solid var(--border)}.d{background:#FEF2F2;color:#B42318;border:1px solid #FECDCA}.hint{font-size:10.5px;color:var(--slate-light);line-height:1.45}.ok,.err{border-radius:9px;padding:10px 13px;font-size:11px;margin-bottom:12px}.ok{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.err{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.level{padding:12px 0;border-bottom:1px solid #EEF2F7}.level:last-child{border-bottom:0}.arms{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px}.arm{padding:10px;border:1px solid var(--border);border-radius:10px;background:#FAFCFF}.arm strong{display:block;font-size:11.5px;color:var(--midnight)}.arm span{display:block;font-size:10px;color:var(--slate-light);margin-top:2px}.rule{padding:9px 0;border-bottom:1px solid #EEF2F7}.rule:last-child{border-bottom:0}.rule strong{display:block;font-size:11.5px;color:var(--midnight)}.rule span{display:block;font-size:10px;color:var(--slate-light);margin-top:3px}.kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;margin:12px 0}.kpi{padding:10px;border:1px solid var(--border);border-radius:9px;text-align:center}.kpi strong{display:block;font-size:17px;color:var(--midnight)}.kpi span{font-size:9.5px;color:var(--slate-light)}.wrap{overflow-x:auto;border:1px solid var(--border);border-radius:10px}.tbl{width:100%;min-width:850px;border-collapse:collapse}.tbl th{padding:9px 10px;background:var(--midnight);color:#fff;text-align:left;font-size:9.5px;white-space:nowrap}.tbl td{padding:9px 10px;border-bottom:1px solid #EEF2F7;font-size:10.5px;color:var(--slate);vertical-align:top}.badge{display:inline-flex;padding:3px 7px;border-radius:999px;font-size:9.5px;font-weight:800;background:#F2F4F7;color:#475467}.badge.promoted{background:#ECFDF3;color:#067647}.badge.repeat,.badge.retain{background:#FFFAEB;color:#B54708}.badge.graduated{background:#EEF4FF;color:#3538CD}.badge.blocked{background:#FEF3F2;color:#B42318}.check{display:flex;align-items:flex-start;gap:7px;font-size:10.5px;color:var(--slate)}details summary{cursor:pointer;color:var(--indigo);font-size:10.5px;font-weight:800}.mobile{display:none}@media(max-width:840px){.grid{grid-template-columns:1fr}.card.full{grid-column:auto}.filter{grid-template-columns:1fr 1fr}.filter .btn{grid-column:1/-1}.row.three{grid-template-columns:1fr 1fr}.kpis{grid-template-columns:repeat(3,1fr)}}@media(max-width:640px){.hero,.body{padding:13px}.filter,.row,.row.three{grid-template-columns:1fr}.btn{width:100%;min-height:42px}.arms{grid-template-columns:1fr}.kpis{grid-template-columns:repeat(2,1fr)}.desktop{display:none}.mobile{display:block}.mrow{padding:10px;border:1px solid var(--border);border-radius:9px;margin-bottom:7px}.mrow strong{display:block;font-size:11.5px;color:var(--midnight)}.mrow span{display:block;font-size:10px;color:var(--slate-light);margin-top:3px}}
</style>
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pcl">
@if(session('success'))<div class="ok">{{ session('success') }}</div>@endif
@if($errors->any())<div class="err"><strong>Could not complete the operation.</strong> {{ $errors->first() }}</div>@endif

<div class="tabs">
<a class="tab" href="{{ route('parallel-curriculum.index') }}">Parallel Curriculum</a>
<a class="tab" href="{{ route('parallel-curriculum.student-assignments') }}">Student Assignments</a>
<a class="tab active" href="{{ route('parallel-curriculum.lifecycle.index') }}">Academic Lifecycle</a>
<a class="tab" href="{{ route('parallel-curriculum.results.index') }}">Parallel Results</a>
</div>

<div class="hero"><h2>Parallel Academic Lifecycle</h2><p>Manage class arms, class-level grading, promotion rules, next-session promotion and audited intra-/inter-class transfers independently from the conventional school structure.</p></div>

<form class="filter" method="GET" action="{{ route('parallel-curriculum.lifecycle.index') }}">
<div class="fg" style="margin:0"><label class="fl">Parallel curriculum</label><select class="fc" name="parallel_curriculum_id">@foreach($curricula as $curriculum)<option value="{{ $curriculum->id }}" @selected((int)$curriculumId===(int)$curriculum->id)>{{ $curriculum->name }}</option>@endforeach</select></div>
<div class="fg" style="margin:0"><label class="fl">Working session</label><select class="fc" name="session_id">@foreach($sessions as $session)<option value="{{ $session->id }}" @selected((int)$sessionId===(int)$session->id)>{{ $session->name }}{{ $session->is_current?' · Current':'' }}</option>@endforeach</select></div>
<button class="btn p" type="submit">Load Lifecycle</button>
</form>

@if($selectedCurriculum)
<div class="grid">
<section class="card full"><div class="head"><div><strong>1. Class levels and arms</strong><br><span>Each parallel level can have independent arms and capacity.</span></div></div><div class="body">
@foreach($selectedCurriculum->classes as $class)
<div class="level"><div class="rule"><strong>{{ $class->name }}</strong><span>{{ $class->code ?: 'No code' }} · {{ $class->arms->where('is_active',true)->count() }} active arm(s)</span></div>
<div class="arms">
@foreach($class->arms as $arm)
<div class="arm"><strong>{{ $class->name }} {{ $arm->name }}</strong><span>{{ $arm->is_active?'Active':'Archived' }} · Capacity: {{ $arm->capacity ?: 'Unlimited' }}</span>
@if($arm->is_active)
<details><summary>Edit arm</summary><form method="POST" action="{{ route('parallel-curriculum.lifecycle.arms.update',$arm) }}">@csrf @method('PUT')
<div class="row"><div class="fg"><label class="fl">Arm</label><input class="fc" name="name" value="{{ $arm->name }}" required></div><div class="fg"><label class="fl">Code</label><input class="fc" name="code" value="{{ $arm->code }}"></div></div>
<div class="fg"><label class="fl">Capacity</label><input class="fc" type="number" min="1" name="capacity" value="{{ $arm->capacity }}"></div><button class="btn p" type="submit">Save</button></form>
<form method="POST" action="{{ route('parallel-curriculum.lifecycle.arms.archive',$arm) }}" style="margin-top:6px">@csrf @method('DELETE')<button class="btn d" type="submit">Archive</button></form></details>
@endif
</div>
@endforeach
<div class="arm"><strong>Add another arm</strong><form method="POST" action="{{ route('parallel-curriculum.lifecycle.arms.store') }}" style="margin-top:7px">@csrf<input type="hidden" name="parallel_curriculum_class_id" value="{{ $class->id }}">
<div class="row"><div class="fg"><label class="fl">Arm</label><input class="fc" name="name" placeholder="B" required></div><div class="fg"><label class="fl">Code</label><input class="fc" name="code" placeholder="B"></div></div><div class="fg"><label class="fl">Capacity</label><input class="fc" type="number" min="1" name="capacity"></div><button class="btn s" type="submit">Create Arm</button></form></div>
</div></div>
@endforeach
</div></section>

<section class="card"><div class="head"><div><strong>2. Class grade system</strong><br><span>Apply a band to one or several parallel levels.</span></div></div><div class="body">
<form method="POST" action="{{ route('parallel-curriculum.lifecycle.grades.store') }}">@csrf<input type="hidden" name="parallel_curriculum_id" value="{{ $selectedCurriculum->id }}">
<div class="fg"><label class="fl">Class levels</label><select class="fc" name="class_ids[]" multiple required>@foreach($selectedCurriculum->classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select><div class="hint">Class-specific grades override the programme default scale.</div></div>
<div class="row three"><div class="fg"><label class="fl">Grade</label><input class="fc" name="grade_letter" required></div><div class="fg"><label class="fl">Min</label><input class="fc" type="number" step=".01" min="0" max="100" name="min_score" required></div><div class="fg"><label class="fl">Max</label><input class="fc" type="number" step=".01" min="0" max="100" name="max_score" required></div></div>
<div class="row"><div class="fg"><label class="fl">Remark</label><input class="fc" name="remark"></div><div class="fg"><label class="fl">Grade point</label><input class="fc" type="number" step=".01" min="0" name="grade_point"></div></div>
<label class="check"><input type="hidden" name="is_pass_grade" value="0"><input type="checkbox" name="is_pass_grade" value="1" checked> Pass grade</label><div style="margin-top:10px"><button class="btn p" type="submit">Apply Grade Band</button></div></form>
<div style="margin-top:14px">
@foreach($selectedCurriculum->classes as $class)
<div class="rule">
    <strong>{{ $class->name }}</strong>
    @if($class->classGrades->isNotEmpty())
        @foreach($class->classGrades as $grade)
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:6px">
                <span>{{ $grade->grade_letter }} · {{ number_format($grade->min_score,0) }}–{{ number_format($grade->max_score,0) }} · {{ $grade->remark ?: 'No remark' }} · {{ $grade->is_pass_grade ? 'Pass' : 'Fail' }}</span>
                <form method="POST" action="{{ route('parallel-curriculum.lifecycle.grades.destroy',$grade) }}">
                    @csrf @method('DELETE')
                    <button class="btn d" type="submit">Remove</button>
                </form>
            </div>
        @endforeach
    @else
        <span>Uses programme default grading scale.</span>
    @endif
</div>
@endforeach
</div>
</div></section>

<section class="card"><div class="head"><div><strong>3. Promotion rules</strong><br><span>Define pass criteria and the next parallel level.</span></div></div><div class="body">
<form method="POST" action="{{ route('parallel-curriculum.lifecycle.promotion-rules.store') }}">@csrf<input type="hidden" name="parallel_curriculum_id" value="{{ $selectedCurriculum->id }}">
<div class="row"><div class="fg"><label class="fl">Source level</label><select class="fc" name="source_class_id" required><option value="">Select</option>@foreach($selectedCurriculum->classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div><div class="fg"><label class="fl">Next level</label><select class="fc" name="destination_class_id"><option value="">None / terminal</option>@foreach($selectedCurriculum->classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div></div>
<div class="row"><div class="fg"><label class="fl">Minimum average</label><input class="fc" type="number" step=".01" min="0" max="100" name="minimum_average" value="50" required></div><div class="fg"><label class="fl">Max failed subjects</label><input class="fc" type="number" min="0" name="max_failed_subjects" value="2" required></div></div>
<div class="row"><div class="fg"><label class="fl">Failure action</label><select class="fc" name="failure_action"><option value="repeat">Repeat</option><option value="retain">Retain</option></select></div><div class="fg"><label class="fl">Arm strategy</label><select class="fc" name="arm_strategy"><option value="same_name">Keep same arm name</option><option value="first_available">First available arm</option></select></div></div>
<label class="check"><input type="hidden" name="require_complete_result" value="0"><input type="checkbox" name="require_complete_result" value="1" checked> Require complete published final result</label><label class="check" style="margin-top:6px"><input type="hidden" name="is_terminal" value="0"><input type="checkbox" name="is_terminal" value="1"> Terminal level: passing students graduate from this parallel programme</label><div style="margin-top:10px"><button class="btn p" type="submit">Save Rule</button></div></form>
<div style="margin-top:14px">@foreach($selectedCurriculum->classes as $class)@php($rule=$class->promotionRule)<div class="rule"><strong>{{ $class->name }}</strong>@if($rule)<span>{{ $rule->is_terminal?'Terminal → Graduate':'Next: '.($rule->destinationClass?->name?:'Not configured') }} · Avg ≥ {{ number_format($rule->minimum_average,1) }}% · Failed ≤ {{ $rule->max_failed_subjects }} · {{ ucfirst($rule->failure_action) }} if unsuccessful</span>@else<span>No rule configured.</span>@endif</div>@endforeach</div>
</div></section>

<section class="card full"><div class="head"><div><strong>4. Promotion engine</strong><br><span>Preview before execution. Source-session placement remains unchanged.</span></div></div><div class="body">
<form method="GET" action="{{ route('parallel-curriculum.lifecycle.index') }}"><input type="hidden" name="parallel_curriculum_id" value="{{ $selectedCurriculum->id }}"><input type="hidden" name="session_id" value="{{ $sessionId }}"><input type="hidden" name="preview_promotion" value="1">
<div class="row"><div class="fg"><label class="fl">Source session</label><select class="fc" name="source_session_id" required><option value="">Select</option>@foreach($sessions as $session)<option value="{{ $session->id }}" @selected((int)$sourceSessionId===(int)$session->id)>{{ $session->name }}</option>@endforeach</select></div><div class="fg"><label class="fl">Destination session</label><select class="fc" name="target_session_id" required><option value="">Select</option>@foreach($sessions as $session)<option value="{{ $session->id }}" @selected((int)$targetSessionId===(int)$session->id)>{{ $session->name }}</option>@endforeach</select></div></div><button class="btn s" type="submit">Preview Promotion</button></form>
@if($preview)
<div class="kpis">@foreach(['total'=>'Students','promoted'=>'Promote','repeat'=>'Repeat','retain'=>'Retain','graduated'=>'Graduate','blocked'=>'Blocked'] as $key=>$label)<div class="kpi"><strong>{{ $preview['counts'][$key] }}</strong><span>{{ $label }}</span></div>@endforeach</div>
<div class="wrap desktop"><table class="tbl"><thead><tr><th>Student</th><th>Source</th><th>Average</th><th>Failed</th><th>Decision</th><th>Destination</th><th>Reason</th></tr></thead><tbody>@foreach($preview['rows'] as $row)<tr><td><strong>{{ $row['student']?->full_name }}</strong><br>{{ $row['student']?->admission_number }}</td><td>{{ $row['enrolment']->curriculumClass?->name }} {{ $row['enrolment']->curriculumClassArm?->name }}</td><td>{{ $row['average']===null?'—':number_format($row['average'],1).'%' }}</td><td>{{ $row['failed_subjects'] }}</td><td><span class="badge {{ $row['decision'] }}">{{ ucfirst($row['decision']) }}</span></td><td>{{ $row['destination_class']?->name ?: '—' }} {{ $row['destination_arm']?->name ?: '' }}</td><td>{{ $row['reason'] }}</td></tr>@endforeach</tbody></table></div>
<div class="mobile">@foreach($preview['rows'] as $row)<div class="mrow"><strong>{{ $row['student']?->full_name }} · {{ ucfirst($row['decision']) }}</strong><span>{{ $row['enrolment']->curriculumClass?->name }} {{ $row['enrolment']->curriculumClassArm?->name }} → {{ $row['destination_class']?->name ?: 'Programme complete' }} {{ $row['destination_arm']?->name }} · Avg {{ $row['average']===null?'—':number_format($row['average'],1).'%' }} · {{ $row['reason'] }}</span></div>@endforeach</div>
@if($preview['counts']['blocked']===0)<form method="POST" action="{{ route('parallel-curriculum.lifecycle.promotions.execute') }}" style="margin-top:12px">@csrf<input type="hidden" name="parallel_curriculum_id" value="{{ $selectedCurriculum->id }}"><input type="hidden" name="source_session_id" value="{{ $preview['source_session']->id }}"><input type="hidden" name="target_session_id" value="{{ $preview['target_session']->id }}"><button class="btn p" type="submit">Execute Promotion</button></form>@else<div class="err" style="margin-top:12px">Resolve all blocked learners before execution.</div>@endif
@endif
</div></section>

<section class="card full"><div class="head"><div><strong>5. Intra- and inter-class transfer</strong><br><span>Arm-to-arm movement within the same level is intra-class. Level-to-level movement is inter-class.</span></div></div><div class="body">
<form method="POST" action="{{ route('parallel-curriculum.lifecycle.transfers.store') }}">@csrf
<div class="row"><div class="fg"><label class="fl">Student / current placement</label><select class="fc" name="enrolment_id" required><option value="">Select learner</option>@foreach($enrolments as $enrolment)<option value="{{ $enrolment->id }}">{{ $enrolment->student?->full_name }} · {{ $enrolment->student?->admission_number }} · {{ $enrolment->curriculumClass?->name }} {{ $enrolment->curriculumClassArm?->name }}</option>@endforeach</select></div><div class="fg"><label class="fl">Destination level</label><select class="fc" name="destination_class_id" id="life-destination-class" required><option value="">Select</option>@foreach($selectedCurriculum->classes->where('is_active',true) as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div></div>
<div class="row"><div class="fg"><label class="fl">Destination arm</label><select class="fc" name="destination_arm_id" id="life-destination-arm" required disabled><option value="">Choose destination level first</option>@foreach($selectedCurriculum->classes as $class)@foreach($class->arms->where('is_active',true) as $arm)<option value="{{ $arm->id }}" data-class-id="{{ $class->id }}" hidden disabled>{{ $class->name }} · {{ $arm->name }}{{ $arm->capacity ? ' · Capacity '.$arm->capacity : '' }}</option>@endforeach @endforeach</select></div><div class="fg"><label class="fl">Effective date</label><input class="fc" type="date" name="effective_date" value="{{ now()->toDateString() }}"></div></div>
<div class="fg"><label class="fl">Reason</label><textarea class="fc" name="reason" rows="3" required></textarea></div><button class="btn p" type="submit">Complete Transfer</button></form>
</div></section>

<section class="card"><div class="head"><strong>Recent transfer history</strong><span>Latest 30</span></div><div class="body">@forelse($transfers as $transfer)<div class="rule"><strong>{{ $transfer->student?->full_name }} · {{ $transfer->movement_type==='intra_class'?'Intra-class':'Inter-class' }}</strong><span>{{ $transfer->fromClass?->name }} {{ $transfer->fromArm?->name }} → {{ $transfer->toClass?->name }} {{ $transfer->toArm?->name }} · {{ $transfer->session?->name }}</span></div>@empty<div class="hint">No transfers recorded.</div>@endforelse</div></section>
<section class="card"><div class="head"><strong>Recent promotion history</strong><span>Latest 30</span></div><div class="body">@forelse($promotions as $promotion)<div class="rule"><strong>{{ $promotion->student?->full_name }} · {{ ucfirst($promotion->decision) }}</strong><span>{{ $promotion->sourceClass?->name }} {{ $promotion->sourceArm?->name }} → {{ $promotion->destinationClass?->name ?: 'Programme complete' }} {{ $promotion->destinationArm?->name }} · {{ $promotion->sourceSession?->name }} → {{ $promotion->targetSession?->name }}</span></div>@empty<div class="hint">No promotions processed.</div>@endforelse</div></section>
</div>
@else
<div class="card"><div class="body"><div class="hint">Create a parallel curriculum programme first.</div></div></div>
@endif
</div>
@endsection


@push('scripts')
<script>
(function(){
    const classSelect = document.getElementById('life-destination-class');
    const armSelect = document.getElementById('life-destination-arm');

    function refreshTransferArms(){
        if(!classSelect || !armSelect) return;
        const classId = classSelect.value;
        let count = 0;
        Array.from(armSelect.options).forEach((option,index) => {
            if(index === 0) return;
            const show = !!classId && option.dataset.classId === classId;
            option.hidden = !show;
            option.disabled = !show;
            if(show) count++;
        });
        armSelect.value = '';
        armSelect.disabled = count === 0;
        armSelect.options[0].textContent = classId
            ? (count ? 'Choose destination arm' : 'No active arm in this level')
            : 'Choose destination level first';
    }

    classSelect?.addEventListener('change', refreshTransferArms);
    refreshTransferArms();
})();
</script>
@endpush
