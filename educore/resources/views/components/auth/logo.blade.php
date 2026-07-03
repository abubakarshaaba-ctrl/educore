@props([
    'dark' => false,
    'compact' => false,
])

@php
    $src = $dark
        ? asset('assets/brand/educore-logo-horizontal.svg')
        : asset('assets/brand/educore-logo-horizontal-dark-transparent.svg');
@endphp

<a {{ $attributes->merge([
    'class' => 'ec-brand-logo' . ($dark ? ' ec-brand-logo--light' : '') . ($compact ? ' ec-brand-logo--compact' : ''),
    'href' => url('/'),
    'aria-label' => 'EduCore home',
]) }}>
    <img
        src="{{ $src }}"
        alt="EduCore School ERP"
        width="980"
        height="230"
        loading="eager"
    >
</a>
