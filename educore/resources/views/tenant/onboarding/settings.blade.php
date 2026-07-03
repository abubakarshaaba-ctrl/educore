@extends('tenant.onboarding.index')
@section('page-title', 'Operational Settings')
@section('content')
<div class="onboard-wrap">
@include('tenant.onboarding.partials.progress')
<div class="onboard-card">
<h2>Operational Settings</h2>
<form method="POST" action="{{ route('tenant.onboarding.settings') }}">
@csrf
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
<label>Admissions Open<select class="fc" name="is_open"><option value="0" @selected(!old('is_open',$portal?->is_open))>No</option><option value="1" @selected(old('is_open',$portal?->is_open))>Yes</option></select></label>
<label>Academic Year<input class="fc" name="academic_year" value="{{ old('academic_year',optional($portal)->academic_year) }}"></label>
<label>Application Fee<input class="fc" type="number" step="0.01" name="application_fee" value="{{ old('application_fee',optional($portal)->application_fee ?? 0) }}"></label>
<label>Website<input class="fc" name="website" value="{{ old('website',optional($settings->get('website'))->value) }}"></label>
<label>Proprietor<input class="fc" name="proprietor" value="{{ old('proprietor',optional($settings->get('proprietor'))->value) }}"></label>
<label>Slogan<input class="fc" name="slogan" value="{{ old('slogan',optional($settings->get('slogan'))->value) }}"></label>
<label style="grid-column:1/-1">Welcome Message<textarea class="fc" name="welcome_message">{{ old('welcome_message',optional($portal)->welcome_message) }}</textarea></label>
</div>
@if($errors->any())<p style="color:var(--crimson);margin-top:12px">{{ $errors->first() }}</p>@endif
<button class="btn btn-primary" style="margin-top:16px">Save and Continue</button>
</form>
</div>
</div>
@endsection
