@props(['subjects', 'standardName', 'showViewAll' => true])

@php
    $variants = ['', 'student-subject-card--gold', 'student-subject-card--emerald'];
@endphp

<div class="admin-card">
    <div class="admin-card-top"></div>
    <div class="admin-card-header">
        <div>
            <h3 class="font-bold text-slate-900">My Subjects — {{ $standardName }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ $subjects->count() }} subject(s) from materials for your standard</p>
        </div>
        @if ($showViewAll && $subjects->isNotEmpty())
            <a href="{{ route('student.subjects.index') }}" class="admin-btn-secondary text-xs py-2 px-3">View All</a>
        @endif
    </div>

    @if ($subjects->isEmpty())
        <div class="p-8 text-center text-sm text-slate-400">
            No material subjects found for your standard and medium yet.
        </div>
    @else
        <div class="student-subjects-grid">
            @foreach ($subjects as $subject)
                @php $variant = $variants[$loop->index % count($variants)]; @endphp
                <a href="{{ route('student.subjects.show', $subject) }}"
                   data-page-loader
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
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ $subject->chapters_count }} {{ Str::plural('chapter', $subject->chapters_count) }}
                        </span>
                    </div>

                    <div class="student-subject-card-footer">
                        <span class="student-subject-card-cta">Open subject</span>
                        <span class="student-subject-card-arrow">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
