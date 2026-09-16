@extends('layouts.app')
@section('title', 'Promotion Rules')
@section('page-title', 'Promotion Engine')

@push('styles')
<style>
.rules-intro{margin-bottom:16px;padding:16px 18px;border:1px solid #E2E8F0;border-radius:13px;background:#fff}.rules-intro h2{margin:0 0 4px;color:#071E45;font-size:17px}.rules-intro p{margin:0;color:#64748B;font-size:11px;line-height:1.5}
.rules-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:14px}.rule-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;box-shadow:0 2px 10px rgba(15,23,42,.035);overflow:hidden}.rule-header{padding:13px 15px;border-bottom:1px solid #E8EDF4;background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;gap:10px}.rule-title{font-size:13px;font-weight:800;color:#071E45}.rule-body{padding:15px}.form-group{margin-bottom:12px}.form-label{display:block;font-size:10px;font-weight:800;color:#475569;margin-bottom:5px}.form-control{width:100%;padding:8px 10px;font-size:12px;font-family:inherit;border:1px solid #D6DDE8;border-radius:8px;background:#fff;outline:none}.form-control:focus{border-color:#D79A21;box-shadow:0 0 0 3px rgba(215,154,33,.11)}.compulsory-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px;max-height:150px;overflow-y:auto;border:1px solid #E2E8F0;border-radius:8px;padding:9px;background:#FAFBFC}.check-label{display:flex;align-items:flex-start;gap:6px;font-size:10.5px;color:#334155;cursor:pointer}.save-rule{width:100%;border:0;border-radius:8px;background:#071E45;color:#fff;padding:9px 13px;font:800 11px inherit;cursor:pointer}.save-rule:hover{background:#0B2D63}.badge{display:inline-flex;font-size:9px;font-weight:800;padding:3px 8px;border-radius:20px}.badge-success{background:#ECFDF3;color:#067647}.badge-warning{background:#FFF8E8;color:#8A5B00}.alert-s,.alert-e{border-radius:9px;padding:11px 14px;font-size:11px;margin-bottom:14px}.alert-s{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.alert-e{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.alert-e ul{margin:5px 0 0 16px;padding:0}
@media(max-width:640px){.rules-grid{grid-template-columns:1fr}.compulsory-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@include('classes.partials.promotion-tabs')

@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e"><strong>Promotion rule could not be saved.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="rules-intro">
    <h2>Promotion rules</h2>
    <p>Define the minimum performance criteria used by the Promotion Engine for each class level. Rules remain class-specific while living in the same Promotion Engine workspace.</p>
</div>

<div class="rules-grid">
    @foreach($levels as $level)
        @php $rule = $level->promotionRule; @endphp
        <div class="rule-card">
            <div class="rule-header">
                <span class="rule-title">{{ $level->name }}</span>
                <span class="badge {{ $rule ? 'badge-success' : 'badge-warning' }}">{{ $rule ? 'Configured' : 'Not configured' }}</span>
            </div>
            <div class="rule-body">
                <form method="POST" action="{{ route('classes.promotion.save') }}">
                    @csrf
                    <input type="hidden" name="class_level_id" value="{{ $level->id }}">
                    <div class="form-group">
                        <label class="form-label">Minimum average (%)</label>
                        <input type="number" name="min_required_average" class="form-control" value="{{ old('class_level_id') == $level->id ? old('min_required_average') : (optional($rule)->min_required_average ?? 40) }}" min="0" max="100" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maximum failed subjects allowed</label>
                        <input type="number" name="max_failed_subjects_allowed" class="form-control" value="{{ old('class_level_id') == $level->id ? old('max_failed_subjects_allowed') : (optional($rule)->max_failed_subjects_allowed ?? 3) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Compulsory subjects that must be passed</label>
                        <div class="compulsory-grid">
                            @foreach($subjects as $subject)
                                <label class="check-label">
                                    <input type="checkbox" name="compulsory_subject_ids[]" value="{{ $subject->id }}" {{ in_array($subject->id, optional($rule)->compulsory_subject_ids ?? []) ? 'checked' : '' }}>
                                    <span>{{ $subject->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="save-rule">Save rule</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
