@props([
    'tenantColor' => null,
    'variant' => 'primary',
])

<button
    type="submit"
    {{ $attributes->merge(['class' => 'ec-btn ec-btn--' . $variant . ($tenantColor ? ' ec-btn--tenant' : '')]) }}
    @if($tenantColor) style="--tenant-primary: {{ $tenantColor }};" @endif
>
    <span>{{ $slot->isEmpty() ? 'Sign In' : $slot }}</span>
</button>
