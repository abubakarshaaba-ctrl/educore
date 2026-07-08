@extends('layouts.auth')

@section('page-title', 'Reset Password - EduCore')

@section('auth-body')
<div class="auth-shell auth-shell--refined" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">
    <aside class="auth-brand" aria-label="EduCore">
        <div class="auth-brand__identity">
            <img src="{{ asset('assets/brand/educore-icon.svg') }}" alt="EduCore">
            <span class="auth-brand__wordmark">EDU<span style="color:var(--ec-gold)">CORE</span></span>
        </div>

        <div class="auth-brand__body">
            <div class="auth-brand__rule" aria-hidden="true"></div>
            <h1 class="auth-brand__title">School management,<br>thoughtfully connected.</h1>
        </div>

        <div class="auth-brand__bottom">
            <span><span style="color:#fff">Edu<span style="color:var(--ec-gold,#D79A21)">Core</span></span> Education Technology</span>
            <span>&copy; {{ date('Y') }}</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="reset-heading">
            <header class="auth-card__header">
                <h2 class="auth-title" id="reset-heading">Choose a new password</h2>
                <p class="auth-subtitle">Enter your email address and a new password.</p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="ec-form-group">
                    <label class="ec-label" for="email">Email Address</label>
                    <input id="email" class="ec-input{{ $errors->has('email') ? ' ec-input--error' : '' }}"
                        type="email" name="email" value="{{ old('email', $email) }}"
                        autocomplete="username" required>
                    @error('email')<p class="ec-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="ec-form-group">
                    <label class="ec-label" for="password">New Password</label>
                    <input id="password" class="ec-input"
                        type="password" name="password" autocomplete="new-password" required minlength="8">
                    @error('password')<p class="ec-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="ec-form-group">
                    <label class="ec-label" for="password_confirmation">Confirm New Password</label>
                    <input id="password_confirmation" class="ec-input"
                        type="password" name="password_confirmation" autocomplete="new-password" required minlength="8">
                </div>

                <x-auth.submit-button>Reset Password</x-auth.submit-button>
            </form>

            <div class="auth-register-link">
                <a href="{{ route('login') }}">&larr; Back to Sign In</a>
            </div>
        </section>
    </main>
</div>
@endsection
