<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Principal</span>
            <h2 class="admin-page-title">Allotted Students</h2>
            <p class="admin-page-subtitle">Add or edit students in your standards — select and send login mail</p>
        </div>
    </x-slot>

    <div class="admin-page" x-data="{ selected: [], all: false,
        toggleAll() {
            this.all = !this.all;
            this.selected = this.all ? @js($students->filter(fn ($s) => filled($s->email) && filter_var($s->email, FILTER_VALIDATE_EMAIL))->pluck('id')->map(fn ($id) => (string) $id)->values()) : [];
        }
    }">
        @include('principal.partials.reports-nav')

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 report-print-hide">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 report-print-hide">{{ session('error') }}</div>
        @endif

        @if (! $hasAllotments)
            <div class="admin-card">
                <div class="p-8 text-center text-sm text-slate-500">
                    No standard allotted yet. Ask admin to allot standards to your principal account.
                </div>
            </div>
        @else
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
                @include('admin.partials.stat-card', ['label' => 'Students', 'value' => $summary['students']])
                @include('admin.partials.stat-card', ['label' => 'Approved', 'value' => $summary['approved']])
                @include('admin.partials.stat-card', ['label' => 'Pending', 'value' => $summary['pending']])
                @include('admin.partials.stat-card', ['label' => 'Your standards', 'value' => $summary['standards']])
            </div>

            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-body">
                    <form method="GET" class="admin-filter-grid">
                        <div class="lg:col-span-4">
                            <label class="admin-label">Search student</label>
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
                            <select name="standard" class="admin-select">
                                <option value="">All allotted standards</option>
                                @foreach ($standards as $standard)
                                    <option value="{{ $standard->slug }}" @selected(($filters['standard'] ?? '') === $standard->slug)>{{ $standard->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="admin-label">Status</label>
                            <select name="status" class="admin-select">
                                <option value="">All status</option>
                                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                            </select>
                        </div>
                        <div class="lg:col-span-1 flex gap-2 items-end">
                            <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                        </div>
                        @if (! empty(array_filter($filters ?? [])))
                            <div class="lg:col-span-12">
                                <a href="{{ route('principal.reports.allotted-students') }}" class="admin-btn-ghost">Clear filters</a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('principal.reports.send-student-credentials') }}" class="admin-card mb-4"
                  onsubmit="return confirm('Send login mail to selected students? Password will be reset to the value you enter if Reset is checked.');">
                @csrf
                <div class="admin-card-body flex flex-wrap items-end gap-3">
                    <div class="min-w-[180px]">
                        <label class="admin-label">Password for mail *</label>
                        <input type="text" name="password" value="Student@123" required minlength="8" class="admin-input">
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 pb-2">
                        <input type="hidden" name="reset_password" value="0">
                        <input type="checkbox" name="reset_password" value="1" class="rounded border-slate-300 text-brand-green" checked>
                        Reset password to this value
                    </label>
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="student_ids[]" :value="id">
                    </template>
                    <button type="submit" class="admin-btn-primary" :disabled="selected.length === 0"
                            :class="selected.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                        Send login mail (<span x-text="selected.length"></span>)
                    </button>
                    <p class="text-xs text-slate-500 w-full">Select students with email. Mail includes app login link, username (mobile) and password.</p>
                </div>
            </form>

            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <div>
                        <h3 class="font-bold text-slate-900">Students on your standards</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $students->total() }} student(s)</p>
                    </div>
                    <a href="{{ route('principal.students.create') }}" class="admin-btn-primary">Add Student</a>
                </div>
                <div class="admin-table-wrap admin-table-wrap--sticky">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="w-10">
                                    <input type="checkbox" class="rounded border-slate-300 text-brand-green" @click="toggleAll()" :checked="all">
                                </th>
                                <th>Student</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Standard</th>
                                <th>Medium</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                @php
                                    $hasMail = filled($student->email) && filter_var($student->email, FILTER_VALIDATE_EMAIL);
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                               class="rounded border-slate-300 text-brand-green"
                                               value="{{ $student->id }}"
                                               x-model="selected"
                                               :disabled="!{{ $hasMail ? 'true' : 'false' }}"
                                               title="{{ $hasMail ? 'Select to send mail' : 'No email — cannot send mail' }}">
                                    </td>
                                    <td>
                                        <div class="admin-user-cell">
                                            <span class="admin-avatar">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ $student->name }}</p>
                                                <p class="text-xs text-slate-400">Joined {{ $student->created_at?->format('d M Y') }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $student->mobile ?: '—' }}</td>
                                    <td>{{ $student->email ?: '—' }}</td>
                                    <td>{{ ($standardNames[$student->standard] ?? null) ?: $student->standardLabel() }}</td>
                                    <td class="capitalize">{{ $student->medium ?: '—' }}</td>
                                    <td>
                                        @if ($student->is_approved)
                                            <span class="admin-badge-green">Approved</span>
                                        @else
                                            <span class="admin-badge-gold">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('principal.students.edit', $student) }}" class="admin-btn-ghost text-sm">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', [
                                    'colspan' => 8,
                                    'message' => 'No students found',
                                    'hint' => 'Use Add Student, or students appear when their standard matches your allotment.',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($students->hasPages())
                    <div class="p-4">{{ $students->links() }}</div>
                @endif
            </div>
        @endif
    </div>
</x-principal-layout>
