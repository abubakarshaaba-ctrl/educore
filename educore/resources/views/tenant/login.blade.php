@extends('layouts.auth')

@section('page-title', ($tenant->name ?? 'School') . ' - Staff Login')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: {{ $branding['primary'] }}; --tenant-accent: {{ $branding['accent'] }};">
    <aside class="auth-brand auth-brand--tenant" aria-labelledby="tenant-staff-title">
        <div class="auth-brand__top">
            <x-auth.tenant-branding
                :tenant="$tenant"
                :branding="$branding"
                :landingUrl="$landingUrl ?? route('tenant.portal.landing', $tenant->slug)"
            />
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">Staff and Administration Login</p>
            <h1 class="auth-brand__title" id="tenant-staff-title">
                Secure access for <span>{{ $tenant->name }}</span> staff.
            </h1>
            <p class="auth-brand__lead">
                For tenant administrators, school owners, principals, teachers,
                accountants, human-resource personnel, and other authorised staff.
            </p>

            <div class="auth-feature-grid" aria-label="School staff capabilities">
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V5h16v14H4Zm4-9h8M8 14h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    School Administration
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 19h14M7 16V8h10v8M10 11h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Academic Operations
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 2a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM2.5 20c.7-4 2.4-6 5-6s4.3 2 5 6M12 20c.7-3.4 2.4-5.1 5-5.1 2.3 0 3.8 1.4 4.5 4.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Workforce Access
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7l-8-4Z" stroke="currentColor" stroke-width="1.8"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Tenant-Isolated Security
                </div>
            </div>
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
        <section class="auth-card" aria-labelledby="tenant-login-heading">
            <header class="auth-card__header">
                <p class="auth-card__eyebrow">Staff and Administrator Login</p>
                <h2 class="auth-title" id="tenant-login-heading">Welcome back</h2>
                <p class="auth-subtitle">
                    Sign in with your staff ID or school email address. Parent,
                    student, and agent portals remain separate.
                </p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif

            @if(session('status'))
                <x-auth.alert type="ok">{{ session('status') }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ $loginAction ?? route('tenant.login.submit', $tenant->slug) }}" novalidate>
                @csrf

                <div class="ec-form-group">
                    <label class="ec-label" for="login_id">Staff ID or Email</label>
                    <input
                        id="login_id"
                        class="ec-input{{ $errors->has('login_id') ? ' ec-input--error' : '' }}"
                        type="text"
                        name="login_id"
                        value="{{ old('login_id') }}"
                        autocomplete="username"
                        aria-invalid="{{ $errors->has('login_id') ? 'true' : 'false' }}"
                        @if($errors->has('login_id')) aria-describedby="login_id-error" @endif
                        required
                    >
                    @error('login_id')
                        <p class="ec-field-error" id="login_id-error">{{ $message }}</p>
                    @enderror
                </div>

                <x-auth.password-input
                    id="password"
                    label="Password"
                    autocomplete="current-password"
                    :hasError="$errors->has('password')"
                />

                <label class="ec-remember">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me signed in</span>
                </label>

                <x-auth.submit-button :tenantColor="$branding['primary']">
                    Sign In
                </x-auth.submit-button>
            </form>

            <nav class="ec-links" aria-label="Tenant login links">
                @if(isset($forgotPasswordUrl) || Route::has('tenant.password.request'))
                    <a class="ec-link" href="{{ $forgotPasswordUrl ?? route('tenant.password.request', $tenant->slug) }}">
                        Forgot password?
                    </a>
                @endif

                <a class="ec-link ec-link--muted" href="{{ $landingUrl ?? route('tenant.portal.landing', $tenant->slug) }}">
                    School portal
                </a>

                @if(isset($admissionsUrl) || Route::has('portal.landing'))
                    <a class="ec-link ec-link--muted" href="{{ $admissionsUrl ?? route('portal.landing', $tenant->slug) }}">
                        Admissions
                    </a>
                @endif

                @if(Route::has('parent.login'))
                    <a class="ec-link ec-link--muted" href="{{ route('parent.login') }}">
                        Parent Portal
                    </a>
                @endif

                @if(Route::has('student.portal.dashboard'))
                    <a class="ec-link ec-link--muted" href="{{ route('student.portal.dashboard') }}">
                        Student Portal
                    </a>
                @endif
            </nav>

            <div class="auth-note">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2M6 10h12v10H6V10Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>This login is isolated to {{ $tenant->name }} staff accounts.</span>
            </div>

            <x-auth.footer :tenant="$tenant->name" />
        </section>
    </main>
</div>
@endsection
