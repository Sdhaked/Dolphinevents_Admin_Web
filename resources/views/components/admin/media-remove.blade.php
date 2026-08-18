@props([
    'exists' => false,
    'deleteUrl' => '',
    'label' => 'media',
    'requiredAfterDelete' => false,
])

<span {{ $attributes->merge(['class' => 'js-media-remove remove-img']) }}
    role="button"
    tabindex="0"
    aria-label="Remove {{ $label }}"
    data-has-media="{{ $exists ? '1' : '0' }}"
    data-delete-url="{{ $deleteUrl }}"
    data-media-label="{{ $label }}"
    data-required-after-delete="{{ $requiredAfterDelete ? '1' : '0' }}"
    style="{{ $exists ? 'display: inline-block;' : '' }}">
    <i class="fa-solid fa-rectangle-xmark"></i>
</span>
