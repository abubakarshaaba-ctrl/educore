@props([
    'href',
    'label',
    'description' => null,
    'variant' => 'default',
])

<a {{ $attributes->merge(['class' => 'auth-portal-link auth-portal-link--' . $variant, 'href' => $href]) }}>
    <span class="auth-portal-link__copy">
        <span class="auth-portal-link__label">{{ $label }}</span>
        @if($description)
            <span class="auth-portal-link__description">{{ $description }}</span>
        @endif
    </span>
    <svg class="auth-portal-link__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</a>
