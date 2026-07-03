@extends('tenant.onboarding.index')
@section('page-title', 'Portal and Hostname Readiness')
@section('content')
<div class="onboard-wrap">
@include('tenant.onboarding.partials.progress')
<div class="onboard-card">
<h2>Portal URLs</h2>
<ul style="line-height:1.9">
@foreach($status->urls as $label => $url)
    @if($url)<li><strong>{{ str($label)->replace('_',' ')->title() }}:</strong> <a href="{{ $url }}" target="_blank" rel="noopener">{{ $url }}</a></li>@endif
@endforeach
</ul>
<p style="color:var(--slate)">Local hostname setup is documented in <code>docs/local-tenant-hosts.md</code>. EduCore does not modify Apache or the Windows hosts file automatically.</p>
<a class="btn btn-primary" href="{{ route('tenant.onboarding.review') }}">Continue to Review</a>
</div>
</div>
@endsection
