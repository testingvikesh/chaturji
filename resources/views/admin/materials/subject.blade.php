<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="{{ $subject->name }}"
            subtitle="{{ $mediumLabel }} · {{ $standard->name }} — chapters from materials">
            <x-slot name="actions">
                <a href="{{ route('admin.materials.standard', [$medium, $standard]) }}" class="admin-btn-secondary">← Subjects</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-4">
        <p class="text-sm text-slate-500">
            <a href="{{ route('admin.materials.index') }}" class="text-brand-green hover:underline font-medium">All Materials</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('admin.materials.medium', $medium) }}" class="text-brand-green hover:underline font-medium">{{ $mediumLabel }}</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('admin.materials.standard', [$medium, $standard]) }}" class="text-brand-green hover:underline font-medium">{{ $standard->name }}</a>
            <span class="text-slate-300">/</span> {{ $subject->name }}
        </p>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Index — {{ $subject->name }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $materials->count() }} chapter(s) · click chapter / topic to view material</p>
                </div>
            </div>

            @include('partials.book-index-materials', [
                'materials' => $materials,
                'subject' => $subject,
                'materialTopicRoute' => 'admin.materials.topics.show',
                'materialChapterRoute' => 'admin.materials.materials.show',
                'topicRouteExtra' => ['medium' => $medium],
            ])
        </div>
    </div>
</x-app-layout>
