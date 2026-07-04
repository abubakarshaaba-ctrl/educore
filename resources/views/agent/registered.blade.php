@extends('layouts.auth')

@section('page-title', 'Agent Application Submitted - EduCore')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">
    <aside class="auth-brand" aria-labelledby="agent-submitted-title">
        <div class="auth-brand__top">
            <x-auth.logo />
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">Agent Network</p>
            <h1 class="auth-brand__title" id="agent-submitted-title">
                Application received for <span>platform review.</span>
            </h1>
            <p class="auth-brand__lead">
                Your account will be reviewed before Agent Portal Access is enabled.
            </p>
        </div>

        <div class="auth-brand__bottom">
            <span>EduCore Agent Network</span>
            <span>Referral partner portal</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="agent-success-heading">
            <div class="auth-success-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>

            <header class="auth-card__header">
                <p class="auth-card__eyebrow">Application submitted</p>
                <h2 class="auth-title" id="agent-success-heading">Thank you, {{ $agent->name }}</h2>
                <p class="auth-subtitle">
                    Your agent application has been received. Your unique referral code is shown below.
                </p>
            </header>

            <span class="auth-ref-code">{{ $agent->referral_code }}</span>

            <div class="auth-note">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Your account will be reviewed and activated within 24 to 48 hours. Once approved, you may sign in to the agent portal.</span>
            </div>

            <div class="auth-split-actions">
                <a class="ec-btn" href="{{ route('agent.portal.login') }}">Agent Portal Access</a>
                <a class="ec-btn ec-btn--secondary" href="{{ url('/') }}">EduCore Home</a>
            </div>

            <x-auth.footer />
        </section>
    </main>
</div>
@endsection
