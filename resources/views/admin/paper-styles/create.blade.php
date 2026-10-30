<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Add Paper Style" subtitle="Choose a standard and subject, then set each question type quantity" />
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')
        <form method="POST" action="{{ route('admin.paper-styles.store') }}" class="admin-card space-y-6 p-5 sm:p-6">
            @csrf
            @include('admin.paper-styles._form')
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">Save paper style</button>
                <a href="{{ route('admin.paper-styles.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
