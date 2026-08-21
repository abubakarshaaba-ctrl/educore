@extends('layouts.auth')

@section('page-title', 'CBT LAN Access - EduCore')

@push('auth-styles')
<style>
    .lan-access-badge{display:inline-flex;align-items:center;gap:7px;padding:6px 10px;border:1px solid rgba(242,195,91,.35);border-radius:999px;color:#F8D889;background:rgba(215,154,33,.10);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .lan-access-badge::before{content:'';width:7px;height:7px;border-radius:50%;background:#34D399;box-shadow:0 0 0 4px rgba(52,211,153,.14)}
    .lan-access-note{margin:16px 0 0;padding:12px 13px;border:1px solid #D8E0E8;border-radius:10px;background:#F8FAFC;color:#667085;font-size:.76rem;line-height:1.55}
    .lan-access-note strong{color:#101828}
    .lan-access-input{font-size:1.1rem!important;font-weight:750!important;letter-spacing:.045em;text-transform:uppercase}
</style>
@endpush

@section('auth-body')
<div class="auth-shell auth-shell--refined" style="--tenant-primary:var(--ec-navy);--tenant-accent:var(--ec-gold)">
    <aside class="auth-brand" aria-label="EduCore CBT LAN">
        <div class="auth-brand__identity">
            <img src="{{ asset('assets/brand/educore-icon.svg') }}" alt="EduCore">
            <span class="auth-brand__wordmark">EDU<span style="color:var(--ec-gold)">CORE</span></span>
        </div>

        <div class="auth-brand__body">
            <div class="lan-access-badge">Local examination network</div>
            <div class="auth-brand__rule" aria-hidden="true"></div>
            <h1 class="auth-brand__title">Your examination.<br>One number away.</h1>
            <p class="auth-brand__lead">Enter the admission number issued by your school to continue to the available CBT examination.</p>
        </div>

        <div class="auth-brand__bottom">
            <span>EduCore Examination Services</span>
            <span>Private LAN access</span>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="lan-access-heading">
            <div class="auth-portal-context">
                <span class="auth-portal-context__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M7 3h10v4H7V3ZM5 7h14v14H5V7Zm3 4h8m-8 4h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>
                    <span class="auth-portal-context__label">CBT LAN Access</span>
                    <span class="auth-portal-context__meta">No password required</span>
                </span>
            </div>

            <header class="auth-card__header">
                <h2 class="auth-title" id="lan-access-heading">Enter your admission number</h2>
                <p class="auth-subtitle">Use the number exactly as it appears on your school record.</p>
            </header>

            @if($errors->any())
                <x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>
            @endif

            <form method="POST" action="{{ route('cbt.lan.student.authenticate') }}" novalidate>
                @csrf
                <div class="ec-form-group">
                    <label class="ec-label" for="admission_number">Admission number</label>
                    <input id="admission_number"
                        class="ec-input lan-access-input{{ $errors->has('admission_number') ? ' ec-input--error' : '' }}"
                        type="text" name="admission_number" value="{{ old('admission_number') }}"
                        autocomplete="off" autocapitalize="characters" spellcheck="false" autofocus required
                        placeholder="e.g. STU001"
                        aria-invalid="{{ $errors->has('admission_number') ? 'true' : 'false' }}">
                    @error('admission_number')<p class="ec-field-error">{{ $message }}</p>@enderror
                </div>

                <x-auth.submit-button>Continue to examination</x-auth.submit-button>
            </form>

            <p class="lan-access-note"><strong>Examination-only access.</strong> This session cannot open results, fees, attendance, messages, or other student records.</p>
        </section>
    </main>
</div>
@endsection
