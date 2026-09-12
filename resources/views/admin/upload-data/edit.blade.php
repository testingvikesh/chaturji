<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Curriculum</span>
            <h2 class="admin-page-title">Edit Upload Data</h2>
            <p class="admin-page-subtitle">{{ $content->chapter->subject->standard->name }} / {{ $content->chapter->subject->name }} / {{ $content->chapter->name }}</p>
        </div>
    </x-slot>

    @include('admin.partials.alert')

    <div class="mb-4">
        <a href="{{ route('admin.upload-data.show', $content) }}" class="text-sm text-brand-green hover:underline">&larr; Back to Upload Details</a>
    </div>

    <form method="POST" action="{{ route('admin.upload-data.update', $content) }}" enctype="multipart/form-data" class="admin-card max-w-3xl">
        <div class="admin-card-top"></div>
        <div class="admin-card-header">
            <h3 class="font-bold text-slate-900">Edit Upload</h3>
        </div>
        <div class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $content->title) }}" required class="admin-input">
                @error('title')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Overview</label>
                <textarea name="overview" rows="5" class="admin-input">{{ old('overview', $content->overview) }}</textarea>
                @error('overview')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Language <span class="text-red-500">*</span></label>
                <select name="language" required class="admin-select">
                    @foreach ($languages as $value => $label)
                        <option value="{{ $value }}" @selected(old('language', $content->language) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('language')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Topic Name</label>
                <input type="text" name="topic_name" value="{{ old('topic_name', $content->chapter->topics->first()?->name) }}" class="admin-input" placeholder="Update topic when re-importing JSON">
                @error('topic_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Replace JSON File (optional)</label>
                <input type="file" name="data_file" accept=".json,application/json"
                       class="block w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-brand-green-50 file:text-brand-green file:font-semibold hover:file:bg-brand-green-100">
                <p class="text-xs text-slate-500 mt-2">Upload a new JSON file to replace all sections and questions. Current file: {{ $content->source_filename }}</p>
                @error('data_file')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Original PDF File</label>
                @if ($content->hasOriginalPdf())
                    <p class="mb-2 text-xs text-slate-600">Current: <strong>{{ $content->original_pdf_filename }}</strong>
                        <a href="{{ route('chapter-content.original-pdf', $content) }}" target="_blank" class="ml-2 text-brand-green font-semibold hover:underline">Open</a>
                    </p>
                    <label class="mb-3 inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="remove_original_pdf" value="1" class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
                        Remove current PDF
                    </label>
                @else
                    <p class="mb-2 text-xs text-slate-500">No PDF uploaded yet.</p>
                @endif
                <input type="file" name="original_pdf" accept="application/pdf,.pdf"
                       class="block w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-amber-50 file:text-amber-800 file:font-semibold hover:file:bg-amber-100">
                <p class="text-xs text-slate-500 mt-2">Students see a Practice PDF button when this is set. Max 50MB.</p>
                @error('original_pdf')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="admin-btn-primary">Update Upload</button>
                <a href="{{ route('admin.upload-data.show', $content) }}" class="admin-btn-secondary">Cancel</a>
            </div>
        </div>
    </form>

    <div class="mt-6">
        <x-admin.action-delete :action="route('admin.upload-data.destroy', $content)" label="Delete upload" confirm="Delete this uploaded data permanently?" :compact="false" />
    </div>
</x-app-layout>
