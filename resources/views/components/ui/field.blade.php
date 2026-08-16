@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'placeholder' => null,
    'inputmode' => null,
    'autocomplete' => null,
])

@php
    $id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], '-', $name));
    $error = $errors->first($name);
@endphp

<div class="field">
    <label class="field__label" for="{{ $id }}">
        {{ $label }}
        @if ($required)
            <span aria-hidden="true" style="color:var(--bad)">*</span>
            <span class="sr-only">(مطلوب)</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea
            id="{{ $id }}" name="{{ $name }}" class="textarea"
            @if ($required) required @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except(['id']) }}
        >{{ old($name, $value) }}</textarea>
    @else
        <input
            type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" class="input"
            value="{{ old($name, $value) }}"
            @if ($required) required @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except(['id']) }}
        >
    @endif

    @if ($hint && ! $error)
        <p class="field__hint">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="field__error" id="{{ $id }}-error">{{ $error }}</p>
    @endif
</div>
