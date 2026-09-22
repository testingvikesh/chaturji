<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="School Periods" subtitle="Master list used in teacher timetables (Period 1, 2, …)">
            <x-slot name="actions">
                <a href="{{ route('admin.timetable.index') }}" class="admin-btn-secondary">← Teacher Timetable</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page max-w-4xl">
        @include('admin.partials.alert')

        <div class="admin-card mb-4">
            <div class="admin-card-top"></div>
            <div class="admin-card-header"><h3 class="font-bold text-slate-900">Add period</h3></div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.timetable.periods.store') }}" class="grid sm:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <label class="admin-label">Period no *</label>
                        <input type="number" name="period_no" min="1" max="20" value="{{ old('period_no') }}" required class="admin-input">
                    </div>
                    <div>
                        <label class="admin-label">Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Period 1" required class="admin-input">
                    </div>
                    <div>
                        <label class="admin-label">Start time</label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" class="admin-input">
                    </div>
                    <div>
                        <label class="admin-label">End time</label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" class="admin-input">
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-brand-green"> Active
                        </label>
                        <button type="submit" class="admin-btn-primary ml-auto">Add period</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-3">
            @forelse ($periods as $period)
                <div class="admin-card">
                    <div class="admin-card-body">
                        <form method="POST" action="{{ route('admin.timetable.periods.update', $period) }}" class="grid sm:grid-cols-6 gap-3 items-end">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="admin-label">No</label>
                                <input type="number" name="period_no" value="{{ $period->period_no }}" min="1" max="20" required class="admin-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="admin-label">Name</label>
                                <input type="text" name="name" value="{{ $period->name }}" required class="admin-input">
                            </div>
                            <div>
                                <label class="admin-label">Start</label>
                                <input type="time" name="start_time" value="{{ $period->start_time ? substr((string) $period->start_time, 0, 5) : '' }}" class="admin-input">
                            </div>
                            <div>
                                <label class="admin-label">End</label>
                                <input type="time" name="end_time" value="{{ $period->end_time ? substr((string) $period->end_time, 0, 5) : '' }}" class="admin-input">
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pb-1">
                                <input type="hidden" name="is_active" value="0">
                                <label class="inline-flex items-center gap-1 text-sm">
                                    <input type="checkbox" name="is_active" value="1" @checked($period->is_active) class="rounded border-slate-300 text-brand-green"> Active
                                </label>
                                <button type="submit" class="admin-btn-secondary text-sm">Save</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.timetable.periods.destroy', $period) }}" class="mt-2" onsubmit="return confirm('Delete this period?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:underline">Delete period</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No periods yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
