@extends('layouts.app')
@section('title','Parallel Curriculum Score Sheet')
@section('page-title','Parallel Curriculum')

@push('styles')
<style>
.pc-tabs{display:flex;gap:5px;overflow-x:auto;margin-bottom:16px}.pc-tab{flex:0 0 auto;padding:8px 14px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--slate);font-size:12px;font-weight:700;text-decoration:none}.pc-tab.active{background:var(--midnight);color:#fff;border-color:var(--midnight)}
.context{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 17px;margin-bottom:14px;background:#fff;border:1px solid var(--border);border-radius:11px}.context h2{font-size:15px;color:var(--midnight);margin:0 0 3px}.context p{font-size:11px;color:var(--slate);margin:0}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:8px;padding:9px 14px;font:700 11.5px inherit;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:#fff}.btn-s{background:#fff;color:var(--midnight);border:1px solid var(--border)}
.alert-s,.alert-e,.note{border-radius:9px;padding:10px 13px;font-size:11px;margin-bottom:12px}.alert-s{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.alert-e{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.note{background:#EFF6FF;border:1px solid #BFDBFE;color:#1D4ED8}
.sheet-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--border);border-radius:12px;background:#fff}.sheet{width:100%;min-width:650px;border-collapse:collapse}.sheet th{padding:8px;background:var(--midnight);color:#fff;font-size:10px;text-align:center;border-right:1px solid rgba(255,255,255,.08)}.sheet th:first-child{text-align:left;min-width:200px}.sheet td{padding:8px;border-right:1px solid var(--border);border-bottom:1px solid var(--border);text-align:center;font-size:11px}.sheet td:first-child{text-align:left}.student{font-weight:700;color:var(--midnight)}.adm{font-size:10px;color:var(--slate-light);margin-top:2px}.score{width:68px;padding:6px;text-align:center;border:1px solid var(--border);border-radius:7px;font:700 12px inherit}.score:focus{outline:none;border-color:var(--indigo)}.score:disabled{background:#F1F5F9;color:#64748B;cursor:not-allowed}.lock-note{display:inline-block;margin-top:3px;color:#B42318;font-size:9.5px;font-weight:700}.total{font-weight:800;color:var(--midnight);background:#F8FAFC}.footer{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;padding:12px 0}.hint{font-size:10.5px;color:var(--slate)}
@media(max-width:640px){.context{flex-direction:column}.context .btn{width:100%}.sheet th:first-child,.sheet td:first-child{position:sticky;left:0;z-index:2;background:#fff;min-width:145px}.sheet th:first-child{background:var(--midnight);z-index:3}.footer{align-items:stretch;flex-direction:column}.footer .btn{width:100%}}
</style>
@endpush

@push('styles')
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pc-score">
@include('parallel-curriculum.partials.module-navigation')

@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif

<div class="context">
    <div>
        <h2>{{ $class->curriculum?->name }} · {{ $class->name }}{{ $arm ? ' '.$arm->name : '' }} · {{ $subject->name }}</h2>
        <p>{{ $term->name }} · {{ $term->session?->name }} · {{ $arm ? 'Arm-specific roster · ' : 'All class arms · ' }}{{ $template?->name ?: 'No assessment template' }}</p>
    </div>
    <a href="{{ route('parallel-curriculum.index') }}" class="btn btn-s">← Back to Workspace</a>
</div>

@if(!$template || $components->isEmpty())
    <div class="alert-e">This parallel class does not have a usable Assessment Template. Assign a default programme template or a class override before entering scores.</div>
@elseif(abs((float)$components->sum('weight_percentage') - 100.0) > 0.001)
    <div class="alert-e">The selected parallel Assessment Template totals {{ number_format($components->sum('weight_percentage'),2) }}%. It must total 100% before composite results can be synchronized.</div>
@elseif($enrolments->isEmpty())
    <div class="note">No active students are assigned to this parallel class{{ $arm ? ' arm' : '' }} for {{ $term->session?->name }}.</div>
@else
    @php($allLocked = $enrolments->isNotEmpty() && $lockedStudents->every(fn($locked) => (bool) $locked))
    <div class="note">Enter each component against its template weight. Once all required parallel subjects are complete, EduCore calculates the student's programme average and distributes it into the mapped conventional subject automatically when auto-sync is enabled. Students whose parallel or conventional results are already published are locked to preserve result integrity.</div>

    <form method="POST" action="{{ route('parallel-curriculum.scores.save') }}">
        @csrf
        <input type="hidden" name="class_id" value="{{ $class->id }}">
        @if($arm)<input type="hidden" name="arm_id" value="{{ $arm->id }}">@endif
        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
        <input type="hidden" name="term_id" value="{{ $term->id }}">

        <div class="sheet-wrap">
            <table class="sheet">
                <thead>
                    <tr>
                        <th>Student</th>
                        @foreach($components as $component)
                            <th>{{ $component->name }}<br><span style="opacity:.7;font-weight:500">Max {{ number_format($component->weight_percentage,0) }}</span></th>
                        @endforeach
                        <th>Total / {{ number_format($components->sum('weight_percentage'),0) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enrolments as $enrolment)
                        @php($student=$enrolment->student)
                        @php($rowLocked=(bool)$lockedStudents->get((int)$enrolment->student_id,false))
                        <tr>
                            <td>
                                <div class="student">{{ $student?->full_name }}</div>
                                <div class="adm">{{ $student?->admission_number }} · Conventional: {{ $student?->currentClassArm?->full_name ?: 'Not assigned' }}</div>
                                @if($rowLocked)<span class="lock-note">Published report — source scores locked</span>@endif
                            </td>
                            @foreach($components as $component)
                                @php($record=$scores->get($enrolment->student_id.':'.$component->id))
                                <td>
                                    <input
                                        class="score"
                                        type="number"
                                        name="scores[{{ $enrolment->student_id }}][{{ $component->id }}]"
                                        value="{{ $record?->score }}"
                                        min="0"
                                        max="{{ $component->weight_percentage }}"
                                        step="0.5"
                                        data-student="{{ $enrolment->student_id }}"
                                        data-max="{{ $component->weight_percentage }}"
                                        oninput="pcUpdateTotal({{ $enrolment->student_id }})"
                                        {{ $rowLocked ? 'disabled' : '' }}
                                    >
                                </td>
                            @endforeach
                            <td class="total" id="pc-total-{{ $enrolment->student_id }}">
                                {{ number_format($scores->filter(fn($score)=>$score->student_id==$enrolment->student_id)->sum('score'),1) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="footer">
            <div class="hint">Blanking a previously saved component removes that component score. Incomplete subjects are excluded from the composite until the configured completeness rule is satisfied.</div>
            @if(!$allLocked)
                <button class="btn btn-p">Save Parallel Scores</button>
            @else
                <span class="hint">All rows are locked because a published result depends on these source scores.</span>
            @endif
        </div>
    </form>
@endif
</div>
@endsection

@push('scripts')
<script>
function pcUpdateTotal(studentId){
    let total=0;
    document.querySelectorAll('[data-student="'+studentId+'"]').forEach(function(input){
        const value=parseFloat(input.value);
        if(!Number.isNaN(value)) total+=Math.min(value,parseFloat(input.dataset.max)||value);
    });
    const el=document.getElementById('pc-total-'+studentId);
    if(el) el.textContent=total.toFixed(1);
}
</script>
@endpush
