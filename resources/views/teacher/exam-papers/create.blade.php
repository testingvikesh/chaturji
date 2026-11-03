<x-teacher-layout>
    <x-slot name="header">
        <div>
            <h2 class="admin-page-title">Upload exam paper</h2>
            <p class="admin-page-subtitle">PDF or photo of the question paper</p>
        </div>
    </x-slot>

    <div class="admin-page">
        <form method="POST" action="{{ route('teacher.exam-papers.store') }}" enctype="multipart/form-data" class="admin-card max-w-3xl space-y-4 p-5 sm:p-6">
            @csrf
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div>
                <label class="admin-label" for="title">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" required class="admin-input">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="admin-label" for="standard_id">Standard</label>
                    <select id="standard_id" name="standard_id" required class="admin-select w-full">
                        <option value="">Select standard</option>
                        @foreach ($standards as $standard)
                            <option value="{{ $standard->id }}" @selected((string) old('standard_id') === (string) $standard->id)>{{ $standard->name }} · {{ $standard->mediumLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-label" for="subject_name">Subject</label>
                    <input id="subject_name" name="subject_name" value="{{ old('subject_name') }}" required class="admin-input">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="admin-label" for="total_marks">Total marks</label>
                    <input id="total_marks" type="number" name="total_marks" min="1" max="500" value="{{ old('total_marks') }}" required class="admin-input">
                </div>
                <div>
                    <label class="admin-label" for="exam_date">Exam date</label>
                    <input id="exam_date" type="date" name="exam_date" value="{{ old('exam_date') }}" class="admin-input">
                </div>
            </div>
            <div>
                <label class="admin-label" for="paper">Question paper</label>
                <input id="paper" type="file" name="paper" accept=".pdf,image/*" required class="admin-input">
                <p class="mt-1 text-xs text-slate-500">PDF or image, up to 20 MB.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">Save paper</button>
                <a href="{{ route('teacher.exam-papers.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
            </div>
        </form>
    </div>
</x-teacher-layout>
