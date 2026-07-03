@props([
    'label',
    'value',
    'href' => null,
])

@if($value)
    <div {{ $attributes->merge(['class' => 'auth-info-item']) }}>
        <span class="auth-info-item__label">{{ $label }}</span>
        @if($href)
            <a class="auth-info-item__value" href="{{ $href }}" rel="noopener noreferrer">{{ $value }}</a>
        @else
            <span class="auth-info-item__value">{{ $value }}</span>
        @endif
    </div>
@endif
