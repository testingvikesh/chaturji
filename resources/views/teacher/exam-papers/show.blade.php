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
            <div style="display:flex;flex-direction:column;gap:12px;padding:12px;">
                @forelse ($students as $student)
                    @php
                        $sheet = $sheets->get($student->id);
                        $questions = $sheet?->ai_result['questions'] ?? [];
                        $overview = trim((string) ($sheet->remarks ?? ''));
                        if ($questions !== [] && preg_match('/\R(?=Q[\d])/u', $overview)) {
                            $overview = trim((string) preg_split('/\R(?=Q[\d])/u', $overview, 2)[0]);
                        }
                    @endphp
                    <article style="border:1px solid #e2e8f0;border-radius:14px;background:#fff;overflow:hidden;">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;background:#f8fafc;">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $student->name }}</p>
                                <p class="text-xs text-slate-500">{{ $student->mobile }}</p>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                                @if ($sheet)
                                    <a href="{{ route('teacher.exam-papers.sheets.file', [$paper, $sheet]) }}" target="_blank" class="text-sm font-semibold text-brand-green hover:underline">View sheet</a>
                                @endif
                                <form method="POST" action="{{ route('teacher.exam-papers.sheets.store', $paper) }}" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                    <input type="file" name="sheet" accept=".pdf,image/*" required class="max-w-[14rem] text-xs">
                                    <button type="submit" class="rounded-lg bg-brand-green px-3 py-1.5 text-xs font-semibold text-white">{{ $sheet ? 'Replace & check' : 'Upload & check' }}</button>
                                </form>
                            </div>
                        </div>
                        @if ($sheet)
                            <div style="padding:12px 14px;display:flex;flex-direction:column;gap:10px;">
                                <form method="POST" action="{{ route('teacher.exam-papers.sheets.check', [$paper, $sheet]) }}" style="display:flex;flex-direction:column;gap:10px;">
                                    @csrf
                                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
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
                                    @if ($questions !== [])
                                        <div style="max-height:280px;overflow:auto;border:1px solid #e2e8f0;border-radius:10px;">
                                            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                                                <thead>
                                                    <tr style="background:#1a367c;color:#fff;">
                                                        <th style="padding:8px 10px;text-align:left;width:72px;">Q</th>
                                                        <th style="padding:8px 10px;text-align:left;width:88px;">Marks</th>
                                                        <th style="padding:8px 10px;text-align:left;">Check</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($questions as $row)
                                                        @php
                                                            $awarded = (float) ($row['awarded'] ?? 0);
                                                            $max = (float) ($row['max_marks'] ?? 0);
                                                            $markColor = $awarded <= 0 ? '#b91c1c' : ($max > 0 && $awarded >= $max ? '#047857' : '#92400e');
                                                        @endphp
                                                        <tr style="border-top:1px solid #e2e8f0;vertical-align:top;">
                                                            <td style="padding:8px 10px;font-weight:700;">{{ $row['number'] ?? '' }}</td>
                                                            <td style="padding:8px 10px;font-weight:700;color:{{ $markColor }};">{{ rtrim(rtrim(number_format($awarded, 1, '.', ''), '0'), '.') }}/{{ rtrim(rtrim(number_format($max, 1, '.', ''), '0'), '.') }}</td>
                                                            <td style="padding:8px 10px;color:#334155;">
                                                                <div>{{ $row['comment'] ?? '' }}</div>
                                                                @if (! empty($row['student_answer']) || ! empty($row['correct_answer']))
                                                                    <div style="margin-top:4px;font-size:12px;color:#64748b;">
                                                                        Written: {{ $row['student_answer'] !== '' ? $row['student_answer'] : '—' }}
                                                                        @if (! empty($row['correct_answer']))
                                                                            · Expected: {{ $row['correct_answer'] }}
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                    <textarea name="remarks" rows="2" placeholder="Remarks" class="w-full rounded-lg border-slate-200 text-sm" style="display:block;width:100%;max-height:72px;overflow:auto;">{{ $overview }}</textarea>
                                    <div>
                                        <button type="submit" class="rounded-lg border border-brand-green px-3 py-1.5 text-xs font-semibold text-brand-green">Save check</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('teacher.exam-papers.sheets.ai', [$paper, $sheet]) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-slate-600 underline">Check again with AI</button>
                                </form>
                            </div>
                        @else
                            <p class="text-xs text-slate-400" style="padding:0 14px 12px;">Upload the answer sheet first.</p>
                        @endif
                    </article>
                @empty
                    <p class="py-8 text-center text-slate-500">No students in this standard and medium.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-teacher-layout>
