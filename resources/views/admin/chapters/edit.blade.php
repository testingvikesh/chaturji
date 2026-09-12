<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-slate-900">Edit Chapter</h2></x-slot>
    <form method="POST" action="{{ route('admin.chapters.update', $chapter) }}" class="max-w-xl bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">@csrf @method('PUT') @include('admin.partials.curriculum-fields', ['item' => $chapter])<x-primary-button>Update</x-primary-button></form>
    <div class="mt-4">
        <x-admin.action-delete :action="route('admin.chapters.destroy', $chapter)" label="Delete chapter" confirm="Delete this chapter?" :compact="false" />
    </div>
</x-app-layout>
