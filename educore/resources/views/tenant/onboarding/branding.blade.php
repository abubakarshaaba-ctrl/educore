@extends('tenant.onboarding.index')
@section('page-title', 'Branding Setup')
@section('content')
<div class="onboard-wrap">
@include('tenant.onboarding.partials.progress')
<div class="onboard-card">
<h2>Branding</h2>
<form method="POST" action="{{ route('tenant.onboarding.branding') }}" enctype="multipart/form-data">
@csrf
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
<label>Logo<input class="fc" type="file" name="logo" accept="image/png,image/jpeg,image/webp"></label>
</div>
@if($errors->any())<p style="color:var(--crimson);margin-top:12px">{{ $errors->first() }}</p>@endif
<button class="btn btn-primary" style="margin-top:16px">Save and Continue</button>
</form>
</div>
</div>
@endsection
