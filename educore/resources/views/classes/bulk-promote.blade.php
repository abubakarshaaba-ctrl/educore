@extends('layouts.app')
@section('title','Bulk Promotion')
@section('page-title','Promotion Engine')
@push('styles')
<style>
.pg{display:grid;grid-template-columns:1fr 380px;gap:16px}.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}.cb{padding:18px}.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}.fl span{color:var(--crimson)}.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}.fc:focus{border-color:var(--indigo);background:white}.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:9px;border:none;cursor:pointer;transition:all 150ms;width:100%;justify-content:center}.btn-g{background:var(--emerald);color:white}.rule-step{display:flex;gap:12px;margin-bottom:14px;padding:12px 14px;background:#F8FAFC;border:1px solid var(--border);border-radius:8px}.rs-num{min-width:24px;height:24px;border-radius:50%;background:var(--indigo);color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}.rs-text{font-size:13px;color:var(--slate);line-height:1.5}.warn-box{background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:12.5px;color:#92400E;margin-bottom:16px}.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}@media(max-width:768px){.pg{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@include('classes.partials.promotion-tabs')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="pg">
  <div>
    <div class="card"><div class="ch">How Manual Bulk Promotion Works</div><div class="cb">
        <div class="rule-step"><div class="rs-num">1</div><div class="rs-text">Select the <strong>From Class</strong> and the <strong>To Class</strong>.</div></div>
        <div class="rule-step"><div class="rs-num">2</div><div class="rs-text">Choose the <strong>Term</strong> whose results are being used.</div></div>
        <div class="rule-step"><div class="rs-num">3</div><div class="rs-text">Set the <strong>Minimum Average</strong> for this manual operation.</div></div>
        <div class="rule-step"><div class="rs-num">4</div><div class="rs-text">Confirm the promotion only after report cards and class destinations have been verified.</div></div>
        <div class="warn-box"><strong>Important:</strong> This changes student class assignments. Verify the destination class before submitting.</div>
    </div></div>
    @if(isset($preview))
    <div class="card"><div class="ch" style="color:var(--emerald)">Preview — Students Eligible for Promotion ({{ count($preview['eligible']) }})</div><div style="overflow-x:auto"><table><thead><tr><th>Student</th><th>Average</th><th>Position</th></tr></thead><tbody>@foreach($preview['eligible'] as $item)<tr><td><strong>{{ $item['student']->full_name }}</strong></td><td style="color:var(--emerald);font-weight:700">{{ $item['summary']->final_average }}</td><td>{{ $item['summary']->position_in_class }}/{{ $item['summary']->total_students_in_class }}</td></tr>@endforeach @if(empty($preview['eligible']))<tr><td colspan="3" style="text-align:center;padding:20px;color:var(--slate-light)">No students meet the minimum average.</td></tr>@endif</tbody></table></div></div>
    @endif
  </div>
  <div><div class="card"><div class="ch">Promote Students</div><div class="cb"><form method="POST" action="{{ route('classes.bulk-promote') }}">@csrf
      <div class="fg"><label class="fl">From Class <span>*</span></label><select name="from_class_arm_id" class="fc" required><option value="">Select current class</option>@foreach($classArms as $arm)<option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>@endforeach</select></div>
      <div class="fg"><label class="fl">To Class <span>*</span></label><select name="to_class_arm_id" class="fc" required><option value="">Select destination class</option>@foreach($classArms as $arm)<option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>@endforeach</select></div>
      <div class="fg"><label class="fl">Based on Term <span>*</span></label><select name="term_id" class="fc" required><option value="">Select term</option>@foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->name }} — {{ $t->session->name }}</option>@endforeach</select></div>
      <div class="fg"><label class="fl">Minimum Average to Pass <span>*</span></label><input type="number" name="min_average" class="fc" value="45" min="0" max="100" step="0.1" required><span style="font-size:11px;color:var(--slate-light)">Students below this average will not be promoted by this operation.</span></div>
      <button type="submit" class="btn btn-g" onclick="return confirm('Promote eligible students? This will move them to the new class.')">Promote Students</button>
  </form></div></div></div>
</div>
@endsection
