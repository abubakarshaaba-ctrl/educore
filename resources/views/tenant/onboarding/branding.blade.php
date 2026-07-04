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
<label>Primary Colour<input class="fc" name="theme_primary" value="{{ old('theme_primary',$tenant->theme_primary ?: '#071E45') }}" required></label>
<label>Accent Colour<input class="fc" name="theme_accent" value="{{ old('theme_accent',$tenant->theme_accent ?: '#D79A21') }}" required></label>
<label>Sidebar Colour<input class="fc" name="theme_sidebar" value="{{ old('theme_sidebar',$tenant->theme_sidebar ?: '#071E45') }}"></label>
<label>Logo<input class="fc" type="file" name="logo" accept="image/png,image/jpeg,image/webp"></label>
</div>
@if($errors->any())<p style="color:var(--crimson);margin-top:12px">{{ $errors->first() }}</p>@endif
<button class="btn btn-primary" style="margin-top:16px">Save and Continue</button>
</form>
</div>
</div>
@endsection
