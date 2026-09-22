@props([
    'action' => null,
    'confirm' => 'Logout?',
    'tone' => 'light',
])

@php
    $action = $action ?: route('logout');
    $btnClass = $tone === 'dark'
        ? 'inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 hover:bg-white/20 text-white transition'
        : 'inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 hover:bg-white/25 text-white transition';
@endphp

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" class="shrink-0">
    @csrf
    <button type="submit"
            class="{{ $btnClass }}"
            title="Logout"
            aria-label="Logout">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
    </button>
</form>
