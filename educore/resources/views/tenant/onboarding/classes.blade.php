@extends('tenant.onboarding.index')
@section('page-title', 'Classes and Arms')
@section('content')
<div class="onboard-wrap">
@include('tenant.onboarding.partials.progress')
<div class="onboard-card">
<h2>Classes and Arms</h2>
<form method="POST" action="{{ route('tenant.onboarding.classes') }}">
@csrf
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
<label>Class Level<input class="fc" name="level_name" value="{{ old('level_name') }}" placeholder="Primary 1" required></label>
<label>Section<select class="fc" name="section" required><option value="primary">Primary</option><option value="nursery">Nursery</option><option value="junior_secondary">Junior Secondary</option><option value="senior_secondary">Senior Secondary</option><option value="creche">Creche</option></select></label>
<label>Class Arm<input class="fc" name="arm_name" value="{{ old('arm_name','A') }}" required></label>
<label>Order<input class="fc" type="number" name="order_index" value="{{ old('order_index',0) }}" min="0"></label>
</div>
@if($errors->any())<p style="color:var(--crimson);margin-top:12px">{{ $errors->first() }}</p>@endif
<button class="btn btn-primary" style="margin-top:16px">Save Class</button>
</form>
</div>
<div class="onboard-card"><h3>Configured Classes</h3>
@forelse($levels as $level)<p><strong>{{ $level->name }}</strong>: {{ $level->classArms->pluck('name')->join(', ') ?: 'No arms' }}</p>@empty<p>No classes yet.</p>@endforelse
</div>
</div>
@endsection
