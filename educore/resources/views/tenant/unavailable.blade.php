@extends('layouts.auth')

@section('page-title', 'School Portal Unavailable - EduCore')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">
    <aside class="auth-brand" aria-labelledby="unavailable-title">
        <div class="auth-brand__top">
            <x-auth.logo />
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">Tenant portal status</p>
            <h1 class="auth-brand__title" id="unavailable-title">
                This school portal is <span>currently unavailable.</span>
            </h1>
            <p class="auth-brand__lead">
                EduCore protects each school portal behind tenant resolution,
                subscription status, and account availability checks.
            </p>
        </div>

        <div class="auth-brand__bottom">
            <span>EduCore Enterprise School Platform</span>
            <span>Tenant-isolated access</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="portal-unavailable-heading">
            <header class="auth-card__header">
                <p class="auth-card__eyebrow">School portal unavailable</p>
                <h2 class="auth-title" id="portal-unavailable-heading">Access cannot continue</h2>
                <p class="auth-subtitle">
                    {{ $message ?? 'This school portal is currently unavailable. Please contact the school administration or try again later.' }}
                </p>
            </header>

            <a href="{{ route('home') }}" class="ec-btn">EduCore Home</a>

            <div class="auth-note">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Ordinary school users should contact the school for the correct portal address or account status.</span>
            </div>

            <x-auth.footer />
        </section>
    </main>
</div>
@endsection
