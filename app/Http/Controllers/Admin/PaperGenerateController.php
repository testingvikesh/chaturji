<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\PaperStyle;
use App\Models\Standard;
use App\Models\Subject;
use App\Support\ExamPaperWeightage;
use App\Support\MaterialPaperBank;
use App\Support\PaperTypeHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaperGenerateController extends Controller
{
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

        $materials = Material::forStudentSubject($subject, $subject->standard?->medium);

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

        $materials = Material::forStudentSubject($subject, $subject->standard?->medium);
        $chapterIds = array_values(array_filter(array_map('intval', (array) $request->input('chapter_ids', []))));
        if ($chapterIds !== []) {
            $materials = $materials->whereIn('id', $chapterIds)->values();
        }

        $available = $materials->isEmpty()
            ? []
            : MaterialPaperBank::availableCounts($materials, null, true);

        return response()->json(['available' => $available]);
    }

    public function store(Request $request): View
    {
        $validated = $request->validate([
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
        $materials = Material::forStudentSubject($subject, $subject->standard?->medium);
        $chapterIds = array_values(array_filter(array_map('intval', (array) ($validated['chapter_ids'] ?? []))));
        if ($chapterIds !== []) {
            $materials = $materials->whereIn('id', $chapterIds)->values();
        }

        $marks = [];
        foreach ($counts as $type => $count) {
            $marks[$type] = (int) (ExamPaperWeightage::MARKS_PER_QUESTION[$type] ?? 1);
        }

        $generated = MaterialPaperBank::generate($materials, null, $counts, $marks, true);

        return view('admin.paper-generate.show', [
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'durationMinutes' => $validated['duration_minutes'] ?? null,
            'standardName' => $subject->standard?->name ?? '',
            'subjectName' => $subject->name,
            'grouped' => $generated['grouped'],
            'breakdown' => $generated['breakdown'],
            'totalMarks' => $generated['total_marks'],
            'totalQuestions' => $generated['questions']->count(),
        ]);
    }
}
