@props([
    'extra' => null,
    'tenant' => null,
])

@php
    $supportEmail = env('EDUCORE_CONTACT_EMAIL', 'support@educoreng.online');
    $supportPhone = '07065595768';
    $supportPhoneInternational = '+2347065595768';
@endphp

<div {{ $attributes->merge(['class' => 'auth-footer', 'style' => 'display:flex;flex-direction:column;align-items:center;gap:8px']) }}>
    <div class="auth-footer__meta" style="display:flex;justify-content:center;flex-wrap:wrap;gap:6px 10px">
        <span>&copy; {{ date('Y') }} EduCore</span>
        @if($tenant)
            <span>Customised and powered by EduCore for {{ $tenant }}</span>
        @elseif($extra)
            <span>{{ $extra }}</span>
        @else
            <span>School Management Platform</span>
        @endif
    </div>
    <nav class="auth-footer__contacts" aria-label="EduCore support contacts" style="display:flex;justify-content:center;flex-wrap:wrap;gap:6px 14px">
        <a href="tel:{{ $supportPhoneInternational }}" style="color:var(--ec-muted);font-weight:650;text-decoration:none">Call {{ $supportPhone }}</a>
        <a href="https://wa.me/2347065595768" target="_blank" rel="noopener" style="color:var(--ec-muted);font-weight:650;text-decoration:none">WhatsApp</a>
        <a href="mailto:{{ $supportEmail }}" style="color:var(--ec-muted);font-weight:650;text-decoration:none">{{ $supportEmail }}</a>
    </nav>
</div>
