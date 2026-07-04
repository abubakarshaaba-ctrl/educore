@extends('layouts.auth')

@section('page-title', ($tenant->name ?? 'School') . ' - Set New Password')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: {{ $branding['primary'] }}; --tenant-accent: {{ $branding['accent'] }};">
    <aside class="auth-brand auth-brand--tenant" aria-labelledby="tenant-reset-title">
        <div class="auth-brand__top">
            <x-auth.tenant-branding
                :tenant="$tenant"
                :branding="$branding"
                :landingUrl="$landingUrl ?? route('tenant.portal.landing', $tenant->slug)"
            />
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">Account security</p>
            <h1 class="auth-brand__title" id="tenant-reset-title">
                Set a new <span>staff password.</span>
            </h1>
            <p class="auth-brand__lead">
                This reset page only applies to eligible {{ $tenant->name }} staff
                and administration accounts.
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
        <section class="auth-card" aria-labelledby="reset-heading">
            <header class="auth-card__header">
                <p class="auth-card__eyebrow">Set new password</p>
                <h2 class="auth-title" id="reset-heading">Choose a secure password</h2>
                <p class="auth-subtitle">
                    Use at least 8 characters for your {{ $tenant->name }} staff account.
                </p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ $resetPasswordAction ?? route('tenant.password.update', $tenant->slug) }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="ec-form-group">
                    <label class="ec-label" for="email">Staff Email Address</label>
                    <input
                        id="email"
                        class="ec-input{{ $errors->has('email') ? ' ec-input--error' : '' }}"
                        type="email"
                        name="email"
                        value="{{ old('email', $email) }}"
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
                    label="New Password"
                    autocomplete="new-password"
                    :hasError="$errors->has('password')"
                />

                <x-auth.password-input
                    id="password_confirmation"
                    label="Confirm New Password"
                    autocomplete="new-password"
                    :hasError="$errors->has('password_confirmation')"
                />

                <x-auth.submit-button :tenantColor="$branding['primary']">Reset Password</x-auth.submit-button>
            </form>

            <nav class="ec-links" aria-label="Password reset links">
                <a class="ec-link" href="{{ $loginUrl ?? route('tenant.login', $tenant->slug) }}">
                    Back to staff login
                </a>
            </nav>

            <x-auth.footer :tenant="$tenant->name" />
        </section>
    </main>
</div>
@endsection
