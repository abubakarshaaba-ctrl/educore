@extends('layouts.auth')

@section('page-title', 'EduCore - Platform Access')

@section('auth-body')
<div class="auth-shell auth-shell--refined" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">
    <aside class="auth-brand" aria-label="EduCore">
        <div class="auth-brand__identity">
            <img src="{{ asset('assets/brand/educore-icon.svg') }}" alt="EduCore">
            <span class="auth-brand__wordmark">EDUCORE</span>
        </div>

        <div class="auth-brand__body">
            <div class="auth-brand__rule" aria-hidden="true"></div>
            <h1 class="auth-brand__title">School management,<br>thoughtfully connected.</h1>
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
            <span>EduCore Education Technology</span>
            <span>&copy; {{ date('Y') }}</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="login-heading">
            <div class="auth-portal-context">
                <span class="auth-portal-context__icon" aria-hidden="true">
                    <img src="{{ asset('assets/brand/educore-icon.svg') }}" alt="">
                </span>
                <span>
                    <span class="auth-portal-context__label">Platform Administration</span>
                    <span class="auth-portal-context__meta">Restricted access</span>
                </span>
            </div>

            <header class="auth-card__header">
                <h2 class="auth-title" id="login-heading">Welcome back</h2>
                <p class="auth-subtitle">Use your platform administrator credentials.</p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif
            @if(session('status'))
                <x-auth.alert type="ok">{{ session('status') }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <div class="ec-form-group">
                    <label class="ec-label" for="login_id">Email address</label>
                    <input id="login_id" class="ec-input{{ $errors->has('login_id') ? ' ec-input--error' : '' }}"
                        type="text" name="login_id" value="{{ old('login_id') }}"
                        autocomplete="username" inputmode="email" required
                        placeholder="Enter your email address"
                        aria-invalid="{{ $errors->has('login_id') ? 'true' : 'false' }}">
                    @error('login_id')<p class="ec-field-error">{{ $message }}</p>@enderror
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

            <x-auth.footer />
        </section>
    </main>
</div>
@endsection
