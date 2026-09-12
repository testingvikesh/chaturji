<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-slate-900">Add Subject — {{ $standard->name }}</h2></x-slot>
    <form method="POST" action="{{ route('admin.standards.subjects.store', $standard) }}" class="max-w-xl bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">@csrf @include('admin.partials.curriculum-fields')<x-primary-button>Create Subject</x-primary-button></form>
</x-app-layout>
