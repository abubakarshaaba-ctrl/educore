@extends('layouts.auth')

@section('page-title', 'Forgot Password - EduCore')

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
        <section class="auth-card" aria-labelledby="forgot-heading">
            <header class="auth-card__header">
                <h2 class="auth-title" id="forgot-heading">Reset your password</h2>
                <p class="auth-subtitle">Enter your email, staff ID, or student ID and we'll send you a reset link.</p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif
            @if(session('status'))
                <x-auth.alert type="ok">{{ session('status') }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf
                <div class="ec-form-group">
                    <label class="ec-label" for="login_id">Email, Staff ID, or Student ID</label>
                    <input id="login_id" class="ec-input{{ $errors->has('login_id') ? ' ec-input--error' : '' }}"
                        type="text" name="login_id" value="{{ old('login_id') }}"
                        autocomplete="username" required
                        placeholder="Enter your email or ID"
                        aria-invalid="{{ $errors->has('login_id') ? 'true' : 'false' }}">
                    @error('login_id')<p class="ec-field-error">{{ $message }}</p>@enderror
                </div>

                <x-auth.submit-button>Send Reset Link</x-auth.submit-button>
            </form>

            <div class="auth-register-link">
                <a href="{{ route('login') }}">&larr; Back to Sign In</a>
            </div>
        </section>
    </main>
</div>
@endsection
