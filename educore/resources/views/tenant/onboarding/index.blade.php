@extends('layouts.app')
@section('title', 'School Onboarding')
@section('page-title', 'School Onboarding')

@push('styles')
<style>
.onboard-wrap{max-width:1100px}.onboard-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:22px;margin-bottom:16px}.onboard-grid{display:grid;grid-template-columns:1fr 360px;gap:16px}.progress-head{display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px}.progress-bar{height:9px;background:#E5E7EB;border-radius:99px;overflow:hidden}.progress-bar span{display:block;height:100%;background:var(--indigo)}.step-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:8px;margin-top:14px}.step-pill{display:flex;justify-content:space-between;gap:10px;border:1px solid var(--border);border-radius:8px;padding:10px;text-decoration:none;color:var(--midnight);font-size:12px}.step-pill b{font-size:10px;text-transform:uppercase}.step-complete{background:#ECFDF5}.step-warning{background:#FFFBEB}.step-blocking{background:#FEF2F2}.check-card{border-radius:10px;padding:14px;margin-bottom:12px;border:1px solid var(--border)}.check-card h3{font-size:13px;margin:0 0 8px}.check-card ul{margin:0;padding-left:18px;font-size:12px;line-height:1.6}.blocking{background:#FEF2F2}.warning{background:#FFFBEB}.complete{background:#ECFDF5}.actions{display:flex;flex-wrap:wrap;gap:10px}.btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:8px;border:1px solid var(--border);text-decoration:none;font-weight:700;font-size:13px}.btn-primary{background:var(--indigo);color:#fff;border-color:var(--indigo)}@media(max-width:900px){.onboard-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="onboard-wrap">
    <div class="onboard-card">
        <h2 style="margin:0 0 8px">{{ $tenant->name }} Setup</h2>
        <p style="margin:0;color:var(--slate)">Complete the blocking items before normal school operations open for this tenant.</p>
    </div>

    @include('tenant.onboarding.partials.progress')

    <div class="onboard-grid">
        <div class="onboard-card">
            <h3 style="margin:0 0 12px">Next Step</h3>
            @if($status->next_step)
                <p style="color:var(--slate)">Continue with {{ $status->steps[$status->current_step]['label'] ?? 'setup' }}.</p>
                <div class="actions"><a class="btn btn-primary" href="{{ route($status->next_step) }}">Continue Setup</a></div>
            @else
                <p style="color:var(--slate)">No blocking setup items remain.</p>
                <div class="actions"><a class="btn btn-primary" href="{{ route('tenant.onboarding.review') }}">Review Activation</a></div>
            @endif
        </div>
        <div>
            @include('tenant.onboarding.partials.checklist')
        </div>
    </div>
</div>
@endsection
