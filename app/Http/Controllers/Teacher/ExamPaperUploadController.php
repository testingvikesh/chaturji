<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ExamAnswerSheet;
use App\Models\ExamPaperUpload;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamPaperUploadController extends Controller
{
    public function index(): View
    {
        $papers = ExamPaperUpload::query()
            ->with('standard:id,name,medium')
            ->withCount([
                'sheets',
                'sheets as checked_count' => fn ($query) => $query->whereNotNull('checked_at'),
            ])
            ->where('teacher_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('teacher.exam-papers.index', [
            'papers' => $papers,
        ]);
    }

    public function create(): View
    {
        return view('teacher.exam-papers.create', [
            'standards' => Standard::query()->where('is_active', true)->orderedByNumber()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'standard_id' => ['required', 'integer', 'exists:standards,id'],
            'subject_name' => ['required', 'string', 'max:255'],
            'exam_date' => ['nullable', 'date'],
            'total_marks' => ['required', 'integer', 'min:1', 'max:500'],
            'paper' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $paper = ExamPaperUpload::query()->create([
            'teacher_id' => $request->user()->id,
            'standard_id' => $validated['standard_id'],
            'subject_name' => $validated['subject_name'],
            'title' => $validated['title'],
            'exam_date' => $validated['exam_date'] ?? null,
            'total_marks' => $validated['total_marks'],
            'paper_path' => '',
            'paper_name' => $request->file('paper')->getClientOriginalName(),
        ]);

        $path = $request->file('paper')->store('exam-papers/'.$paper->id, 'local');
        $paper->update(['paper_path' => $path]);

        return redirect()
            ->route('teacher.exam-papers.show', $paper)
            ->with('success', 'Exam paper uploaded. Add each student answer sheet and check it.');
    }

    public function show(ExamPaperUpload $examPaper): View
    {
        $paper = $this->owned($examPaper);
        $paper->load('standard');
        $sheets = $paper->sheets()->get()->keyBy('student_id');
        $medium = $paper->standard?->medium;
        $slug = $paper->standard?->slug;

        $students = User::query()
            ->students()
            ->approved()
            ->when($slug, fn ($query) => $query->where('standard', $slug))
            ->when($medium, fn ($query) => $query->where('medium', $medium))
            ->orderBy('name')
            ->get(['id', 'name', 'mobile', 'standard', 'medium']);

        return view('teacher.exam-papers.show', [
            'paper' => $paper,
            'students' => $students,
            'sheets' => $sheets,
        ]);
    }

    public function destroy(ExamPaperUpload $examPaper): RedirectResponse
    {
        $paper = $this->owned($examPaper);
        Storage::disk('local')->deleteDirectory('exam-papers/'.$paper->id);
        $paper->delete();

        return redirect()
            ->route('teacher.exam-papers.index')
            ->with('success', 'Exam paper deleted.');
    }

    public function paperFile(ExamPaperUpload $examPaper): StreamedResponse
    {
        $paper = $this->owned($examPaper);
        abort_unless(Storage::disk('local')->exists($paper->paper_path), 404);

        return Storage::disk('local')->response($paper->paper_path, $paper->paper_name ?: 'paper');
    }

    public function storeSheet(Request $request, ExamPaperUpload $examPaper): RedirectResponse
    {
        $paper = $this->owned($examPaper);
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'sheet' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $student = User::query()->students()->findOrFail($validated['student_id']);
        $this->studentMatchesPaper($paper, $student);

        $existing = ExamAnswerSheet::query()
            ->where('exam_paper_upload_id', $paper->id)
            ->where('student_id', $student->id)
            ->first();

        $path = $request->file('sheet')->store('exam-papers/'.$paper->id.'/sheets', 'local');

        if ($existing) {
            Storage::disk('local')->delete($existing->file_path);
        }

        ExamAnswerSheet::query()->updateOrCreate(
            [
                'exam_paper_upload_id' => $paper->id,
                'student_id' => $student->id,
            ],
            [
                'file_path' => $path,
                'file_name' => $request->file('sheet')->getClientOriginalName(),
            ]
        );

        return back()->with('success', 'Answer sheet uploaded for '.$student->name.'.');
    }

    public function check(Request $request, ExamPaperUpload $examPaper, ExamAnswerSheet $sheet): RedirectResponse
    {
        $paper = $this->owned($examPaper);
        abort_unless($sheet->exam_paper_upload_id === $paper->id, 404);

        $validated = $request->validate([
            'marks_obtained' => ['required', 'numeric', 'min:0', 'max:'.$paper->total_marks],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $sheet->update([
            'marks_obtained' => $validated['marks_obtained'],
            'remarks' => $validated['remarks'] ?? null,
            'checked_at' => now(),
        ]);

        return back()->with('success', 'Answer sheet checked.');
    }

    public function sheetFile(ExamPaperUpload $examPaper, ExamAnswerSheet $sheet): StreamedResponse
    {
        $paper = $this->owned($examPaper);
        abort_unless($sheet->exam_paper_upload_id === $paper->id, 404);
        abort_unless(Storage::disk('local')->exists($sheet->file_path), 404);

        return Storage::disk('local')->response($sheet->file_path, $sheet->file_name ?: 'answer-sheet');
    }

    private function owned(ExamPaperUpload $paper): ExamPaperUpload
    {
        abort_unless((int) $paper->teacher_id === (int) auth()->id(), 403);

        return $paper;
    }

    private function studentMatchesPaper(ExamPaperUpload $paper, User $student): void
    {
        $paper->loadMissing('standard');
        $standard = $paper->standard;
        abort_unless(
            $standard
            && $student->standard === $standard->slug
            && $student->medium === $standard->medium,
            422,
            'This student is not in the paper standard.'
        );
    }
}
