<x-principal-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Exam Papers</h1>
            <p class="text-sm text-slate-500 mt-1">Teacher papers for your standards. Edit or delete to change the details.</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
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
                            <option value="{{ $standard->slug }}" @selected($selected === $standard->slug)>
                                {{ $standard->name }} · {{ $standard->mediumLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <p class="text-sm text-slate-500 pb-2">{{ $examCount }} {{ str('paper')->plural($examCount) }}</p>
            </form>

            @forelse ($groups as $group)
                <div class="admin-card overflow-hidden mb-6">
                    <div class="admin-card-top"></div>
                    <div class="admin-card-header flex items-center justify-between gap-3">
                        <h3 class="font-bold text-slate-900">{{ $group['standard']->name }} · {{ $group['standard']->mediumLabel() }}</h3>
                        <span class="text-xs font-semibold text-slate-500">{{ $group['exams']->count() }} papers</span>
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($group['exams'] as $exam)
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
                                        <td class="whitespace-nowrap">
                                            <a href="{{ route('principal.exams.edit', $exam) }}" class="text-xs font-semibold text-brand-green hover:underline">Edit</a>
                                            <form method="POST" action="{{ route('principal.exams.destroy', $exam) }}" class="inline" onsubmit="return confirm('Delete this exam paper?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ml-2 text-xs font-semibold text-red-500 hover:underline">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-slate-500 py-8">No teacher exam papers for this standard.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="admin-card p-8 text-center text-sm text-slate-500">No teacher exam papers for your standards yet.</div>
            @endforelse
        @endif
    </div>
</x-principal-layout>
