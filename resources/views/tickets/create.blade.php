<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <div>
            <span class="admin-section-label">{{ $label }}</span>
            <h2 class="admin-page-title">Generate Ticket</h2>
            <p class="admin-page-subtitle">Choose what you want to submit.</p>
        </div>
    </x-slot>

    <div class="admin-page"
         x-data="{ mode: @js($ticketType) }"
         @ticket-mode.window="mode = $event.detail">
        @include('admin.partials.alert')

        <div class="mb-4 flex flex-wrap items-center gap-3">
            <a href="{{ route($routePrefix.'.index') }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to Tickets</a>
            <button type="button"
                    x-show="mode"
                    x-cloak
                    class="text-sm font-semibold text-slate-500 hover:text-brand-green"
                    @click="mode = null; $dispatch('ticket-mode', null)">
                Change option
            </button>
        </div>

        <div x-show="!mode" class="grid sm:grid-cols-2 gap-4">
            <button type="button"
                    class="text-left rounded-2xl border-2 border-brand-green-100 bg-white p-5 shadow-sm transition hover:border-brand-green hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                    @click="mode = 'issue'">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-green-50 text-brand-green mb-3">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </span>
                <span class="block text-lg font-bold text-slate-900">1) Issue Ticket</span>
                <span class="mt-1 block text-sm text-slate-500">Report a problem, ask for help, or send a general support request.</span>
            </button>

            <button type="button"
                    class="text-left rounded-2xl border-2 border-amber-200 bg-white p-5 shadow-sm transition hover:border-amber-500 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                    @click="mode = 'missing'">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700 mb-3">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </span>
                <span class="block text-lg font-bold text-slate-900">2) Missing File Upload</span>
                <span class="mt-1 block text-sm text-slate-500">Select medium, standard, subject, chapter &amp; chapter no, then upload missing files.</span>
            </button>
        </div>

        <div x-show="mode === 'issue'" x-cloak>
            @include('tickets.partials.issue-form')
        </div>

        <div x-show="mode === 'missing'" x-cloak>
            @include('tickets.partials.missing-form')
        </div>
    </div>
</x-dynamic-component>
