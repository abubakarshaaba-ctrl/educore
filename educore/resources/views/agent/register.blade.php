@extends('layouts.auth')

@section('page-title', 'Agent Registration - EduCore')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">
    <aside class="auth-brand" aria-labelledby="agent-register-title">
        <div class="auth-brand__top">
            <x-auth.logo />
            <a class="ec-link" style="color:#FFFFFF" href="{{ route('agent.portal.login') }}">Agent Portal Access</a>
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">Agent Network</p>
            <h1 class="auth-brand__title" id="agent-register-title">
                Apply to become an <span>EduCore agent.</span>
            </h1>
            <p class="auth-brand__lead">
                Submit your details for platform review. Approved agents receive
                portal access for referrals, commissions, messages, and profile management.
            </p>

            <div class="auth-feature-grid" aria-label="Agent registration steps">
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 6h14v12H5V6Zm3 4h8M8 14h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Submit Details
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7l-8-4Z" stroke="currentColor" stroke-width="1.8"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Platform Review
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 18h16M7 16V9m5 7V5m5 11v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Track Referrals
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 19h12M8 19V5h8v14M10 9h4M10 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Support Schools
                </div>
            </div>
        </div>

        <div class="auth-brand__bottom">
            <span>EduCore Agent Network</span>
            <span>Applications are reviewed before activation</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card auth-card--wide" aria-labelledby="agent-application-heading">
            <header class="auth-card__header">
                <p class="auth-card__eyebrow">Agent application</p>
                <h2 class="auth-title" id="agent-application-heading">Create your agent profile</h2>
                <p class="auth-subtitle">
                    Enter your contact details and portal password. Your account
                    remains pending until a platform administrator approves it.
                </p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ route('agent.register.post') }}" novalidate>
                @csrf

                <div class="auth-info-grid">
                    <div class="ec-form-group">
                        <label class="ec-label" for="name">Full Name</label>
                        <input
                            id="name"
                            class="ec-input{{ $errors->has('name') ? ' ec-input--error' : '' }}"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            required
                        >
                        @error('name')<p class="ec-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="ec-form-group">
                        <label class="ec-label" for="phone">Phone Number</label>
                        <input
                            id="phone"
                            class="ec-input{{ $errors->has('phone') ? ' ec-input--error' : '' }}"
                            type="tel"
                            name="phone"
                            value="{{ old('phone') }}"
                            autocomplete="tel"
                            required
                        >
                        @error('phone')<p class="ec-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="ec-form-group">
                    <label class="ec-label" for="email">Email Address</label>
                    <input
                        id="email"
                        class="ec-input{{ $errors->has('email') ? ' ec-input--error' : '' }}"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    >
                    @error('email')<p class="ec-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="ec-form-group">
                    <label class="ec-label" for="state">State or Territory</label>
                    <input
                        id="state"
                        class="ec-input{{ $errors->has('state') ? ' ec-input--error' : '' }}"
                        type="text"
                        name="state"
                        value="{{ old('state') }}"
                        autocomplete="address-level1"
                        required
                    >
                    @error('state')<p class="ec-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="auth-info-grid">
                    <x-auth.password-input
                        id="password"
                        label="Portal Password"
                        autocomplete="new-password"
                        :hasError="$errors->has('password')"
                    />

                    <x-auth.password-input
                        id="password_confirmation"
                        label="Confirm Password"
                        autocomplete="new-password"
                        :hasError="$errors->has('password_confirmation')"
                    />
                </div>

                <x-auth.submit-button>Submit Agent Application</x-auth.submit-button>
            </form>

            <nav class="ec-links" aria-label="Agent registration links">
                <span class="ec-hint">Already registered?</span>
                <a class="ec-link" href="{{ route('agent.portal.login') }}">Sign in to Agent Portal Access</a>
            </nav>

            <x-auth.footer />
        </section>
    </main>
</div>
@endsection
