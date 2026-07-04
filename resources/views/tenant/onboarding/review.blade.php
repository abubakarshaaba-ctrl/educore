@extends('tenant.onboarding.index')
@section('page-title', 'Review and Activation')
@section('content')
<div class="onboard-wrap">
@include('tenant.onboarding.partials.progress')
<div class="onboard-grid">
<div class="onboard-card">
<h2>Activation Review</h2>
<p style="color:var(--slate)">Onboarding completion is derived from real school data. No separate archive or setup table is created.</p>
<form method="POST" action="{{ route('tenant.onboarding.complete') }}">
@csrf
<button class="btn btn-primary" @disabled(!$status->can_activate)>Complete Onboarding Review</button>
</form>
@if($errors->any())<p style="color:var(--crimson);margin-top:12px">{{ $errors->first() }}</p>@endif
</div>
<div>@include('tenant.onboarding.partials.checklist')</div>
</div>
</div>
@endsection
