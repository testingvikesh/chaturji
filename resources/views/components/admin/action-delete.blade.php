@props([
    'action',
    'label' => 'Delete',
    'confirm' => 'Are you sure you want to delete this?',
    'compact' => true,
])

<form method="POST" action="{{ $action }}" {{ $attributes->merge(['class' => 'inline']) }} onsubmit="return confirm(@js($confirm))">
    @csrf
    @method('DELETE')
    <button type="submit" class="admin-action-icon-btn admin-action-icon-btn--delete{{ $compact ? '' : ' admin-action-icon-btn--with-label' }}" title="{{ $label }}" aria-label="{{ $label }}">
        <x-admin.icon name="delete" />
        @unless ($compact)
            <span>{{ $label }}</span>
        @endunless
    </button>
</form>
