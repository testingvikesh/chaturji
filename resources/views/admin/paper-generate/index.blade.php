<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Paper Generate" subtitle="Saved papers from important questions">
            <x-slot name="actions">
                <a href="{{ route('admin.paper-generate.create') }}" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">Generate paper</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card overflow-hidden">
            <div class="admin-card-top"></div>
            <div class="admin-table-wrap">
                <table class="admin-table min-w-[920px]">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Medium</th>
                            <th>Standard</th>
                            <th>Subject</th>
                            <th>Questions</th>
                            <th>Marks</th>
                            <th>Generated</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($papers as $paper)
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $paper->title }}</td>
                                <td>{{ $paper->mediumLabel() }}</td>
                                <td>{{ $paper->standard->name ?? '—' }}</td>
                                <td>{{ $paper->subject->name ?? '—' }}</td>
                                <td>{{ $paper->total_questions }}</td>
                                <td class="font-semibold text-slate-900">{{ $paper->total_marks }}</td>
                                <td class="whitespace-nowrap text-sm text-slate-600">{{ $paper->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                                <td class="whitespace-nowrap text-right text-xs font-semibold">
                                    <a href="{{ route('admin.paper-generate.show', $paper) }}" class="text-brand-green hover:underline">View</a>
                                    <a href="{{ route('admin.paper-generate.print', ['generatedPaper' => $paper, 'answers' => 0]) }}" target="_blank" class="ml-3 text-slate-700 hover:underline">Without answers</a>
                                    <a href="{{ route('admin.paper-generate.print', ['generatedPaper' => $paper, 'answers' => 1]) }}" target="_blank" class="ml-3 text-slate-700 hover:underline">With answers</a>
                                    <form method="POST" action="{{ route('admin.paper-generate.destroy', $paper) }}" class="inline" onsubmit="return confirm('Delete this paper?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-red-500 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500">No papers yet. Generate one from a paper style.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($papers->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">{{ $papers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
