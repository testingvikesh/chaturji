<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Student</span>
            <h2 class="admin-page-title">Self Practice</h2>
            <p class="admin-page-subtitle">Practice questions for {{ $standardName }}{{ ! empty($medium) ? ' / '.ucfirst($medium).' medium' : '' }}</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @if ($subjects->isEmpty())
            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="p-10 text-center">
                    <h3 class="text-lg font-bold text-slate-900">No subjects available</h3>
                    <p class="text-sm text-slate-500 mt-2">Subjects from the materials table will appear here for your standard and medium.</p>
                </div>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($subjects as $subject)
                    <a href="{{ route('student.self-practice.subject', $subject) }}" class="admin-card block hover:shadow-lg hover:-translate-y-0.5 transition group">
                        <div class="admin-card-top"></div>
                        <div class="p-5 sm:p-6">
                            <div class="flex items-start gap-4">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-green-50 text-brand-green font-bold text-lg">
                                    {{ strtoupper(substr($subject->name, 0, 1)) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-slate-900 group-hover:text-brand-green transition">{{ $subject->name }}</h3>
                                    <p class="text-sm text-slate-500 mt-1">{{ $subject->chapters_count }} chapter{{ $subject->chapters_count !== 1 ? 's' : '' }}</p>
                                </div>
                                <svg class="h-5 w-5 text-slate-300 group-hover:text-brand-green shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-student-layout>
