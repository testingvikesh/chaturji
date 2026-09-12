@if (session('success'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl bg-brand-green-50 border border-brand-green-200 px-5 py-4 text-sm text-brand-green-dark shadow-sm">
        <span class="mt-0.5 h-5 w-5 shrink-0 rounded-full bg-brand-green text-white flex items-center justify-center text-xs">✓</span>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl bg-red-50 border border-red-200 px-5 py-4 text-sm text-red-700 shadow-sm">
        <span class="mt-0.5 h-5 w-5 shrink-0 rounded-full bg-red-500 text-white flex items-center justify-center text-xs">!</span>
        <span>{{ session('error') }}</span>
    </div>
@endif
