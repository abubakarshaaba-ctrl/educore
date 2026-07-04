@extends('layouts.auth')
@section('page-title', 'Set Up Two-Factor Authentication')

@section('auth-body')
<div class="auth-shell" style="--tenant-primary: var(--ec-navy); --tenant-accent: var(--ec-gold);">
    <aside class="auth-brand" aria-labelledby="2fa-setup-title">
        <div class="auth-brand__top"><x-auth.logo /></div>
        <div class="auth-brand__body">
            <p class="auth-eyebrow">Security</p>
            <h1 class="auth-brand__title" id="2fa-setup-title">Set up two-factor <span>authentication.</span></h1>
            <p class="auth-brand__lead">Protect your super-admin account with a time-based one-time password (TOTP) from an authenticator app.</p>
        </div>
    </aside>

    <main class="auth-form-area">
        <div class="auth-card">
            <div class="auth-card__header">
                <h2>Scan QR Code</h2>
                <p>Open your authenticator app (Google Authenticator, Authy, etc.) and scan the code below.</p>
            </div>

            @if(session('success'))
            <div class="auth-alert auth-alert--success">{{ session('success') }}</div>
            @endif

            <div style="text-align:center;margin:24px 0">
                {!! QrCode::size(200)->generate($qrUrl) !!}
            </div>

            <p style="font-size:13px;color:var(--slate);text-align:center;margin-bottom:20px">
                Can't scan? Enter your secret key manually in your app.
            </p>

            <form method="POST" action="{{ route('2fa.confirm') }}">
                @csrf
                <div class="auth-field">
                    <label class="auth-label" for="code">Enter the 6-digit code to confirm</label>
                    <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}"
                        maxlength="6" autocomplete="one-time-code"
                        class="auth-input @error('code') is-invalid @enderror"
                        placeholder="000000" autofocus>
                    @error('code')<span class="auth-error">{{ $message }}</span>@enderror
                </div>
                <button type="submit" class="auth-btn auth-btn--primary" style="width:100%;margin-top:8px">
                    Confirm &amp; Enable
                </button>
            </form>
        </div>
    </main>
</div>
@endsection
