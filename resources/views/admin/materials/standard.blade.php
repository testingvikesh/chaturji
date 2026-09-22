<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="{{ $standard->name }}"
            subtitle="{{ $mediumLabel }} · select subject">
            <x-slot name="actions">
                <a href="{{ route('admin.materials.medium', $medium) }}" class="admin-btn-secondary">← Standards</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-4">
        <p class="text-sm text-slate-500">
            <a href="{{ route('admin.materials.index') }}" class="text-brand-green hover:underline font-medium">All Materials</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('admin.materials.medium', $medium) }}" class="text-brand-green hover:underline font-medium">{{ $mediumLabel }}</a>
            <span class="text-slate-300">/</span> {{ $standard->name }}
        </p>

        @php $variants = ['', 'student-subject-card--gold', 'student-subject-card--emerald']; @endphp

        <div class="student-subjects-grid">
            @foreach ($subjects as $subject)
                @php $variant = $variants[$loop->index % count($variants)]; @endphp
                <a href="{{ route('admin.materials.subject', [$medium, $subject]) }}"
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
                            {{ (int) $subject->chapters_count }} {{ \Illuminate\Support\Str::plural('chapter', (int) $subject->chapters_count) }}
                        </span>
                    </div>
                    <div class="student-subject-card-footer">
                        <span class="student-subject-card-cta">Open subject</span>
                        <span class="student-subject-card-arrow">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
