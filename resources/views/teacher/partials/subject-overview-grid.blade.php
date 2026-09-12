@props(['standards'])

@php
    $variants = ['', 'student-subject-card--gold', 'student-subject-card--emerald'];
@endphp

@foreach ($standards as $standard)
    @if ($standard->activeSubjects->isEmpty())
        @continue
    @endif

    <div class="admin-card">
        <div class="admin-card-top"></div>
        <div class="admin-card-header">
            <div>
                <h3 class="font-bold text-slate-900">{{ $standard->name }} — Subjects</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $standard->activeSubjects->count() }} subject(s) · chapters, questions, exams & homework</p>
            </div>
        </div>

        <div class="student-subjects-grid">
            @foreach ($standard->activeSubjects as $subject)
                @php
                    $variant = $variants[$loop->index % count($variants)];
                    $stats = $subject->teacher_stats ?? [];
                @endphp
                <a href="{{ route('teacher.subjects.show', $subject) }}"
                   class="group student-subject-card {{ $variant }}">
                    <span class="student-subject-card-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>

                    <div class="student-subject-card-icon">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>

                    <div class="student-subject-card-body">
                        <h4 class="student-subject-card-title">{{ $subject->name }}</h4>
                        <div class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-[11px] leading-snug">
                            <span class="student-subject-card-meta">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ $stats['chapters'] ?? 0 }} chapters
                            </span>
                            <span class="student-subject-card-meta">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                {{ $stats['topics'] ?? 0 }} topics
                            </span>
                            <span class="student-subject-card-meta">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $stats['questions'] ?? 0 }} questions
                            </span>
                            <span class="student-subject-card-meta">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ $stats['exams'] ?? 0 }} exams
                                @if (($stats['exams_published'] ?? 0) > 0)
                                    <span class="text-brand-green">({{ $stats['exams_published'] }} live)</span>
                                @endif
                            </span>
                            <span class="student-subject-card-meta col-span-2">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                {{ $stats['homework'] ?? 0 }} homework
                                @if (($stats['homework_published'] ?? 0) > 0)
                                    <span class="text-brand-green">({{ $stats['homework_published'] }} live)</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="student-subject-card-footer">
                        <span class="student-subject-card-cta">View details</span>
                        <span class="student-subject-card-arrow">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endforeach
