<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="admin-page-title">{{ $paper->title }}</h2>
                <p class="admin-page-subtitle">{{ $paper->standard?->name }} · {{ $paper->standard?->mediumLabel() }} · {{ $paper->subject_name }} · {{ $paper->total_marks }} marks</p>
            </div>
            <a href="{{ route('teacher.exam-papers.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">All papers</a>
        </div>
    </x-slot>

    <div class="admin-page space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="rounded-xl px-4 py-3 text-sm" style="border:1px solid #f5d48a;background:#fff8e8;color:#8a5a00;">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <div class="admin-card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <h3 class="font-bold text-slate-900">Question paper</h3>
                <a href="{{ route('teacher.exam-papers.file', $paper) }}" target="_blank" class="text-sm font-semibold text-brand-green hover:underline">Open file</a>
            </div>
            @php $paperIsImage = preg_match('/\.(jpe?g|png|webp)$/i', (string) $paper->paper_path); @endphp
            @if ($paperIsImage)
                <img src="{{ route('teacher.exam-papers.file', $paper) }}" alt="Question paper" class="max-h-[70vh] w-full object-contain bg-slate-50">
            @else
                <iframe src="{{ route('teacher.exam-papers.file', $paper) }}" title="Question paper" class="h-[70vh] w-full bg-slate-50"></iframe>
            @endif
        </div>

        <div class="admin-card overflow-hidden">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="font-bold text-slate-900">Student answer sheets</h3>
                <p class="text-xs text-slate-500">Uploading a sheet checks it against this question paper and fills the marks. You can change them before saving.</p>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table min-w-[980px]">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Answer sheet</th>
                            <th>Checking</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            @php $sheet = $sheets->get($student->id); @endphp
                            <tr>
                                <td class="align-top">
                                    <p class="font-semibold text-slate-900">{{ $student->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $student->mobile }}</p>
                                </td>
                                <td class="align-top">
                                    @if ($sheet)
                                        <a href="{{ route('teacher.exam-papers.sheets.file', [$paper, $sheet]) }}" target="_blank" class="text-sm font-semibold text-brand-green hover:underline">View sheet</a>
                                    @endif
                                    <form method="POST" action="{{ route('teacher.exam-papers.sheets.store', $paper) }}" enctype="multipart/form-data" class="mt-2 flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="file" name="sheet" accept=".pdf,image/*" required class="max-w-[14rem] text-xs">
                                        <button type="submit" class="rounded-lg bg-brand-green px-3 py-1.5 text-xs font-semibold text-white">{{ $sheet ? 'Replace & check' : 'Upload & check' }}</button>
                                    </form>
                                </td>
                                <td class="align-top">
                                    @if ($sheet)
                                        <form method="POST" action="{{ route('teacher.exam-papers.sheets.check', [$paper, $sheet]) }}" class="space-y-2">
                                            @csrf
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="marks_obtained" min="0" max="{{ $paper->total_marks }}" step="0.5" value="{{ $sheet->marks_obtained }}" required class="w-24 rounded-lg border-slate-200 text-sm">
                                                <span class="text-xs text-slate-500">/ {{ $paper->total_marks }}</span>
                                                @if ($sheet->ai_result)
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold text-white" style="background:#1a367c;">AI checked</span>
                                                @elseif ($sheet->isChecked())
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Checked</span>
                                                @endif
                                            </div>
                                            @if ($sheet->ai_error)
                                                <p class="text-xs text-red-600">{{ $sheet->ai_error }}</p>
                                            @endif
                                            @if (! empty($sheet->ai_result['questions']))
                                                <div class="max-h-40 space-y-1 overflow-auto rounded-lg bg-slate-50 p-2 text-xs text-slate-700">
                                                    @foreach ($sheet->ai_result['questions'] as $row)
                                                        <p><span class="font-semibold">Q{{ $row['number'] ?? '' }}</span> {{ $row['awarded'] ?? 0 }}/{{ $row['max_marks'] ?? 0 }} — {{ $row['comment'] ?? '' }}</p>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <textarea name="remarks" rows="2" placeholder="Remarks" class="w-full rounded-lg border-slate-200 text-sm">{{ $sheet->remarks }}</textarea>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="submit" class="rounded-lg border border-brand-green px-3 py-1.5 text-xs font-semibold text-brand-green">Save check</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('teacher.exam-papers.sheets.ai', [$paper, $sheet]) }}" class="mt-2">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-slate-600 underline">Check again with AI</button>
                                        </form>
                                    @else
                                        <p class="text-xs text-slate-400">Upload the answer sheet first.</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-500">No students in this standard and medium.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-teacher-layout>
