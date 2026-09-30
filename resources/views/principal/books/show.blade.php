<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Books</span>
            <h2 class="admin-page-title">{{ $subject->name }}</h2>
            <p class="admin-page-subtitle">{{ $standard->name }}{{ ! empty($medium) ? ' / '.ucfirst($medium).' medium' : '' }} — chapters from materials</p>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mb-4">
            <a href="{{ route('principal.books.index', ['medium' => $medium]) }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to Books</a>
        </div>

        @if (session('error'))
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('error') }}</div>
        @endif

        @if (! ($allowsFullMaterial ?? true))
            <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Standard 1–4: subject, chapter and topic list only. Full material opens from <strong>Standard 5</strong> and above.
            </div>
        @endif

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Index — {{ $subject->name }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $materials->count() }} chapter(s){{ ! empty($medium) ? ' · '.ucfirst($medium).' medium' : '' }}
                        @if (! ($allowsFullMaterial ?? true))
                            · list only
                        @endif
                    </p>
                </div>
            </div>

            @include('partials.book-index-materials', [
                'materials' => $materials,
                'subject' => $subject,
                'materialTopicRoute' => 'principal.books.topics.show',
                'materialChapterRoute' => 'principal.books.materials.show',
                'topicRouteExtra' => ['medium' => $medium],
                'topicsClickable' => (bool) ($allowsFullMaterial ?? true),
            ])
        </div>
    </div>
</x-principal-layout>
