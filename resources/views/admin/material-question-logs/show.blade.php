<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Question Edit Detail" subtitle="{{ $log->topic_title }}">
            <x-slot name="actions">
                <a href="{{ route('admin.material-question-logs.index') }}" class="admin-btn-secondary">Back to Log</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-4 max-w-4xl">
        @include('admin.partials.alert')

        <div class="admin-card p-5 space-y-3">
            <div class="admin-card-top"></div>
            <div class="grid sm:grid-cols-2 gap-3 text-sm">
                <p><span class="text-slate-500">Teacher:</span> <span class="font-semibold">{{ $log->teacher?->name ?? '—' }}</span></p>
                <p><span class="text-slate-500">When:</span> <span class="font-semibold">{{ $log->created_at?->format('d M Y, h:i A') }}</span></p>
                <p><span class="text-slate-500">Medium:</span> <span class="font-semibold">{{ $log->medium ?: '—' }}</span></p>
                <p><span class="text-slate-500">Type:</span> <span class="font-semibold">{{ \App\Models\ChapterQuestion::labelForType((string) $log->question_type) }}</span></p>
                <p class="sm:col-span-2"><span class="text-slate-500">Material:</span> <span class="font-semibold">{{ collect([$log->subject_name, $log->chapter_name, $log->topic_title])->filter()->implode(' · ') }}</span></p>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-4">
            <div class="admin-card p-5 space-y-3">
                <h3 class="font-bold text-slate-900">Before</h3>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-400 mb-1">Question</p>
                    <p class="text-sm text-slate-800 whitespace-pre-line">{{ $log->old_question_text ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-400 mb-1">Answer</p>
                    <p class="text-sm text-slate-800 whitespace-pre-line">{{ $log->old_answer ?: '—' }}</p>
                </div>
                @if (is_array($log->old_options) && $log->old_options !== [])
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-400 mb-1">Options</p>
                        <ul class="text-sm text-slate-700 list-disc pl-5 space-y-1">
                            @foreach ($log->old_options as $opt)
                                <li>{{ is_array($opt) ? json_encode($opt, JSON_UNESCAPED_UNICODE) : $opt }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="admin-card p-5 space-y-3 border-brand-green-100">
                <h3 class="font-bold text-brand-green">After</h3>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-400 mb-1">Question</p>
                    <p class="text-sm text-slate-800 whitespace-pre-line">{{ $log->new_question_text ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-400 mb-1">Answer</p>
                    <p class="text-sm text-slate-800 whitespace-pre-line">{{ $log->new_answer ?: '—' }}</p>
                </div>
                @if (is_array($log->new_options) && $log->new_options !== [])
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-400 mb-1">Options</p>
                        <ul class="text-sm text-slate-700 list-disc pl-5 space-y-1">
                            @foreach ($log->new_options as $opt)
                                <li>{{ is_array($opt) ? json_encode($opt, JSON_UNESCAPED_UNICODE) : $opt }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
