<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Mentor</span>
            <h2 class="admin-page-title">Mentor Teachers</h2>
            <p class="admin-page-subtitle">Assign students to a teacher — that teacher becomes their mentor</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if (! $hasAllotments)
            <div class="admin-card">
                <div class="p-8 text-center text-sm text-slate-500">
                    No standard allotted yet. Ask admin to allot standards first.
                </div>
            </div>
        @else
            <div class="grid sm:grid-cols-3 gap-4">
                @include('admin.partials.stat-card', ['label' => 'Teachers', 'value' => $summary['teachers'], 'hint' => 'On your standards'])
                @include('admin.partials.stat-card', ['label' => 'Mentors', 'value' => $summary['mentors'], 'hint' => 'Teachers with students'])
                @include('admin.partials.stat-card', ['label' => 'Mentored students', 'value' => $summary['students']])
            </div>

            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-body">
                    <form method="GET" class="admin-filter-grid">
                        <div class="lg:col-span-4">
                            <label class="admin-label">Search teacher</label>
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, mobile or email..." class="admin-input">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="admin-label">Medium</label>
                            <select name="medium" class="admin-select">
                                <option value="">All mediums</option>
                                <option value="english" @selected(($filters['medium'] ?? '') === 'english')>English</option>
                                <option value="gujarati" @selected(($filters['medium'] ?? '') === 'gujarati')>Gujarati</option>
                            </select>
                        </div>
                        <div class="lg:col-span-3">
                            <label class="admin-label">Standard</label>
                            <select name="standard_id" class="admin-select">
                                <option value="">All allotted standards</option>
                                @foreach ($standards as $standard)
                                    <option value="{{ $standard->id }}" @selected((string) ($filters['standard_id'] ?? '') === (string) $standard->id)>{{ $standard->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-3 flex gap-2 items-end">
                            <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                            @if (! empty(array_filter($filters ?? [])))
                                <a href="{{ route('principal.mentors.index') }}" class="admin-btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <div>
                        <h3 class="font-bold text-slate-900">Teachers — select students to mentor</h3>
                        <p class="text-xs text-slate-500 mt-0.5">1 teacher can mentor many students</p>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Contact</th>
                                <th>Mentored students</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($teachers as $teacher)
                                <tr>
                                    <td>
                                        <div class="admin-user-cell">
                                            <span class="admin-avatar">{{ strtoupper(substr($teacher->name, 0, 2)) }}</span>
                                            <p class="font-semibold text-slate-900">{{ $teacher->name }}</p>
                                        </div>
                                    </td>
                                    <td class="text-sm">
                                        <p>{{ $teacher->mobile ?: '—' }}</p>
                                        <p class="text-xs text-slate-400">{{ $teacher->email ?: '' }}</p>
                                    </td>
                                    <td>
                                        @if (($teacher->mentee_count ?? 0) > 0)
                                            <span class="admin-badge-green">{{ $teacher->mentee_count }} student(s)</span>
                                        @else
                                            <span class="text-sm text-slate-400">Not a mentor yet</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('principal.mentors.show', $teacher) }}" class="admin-btn-primary text-sm">Select students</a>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', [
                                    'colspan' => 4,
                                    'message' => 'No teachers on your standards',
                                    'hint' => 'Teachers appear after they allot subjects for your standards.',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($teachers->hasPages())
                    <div class="p-4">{{ $teachers->links() }}</div>
                @endif
            </div>
        @endif
    </div>
</x-principal-layout>
