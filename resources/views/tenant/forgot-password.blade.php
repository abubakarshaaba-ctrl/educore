@extends('layouts.auth')

@section('page-title', ($tenant->name ?? 'School') . ' - Password Recovery')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: {{ $branding['primary'] }}; --tenant-accent: {{ $branding['accent'] }};">
    <aside class="auth-brand auth-brand--tenant" aria-labelledby="tenant-recovery-title">
        <div class="auth-brand__top">
            <x-auth.tenant-branding
                :tenant="$tenant"
                :branding="$branding"
                :landingUrl="$landingUrl ?? route('tenant.portal.landing', $tenant->slug)"
            />
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">Staff account recovery</p>
            <h1 class="auth-brand__title" id="tenant-recovery-title">
                Reset access for <span>{{ $tenant->name }}</span>.
            </h1>
            <p class="auth-brand__lead">
                Password recovery is available for eligible staff and administration
                accounts attached to this school.
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
        <section class="auth-card" aria-labelledby="forgot-heading">
            <header class="auth-card__header">
                <p class="auth-card__eyebrow">Password recovery</p>
                <h2 class="auth-title" id="forgot-heading">Reset password</h2>
                <p class="auth-subtitle">
                    Enter your staff email address. If an eligible account exists,
                    reset instructions will be sent securely.
                </p>
            </header>

            @if(session('status'))
                <x-auth.alert type="ok">{{ session('status') }}</x-auth.alert>
            @endif

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ $forgotPasswordAction ?? route('tenant.password.email', $tenant->slug) }}" novalidate>
                @csrf

                <div class="ec-form-group">
                    <label class="ec-label" for="email">Staff Email Address</label>
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

                <x-auth.submit-button :tenantColor="$branding['primary']">Send Reset Link</x-auth.submit-button>
            </form>

            <nav class="ec-links" aria-label="Password recovery links">
                <a class="ec-link" href="{{ $loginUrl ?? route('tenant.login', $tenant->slug) }}">
                    Back to login
                </a>
                <a class="ec-link ec-link--muted" href="{{ $landingUrl ?? route('tenant.portal.landing', $tenant->slug) }}">
                    School portal
                </a>
            </nav>

            <x-auth.footer :tenant="$tenant->name" />
        </section>
    </main>
</div>
@endsection
