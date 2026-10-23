<x-principal-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Exam Papers</h1>
            <p class="text-sm text-slate-500 mt-1">Teacher papers for your standards, in English and Gujarati.</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        @if ($standards->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                No standard allotted yet. Ask admin to allot standards.
            </div>
        @else
            <form method="GET" action="{{ route('principal.exams.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="admin-label" for="standard">Standard</label>
                    <select id="standard" name="standard" class="admin-select min-w-[12rem]" onchange="this.form.submit()">
                        <option value="">All standards</option>
                        @foreach ($standards as $standard)
                            <option value="{{ \App\Models\Material::standardNumber($standard) }}" @selected($selected === \App\Models\Material::standardNumber($standard))>
                                {{ $standard->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-label" for="medium">Medium</label>
                    <select id="medium" name="medium" class="admin-select min-w-[12rem]" onchange="this.form.submit()">
                        <option value="">English and Gujarati</option>
                        @foreach ($mediums as $key => $label)
                            <option value="{{ $key }}" @selected($medium === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-sm text-slate-500 pb-2">{{ $examCount }} {{ str('paper')->plural($examCount) }}</p>
            </form>

            @forelse ($groups as $group)
                <div class="admin-card overflow-hidden mb-6">
                    <div class="admin-card-top"></div>
                    <div class="admin-card-header flex items-center justify-between gap-3">
                        <h3 class="font-bold text-slate-900">{{ $group['name'] }}</h3>
                        <span class="text-xs font-semibold text-slate-500">{{ $group['count'] }} papers</span>
                    </div>
                    @foreach ($group['blocks'] as $block)
                        <div class="border-t border-slate-100">
                            <div class="flex items-center justify-between gap-3 px-5 py-3">
                                <p class="text-sm font-bold text-slate-800">{{ $block['label'] }}</p>
                                <span class="text-xs font-semibold text-slate-500">{{ $block['exams']->count() }} papers</span>
                            </div>
                            <div class="admin-table-wrap">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                            <th>Questions</th>
                                            <th>Marks</th>
                                            <th>Starts</th>
                                            <th>Status</th>
                                            <th>Regenerated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($block['exams'] as $exam)
                                            <tr>
                                                <td class="font-semibold text-slate-900">{{ $exam->title }}</td>
                                                <td>{{ $exam->subject?->name ?? '—' }}</td>
                                                <td>{{ $exam->teacher?->name ?? '—' }}</td>
                                                <td>{{ $exam->questions_count }}</td>
                                                <td>{{ $exam->total_marks }}</td>
                                                <td class="whitespace-nowrap text-slate-600">{{ $exam->localStart()?->format('d M Y, h:i A') ?? '—' }}</td>
                                                <td>
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $exam->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                        {{ ucfirst($exam->status) }}
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap text-xs text-slate-600">{{ $exam->regeneratedAt()?->format('d M Y, h:i A') ?? '—' }}</td>
                                                <td class="whitespace-nowrap">
                                                    <a href="{{ route('principal.exams.preview', $exam) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-slate-700 hover:underline">Preview</a>
                                                    <a href="{{ route('principal.exams.print', $exam) }}" target="_blank" rel="noopener" class="ml-2 text-xs font-semibold text-slate-700 hover:underline">Print</a>
                                                    <a href="{{ route('principal.exams.edit', $exam) }}" class="ml-2 text-xs font-semibold text-brand-green hover:underline">Edit</a>
                                                    <form method="POST" action="{{ route('principal.exams.regenerate', $exam) }}" class="inline" onsubmit="return confirm('Regenerate only the questions and answers? The exam name, date, and marks stay the same.')">
                                                        @csrf
                                                        <button type="submit" class="ml-2 text-xs font-semibold text-amber-700 hover:underline">Regenerate</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('principal.exams.destroy', $exam) }}" class="inline" onsubmit="return confirm('Delete this exam paper?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="ml-2 text-xs font-semibold text-red-500 hover:underline">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-slate-500 py-6">No {{ $block['label'] }} papers for this standard.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="admin-card p-8 text-center text-sm text-slate-500">No teacher exam papers for your standards yet.</div>
            @endforelse
        @endif
    </div>
</x-principal-layout>
