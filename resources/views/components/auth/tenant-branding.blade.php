@props([
    'tenant',
    'branding',
    'landingUrl' => null,
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'auth-tenant-lockup' . ($compact ? ' auth-tenant-lockup--compact' : '')]) }}>
    @if(!empty($branding['logo_url']))
        <a class="auth-tenant-logo" href="{{ $landingUrl ?: '#' }}" aria-label="{{ $tenant->name }} school portal">
            <img
                src="{{ $branding['logo_url'] }}"
                alt="{{ $tenant->name }} logo"
                width="112"
                height="112"
            >
        </a>
    @else
        <div class="auth-tenant-logo auth-tenant-logo--fallback" aria-hidden="true">
            {{ strtoupper(mb_substr($tenant->name, 0, 1)) }}
        </div>
    @endif

    <div class="auth-tenant-copy">
        <p class="auth-tenant-label">Official school portal</p>
        <h1>{{ $tenant->name }}</h1>
        @if(!empty($branding['motto']))
            <p class="auth-tenant-motto">{{ $branding['motto'] }}</p>
        @else
            <p class="auth-tenant-motto">Secure access for authorised school users.</p>
        @endif
    </div>
</div>
