<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="admin-section-label">Curriculum</span>
                <h2 class="admin-page-title">{{ $content->title }}</h2>
                <p class="admin-page-subtitle">
                    {{ $content->chapter->subject->standard->name }} /
                    {{ $content->chapter->subject->name }} /
                    {{ $content->chapter->name }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-admin.action-edit :href="route('admin.upload-data.edit', $content)" label="Edit upload" :compact="false" />
                <x-admin.action-delete :action="route('admin.upload-data.destroy', $content)" label="Delete upload" confirm="Delete this uploaded data?" :compact="false" />
            </div>
        </div>
    </x-slot>

    @include('admin.partials.alert')

    <div class="mb-4 flex flex-wrap gap-4 text-sm">
        <a href="{{ route('admin.upload-data.index') }}" class="text-brand-green hover:underline">&larr; Back to Upload List</a>
        <a href="{{ route('admin.subjects.chapters.index', $content->chapter->subject) }}" class="text-slate-600 hover:underline">View Chapters</a>
    </div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @include('admin.partials.stat-card', ['label' => 'Total Questions', 'value' => $content->total_questions])
        @include('admin.partials.stat-card', ['label' => 'Parsed Questions', 'value' => $content->questions->count()])
        @include('admin.partials.stat-card', ['label' => 'Sections', 'value' => $content->sections->count()])
        @include('admin.partials.stat-card', ['label' => 'Language', 'value' => $content->languageLabel()])
        @include('admin.partials.stat-card', ['label' => 'Source File', 'value' => Str::limit($content->source_filename, 20)])
    </div>

    <div class="admin-card mb-6 p-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-slate-900">Original PDF (Practice)</p>
            <p class="text-xs text-slate-500 mt-0.5">
                @if ($content->hasOriginalPdf())
                    {{ $content->original_pdf_filename }}
                @else
                    Not uploaded — students will not see the Practice PDF button.
                @endif
            </p>
        </div>
        @if ($content->hasOriginalPdf())
            <a href="{{ route('chapter-content.original-pdf', $content) }}" target="_blank"
               class="inline-flex h-9 items-center rounded-lg bg-amber-500 px-4 text-xs font-semibold text-white hover:bg-amber-600">
                Open Practice PDF
            </a>
        @else
            <a href="{{ route('admin.upload-data.edit', $content) }}"
               class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Upload PDF
            </a>
        @endif
    </div>

    @if ($content->chapter->topics->isNotEmpty())
        <div class="admin-card mb-6">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <h3 class="font-bold text-slate-900">Chapter Topics</h3>
                <span class="admin-badge-green">{{ $content->chapter->topics->count() }}</span>
            </div>
            <div class="p-5 flex flex-wrap gap-2">
                @foreach ($content->chapter->topics as $topic)
                    <span class="inline-flex items-center rounded-full bg-brand-green-50 border border-brand-green-100 px-3 py-1 text-sm font-medium text-brand-green">
                        {{ $topic->name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if ($content->overview)
        <div class="admin-card mb-6">
            <div class="admin-card-top"></div>
            <div class="admin-card-header"><h3 class="font-bold text-slate-900">Overview</h3></div>
            <div class="p-5 text-sm text-slate-700 whitespace-pre-line leading-relaxed">{{ $content->overview }}</div>
        </div>
    @endif

    @if ($content->sections->isNotEmpty())
        <div class="admin-card mb-6">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <h3 class="font-bold text-slate-900">Content Sections</h3>
                <span class="admin-badge-green">{{ $content->sections->count() }}</span>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($content->sections as $section)
                    <div class="p-5">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <h4 class="font-semibold text-slate-900">{{ $section->title ?: $section->typeLabel() }}</h4>
                            <div class="flex items-center gap-1">
                                <span class="text-xs text-slate-400 mr-1">#{{ $section->sort_order + 1 }}</span>
                                <x-admin.action-edit :href="route('admin.upload-data.sections.edit', [$content, $section])" label="Edit section" />
                                <x-admin.action-delete :action="route('admin.upload-data.sections.destroy', [$content, $section])" label="Delete section" confirm="Delete this section?" />
                            </div>
                        </div>
                        <div class="text-sm text-slate-600 whitespace-pre-line leading-relaxed max-h-48 overflow-y-auto">{{ Str::limit($section->content, 800) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="admin-card">
        <div class="admin-card-top"></div>
        <div class="admin-card-header">
            <h3 class="font-bold text-slate-900">Questions</h3>
            <span class="admin-badge-green">{{ $content->questions->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="admin-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Question</th>
                        <th>Answer</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($content->questions as $question)
                        <tr>
                            <td class="text-slate-400">{{ $question->sort_order + 1 }}</td>
                            <td><span class="admin-badge-gold">{{ $question->typeLabel() }}</span></td>
                            <td class="max-w-md">
                                <p class="font-medium text-slate-900">{{ Str::limit($question->question_text, 120) }}</p>
                                @php
                                    $opts = $question->options;
                                    $meta = $question->metadata ?? [];
                                    if (is_string($opts)) {
                                        $opts = json_decode($opts, true);
                                    }
                                    $optEntries = [];
                                    if (is_array($opts) && ! isset($opts['column_a']) && ! isset($opts['column_b'])) {
                                        $letters = range('A', 'D');
                                        $i = 0;
                                        foreach ($opts as $opt) {
                                            if (is_array($opt)) {
                                                continue;
                                            }
                                            $optEntries[] = ($letters[$i] ?? (string) ($i + 1)).'. '.$opt;
                                            $i++;
                                        }
                                    }
                                @endphp
                                @if (($meta['source'] ?? null) === 'practice_examination')
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        @if (! empty($meta['paper_type']))
                                            <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                                {{ ucfirst(str_replace('_', ' ', $meta['paper_type'])) }}
                                            </span>
                                        @endif
                                        @if (! empty($meta['section']))
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                {{ ucfirst(str_replace('_', ' ', $meta['section'])) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                @if ($optEntries !== [])
                                    <ul class="mt-1.5 space-y-0.5 text-xs text-slate-600">
                                        @foreach ($optEntries as $optLabel)
                                            <li>{{ $optLabel }}</li>
                                        @endforeach
                                    </ul>
                                @elseif (in_array($question->question_type, ['fill_blank', 'one_word'], true))
                                    <p class="mt-1 text-xs text-amber-600">No options stored — re-import chapter or run: php artisan homework:fix-question-options --force</p>
                                @endif
                            </td>
                            <td class="max-w-xs text-slate-600">{{ Str::limit($question->answer, 100) ?: '—' }}</td>
                            <td class="text-right">
                                <div class="admin-action-group justify-end">
                                    <x-admin.action-edit :href="route('admin.upload-data.questions.edit', [$content, $question])" label="Edit question" />
                                    <x-admin.action-delete :action="route('admin.upload-data.questions.destroy', [$content, $question])" label="Delete question" confirm="Delete this question?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-400">No questions imported from this file.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card mt-6">
        <div class="admin-card-header">
            <h3 class="font-bold text-slate-900">Upload Info</h3>
        </div>
        <div class="p-5 grid sm:grid-cols-2 gap-4 text-sm">
            <div><span class="text-slate-500">Language:</span> <span class="font-medium">{{ $content->languageLabel() }}</span></div>
            <div><span class="text-slate-500">Import method:</span> <span class="font-medium">{{ $content->extraction_method === 'material_json' ? 'Material JSON' : ucfirst(str_replace('_', ' ', $content->extraction_method ?? '—')) }}</span></div>
            <div><span class="text-slate-500">Uploaded by:</span> <span class="font-medium">{{ $content->uploader?->name ?? '—' }}</span></div>
            <div><span class="text-slate-500">Uploaded at:</span> <span class="font-medium">{{ $content->created_at->format('d M Y, h:i A') }}</span></div>
        </div>
    </div>
</x-app-layout>
