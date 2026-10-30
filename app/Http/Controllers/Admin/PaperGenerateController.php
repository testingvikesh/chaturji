<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedPaper;
use App\Models\Material;
use App\Models\PaperStyle;
use App\Models\Standard;
use App\Models\Subject;
use App\Support\ExamPaperWeightage;
use App\Support\MaterialPaperBank;
use App\Support\PaperTypeHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaperGenerateController extends Controller
{
    public function index(): View
    {
        $papers = GeneratedPaper::query()
            ->with(['standard:id,name', 'subject:id,name'])
            ->select([
                'id',
                'standard_id',
                'subject_id',
                'medium',
                'title',
                'total_marks',
                'total_questions',
                'created_at',
            ])
            ->latest()
            ->paginate(20);

        return view('admin.paper-generate.index', [
            'papers' => $papers,
        ]);
    }

    public function create(): View
    {
        $styles = PaperStyle::query()->get()->mapWithKeys(
            fn (PaperStyle $style) => [(string) $style->subject_id => $style->type_counts ?? []]
        );

        $types = collect(PaperTypeHelper::types())
            ->reject(fn ($label, $type) => in_array($type, ['short_answer', 'long_answer'], true));

        return view('admin.paper-generate.create', [
            'standards' => Standard::query()->where('is_active', true)->orderedByNumber()->get(),
            'subjects' => Subject::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'standard_id', 'name']),
            'mediums' => Standard::MEDIUMS,
            'paperTypes' => $types,
            'styleCounts' => $styles,
            'marksEach' => ExamPaperWeightage::MARKS_PER_QUESTION,
        ]);
    }

    public function chapters(Request $request): JsonResponse
    {
        $subject = Subject::query()->with('standard')->find($request->integer('subject_id'));
        if (! $subject) {
            return response()->json(['chapters' => []]);
        }

        $materials = Material::forStudentSubject($subject, $this->mediumFrom($request, $subject));

        return response()->json([
            'chapters' => $materials->map(fn (Material $material) => [
                'id' => $material->id,
                'name' => $material->displayChapterName(),
            ])->values(),
        ]);
    }

    public function available(Request $request): JsonResponse
    {
        $subject = Subject::query()->with('standard')->find($request->integer('subject_id'));
        if (! $subject) {
            return response()->json(['available' => []]);
        }

        $materials = Material::forStudentSubject($subject, $this->mediumFrom($request, $subject));
        $chapterIds = array_values(array_filter(array_map('intval', (array) $request->input('chapter_ids', []))));
        if ($chapterIds !== []) {
            $materials = $materials->whereIn('id', $chapterIds)->values();
        }

        $available = $materials->isEmpty()
            ? []
            : MaterialPaperBank::availableCounts($materials, null, true);

        return response()->json(['available' => $available]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'medium' => ['required', 'string', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard_id' => ['required', 'integer', 'exists:standards,id'],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(fn ($query) => $query->where('standard_id', $request->input('standard_id'))),
            ],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'chapter_ids' => ['nullable', 'array'],
            'chapter_ids.*' => ['integer', 'exists:materials,id'],
            'type_counts' => ['required', 'array'],
            'type_counts.*' => ['nullable', 'integer', 'min:0', 'max:200'],
        ]);

        $counts = PaperTypeHelper::normalizeCounts((array) $validated['type_counts']);
        if ($counts === []) {
            throw ValidationException::withMessages([
                'type_counts' => 'Enter a quantity for at least one question type.',
            ]);
        }

        $subject = Subject::query()->with('standard')->findOrFail($validated['subject_id']);
        $medium = Material::normalizeMedium($validated['medium']) ?: 'english';
        $materials = Material::forStudentSubject($subject, $medium);
        $chapterIds = array_values(array_filter(array_map('intval', (array) ($validated['chapter_ids'] ?? []))));
        if ($chapterIds !== []) {
            $materials = $materials->whereIn('id', $chapterIds)->values();
        }

        $marks = [];
        foreach ($counts as $type => $count) {
            $marks[$type] = (int) (ExamPaperWeightage::MARKS_PER_QUESTION[$type] ?? 1);
        }

        $generated = MaterialPaperBank::generate($materials, null, $counts, $marks, true);

        $questions = $generated['questions']->map(function ($question) use ($marks) {
            $type = (string) $question->question_type;

            return [
                'question_type' => $type,
                'question_text' => (string) $question->question_text,
                'options' => $question->options,
                'answer' => is_array($question->answer)
                    ? json_encode($question->answer, JSON_UNESCAPED_UNICODE)
                    : $question->answer,
                'marks' => (int) ($marks[$type] ?? 1),
            ];
        })->values()->all();

        $paper = GeneratedPaper::query()->create([
            'user_id' => $request->user()?->id,
            'standard_id' => $subject->standard_id,
            'subject_id' => $subject->id,
            'medium' => $medium,
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'chapter_label' => $materials->map(fn (Material $material) => $material->displayChapterName())->implode(', '),
            'chapter_ids' => $materials->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'type_counts' => $counts,
            'questions' => $questions,
            'total_marks' => $generated['total_marks'],
            'total_questions' => count($questions),
        ]);

        return redirect()
            ->route('admin.paper-generate.show', $paper)
            ->with('success', 'Paper generated from important questions.');
    }

    public function show(GeneratedPaper $generatedPaper): View
    {
        $generatedPaper->load(['standard:id,name', 'subject:id,name']);

        return view('admin.paper-generate.show', [
            'paper' => $generatedPaper,
            'grouped' => $generatedPaper->groupedQuestions(),
            'breakdown' => $generatedPaper->breakdown(),
        ]);
    }

    public function print(GeneratedPaper $generatedPaper, Request $request): View
    {
        $generatedPaper->load(['standard:id,name,slug', 'subject:id,name']);
        $withAnswers = $request->boolean('answers');

        return view('admin.paper-generate.print', [
            'paper' => $generatedPaper,
            'withAnswers' => $withAnswers,
            'grouped' => $generatedPaper->groupedQuestions(),
            'breakdown' => $generatedPaper->breakdown(),
        ]);
    }

    public function destroy(GeneratedPaper $generatedPaper): RedirectResponse
    {
        $generatedPaper->delete();

        return redirect()
            ->route('admin.paper-generate.index')
            ->with('success', 'Generated paper deleted.');
    }

    private function mediumFrom(Request $request, ?Subject $subject): string
    {
        $medium = Material::normalizeMedium((string) $request->input('medium', ''));
        if (in_array($medium, array_keys(Standard::MEDIUMS), true)) {
            return $medium;
        }

        return Material::normalizeMedium($subject?->standard?->medium) ?: 'english';
    }
}
