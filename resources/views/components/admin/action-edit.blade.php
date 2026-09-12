@props([
    'href',
    'label' => 'Edit',
    'compact' => true,
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'admin-action-icon-btn admin-action-icon-btn--edit'.($compact ? '' : ' admin-action-icon-btn--with-label')]) }} title="{{ $label }}" aria-label="{{ $label }}">
    <x-admin.icon name="edit" />
    @unless ($compact)
        <span>{{ $label }}</span>
    @endunless
</a>
