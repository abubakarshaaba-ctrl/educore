@extends('layouts.auth')
@section('page-title', 'Two-Factor Authentication')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">
    <aside class="auth-brand" aria-labelledby="2fa-challenge-title">
        <div class="auth-brand__top"><x-auth.logo /></div>
        <div class="auth-brand__body">
            <p class="auth-eyebrow">Security Check</p>
            <h1 class="auth-brand__title" id="2fa-challenge-title">Two-factor <span>authentication.</span></h1>
            <p class="auth-brand__lead">Enter the 6-digit code from your authenticator app to continue.</p>
        </div>
    </aside>

    <main class="auth-form-area">
        <div class="auth-card">
            <div class="auth-card__header">
                <h2>Enter Authentication Code</h2>
                <p>Open your authenticator app and enter the current code.</p>
            </div>

            @if($errors->any())
            <div class="auth-alert auth-alert--error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('2fa.verify') }}">
                @csrf
                <div class="auth-field">
                    <label class="auth-label" for="code">Authentication Code</label>
                    <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}"
                        maxlength="6" autocomplete="one-time-code"
                        class="auth-input @error('code') is-invalid @enderror"
                        placeholder="000000" autofocus>
                    @error('code')<span class="auth-error">{{ $message }}</span>@enderror
                </div>
                <button type="submit" class="auth-btn auth-btn--primary" style="width:100%;margin-top:8px">
                    Verify
                </button>
            </form>

            <div style="margin-top:16px;text-align:center;font-size:13px;color:var(--slate)">
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:var(--indigo);cursor:pointer;font-size:13px">
                        Sign out and use a different account
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
@endsection
