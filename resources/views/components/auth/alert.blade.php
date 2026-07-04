@props(['type' => 'error'])

@php
    $cssType = in_array($type, ['error', 'ok', 'warn', 'info'], true) ? $type : 'error';
    $role = in_array($cssType, ['error', 'warn'], true) ? 'alert' : 'status';
@endphp

<div {{ $attributes->merge([
    'class' => 'ec-alert ec-alert--' . $cssType,
    'role' => $role,
    'aria-live' => $role === 'alert' ? 'assertive' : 'polite',
]) }}>
    <span class="ec-alert__icon" aria-hidden="true">
        @if($cssType === 'ok')
            <svg viewBox="0 0 24 24" fill="none"><path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        @elseif($cssType === 'info')
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 17v-6M12 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        @else
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 8v5M12 17h.01M10.3 4.3 2.5 18a1.4 1.4 0 0 0 1.2 2h16.6a1.4 1.4 0 0 0 1.2-2L13.7 4.3a1.9 1.9 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        @endif
    </span>
    <span>{{ $slot }}</span>
</div>
