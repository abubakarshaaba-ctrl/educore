@extends('layouts.auth')

@section('page-title', 'Parent Portal — EduCore')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">

    {{-- Brand panel --}}
    <aside class="auth-brand" aria-hidden="true">
        <div class="auth-brand__top">
            <x-auth.logo />
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">EduCore School Management</p>
            <h1 class="auth-brand__title">
                Stay close<br><span>to your child's journey.</span>
            </h1>
        </div>

        <div class="auth-brand__bottom">
            <span>EduCore Education Technology</span>
            <span>&copy; {{ date('Y') }}</span>
        </div>
    </aside>

    {{-- Form panel --}}
    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="parent-login-heading">

            {{-- Portal badge --}}
            <div style="display:inline-flex;align-items:center;gap:7px;padding:5px 11px 5px 8px;border:1px solid var(--ec-border);border-radius:20px;margin-bottom:20px;background:#F8FAFC">
                <svg viewBox="0 0 20 20" fill="none" width="14" height="14" style="color:var(--ec-navy)"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M3 17c0-3.3 3.1-6 7-6s7 2.7 7 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                <span style="font-size:.74rem;font-weight:700;color:var(--ec-navy);letter-spacing:.04em">Parent Portal</span>
            </div>

            <header class="auth-card__header" style="margin-bottom:20px">
                <h2 class="auth-title" id="parent-login-heading">Welcome back</h2>
                <p class="auth-subtitle">Use the email and password provided by your school.</p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif

            @if(session('status'))
                <x-auth.alert type="ok">{{ session('status') }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ route('portal.parent.login.post') }}" novalidate>
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
                        required
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        @if($errors->has('email')) aria-describedby="email-error" @endif
                    >
                    @error('email')
                        <p class="ec-field-error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>

                <x-auth.password-input id="password" label="Password" autocomplete="current-password" :hasError="$errors->has('password')" />

                <x-auth.submit-button>Sign in</x-auth.submit-button>
            </form>

            <div class="auth-note" style="margin-top:16px">
                <svg viewBox="0 0 20 20" fill="none" width="14" height="14" style="color:var(--ec-muted);flex-shrink:0"><path d="M10 9v4M10 17h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                <span>Need access or a password reset? Contact your school administration.</span>
            </div>

            <x-auth.footer />
        </section>
    </main>
</div>
@endsection
