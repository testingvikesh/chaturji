<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\HandlesPaperBuilder;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Material;
use App\Models\Standard;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\PaperTypeHelper;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ExamController extends Controller
{
    use HandlesPaperBuilder;

    public function index(Request $request): View
    {
        $principal = auth()->user();
        $standards = $this->allottedStandards($principal);
        $selectedNumber = preg_replace('/\D+/', '', (string) $request->query('standard', '')) ?: '';
        $mediumFilter = Material::normalizeMedium((string) $request->query('medium', '')) ?: '';
        if (! in_array($mediumFilter, ['english', 'gujarati'], true)) {
            $mediumFilter = '';
        }

        $knownNumbers = $standards
            ->map(fn (Standard $standard) => Material::standardNumber($standard))
            ->filter()
            ->unique()
            ->values();
        if ($selectedNumber !== '' && ! $knownNumbers->contains($selectedNumber)) {
            $selectedNumber = '';
        }

        $slugs = $standards->pluck('slug')->filter()->unique()->values();
        $exams = collect();
        if ($slugs->isNotEmpty()) {
            $exams = Exam::query()
                ->excludeSelfExams()
                ->whereIn('standard', $slugs->all())
                ->with(['teacher:id,name', 'subject:id,name', 'subject.standard:id,name,slug,medium'])
                ->withCount('questions')
                ->orderByDesc('id')
                ->get();
            $this->attachPaperMedium($exams, $standards);
        }

        $groups = $standards
            ->groupBy(fn (Standard $standard) => Material::standardNumber($standard) ?: (string) $standard->slug)
            ->map(function (Collection $rows, string $number) use ($exams, $selectedNumber, $mediumFilter) {
                if ($selectedNumber !== '' && $number !== $selectedNumber) {
                    return null;
                }

                $slugs = $rows->pluck('slug')->filter()->all();
                $matched = $exams->filter(function (Exam $exam) use ($slugs, $number) {
                    $examNumber = preg_replace('/\D+/', '', (string) $exam->standard) ?: '';

                    return in_array((string) $exam->standard, $slugs, true) || $examNumber === $number;
                })->unique('id')->values();

                $blocks = collect(['english', 'gujarati'])
                    ->filter(fn (string $medium) => $mediumFilter === '' || $mediumFilter === $medium)
                    ->map(fn (string $medium) => [
                        'medium' => $medium,
                        'label' => Standard::MEDIUMS[$medium],
                        'exams' => $matched->filter(fn (Exam $exam) => $exam->getAttribute('paper_medium') === $medium)->values(),
                    ])
                    ->values();

                return [
                    'name' => $rows->first()->name,
                    'number' => $number,
                    'blocks' => $blocks,
                    'count' => $blocks->sum(fn (array $block) => $block['exams']->count()),
                ];
            })
            ->filter()
            ->filter(fn (array $group) => $selectedNumber !== '' || $group['count'] > 0)
            ->values();

        return view('principal.exams.index', [
            'standards' => $standards
                ->unique(fn (Standard $standard) => Material::standardNumber($standard) ?: $standard->slug)
                ->values(),
            'groups' => $groups,
            'selected' => $selectedNumber,
            'medium' => $mediumFilter,
            'mediums' => Standard::MEDIUMS,
            'examCount' => $groups->sum('count'),
        ]);
    }

    public function preview(Exam $exam): View
    {
        return view('teacher.exams.print', [
            ...$this->paperViewData($exam),
            'autoPrint' => false,
        ]);
    }

    public function printPaper(Exam $exam): View
    {
        return view('teacher.exams.print', [
            ...$this->paperViewData($exam),
            'autoPrint' => true,
        ]);
    }

    public function edit(Exam $exam): View
    {
        $this->assertAllottedExam(auth()->user(), $exam);
        $exam->load(['teacher:id,name', 'subject:id,name'])->loadCount('questions');

        return view('principal.exams.edit', [
            'exam' => $exam,
            'standardName' => $this->standardName($exam->standard),
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->assertAllottedExam(auth()->user(), $exam);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $exam->update([
            'title' => $validated['title'],
            'starts_at' => $this->schoolTime($validated['starts_at'] ?? null),
            'ends_at' => $this->schoolTime($validated['ends_at'] ?? null),
        ]);

        ActivityLogger::log(
            'principal.exams.update',
            'Principal updated exam '.$exam->title,
            $exam
        );

        return redirect()
            ->route('principal.exams.index', ['standard' => $exam->standard])
            ->with('success', 'Exam name and date updated. Questions are unchanged.');
    }

    public function regenerate(Exam $exam): RedirectResponse
    {
        $this->assertAllottedExam(auth()->user(), $exam);

        $config = is_array($exam->generation_config) ? $exam->generation_config : [];
        $typeCounts = PaperTypeHelper::normalizeCounts($config['type_counts'] ?? []);
        $marksPerType = PaperTypeHelper::normalizeMarksPerType($config['marks_per_type'] ?? [], $typeCounts);
        $optionalCounts = is_array($config['optional_counts'] ?? null) ? $config['optional_counts'] : [];
        $materialIds = array_values(array_filter(array_map('intval', (array) ($config['material_ids'] ?? []))));

        if ($typeCounts === [] || $materialIds === []) {
            return back()->with('error', 'This exam has no saved question plan to regenerate.');
        }

        try {
            $generated = $this->generateFromMaterials([
                'material_ids' => $materialIds,
                'material_topic_ids' => array_values(array_filter(array_map(
                    'intval',
                    (array) ($config['material_topic_ids'] ?? [])
                ))),
            ], $typeCounts, $marksPerType, $optionalCounts);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();

            return back()->with('error', $message ?: 'Could not regenerate the questions.');
        }

        $this->syncGeneratedQuestions($exam, [
            'marks_per_type' => $generated['marks_per_type'],
            'questions' => $this->snapshotQuestions($generated['questions']),
        ]);

        $config['regenerated_at'] = now()->toIso8601String();
        $exam->update(['generation_config' => $config]);

        ActivityLogger::log(
            'principal.exams.regenerate',
            'Principal regenerated questions for exam '.$exam->title,
            $exam
        );

        return back()->with('success', 'Questions and answers regenerated. The exam name, date, and marks are unchanged.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->assertAllottedExam(auth()->user(), $exam);
        $standard = $exam->standard;
        $title = $exam->title;

        ActivityLogger::log(
            'principal.exams.delete',
            'Principal deleted exam '.$title,
            $exam
        );

        $exam->delete();

        return redirect()
            ->route('principal.exams.index', ['standard' => $standard])
            ->with('success', 'Exam deleted.');
    }

    private function syncGeneratedQuestions(Exam $exam, array $preview): void
    {
        $rows = $preview['questions'] ?? [];
        $exam->questions()->delete();

        $sort = 0;
        foreach ($rows as $question) {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'chapter_question_id' => $question['id'] ?? null,
                'question_type' => $question['question_type'],
                'question_text' => $question['question_text'],
                'options' => $question['options'] ?? null,
                'answer' => $question['answer'] ?? null,
                'marks' => $preview['marks_per_type'][$question['question_type']] ?? 1,
                'sort_order' => $sort++,
            ]);
        }
    }

    /**
     * @return array{exam: Exam, grouped: \Illuminate\Support\Collection, breakdown: array<int, array<string, mixed>>}
     */
    private function paperViewData(Exam $exam): array
    {
        $this->assertAllottedExam(auth()->user(), $exam);

        $exam->load(['subject', 'chapter:id,name,sort_order', 'topic:id,name', 'questions']);
        $grouped = PaperTypeHelper::groupInPaperOrder($exam->questions);
        $breakdown = PaperTypeHelper::breakdown(
            $exam->generation_config['type_counts'] ?? $grouped->map->count()->all(),
            $exam->generation_config['marks_per_type'] ?? []
        );

        return compact('exam', 'grouped', 'breakdown');
    }

    private function attachPaperMedium(Collection $exams, Collection $standards): void
    {
        $standardsBySlug = $standards->keyBy('slug');
        $materialIds = $exams
            ->flatMap(fn (Exam $exam) => (array) ($exam->generation_config['material_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $mediumByMaterial = $materialIds->isEmpty()
            ? collect()
            : Material::query()->whereIn('id', $materialIds)->pluck('medium', 'id');

        foreach ($exams as $exam) {
            $counts = [];
            foreach ((array) ($exam->generation_config['material_ids'] ?? []) as $id) {
                $medium = Material::normalizeMedium((string) ($mediumByMaterial[(int) $id] ?? ''));
                if (in_array($medium, ['english', 'gujarati'], true)) {
                    $counts[$medium] = ($counts[$medium] ?? 0) + 1;
                }
            }

            if ($counts !== []) {
                arsort($counts);
                $exam->setAttribute('paper_medium', array_key_first($counts));
                continue;
            }

            $fromSubject = Material::normalizeMedium($exam->subject?->standard?->medium);
            $fromSlug = Material::normalizeMedium($standardsBySlug->get($exam->standard)?->medium);
            $medium = in_array($fromSubject, ['english', 'gujarati'], true)
                ? $fromSubject
                : (in_array($fromSlug, ['english', 'gujarati'], true) ? $fromSlug : 'english');
            $exam->setAttribute('paper_medium', $medium);
        }
    }

    private function allottedStandards(?User $principal): Collection
    {
        if (! $principal) {
            return collect();
        }

        return $principal->allottedStandards()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get(['standards.id', 'standards.name', 'standards.slug', 'standards.medium']);
    }

    private function assertAllottedExam(?User $principal, Exam $exam): void
    {
        abort_if($exam->isSelfExam(), 404);

        $slugs = $this->allottedStandards($principal)->pluck('slug')->filter()->all();
        abort_unless($slugs !== [] && in_array((string) $exam->standard, $slugs, true), 404);
    }

    private function standardName(?string $slug): string
    {
        $standard = Standard::query()->where('slug', $slug)->first(['name', 'medium']);
        if (! $standard) {
            return str_replace('_', ' ', ucwords((string) $slug, '_'));
        }

        $medium = $standard->mediumLabel();

        return trim($standard->name.($medium !== '' ? ' · '.$medium : ''));
    }

    private function schoolTime(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value, Exam::SCHOOL_TIMEZONE)->utc()->toDateTimeString();
    }
}
