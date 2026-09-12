@props([
    'href',
    'label' => 'View',
    'compact' => true,
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'admin-action-icon-btn admin-action-icon-btn--view'.($compact ? '' : ' admin-action-icon-btn--with-label')]) }} title="{{ $label }}" aria-label="{{ $label }}">
    <x-admin.icon name="view" />
    @unless ($compact)
        <span>{{ $label }}</span>
    @endunless
</a>
