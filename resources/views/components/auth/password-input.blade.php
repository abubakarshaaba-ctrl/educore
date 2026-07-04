@props([
    'id' => 'password',
    'name' => null,
    'label' => 'Password',
    'autocomplete' => 'current-password',
    'required' => true,
    'hasError' => false,
])

@php
    $fieldName = $name ?: $id;
    $errorId = $id . '-error';
@endphp

<div class="ec-form-group">
    <label class="ec-label" for="{{ $id }}">{{ $label }}</label>
    <div class="ec-input-wrap">
        <input
            id="{{ $id }}"
            type="password"
            name="{{ $fieldName }}"
            class="ec-input{{ $hasError || $errors->has($fieldName) ? ' ec-input--error' : '' }}"
            autocomplete="{{ $autocomplete }}"
            aria-invalid="{{ $hasError || $errors->has($fieldName) ? 'true' : 'false' }}"
            @if($errors->has($fieldName)) aria-describedby="{{ $errorId }}" @endif
            @required($required)
        >
        <button
            class="ec-eye-btn"
            data-ec-eye="{{ $id }}"
            aria-label="Show password"
            aria-pressed="false"
        >
            <svg class="ec-eye-svg" width="18" height="18" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true" focusable="false">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
    </div>
    @error($fieldName)
        <p class="ec-field-error" id="{{ $errorId }}">{{ $message }}</p>
    @enderror
</div>
