@extends('layouts.auth')

@section('page-title', 'Create New Password - EduCore')

@section('auth-body')
<div class="auth-shell auth-shell--refined" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">
    <aside class="auth-brand" aria-label="EduCore">
        <div class="auth-brand__identity">
            <img src="{{ asset('assets/brand/educore-icon.svg') }}" alt="EduCore">
            <span class="auth-brand__wordmark">EDU<span style="color:var(--ec-gold)">CORE</span></span>
        </div>
        <div class="auth-brand__body">
            <div class="auth-brand__rule" aria-hidden="true"></div>
            <h1 class="auth-brand__title">Secure account recovery,<br>without email dependency.</h1>
        </div>
        <div class="auth-brand__bottom">
            <span><span style="color:#fff">Edu<span style="color:var(--ec-gold,#D79A21)">Core</span></span> Education Technology</span>
            <span>&copy; {{ date('Y') }}</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="password-required-heading">
            <header class="auth-card__header">
                <h2 class="auth-title" id="password-required-heading">Create your new password</h2>
                <p class="auth-subtitle">{{ $user->name }}, an administrator issued a temporary password for your account. Replace it before continuing to EduCore.</p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ url('/account/password-required') }}" novalidate>
                @csrf
                @method('PUT')
                <div class="ec-form-group">
                    <label class="ec-label" for="current_password">Temporary Password</label>
                    <input id="current_password" class="ec-input{{ $errors->has('current_password') ? ' ec-input--error' : '' }}" type="password" name="current_password" autocomplete="current-password" required>
                    @error('current_password')<p class="ec-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="ec-form-group">
                    <label class="ec-label" for="password">New Password</label>
                    <input id="password" class="ec-input{{ $errors->has('password') ? ' ec-input--error' : '' }}" type="password" name="password" autocomplete="new-password" required minlength="10">
                    <p style="font-size:11px;color:#64748B;margin:6px 0 0">Use at least 10 characters with uppercase, lowercase and a number.</p>
                    @error('password')<p class="ec-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="ec-form-group">
                    <label class="ec-label" for="password_confirmation">Confirm New Password</label>
                    <input id="password_confirmation" class="ec-input" type="password" name="password_confirmation" autocomplete="new-password" required minlength="10">
                </div>
                <x-auth.submit-button>Save New Password</x-auth.submit-button>
            </form>

            <form method="POST" action="{{ url('/logout') }}" style="margin-top:14px;text-align:center">
                @csrf
                <button type="submit" style="background:none;border:0;color:#64748B;font:inherit;font-size:12px;cursor:pointer">Sign out instead</button>
            </form>
        </section>
    </main>
</div>
@endsection
