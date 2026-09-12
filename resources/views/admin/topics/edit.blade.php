<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-slate-900">Edit Topic</h2></x-slot>
    <form method="POST" action="{{ route('admin.topics.update', $topic) }}" class="max-w-xl bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">@csrf @method('PUT') @include('admin.partials.curriculum-fields', ['item' => $topic])<x-primary-button>Update</x-primary-button></form>
    <div class="mt-4">
        <x-admin.action-delete :action="route('admin.topics.destroy', $topic)" label="Delete topic" confirm="Delete this topic?" :compact="false" />
    </div>
</x-app-layout>
