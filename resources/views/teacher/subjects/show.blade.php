<x-teacher-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center gap-1 text-sm text-brand-green hover:underline mb-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Dashboard
            </a>
            <span class="admin-section-label">Subject</span>
            <h2 class="admin-page-title">{{ $subject->name }}</h2>
            <p class="admin-page-subtitle">{{ $standard->name }} — full subject overview</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-6">
        @php $stats = $subject->teacher_stats ?? []; @endphp

        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Chapters', 'value' => $stats['chapters'] ?? 0])
            @include('admin.partials.stat-card', ['label' => 'Topics', 'value' => $stats['topics'] ?? 0])
            @include('admin.partials.stat-card', ['label' => 'Question Bank', 'value' => $stats['questions'] ?? 0])
            @include('admin.partials.stat-card', ['label' => 'My Exams', 'value' => $stats['exams'] ?? 0, 'hint' => ($stats['exams_published'] ?? 0).' published'])
            @include('admin.partials.stat-card', ['label' => 'My Homework', 'value' => $stats['homework'] ?? 0, 'hint' => ($stats['homework_published'] ?? 0).' published'])
            <div class="admin-card p-5 flex flex-col justify-center gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick Actions</p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('teacher.exams.create') }}" class="rounded-lg bg-brand-green px-3 py-2 text-xs font-semibold text-white hover:bg-brand-green-dark">+ New Exam</a>
                    <a href="{{ route('teacher.homework.create') }}" class="rounded-lg border border-brand-green px-3 py-2 text-xs font-semibold text-brand-green hover:bg-brand-green-50">+ New Homework</a>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Index — {{ $subject->name }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $chapters->count() }} chapter(s) — expand chapter, click topic for full content</p>
                </div>
            </div>

            @include('partials.book-index-chapters', [
                'chapters' => $chapters,
                'subject' => $subject,
                'topicRoute' => 'teacher.subjects.topics.show',
                'chapterRoute' => 'teacher.subjects.chapters.show',
            ])
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="admin-card overflow-hidden">
                <div class="admin-card-top"></div>
                <div class="admin-card-header flex items-center justify-between">
                    <h3 class="font-bold text-slate-900">My Exams — {{ $subject->name }}</h3>
                    <a href="{{ route('teacher.exams.create') }}" class="text-xs font-semibold text-brand-green hover:underline">+ New</a>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Chapter</th>
                                <th>Q</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exams as $exam)
                                <tr>
                                    <td>
                                        <a href="{{ route('teacher.exams.show', $exam) }}" class="font-semibold text-brand-green hover:underline">{{ $exam->title }}</a>
                                    </td>
                                    <td class="text-slate-600">{{ $exam->chapter?->name ?? '—' }}</td>
                                    <td>{{ $exam->questions_count }}</td>
                                    <td>
                                        <span @class([
                                            'admin-badge text-xs',
                                            $exam->isPublished() ? 'admin-badge-green' : 'admin-badge-slate',
                                        ])>{{ ucfirst($exam->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-slate-500 py-8">No exams for this subject yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="admin-card overflow-hidden">
                <div class="admin-card-top"></div>
                <div class="admin-card-header flex items-center justify-between">
                    <h3 class="font-bold text-slate-900">My Homework — {{ $subject->name }}</h3>
                    <a href="{{ route('teacher.homework.create') }}" class="text-xs font-semibold text-brand-green hover:underline">+ New</a>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Chapter</th>
                                <th>Q</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($homeworks as $homework)
                                <tr>
                                    <td>
                                        <a href="{{ route('teacher.homework.show', $homework) }}" class="font-semibold text-brand-green hover:underline">{{ $homework->title }}</a>
                                    </td>
                                    <td class="text-slate-600">{{ $homework->chapter?->name ?? '—' }}</td>
                                    <td>{{ $homework->questions_count }}</td>
                                    <td>
                                        <span @class([
                                            'admin-badge text-xs',
                                            $homework->isPublished() ? 'admin-badge-green' : 'admin-badge-slate',
                                        ])>{{ ucfirst($homework->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-slate-500 py-8">No homework for this subject yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-teacher-layout>
