@extends('layouts.app')
@section('title','Manual Bulk Promotion')
@section('page-title','Promotion Engine')

@push('styles')
<style>
.pg{display:grid;grid-template-columns:minmax(0,1fr) 430px;gap:16px}.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}.cb{padding:18px}.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.04em}.fl span{color:var(--crimson)}.fc{padding:9px 11px;font-size:12px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#fff;outline:none;width:100%}.fc:focus{border-color:var(--indigo)}.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 14px;font-size:12px;font-weight:700;font-family:inherit;border-radius:9px;border:0;cursor:pointer;justify-content:center}.btn-g{background:var(--emerald);color:#fff}.btn-n{background:var(--midnight);color:#fff}.btn-o{background:#fff;color:var(--midnight);border:1px solid var(--border)}.btn-d{background:#FFF5F4;color:#B42318;border:1px solid #FECDCA}.actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px}.rule-step{display:flex;gap:12px;margin-bottom:12px;padding:11px 13px;background:#F8FAFC;border:1px solid var(--border);border-radius:8px}.rs-num{min-width:24px;height:24px;border-radius:50%;background:var(--indigo);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center}.rs-text{font-size:12px;color:var(--slate);line-height:1.5}.warn-box{background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 14px;font-size:11.5px;color:#92400E;margin-bottom:14px}.alert-s,.alert-e,.alert-w{border-radius:9px;padding:11px 14px;font-size:12px;margin-bottom:14px}.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;color:#067647}.alert-e{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.alert-w{background:#FFFAEB;border:1px solid #FEDF89;color:#93370D}.alert-e ul,.alert-w ul{margin:5px 0 0 16px;padding:0}.operation{border:1px solid #DDE4ED;border-radius:10px;padding:13px;margin-bottom:12px;background:#FCFDFE}.operation-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}.operation-title{font-size:11px;font-weight:800;color:var(--midnight)}.remove-op{border:0;background:transparent;color:#B42318;font-size:11px;cursor:pointer}.op-row{display:grid;grid-template-columns:1fr 1fr;gap:9px}.preview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}.preview-card{border:1px solid #E2E8F0;border-radius:10px;background:#fff;overflow:hidden}.preview-head{padding:10px 12px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;font-size:11px;font-weight:800;color:#071E45}.preview-body{padding:12px}.stats{display:grid;grid-template-columns:1fr 1fr;gap:8px}.stat{border-radius:8px;padding:10px;background:#F8FAFC}.stat strong{display:block;font-size:18px;color:#071E45}.stat span{font-size:9px;color:#64748B;text-transform:uppercase;font-weight:700}.error-text{font-size:11px;color:#B42318;line-height:1.5}.summary-list{margin:8px 0 0;padding-left:16px;font-size:10.5px;color:#475569;line-height:1.5}.add-row{width:100%;margin-top:2px}.muted{font-size:10px;color:#94A3B8;line-height:1.45}@media(max-width:900px){.pg{grid-template-columns:1fr}.op-row{grid-template-columns:1fr}}@media(max-width:560px){.actions{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@include('classes.partials.promotion-tabs')

@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())
<div class="alert-e"><strong>Manual promotion could not be completed.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if(session('promotion_errors'))
<div class="alert-w"><strong>Some class operations were not processed.</strong><ul>@foreach(session('promotion_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if(session('promotion_results'))
<div class="alert-s"><strong>Processed class operations</strong><ul>@foreach(session('promotion_results') as $result)<li>{{ $result }}</li>@endforeach</ul></div>
@endif

@php
    $operationValues = old('operations', $submittedOperations ?? [[
        'from_class_arm_id' => '',
        'to_class_arm_id' => '',
        'term_id' => '',
        'min_average' => 45,
    ]]);
@endphp

<div class="pg">
    <div>
        <div class="card">
            <div class="ch">Safe Manual Promotion Workflow</div>
            <div class="cb">
                <div class="rule-step"><div class="rs-num">1</div><div class="rs-text">Add one or more class operations. Each source class is processed independently.</div></div>
                <div class="rule-step"><div class="rs-num">2</div><div class="rs-text">The destination must belong to the next configured class level. Same-class, skipped-level and terminal-class destinations are blocked.</div></div>
                <div class="rule-step"><div class="rs-num">3</div><div class="rs-text">Eligibility is calculated from the selected term's computed summary and the minimum average you specify.</div></div>
                <div class="rule-step"><div class="rs-num">4</div><div class="rs-text">Use <strong>Preview</strong> first. Running promotion is idempotent: students already moved out of the source class are skipped rather than duplicated.</div></div>
                <div class="warn-box"><strong>Terminal classes:</strong> Manual promotion will not push a terminal class into an arbitrary destination. Use the dedicated graduation/academic rollover workflow for terminal students.</div>
            </div>
        </div>

        @if(isset($preview))
        <div class="card">
            <div class="ch">Promotion Preview</div>
            <div class="cb">
                <div class="preview-grid">
                    @foreach($preview['operations'] as $plan)
                        <div class="preview-card">
                            @if(!empty($plan['error']))
                                <div class="preview-head">Operation {{ ($plan['operation_index'] ?? $loop->index) + 1 }}</div>
                                <div class="preview-body"><div class="error-text">{{ $plan['error'] }}</div></div>
                            @else
                                <div class="preview-head">{{ $plan['fromArm']->classLevel->name }} {{ $plan['fromArm']->name }} → {{ $plan['toArm']->classLevel->name }} {{ $plan['toArm']->name }}</div>
                                <div class="preview-body">
                                    <div class="stats">
                                        <div class="stat"><strong>{{ $plan['eligible_count'] }}</strong><span>Eligible</span></div>
                                        <div class="stat"><strong>{{ $plan['blocked_count'] }}</strong><span>Blocked</span></div>
                                    </div>
                                    <div class="muted" style="margin-top:9px">{{ $plan['term']->name }} · Minimum average {{ rtrim(rtrim(number_format($plan['minimum_average'],2),'0'),'.') }}%</div>
                                    @if($plan['blocked_count'] > 0)
                                        <ul class="summary-list">
                                            @foreach(array_slice($plan['blocked'],0,5) as $item)
                                                <li>{{ $item['student']->full_name }} — {{ $item['reason'] }}</li>
                                            @endforeach
                                            @if($plan['blocked_count'] > 5)<li>+ {{ $plan['blocked_count'] - 5 }} more blocked student(s)</li>@endif
                                        </ul>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <div>
        <div class="card">
            <div class="ch">Manual Bulk Promotion</div>
            <div class="cb">
                <form method="POST" action="{{ route('classes.bulk-promote') }}" id="promotionForm">
                    @csrf
                    <div id="operations">
                        @foreach($operationValues as $i => $operation)
                        <div class="operation" data-index="{{ $i }}">
                            <div class="operation-head">
                                <span class="operation-title">Class operation <span class="op-number">{{ $loop->iteration }}</span></span>
                                <button type="button" class="remove-op" onclick="removeOperation(this)" {{ count($operationValues) <= 1 ? 'style=display:none' : '' }}>Remove</button>
                            </div>
                            <div class="fg">
                                <label class="fl">From Class <span>*</span></label>
                                <select name="operations[{{ $i }}][from_class_arm_id]" class="fc from-class" required>
                                    <option value="">Select current class</option>
                                    @foreach($classArms as $arm)
                                        <option value="{{ $arm->id }}" {{ (string)($operation['from_class_arm_id'] ?? '') === (string)$arm->id ? 'selected' : '' }}>{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="fg">
                                <label class="fl">To Class <span>*</span></label>
                                <select name="operations[{{ $i }}][to_class_arm_id]" class="fc to-class" required>
                                    <option value="">Select destination class</option>
                                    @foreach($classArms as $arm)
                                        <option value="{{ $arm->id }}" {{ (string)($operation['to_class_arm_id'] ?? '') === (string)$arm->id ? 'selected' : '' }}>{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="op-row">
                                <div class="fg">
                                    <label class="fl">Based on Term <span>*</span></label>
                                    <select name="operations[{{ $i }}][term_id]" class="fc" required>
                                        <option value="">Select term</option>
                                        @foreach($terms as $t)
                                            <option value="{{ $t->id }}" {{ (string)($operation['term_id'] ?? '') === (string)$t->id ? 'selected' : '' }}>{{ $t->name }}{{ optional($t->session)->name ? ' — '.optional($t->session)->name : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="fg">
                                    <label class="fl">Minimum Average <span>*</span></label>
                                    <input type="number" name="operations[{{ $i }}][min_average]" class="fc" value="{{ $operation['min_average'] ?? 45 }}" min="0" max="100" step="0.1" required>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-o add-row" onclick="addOperation()">+ Add another class operation</button>
                    <div class="actions">
                        <button type="submit" name="preview_only" value="1" class="btn btn-n">Preview eligibility</button>
                        <button type="submit" class="btn btn-g" onclick="return confirm('Run the reviewed promotion operations? Eligible students will be moved to their destination classes.')">Run promotion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="operationTemplate">
<div class="operation" data-index="__INDEX__">
    <div class="operation-head"><span class="operation-title">Class operation <span class="op-number">__NUMBER__</span></span><button type="button" class="remove-op" onclick="removeOperation(this)">Remove</button></div>
    <div class="fg"><label class="fl">From Class <span>*</span></label><select name="operations[__INDEX__][from_class_arm_id]" class="fc from-class" required><option value="">Select current class</option>@foreach($classArms as $arm)<option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>@endforeach</select></div>
    <div class="fg"><label class="fl">To Class <span>*</span></label><select name="operations[__INDEX__][to_class_arm_id]" class="fc to-class" required><option value="">Select destination class</option>@foreach($classArms as $arm)<option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>@endforeach</select></div>
    <div class="op-row">
        <div class="fg"><label class="fl">Based on Term <span>*</span></label><select name="operations[__INDEX__][term_id]" class="fc" required><option value="">Select term</option>@foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->name }}{{ optional($t->session)->name ? ' — '.optional($t->session)->name : '' }}</option>@endforeach</select></div>
        <div class="fg"><label class="fl">Minimum Average <span>*</span></label><input type="number" name="operations[__INDEX__][min_average]" class="fc" value="45" min="0" max="100" step="0.1" required></div>
    </div>
</div>
</template>

<script>
let operationIndex = {{ count($operationValues) ? max(array_keys($operationValues)) + 1 : 1 }};
function addOperation(){
    const count = document.querySelectorAll('#operations .operation').length + 1;
    const html = document.getElementById('operationTemplate').innerHTML.replaceAll('__INDEX__', operationIndex).replaceAll('__NUMBER__', count);
    document.getElementById('operations').insertAdjacentHTML('beforeend', html);
    operationIndex++;
    refreshOperationButtons();
}
function removeOperation(button){
    button.closest('.operation').remove();
    document.querySelectorAll('#operations .operation .op-number').forEach((node,i)=>node.textContent=i+1);
    refreshOperationButtons();
}
function refreshOperationButtons(){
    const operations = document.querySelectorAll('#operations .operation');
    operations.forEach(op=>{ const b=op.querySelector('.remove-op'); if(b) b.style.display=operations.length>1?'inline':'none'; });
}
refreshOperationButtons();
</script>
@endsection
