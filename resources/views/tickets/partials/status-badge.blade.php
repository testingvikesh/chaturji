@php
    $class = match ($status) {
        'answered' => 'admin-badge-green',
        'closed' => 'admin-badge-slate',
        default => 'admin-badge-gold',
    };
@endphp
<span class="{{ $class }} text-xs capitalize">{{ $status }}</span>
