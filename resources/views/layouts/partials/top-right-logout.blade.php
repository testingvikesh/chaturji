{{-- Desktop: floating logout at full-screen top-right (outside sidebar) --}}
<div class="hidden lg:block fixed top-3 right-4 z-50">
    @include('layouts.partials.logout-icon-button', [
        'action' => $action ?? route('logout'),
        'confirm' => $confirm ?? 'Are you sure you want to logout?',
        'tone' => 'panel',
    ])
</div>
