@extends('layouts.auth')

@section('page-title', ($tenant->name ?? 'School') . ' - Account Status')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: {{ $branding['primary'] }}; --tenant-accent: {{ $branding['accent'] }};">
    <aside class="auth-brand auth-brand--tenant" aria-labelledby="account-status-title">
        <div class="auth-brand__top">
            <x-auth.tenant-branding
                :tenant="$tenant"
                :branding="$branding"
                :landingUrl="Route::has('tenant.portal.landing') ? route('tenant.portal.landing', $tenant->slug) : null"
            />
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">School account status</p>
            <h1 class="auth-brand__title" id="account-status-title">
                {{ $tenant->name }} access status.
            </h1>
            <p class="auth-brand__lead">
                Tenant availability is checked before operational access continues.
            </p>
        </div>

        <div class="auth-brand__bottom">
            <div class="auth-provider">
                <x-auth.logo :compact="true" />
                <span class="auth-provider__copy">
                    <span class="auth-provider__label">Customised and powered by</span>
                    <span class="auth-provider__name">EduCore Enterprise School Platform</span>
                </span>
            </div>
            <span>&copy; {{ date('Y') }} {{ $tenant->name }}</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="status-heading">
            <header class="auth-card__header">
                <p class="auth-card__eyebrow">Current access decision</p>
                <h2 class="auth-title" id="status-heading">{{ $decision->title() }}</h2>
                <p class="auth-subtitle">{{ $decision->message }}</p>
                @if($decision->expiresAt)
                    <p class="ec-hint">Expiry: {{ $decision->expiresAt->format('d M Y') }}</p>
                @endif
            </header>

            <div class="auth-note">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    @if($decision->allowed)
                        <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    @else
                        <path d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    @endif
                </svg>
                <span>If this status is unexpected, contact the school administration or EduCore support. Billing details are intentionally not shown here.</span>
            </div>

            <div class="auth-split-actions">
                @if($decision->allowed)
                    <a href="{{ route('dashboard') }}" class="ec-btn">Continue to Dashboard</a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="ec-btn ec-btn--secondary">Sign Out</button>
                </form>
            </div>

            @if(session('super_admin_id') && Route::has('super.stop-impersonating'))
                <form method="POST" action="{{ route('super.stop-impersonating') }}" style="margin-top:10px">
                    @csrf
                    <button type="submit" class="ec-btn ec-btn--secondary">Stop Impersonating</button>
                </form>
            @endif

            <x-auth.footer :tenant="$tenant->name" />
        </section>
    </main>
</div>
@endsection
