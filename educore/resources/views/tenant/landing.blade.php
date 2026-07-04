@extends('layouts.auth')

@section('page-title', ($tenant->name ?? 'School') . ' - School Portal')

@section('auth-body')
@php
    $schoolPortalUrl = $landingUrl ?? route('tenant.portal.landing', $tenant->slug);
    $signInUrl = $loginUrl ?? route('tenant.login', $tenant->slug);
    $admissionUrl = $admissionsUrl ?? (Route::has('portal.landing') ? route('portal.landing', $tenant->slug) : null);
    $forgotUrl = $forgotPasswordUrl ?? (Route::has('tenant.password.request') ? route('tenant.password.request', $tenant->slug) : null);
@endphp

<div class="auth-shell auth-shell--refined" style="--tenant-primary: {{ $branding['primary'] }}; --tenant-accent: {{ $branding['accent'] }};">
    <aside class="auth-brand" aria-label="{{ $tenant->name }}" style="background: {{ $branding['primary'] }};">
        <div class="auth-brand__identity">
            @if(!empty($branding['logo_url']))
                <img src="{{ $branding['logo_url'] }}" alt="{{ $tenant->name }} logo">
            @else
                <span class="auth-tenant-logo auth-tenant-logo--fallback" aria-hidden="true">
                    {{ strtoupper(mb_substr($tenant->name, 0, 1)) }}
                </span>
            @endif
            <span class="auth-brand__wordmark">{{ $tenant->name }}</span>
        </div>

        <div class="auth-brand__body">
            <div class="auth-brand__rule" aria-hidden="true"></div>
            <h1 class="auth-brand__title">{{ $tenant->name }}</h1>
            <p class="auth-brand__lead">
                {{ $branding['motto'] ?: 'A secure school portal for staff, students, and families.' }}
            </p>
        </div>

        <svg class="auth-brand__motif" viewBox="0 0 620 290" fill="none" aria-hidden="true">
            <path d="M24 220h116l38-42h92l38 38h94l44-55h135" stroke="currentColor" stroke-width="2"/>
            <path d="M74 88h90l34 34h96l30-30h112l34 34h92" stroke="currentColor" stroke-width="2" stroke-dasharray="8 10"/>
            <rect x="58" y="176" width="88" height="70" rx="6" stroke="currentColor" stroke-width="2"/>
            <path d="M78 196c18-8 34-8 48 0v30c-14-8-30-8-48 0v-30Zm96-56h76v96h-76z" stroke="currentColor" stroke-width="2"/>
            <circle cx="212" cy="168" r="13" stroke="currentColor" stroke-width="2"/>
            <path d="M192 208c4-17 12-26 20-26s16 9 20 26M346 194v42h22v-62h22v62h22v-86h22v86" stroke="currentColor" stroke-width="2"/>
            <circle cx="470" cy="126" r="10" stroke="currentColor" stroke-width="2"/>
            <circle cx="552" cy="160" r="10" stroke="currentColor" stroke-width="2"/>
        </svg>

        <div class="auth-brand__bottom">
            <span>{{ $tenant->name }} — powered by EduCore</span>
            <span>&copy; {{ date('Y') }}</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="school-login-heading">
            <div class="auth-portal-context">
                <span class="auth-portal-context__icon" aria-hidden="true">
                    @if(!empty($branding['logo_url']))
                        <img src="{{ $branding['logo_url'] }}" alt="">
                    @else
                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 20V9l8-5 8 5v11M8 20v-7h8v7M3 20h18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </span>
                <span>
                    <span class="auth-portal-context__label">{{ $tenant->name }}</span>
                    <span class="auth-portal-context__meta">Official school portal — all users, one door</span>
                </span>
            </div>

            <header class="auth-card__header">
                <h2 class="auth-title" id="school-login-heading">Welcome back</h2>
                <p class="auth-subtitle">Sign in with your email address, staff ID, or student ID.</p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif
            @if(session('status'))
                <x-auth.alert type="ok">{{ session('status') }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ $signInUrl }}" novalidate>
                @csrf
                <div class="ec-form-group">
                    <label class="ec-label" for="login_id">Email, Staff ID, or Student ID</label>
                    <input id="login_id" class="ec-input{{ $errors->has('login_id') ? ' ec-input--error' : '' }}"
                        type="text" name="login_id" value="{{ old('login_id') }}"
                        autocomplete="username" required
                        placeholder="Enter your email or ID"
                        aria-invalid="{{ $errors->has('login_id') ? 'true' : 'false' }}"
                        @if($errors->has('login_id')) aria-describedby="login_id-error" @endif>
                    @error('login_id')<p class="ec-field-error" id="login_id-error">{{ $message }}</p>@enderror
                </div>

                <div class="ec-form-group">
                    <label class="ec-label" for="password">Password</label>
                    <div class="ec-input-wrap">
                        <input id="password" class="ec-input{{ $errors->has('password') ? ' ec-input--error' : '' }}"
                            type="password" name="password" autocomplete="current-password" required
                            placeholder="Enter your password"
                            aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                        <button class="ec-eye-btn" type="button" data-ec-eye="password" aria-label="Show password" aria-pressed="false">
                            <svg class="ec-eye-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')<p class="ec-field-error">{{ $message }}</p>@enderror
                </div>

                <label class="ec-remember">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me signed in</span>
                </label>

                <x-auth.submit-button>Sign in</x-auth.submit-button>
            </form>

            <nav class="ec-links" aria-label="School portal links">
                @if($forgotUrl)
                    <a class="ec-link" href="{{ $forgotUrl }}">Forgot password?</a>
                @endif
                @if($admissionUrl)
                    <a class="ec-link ec-link--muted" href="{{ $admissionUrl }}">Admissions</a>
                @endif
            </nav>

            @if($branding['address'] || $branding['phone'] || $branding['email'] || $branding['website'])
                <div class="auth-info-grid" aria-label="School contact information">
                    <x-auth.tenant-info-item label="Address" :value="$branding['address']" />
                    <x-auth.tenant-info-item label="Phone" :value="$branding['phone']" />
                    <x-auth.tenant-info-item label="Email" :value="$branding['email']" :href="$branding['email'] ? 'mailto:' . $branding['email'] : null" />
                    <x-auth.tenant-info-item label="Website" :value="$branding['website']" :href="$branding['website']" />
                </div>
            @endif

            <div class="auth-note">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2M6 10h12v10H6V10Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>This portal is isolated to {{ $tenant->name }} accounts.</span>
            </div>

            <x-auth.footer :tenant="$tenant->name" />
        </section>
    </main>
</div>

@push('auth-styles')
<style>
    /* School landing inherits the refined login shell; tenant colour drives the brand panel */
    .auth-shell--refined .auth-brand__identity img,
    .auth-shell--refined .auth-brand__identity .auth-tenant-logo--fallback {
        width: clamp(62px, 6vw, 88px);
        height: clamp(62px, 6vw, 88px);
        border-radius: 12px;
        background: rgba(255, 255, 255, .10);
        border: 1px solid rgba(255, 255, 255, .16);
        object-fit: contain;
        display: grid;
        place-items: center;
    }
    .auth-shell--refined .auth-brand__wordmark {
        letter-spacing: .02em;
        line-height: 1.15;
    }
</style>
@endpush
@endsection
