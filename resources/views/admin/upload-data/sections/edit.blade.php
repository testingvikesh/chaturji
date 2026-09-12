<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Curriculum</span>
            <h2 class="admin-page-title">Edit Section</h2>
            <p class="admin-page-subtitle">{{ $content->title }}</p>
        </div>
    </x-slot>

    @include('admin.partials.alert')

    <div class="mb-4">
        <a href="{{ route('admin.upload-data.show', $content) }}" class="text-sm text-brand-green hover:underline">&larr; Back to Upload Details</a>
    </div>

    <form method="POST" action="{{ route('admin.upload-data.sections.update', [$content, $section]) }}" class="admin-card max-w-3xl">
        <div class="admin-card-top"></div>
        <div class="admin-card-header">
            <h3 class="font-bold text-slate-900">{{ $section->title ?: $section->typeLabel() }}</h3>
            <span class="admin-badge-green">{{ $section->typeLabel() }}</span>
        </div>
        <div class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Title</label>
                <input type="text" name="title" value="{{ old('title', $section->title) }}" class="admin-input">
                @error('title')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Content <span class="text-red-500">*</span></label>
                <textarea name="content" rows="12" required class="admin-input">{{ old('content', $section->content) }}</textarea>
                @error('content')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="admin-btn-primary">Update Section</button>
                <a href="{{ route('admin.upload-data.show', $content) }}" class="admin-btn-secondary">Cancel</a>
            </div>
        </div>
    </form>

    <div class="mt-6">
        <x-admin.action-delete :action="route('admin.upload-data.sections.destroy', [$content, $section])" label="Delete section" confirm="Delete this section?" :compact="false" />
    </div>
</x-app-layout>
