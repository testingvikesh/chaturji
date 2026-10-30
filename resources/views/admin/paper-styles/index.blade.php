<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Paper Styles" subtitle="Set objective and subjective question quantities for each standard and subject">
            <x-slot name="actions">
                <a href="{{ route('admin.paper-styles.create') }}" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">+ Add paper style</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card overflow-hidden">
            <div class="admin-card-top"></div>
            <div class="admin-table-wrap">
                <table class="admin-table min-w-[760px]">
                    <thead>
                        <tr>
                            <th>Standard</th>
                            <th>Subject</th>
                            <th>Objective</th>
                            <th>Subjective</th>
                            <th>Marks</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($styles as $style)
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $style->standard->name ?? '—' }}</td>
                                <td>{{ $style->subject->name ?? '—' }}</td>
                                <td class="text-sm text-slate-700">
                                    @forelse ($style->quantityRows(false) as $row)
                                        <span class="mr-2 inline-block">{{ $row['label'] }}: {{ $row['quantity'] }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="text-sm text-slate-700">
                                    @forelse ($style->quantityRows(true) as $row)
                                        <span class="mr-2 inline-block">{{ $row['label'] }}: {{ $row['quantity'] }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="font-semibold text-slate-900">{{ $style->totalMarks() }}</td>
                                <td class="whitespace-nowrap text-right">
                                    <a href="{{ route('admin.paper-styles.edit', $style) }}" class="text-xs font-semibold text-brand-green hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.paper-styles.destroy', $style) }}" class="inline" onsubmit="return confirm('Delete this paper style?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-xs font-semibold text-red-500 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500">No paper styles yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
