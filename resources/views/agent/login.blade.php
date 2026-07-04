@extends('layouts.auth')

@section('page-title', 'Agent Portal Access - EduCore')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">
    <aside class="auth-brand" aria-labelledby="agent-access-title">
        <div class="auth-brand__top">
            <x-auth.logo />
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">Agent Portal Access</p>
            <h1 class="auth-brand__title" id="agent-access-title">
                Referral operations for <span>approved agents.</span>
            </h1>
            <p class="auth-brand__lead">
                Approved EduCore agents can manage school referrals, track
                commission activity, review messages, and maintain their profile.
            </p>

            <div class="auth-feature-grid" aria-label="Agent portal capabilities">
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 19V5h14v14H5Zm4-9h6M9 14h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Referral Dashboard
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 18h16M7 16V9m5 7V5m5 11v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Earnings
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 20V8l7-4 7 4v12M9 20v-7h6v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    School Directory
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v12H4V6Zm0 1 8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    Platform Messages
                </div>
            </div>
        </div>

        <div class="auth-brand__bottom">
            <span>EduCore Agent Network</span>
            <span>Referral partner portal</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="agent-login-heading">
            <header class="auth-card__header">
                <p class="auth-card__eyebrow">Agent Portal Access</p>
                <h2 class="auth-title" id="agent-login-heading">Sign in as an agent</h2>
                <p class="auth-subtitle">
                    Manage referrals, schools, commissions, messages, and your portal profile.
                </p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif

            @if(session('success'))
                <x-auth.alert type="ok">{{ session('success') }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ route('agent.portal.login.post') }}" novalidate>
                @csrf

                <div class="ec-form-group">
                    <label class="ec-label" for="email">Email Address</label>
                    <input
                        id="email"
                        class="ec-input{{ $errors->has('email') ? ' ec-input--error' : '' }}"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        @if($errors->has('email')) aria-describedby="email-error" @endif
                        required
                    >
                    @error('email')
                        <p class="ec-field-error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>

                <x-auth.password-input
                    id="password"
                    label="Password"
                    autocomplete="current-password"
                    :hasError="$errors->has('password')"
                />

                <x-auth.submit-button>Sign In to Agent Portal</x-auth.submit-button>
            </form>

            <nav class="ec-links" aria-label="Agent portal links">
                <span class="ec-hint">Not an approved agent yet?</span>
                <a class="ec-link" href="{{ route('agent.register') }}">Apply for agent access</a>
                <a class="ec-link ec-link--muted" href="{{ url('/') }}">Back to EduCore home</a>
            </nav>

            <x-auth.footer />
        </section>
    </main>
</div>
@endsection
