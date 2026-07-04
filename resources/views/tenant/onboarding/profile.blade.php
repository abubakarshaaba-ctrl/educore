@extends('tenant.onboarding.index')
@section('page-title', 'School Profile')
@section('content')
<div class="onboard-wrap">
@include('tenant.onboarding.partials.progress')
<div class="onboard-card">
<h2>School Profile</h2>
<form method="POST" action="{{ route('tenant.onboarding.profile') }}">
@csrf
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">
<label>School Name<input class="fc" name="name" value="{{ old('name',$tenant->name) }}" required></label>
<label>Email<input class="fc" type="email" name="email" value="{{ old('email',$tenant->email) }}" required></label>
<label>Phone<input class="fc" name="phone" value="{{ old('phone',$tenant->phone) }}" required></label>
<label>Address<input class="fc" name="address" value="{{ old('address',$tenant->address) }}" required></label>
<label style="grid-column:1/-1">Motto<input class="fc" name="motto" value="{{ old('motto',$tenant->motto) }}"></label>
</div>
@if($errors->any())<p style="color:var(--crimson);margin-top:12px">{{ $errors->first() }}</p>@endif
<button class="btn btn-primary" style="margin-top:16px">Save and Continue</button>
</form>
</div>
</div>
@endsection
