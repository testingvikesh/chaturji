<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Subjects" subtitle="Standard-wise subject list. Update names, order, and status in one save.">
            <x-slot name="actions">
                <a href="{{ route('admin.standards.index') }}" class="admin-btn-secondary">Standards</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-6">
        @include('admin.partials.alert')

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Choose standard</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $standard->name }} · {{ $standard->mediumLabel() }}</p>
                </div>
            </div>
            <div class="admin-card-body">
                <label class="admin-label" for="standard-switch">Standard</label>
                <select id="standard-switch" class="admin-select max-w-md" onchange="if (this.value) window.location.href = this.value">
                    @foreach ($standards as $item)
                        <option value="{{ route('admin.standards.subjects.index', $item) }}" @selected($item->id === $standard->id)>
                            {{ $item->name }} · {{ $item->mediumLabel() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">{{ $standard->name }} subjects</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $subjects->count() }} {{ \Illuminate\Support\Str::plural('subject', $subjects->count()) }} · turning a subject off hides it from students and teachers</p>
                </div>
            </div>

            @if ($subjects->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-slate-400">No subjects for this standard yet. Add one below.</div>
            @else
                <form id="subjects-bulk" method="POST" action="{{ route('admin.standards.subjects.bulk', $standard) }}">
                    @csrf
                    @method('PUT')
                </form>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Sort</th>
                                <th>Chapters</th>
                                <th>Active</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subjects as $subject)
                                <tr>
                                    <td>
                                        <input
                                            type="text"
                                            name="subjects[{{ $subject->id }}][name]"
                                            value="{{ old('subjects.'.$subject->id.'.name', $subject->name) }}"
                                            form="subjects-bulk"
                                            required
                                            class="admin-input"
                                        >
                                    </td>
                                    <td class="w-28">
                                        <input
                                            type="number"
                                            name="subjects[{{ $subject->id }}][sort_order]"
                                            value="{{ old('subjects.'.$subject->id.'.sort_order', $subject->sort_order) }}"
                                            form="subjects-bulk"
                                            min="0"
                                            class="admin-input"
                                        >
                                    </td>
                                    <td>{{ $subject->chapters_count }}</td>
                                    <td>
                                        <input type="hidden" name="subjects[{{ $subject->id }}][is_active]" value="0" form="subjects-bulk">
                                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                            <input
                                                type="checkbox"
                                                name="subjects[{{ $subject->id }}][is_active]"
                                                value="1"
                                                form="subjects-bulk"
                                                class="rounded border-slate-300 text-brand-green focus:ring-brand-green"
                                                @checked(old('subjects.'.$subject->id.'.is_active', $subject->is_active))
                                            >
                                            Active
                                        </label>
                                    </td>
                                    <td class="text-right">
                                        <div class="admin-action-group justify-end">
                                            <a href="{{ route('admin.subjects.chapters.index', $subject) }}" class="admin-action-icon-btn admin-action-icon-btn--view" title="Chapters" aria-label="Chapters">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            </a>
                                            <x-admin.action-delete :action="route('admin.subjects.destroy', $subject)" label="Delete subject" confirm="Delete this subject?" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4 flex justify-end">
                    <button type="submit" form="subjects-bulk" class="admin-btn-primary">Update subjects</button>
                </div>
            @endif
        </div>

        <div class="admin-form-card max-w-xl">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('admin.standards.subjects.store', $standard) }}" class="admin-card-body">
                @csrf
                <h3 class="font-bold text-slate-900">Add subject</h3>
                <p class="text-xs text-slate-500">Adds a subject to {{ $standard->name }} · {{ $standard->mediumLabel() }}.</p>
                @include('admin.partials.curriculum-fields')
                <div>
                    <button type="submit" class="admin-btn-primary">Add subject</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
