<x-principal-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Exam</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $exam->title }}</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-3xl">
        <div class="mb-4">
            <a href="{{ route('principal.exams.index', ['standard' => $exam->standard]) }}" class="text-sm font-medium text-brand-green hover:underline">&larr; Back to exam papers</a>
        </div>

        <div class="admin-card mb-4">
            <div class="admin-card-top"></div>
            <div class="p-5 grid sm:grid-cols-2 gap-3 text-sm">
                <p><span class="text-slate-500">Standard</span><br><span class="font-semibold text-slate-900">{{ $standardName }}</span></p>
                <p><span class="text-slate-500">Subject</span><br><span class="font-semibold text-slate-900">{{ $exam->subject?->name ?? '—' }}</span></p>
                <p><span class="text-slate-500">Teacher</span><br><span class="font-semibold text-slate-900">{{ $exam->teacher?->name ?? '—' }}</span></p>
                <p><span class="text-slate-500">Paper</span><br><span class="font-semibold text-slate-900">{{ $exam->questions_count }} questions · {{ $exam->total_marks }} marks</span></p>
            </div>
        </div>

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('principal.exams.update', $exam) }}" class="admin-card-body space-y-4">
                @csrf
                @method('PUT')

                <p class="text-sm text-slate-600">Only the exam name and date can be changed. The questions stay the same.</p>

                <div>
                    <label class="admin-label">Name *</label>
                    <input type="text" name="title" value="{{ old('title', $exam->title) }}" required class="admin-input">
                    @error('title')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">Starts At</label>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->localStart()?->format('Y-m-d\TH:i')) }}" class="admin-input">
                        @error('starts_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Ends At</label>
                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->copy()->timezone(\App\Models\Exam::SCHOOL_TIMEZONE)->format('Y-m-d\TH:i')) }}" class="admin-input">
                        @error('ends_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">Save name and date</button>
                    <a href="{{ route('principal.exams.index', ['standard' => $exam->standard]) }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-principal-layout>
