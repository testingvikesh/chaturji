<x-teacher-layout>

    <x-slot name="header">

        <div>

            <span class="admin-section-label">Chaturji</span>

            <h2 class="admin-page-title">Welcome, {{ $teacher->name }}!</h2>

            <p class="admin-page-subtitle">Your exams, homework, and recent activity</p>

        </div>

    </x-slot>



    <div class="admin-page space-y-6">

        <div class="rounded-2xl border border-brand-green-100 bg-gradient-to-r from-brand-green-50 via-white to-amber-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-slate-900">📚 Daily Syllabus Update</p>
                <p class="text-xs text-slate-500 mt-0.5">Auto-filled from login · select chapter/topic · mark status · submit</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('teacher.daily-syllabus.create') }}" class="rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white hover:bg-brand-green-dark">Update Today</a>
                <a href="{{ route('teacher.daily-syllabus.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Previous Updates</a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-slate-900">Your standard & subjects</p>
                    <p class="text-xs text-slate-500 mt-0.5">Set this in Settings so other teachers cannot take the same subject</p>
                </div>
                <a href="{{ route('teacher.settings.edit') }}" class="rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white hover:bg-brand-green-dark">Settings</a>
            </div>
            @if ($assignedGroups->isEmpty())
                <p class="mt-3 text-sm text-slate-500">No standard or subject assigned yet.</p>
            @else
                <div class="mt-3 space-y-3">
                    @foreach ($assignedGroups as $rows)
                        @php
                            $first = $rows->first();
                            $mediumKey = \App\Models\Material::normalizeMedium($first?->medium) ?: $first?->medium;
                            $mediumLabel = \App\Models\Standard::MEDIUMS[$mediumKey] ?? ucfirst((string) $first?->medium);
                        @endphp
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-brand-green">{{ $mediumLabel }} · {{ $first?->standard?->name }}</p>
                            <p class="text-sm text-slate-700 mt-1">{{ $rows->pluck('subject.name')->filter()->implode(', ') }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">

            @include('admin.partials.stat-card', ['label' => 'Total Exams', 'value' => $examCount])

            @include('admin.partials.stat-card', ['label' => 'Published Exams', 'value' => $publishedExams])

            @include('admin.partials.stat-card', ['label' => 'Total Homework', 'value' => $homeworkCount])

            @include('admin.partials.stat-card', ['label' => 'Published Homework', 'value' => $publishedHomework])

        </div>

        <div class="grid lg:grid-cols-2 gap-6">

            <div class="admin-card">

                <div class="admin-card-top"></div>

                <div class="admin-card-header flex items-center justify-between">

                    <h3 class="font-bold text-slate-900">Recent Exams</h3>

                    <a href="{{ route('teacher.exams.create') }}" class="text-xs font-semibold text-brand-green hover:underline">+ New Exam</a>

                </div>

                <div class="divide-y divide-slate-100">

                    @forelse ($recentExams as $exam)

                        <a href="{{ route('teacher.exams.show', $exam) }}" class="block p-4 hover:bg-slate-50 transition">

                            <p class="font-semibold text-slate-900">{{ $exam->title }}</p>

                            <p class="text-xs text-slate-500 mt-0.5">{{ ucfirst($exam->status) }} &middot; {{ $exam->created_at->diffForHumans() }}</p>

                        </a>

                    @empty

                        <p class="p-4 text-sm text-slate-500">No exams yet.</p>

                    @endforelse

                </div>

            </div>



            <div class="admin-card">

                <div class="admin-card-top"></div>

                <div class="admin-card-header flex items-center justify-between">

                    <h3 class="font-bold text-slate-900">Recent Homework</h3>

                    <a href="{{ route('teacher.homework.create') }}" class="text-xs font-semibold text-brand-green hover:underline">+ New Homework</a>

                </div>

                <div class="divide-y divide-slate-100">

                    @forelse ($recentHomework as $hw)

                        <a href="{{ route('teacher.homework.show', $hw) }}" class="block p-4 hover:bg-slate-50 transition">

                            <p class="font-semibold text-slate-900">{{ $hw->title }}</p>

                            <p class="text-xs text-slate-500 mt-0.5">{{ ucfirst($hw->status) }} &middot; {{ $hw->created_at->diffForHumans() }}</p>

                        </a>

                    @empty

                        <p class="p-4 text-sm text-slate-500">No homework yet.</p>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

</x-teacher-layout>

