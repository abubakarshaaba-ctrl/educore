@extends('layouts.auth')

@section('page-title', ($tenant->name ?? 'School') . ' - School Portal')

@section('auth-body')
@php
    $schoolPortalUrl = $landingUrl ?? route('tenant.portal.landing', $tenant->slug);
    $staffLoginUrl = $loginUrl ?? route('tenant.login', $tenant->slug);
    $admissionUrl = $admissionsUrl ?? (Route::has('portal.landing') ? route('portal.landing', $tenant->slug) : null);
    $admissionStatusUrl = Route::has('portal.status.form') ? route('portal.status.form', $tenant->slug) : null;
@endphp

<main class="auth-shell auth-shell--gateway" style="--tenant-primary: {{ $branding['primary'] }}; --tenant-accent: {{ $branding['accent'] }};">
    <section class="auth-brand auth-brand--tenant" aria-labelledby="school-gateway-title">
        <div class="auth-brand__top">
            <x-auth.tenant-branding
                :tenant="$tenant"
                :branding="$branding"
                :landingUrl="$schoolPortalUrl"
            />
        </div>

        <div class="auth-brand__body">
            <p class="auth-eyebrow">Official School Portal</p>
            <h1 class="auth-brand__title" id="school-gateway-title">
                {{ $tenant->name }}
            </h1>
            <p class="auth-brand__lead">
                {{ $branding['motto'] ?: 'A secure school access gateway for staff, families, applicants, students, and authorised partners.' }}
            </p>

            <div class="auth-split-actions" aria-label="Primary school actions">
                <a class="ec-btn ec-btn--gold" href="{{ $staffLoginUrl }}">Staff Login</a>
                @if($admissionUrl)
                    <a class="ec-btn ec-btn--secondary" href="{{ $admissionUrl }}">Admissions</a>
                @endif
            </div>

            <div class="auth-feature-grid" aria-label="School gateway capabilities">
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V5h16v14H4Zm4-9h8M8 14h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Staff Operations
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 6h14v12H5V6Zm3 4h8M8 14h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Admissions
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 2a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM2.5 20c.7-4 2.4-6 5-6s4.3 2 5 6M12 20c.7-3.4 2.4-5.1 5-5.1 2.3 0 3.8 1.4 4.5 4.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Family Access
                </div>
                <div class="auth-feature">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7l-8-4Z" stroke="currentColor" stroke-width="1.8"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Tenant-Isolated Access
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
    </section>

    <aside class="auth-panel" aria-label="School information and access links">
        <section class="auth-card auth-card--wide" aria-labelledby="gateway-links-title">
            <header class="auth-card__header">
                <p class="auth-card__eyebrow">School Access Gateway</p>
                <h2 class="auth-title" id="gateway-links-title">Choose the correct portal</h2>
                <p class="auth-subtitle">
                    Use the entry point assigned by {{ $tenant->name }}. EduCore platform
                    access is reserved for platform administrators only.
                </p>
            </header>

            @if($branding['address'] || $branding['phone'] || $branding['email'] || $branding['website'])
                <div class="auth-info-grid" aria-label="School contact information">
                    <x-auth.tenant-info-item label="Address" :value="$branding['address']" />
                    <x-auth.tenant-info-item label="Phone" :value="$branding['phone']" />
                    <x-auth.tenant-info-item label="Email" :value="$branding['email']" :href="$branding['email'] ? 'mailto:' . $branding['email'] : null" />
                    <x-auth.tenant-info-item label="Website" :value="$branding['website']" :href="$branding['website']" />
                </div>
            @endif

            <nav class="auth-portal-list" aria-label="Tenant portal links">
                <x-auth.portal-link
                    :href="$staffLoginUrl"
                    label="Staff and administration login"
                    description="For authorised school staff and administrators."
                    variant="primary"
                />

                @if($admissionUrl)
                    <x-auth.portal-link
                        :href="$admissionUrl"
                        label="Admissions"
                        description="Start or continue an application."
                    />
                @endif

                @if($admissionStatusUrl)
                    <x-auth.portal-link
                        :href="$admissionStatusUrl"
                        label="Check admission status"
                        description="Check an application decision or payment status."
                    />
                @endif

                @if(Route::has('parent.login'))
                    <x-auth.portal-link
                        :href="route('parent.login')"
                        label="Parent Portal Access"
                        description="For school-issued parent and guardian accounts."
                    />
                @endif

                @if(Route::has('student.portal.dashboard'))
                    <x-auth.portal-link
                        :href="route('student.portal.dashboard')"
                        label="Student Portal Access"
                        description="For verified student portal sessions."
                    />
                @endif

                @if(Route::has('login'))
                    <x-auth.portal-link
                        :href="route('login')"
                        label="EduCore Platform Access"
                        description="For EduCore platform administrators only."
                    />
                @endif
            </nav>

            <div class="auth-note">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2M6 10h12v10H6V10Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>{{ $tenant->name }} remains the primary portal owner. EduCore provides the secure tenant-isolated platform underneath.</span>
            </div>
        </section>
    </aside>
</main>
@endsection
