@extends('tenant.onboarding.index')
@section('page-title', 'Subjects and Grading')
@section('content')
<div class="onboard-wrap">
@include('tenant.onboarding.partials.progress')
<div class="onboard-grid">
<div class="onboard-card">
<h2>Add Subject</h2>
<form method="POST" action="{{ route('tenant.onboarding.subjects') }}">
@csrf
<input type="hidden" name="setup_action" value="subject">
<label>Subject Name<input class="fc" name="name" value="{{ old('name') }}" required></label>
<label>Code<input class="fc" name="code" value="{{ old('code') }}"></label>
<button class="btn btn-primary" style="margin-top:16px">Save Subject</button>
</form>
</div>
<div class="onboard-card">
<h2>Add Grading Rule</h2>
<form method="POST" action="{{ route('tenant.onboarding.subjects') }}">
@csrf
<input type="hidden" name="setup_action" value="grade">
<label>Class Level<select class="fc" name="class_level_id" required>@foreach($levels as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach</select></label>
<label>Grade Letter<input class="fc" name="grade_letter" value="{{ old('grade_letter','A') }}" required></label>
<label>Min Score<input class="fc" type="number" name="min_score" value="{{ old('min_score',70) }}" required></label>
<label>Max Score<input class="fc" type="number" name="max_score" value="{{ old('max_score',100) }}" required></label>
<label>Remark<input class="fc" name="remark" value="{{ old('remark','Excellent') }}" required></label>
<button class="btn btn-primary" style="margin-top:16px">Save Grade</button>
</form>
</div>
</div>
@if($errors->any())<p style="color:var(--crimson);margin-top:12px">{{ $errors->first() }}</p>@endif
<div class="onboard-card"><h3>Current Setup</h3><p>Subjects: {{ $subjects->count() }}</p><p>Grading rules: {{ $grading->count() }}</p></div>
</div>
@endsection
