<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\ChapterContent;
use App\Models\ChapterContentSection;
use App\Models\ChapterQuestion;
use App\Models\Standard;
use App\Models\Subject;
use App\Services\ChapterContentImporter;
use App\Support\SlugHelper;
use App\Support\TextSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DataUploadController extends Controller
{
    public function __construct(
        private readonly ChapterContentImporter $importer
    ) {}

    public function index(): View
    {
        $uploads = ChapterContent::query()
            ->with(['chapter.subject.standard', 'uploader'])
            ->latest()
            ->paginate(500);

        return view('admin.upload-data.index', compact('uploads'));
    }

    public function create(): View
    {
        return view('admin.upload-data.create', [
            'standards' => Standard::where('is_active', true)->orderBy('sort_order')->get(),
            'languages' => ChapterContent::LANGUAGES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'standard_id' => ['required', 'exists:standards,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'chapter_mode' => ['required', 'in:existing,new'],
            'chapter_id' => ['required_if:chapter_mode,existing', 'nullable', 'exists:chapters,id'],
            'chapter_name' => ['required_if:chapter_mode,new', 'nullable', 'string', 'max:255'],
            'language' => ['required', 'in:english,hindi,gujarati'],
            'topic_name' => ['required', 'string', 'max:255'],
            'data_file' => ['required', 'file', 'mimes:json,txt', 'max:10240'],
            'original_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        $subject = Subject::where('id', $validated['subject_id'])
            ->where('standard_id', $validated['standard_id'])
            ->firstOrFail();

        if ($validated['chapter_mode'] === 'new') {
            $chapter = $subject->chapters()->create([
                'name' => $validated['chapter_name'],
                'slug' => SlugHelper::unique($validated['chapter_name'], fn ($slug) => Chapter::where('subject_id', $subject->id)->where('slug', $slug)->exists()),
                'sort_order' => ($subject->chapters()->max('sort_order') ?? 0) + 1,
                'is_active' => true,
            ]);
        } else {
            $chapter = Chapter::where('id', $validated['chapter_id'])
                ->where('subject_id', $subject->id)
                ->firstOrFail();
        }

        try {
            $result = $this->importer->import(
                $chapter,
                $request->file('data_file'),
                (int) auth()->id(),
                $validated['language'],
                $validated['topic_name'],
                $request->file('original_pdf')
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Upload failed: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.upload-data.show', $result['content'])
            ->with('success', "Material JSON uploaded successfully. Saved {$result['stats']['sections']} section(s), {$result['stats']['questions']} question(s), and {$result['stats']['topics']} topic(s).");
    }

    public function show(ChapterContent $chapterContent): View
    {
        $chapterContent->load(['chapter.subject.standard', 'chapter.topics', 'uploader', 'sections', 'questions']);

        return view('admin.upload-data.show', ['content' => $chapterContent]);
    }

    public function edit(ChapterContent $chapterContent): View
    {
        $chapterContent->load(['chapter.subject.standard', 'chapter.topics']);

        return view('admin.upload-data.edit', [
            'content' => $chapterContent,
            'languages' => ChapterContent::LANGUAGES,
        ]);
    }

    public function update(Request $request, ChapterContent $chapterContent): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'overview' => ['nullable', 'string'],
            'language' => ['required', 'in:english,hindi,gujarati'],
            'topic_name' => ['nullable', 'string', 'max:255'],
            'data_file' => ['nullable', 'file', 'mimes:json,txt', 'max:10240'],
            'original_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
            'remove_original_pdf' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('data_file')) {
            try {
                $result = $this->importer->import(
                    $chapterContent->chapter,
                    $request->file('data_file'),
                    (int) auth()->id(),
                    $validated['language'],
                    $validated['topic_name'] ?? null,
                    $request->file('original_pdf')
                );

                $chapterContent = $result['content'];
            } catch (\Throwable $e) {
                return back()
                    ->withInput()
                    ->with('error', 'Re-import failed: '.$e->getMessage());
            }
        } elseif ($request->hasFile('original_pdf')) {
            $this->importer->storeOriginalPdf($chapterContent, $request->file('original_pdf'));
        } elseif ($request->boolean('remove_original_pdf')) {
            $this->importer->deleteStoredFile($chapterContent->original_pdf_path);
            $chapterContent->update([
                'original_pdf_path' => null,
                'original_pdf_filename' => null,
            ]);
        }

        $chapterContent->update([
            'title' => TextSanitizer::forDatabase($validated['title']) ?? $validated['title'],
            'overview' => TextSanitizer::forDatabase($validated['overview'] ?? null),
            'language' => $validated['language'],
        ]);

        return redirect()
            ->route('admin.upload-data.show', $chapterContent)
            ->with('success', 'Upload data updated successfully.');
    }

    public function destroy(ChapterContent $chapterContent): RedirectResponse
    {
        if ($chapterContent->source_path && Storage::disk('local')->exists($chapterContent->source_path)) {
            Storage::disk('local')->delete($chapterContent->source_path);
        }
        if ($chapterContent->original_pdf_path && Storage::disk('local')->exists($chapterContent->original_pdf_path)) {
            Storage::disk('local')->delete($chapterContent->original_pdf_path);
        }

        $chapterContent->delete();

        return redirect()
            ->route('admin.upload-data.index')
            ->with('success', 'Uploaded data deleted successfully.');
    }

    public function editQuestion(ChapterContent $chapterContent, ChapterQuestion $question): View
    {
        $this->ensureQuestionBelongsToContent($chapterContent, $question);

        return view('admin.upload-data.questions.edit', [
            'content' => $chapterContent,
            'question' => $question,
        ]);
    }

    public function updateQuestion(Request $request, ChapterContent $chapterContent, ChapterQuestion $question): RedirectResponse
    {
        $this->ensureQuestionBelongsToContent($chapterContent, $question);

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'answer' => ['nullable', 'string'],
            'options_text' => ['nullable', 'string'],
        ]);

        $options = null;
        if ($request->filled('options_text')) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $validated['options_text']))));
            $options = $lines !== [] ? $lines : null;
        }

        $question->update([
            'question_text' => TextSanitizer::forDatabase($validated['question_text']) ?? '',
            'answer' => TextSanitizer::forDatabase($validated['answer'] ?? null),
            'options' => $options,
        ]);

        return redirect()
            ->route('admin.upload-data.show', $chapterContent)
            ->with('success', 'Question updated successfully.');
    }

    public function destroyQuestion(ChapterContent $chapterContent, ChapterQuestion $question): RedirectResponse
    {
        $this->ensureQuestionBelongsToContent($chapterContent, $question);

        $question->delete();
        $chapterContent->update(['total_questions' => $chapterContent->questions()->count()]);

        return redirect()
            ->route('admin.upload-data.show', $chapterContent)
            ->with('success', 'Question deleted successfully.');
    }

    public function editSection(ChapterContent $chapterContent, ChapterContentSection $section): View
    {
        $this->ensureSectionBelongsToContent($chapterContent, $section);

        return view('admin.upload-data.sections.edit', [
            'content' => $chapterContent,
            'section' => $section,
        ]);
    }

    public function updateSection(Request $request, ChapterContent $chapterContent, ChapterContentSection $section): RedirectResponse
    {
        $this->ensureSectionBelongsToContent($chapterContent, $section);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $section->update([
            'title' => TextSanitizer::forDatabase($validated['title'] ?? null),
            'content' => TextSanitizer::forDatabase($validated['content']) ?? '',
        ]);

        if ($section->section_type === 'introduction') {
            $chapterContent->update(['overview' => TextSanitizer::forDatabase($validated['content'])]);
        }

        return redirect()
            ->route('admin.upload-data.show', $chapterContent)
            ->with('success', 'Section updated successfully.');
    }

    public function destroySection(ChapterContent $chapterContent, ChapterContentSection $section): RedirectResponse
    {
        $this->ensureSectionBelongsToContent($chapterContent, $section);

        $section->delete();

        return redirect()
            ->route('admin.upload-data.show', $chapterContent)
            ->with('success', 'Section deleted successfully.');
    }

    public function subjects(Request $request): JsonResponse
    {
        $request->validate(['standard_id' => ['required', 'exists:standards,id']]);

        $subjects = Subject::where('standard_id', $request->standard_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return response()->json($subjects);
    }

    public function chapters(Request $request): JsonResponse
    {
        $request->validate(['subject_id' => ['required', 'exists:subjects,id']]);

        $chapters = Chapter::where('subject_id', $request->subject_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->withExists('content')
            ->get(['id', 'name']);

        return response()->json($chapters);
    }

    private function ensureQuestionBelongsToContent(ChapterContent $chapterContent, ChapterQuestion $question): void
    {
        abort_unless($question->chapter_content_id === $chapterContent->id, 404);
    }

    private function ensureSectionBelongsToContent(ChapterContent $chapterContent, ChapterContentSection $section): void
    {
        abort_unless($section->chapter_content_id === $chapterContent->id, 404);
    }
}
