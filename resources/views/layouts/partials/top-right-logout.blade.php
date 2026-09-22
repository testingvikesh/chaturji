@php
    $logoutAction = $action ?? route('logout');
    $logoutConfirm = $confirm ?? 'Are you sure you want to logout?';
@endphp

{{-- Always visible on desktop, pinned to viewport top-right of the content screen --}}
<div class="hidden lg:flex items-center justify-end fixed top-0 right-0 z-[60] h-14 px-4 pointer-events-none">
    <div class="pointer-events-auto">
        @include('layouts.partials.logout-icon-button', [
            'action' => $logoutAction,
            'confirm' => $logoutConfirm,
            'tone' => 'panel',
        ])
    </div>
</div>
