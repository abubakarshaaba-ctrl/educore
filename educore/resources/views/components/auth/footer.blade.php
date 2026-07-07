@props([
    'extra' => null,
    'tenant' => null,
])

@php
    $supportEmail = 'support@educoreng.online';
    $supportPhone = '07065595768';
    $supportPhoneInternational = '+2347065595768';
@endphp

<div {{ $attributes->merge(['class' => 'auth-footer', 'style' => 'display:flex;flex-direction:column;align-items:stretch;gap:0;background:var(--ec-navy);border-radius:12px;padding:18px 20px;margin-top:8px']) }}>
    <nav class="auth-footer__contacts" aria-label="EduCore support contacts" style="display:flex;flex-direction:column;align-items:flex-start;gap:9px">
        <a href="tel:{{ $supportPhoneInternational }}" style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.85);font-weight:650;text-decoration:none;font-size:.8rem">
            <svg viewBox="0 0 24 24" fill="var(--ec-gold)" style="width:15px;height:15px;flex-shrink:0"><path d="M6.6 10.8c1.4 2.8 3.7 5.1 6.5 6.5l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1.1.5 1.1 1.1V20c0 .6-.5 1.1-1.1 1.1C10.9 21.1 2.9 13.1 2.9 3.2c0-.6.5-1.1 1.1-1.1h3.5c.6 0 1.1.5 1.1 1.1 0 1.2.2 2.4.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
            {{ $supportPhone }}
        </a>
        <a href="https://wa.me/2347065595768" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.85);font-weight:650;text-decoration:none;font-size:.8rem">
            <svg viewBox="0 0 24 24" fill="var(--ec-gold)" style="width:15px;height:15px;flex-shrink:0"><path d="M12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5.2-1.3c1.5.8 3.1 1.3 4.8 1.3 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18.1c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3.1.8.8-3-.2-.3c-.9-1.4-1.3-2.9-1.3-4.5 0-4.5 3.7-8.2 8.2-8.2s8.2 3.7 8.2 8.2-3.7 8.2-8.2 8.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8.9-.1.2-.3.2-.6.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5.1-.1.2-.3.4-.4.1-.1.2-.2.2-.4.1-.1 0-.3 0-.4-.1-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.4 3.8 3.4.5.2.9.4 1.3.5.5.2 1 .1 1.4.1.4-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2-.1-.1-.2-.2-.4-.3z"/></svg>
            WhatsApp: {{ $supportPhoneInternational }}
        </a>
        <a href="mailto:{{ $supportEmail }}" style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.85);font-weight:650;text-decoration:none;font-size:.8rem">
            <svg viewBox="0 0 24 24" fill="var(--ec-gold)" style="width:15px;height:15px;flex-shrink:0"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
            {{ $supportEmail }}
        </a>
    </nav>
    <div class="auth-footer__meta" style="display:flex;justify-content:flex-start;flex-wrap:wrap;gap:6px 10px;margin-top:14px;padding-top:14px;border-top:1px solid rgba(255,255,255,.12);font-size:.73rem;color:rgba(255,255,255,.55)">
        <span><span style="color:#fff">Edu<span style="color:var(--ec-gold)">Core</span></span>&nbsp;&copy; {{ date('Y') }}</span>
        @if($tenant)
            <span>Customised and powered by EduCore for {{ $tenant }}</span>
        @elseif($extra)
            <span>{{ $extra }}</span>
        @else
            <span>School Management Platform</span>
        @endif
    </div>
</div>
