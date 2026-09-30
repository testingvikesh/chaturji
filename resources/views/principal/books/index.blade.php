<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Principal</span>
            <h2 class="admin-page-title">Books</h2>
            <p class="admin-page-subtitle">Only the standards allotted to you by admin</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-6">
        @if ($hasAllotments)
            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-body">
                    <form method="GET" action="{{ route('principal.books.index') }}" class="admin-filter-grid">
                        <div class="lg:col-span-4">
                            <label class="admin-label">Search subject</label>
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Subject name..." class="admin-input">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="admin-label">Medium</label>
                            <select name="medium" class="admin-select">
                                @foreach (($mediums ?: \App\Models\Standard::MEDIUMS) as $key => $label)
                                    <option value="{{ $key }}" @selected(($filters['medium'] ?? $medium) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-3">
                            <label class="admin-label">Standard</label>
                            <select name="standard_id" class="admin-select">
                                <option value="">All allotted standards</option>
                                @foreach ($allottedStandards as $standard)
                                    <option value="{{ $standard->id }}" @selected((string) ($filters['standard_id'] ?? '') === (string) $standard->id)>{{ $standard->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-2 flex gap-2 items-end">
                            <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                            @if (($filters['search'] ?? '') !== '' || ($filters['standard_id'] ?? '') !== '' || request()->filled('medium'))
                                <a href="{{ route('principal.books.index') }}" class="admin-btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if ($standards->isEmpty())
            <div class="admin-card">
                <div class="p-8 text-center">
                    <p class="text-sm text-slate-500">
                        @if (! $hasAllotments)
                            No standard allotted yet. Ask admin to allot standards to your principal account.
                        @else
                            No books match this filter.
                        @endif
                    </p>
                </div>
            </div>
        @else
            @php
                $variants = ['', 'student-subject-card--gold', 'student-subject-card--emerald'];
            @endphp

            @foreach ($standards as $standard)
                <div class="admin-card">
                    <div class="admin-card-top"></div>
                    <div class="admin-card-header">
                        <div>
                            <h3 class="font-bold text-slate-900">{{ $standard->name }} — Subjects</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $standard->bookSubjects->count() }} subject(s) · {{ ucfirst($standard->books_medium ?? $medium) }} medium
                                · {{ ($standard->allows_full_material ?? false) ? 'Full material' : 'Index only (Std 1–4)' }}
                            </p>
                        </div>
                    </div>

                    <div class="student-subjects-grid">
                        @foreach ($standard->bookSubjects as $subject)
                            @php $variant = $variants[$loop->index % count($variants)]; @endphp
                            <a href="{{ route('principal.books.show', ['subject' => $subject, 'medium' => ($standard->books_medium ?? $medium)]) }}"
                               class="group student-subject-card {{ $variant }}">
                                <span class="student-subject-card-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <div class="student-subject-card-icon">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                </div>
                                <div class="student-subject-card-body">
                                    <h4 class="student-subject-card-title">{{ $subject->name }}</h4>
                                    <span class="student-subject-card-meta">
                                        {{ $subject->chapters_count }} {{ \Illuminate\Support\Str::plural('chapter', $subject->chapters_count) }}
                                    </span>
                                </div>
                                <div class="student-subject-card-footer">
                                    <span class="student-subject-card-cta">Open subject</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-principal-layout>
