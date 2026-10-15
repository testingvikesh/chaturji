<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Services\SelfExamGeneratorService;
use App\Support\MaterialPaperBank;
use App\Support\PaperTypeHelper;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    public function subjects(Request $request): JsonResponse
    {
        $request->validate(['standard' => ['required', 'string']]);

        $standard = Standard::where('slug', $request->standard)->where('is_active', true)->first();

        if (! $standard) {
            return response()->json([]);
        }

        $query = $standard->activeSubjects()->orderBy('sort_order');

        $assignedIds = TeacherSubject::query()
            ->where('teacher_id', auth()->id())
            ->pluck('subject_id');

        if ($assignedIds->isNotEmpty()) {
            $query->whereIn('id', $assignedIds);
        }

        return response()->json(
            $query->get(['id', 'name'])
        );
    }

    /**
     * Chapters = materials for the subject (Books chapters), both mediums if allotted.
     */
    public function chapters(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'medium' => ['nullable', 'string'],
        ]);

        $subject = Subject::query()->with('standard')->findOrFail((int) $request->subject_id);
        $mediums = $this->mediumsForSubject((int) $subject->id, $request->string('medium')->toString());

        $chapters = collect();
        foreach ($mediums as $medium) {
            foreach (Material::forStudentSubject($subject, $medium) as $index => $material) {
                $no = $material->displayChapterNo($index + 1);
                $label = trim($no.'. '.$material->displayChapterName());
                if (count($mediums) > 1) {
                    $label .= ' ('.(Standard::MEDIUMS[$medium] ?? ucfirst($medium)).')';
                }
                $chapters->push([
                    'id' => $material->id,
                    'name' => $label,
                    'medium' => $medium,
                ]);
            }
        }

        return response()->json($chapters->unique('id')->values());
    }

    /**
     * Topics = material_topics for the selected material chapter.
     */
    public function topics(Request $request): JsonResponse
    {
        $chapterIds = $this->idList($request, 'chapter_ids', 'chapter_id');

        $request->validate([
            'chapter_id' => ['nullable', 'integer', 'exists:materials,id'],
            'chapter_ids' => ['nullable', 'array'],
            'chapter_ids.*' => ['integer', 'exists:materials,id'],
        ]);

        if ($chapterIds === []) {
            return response()->json([]);
        }

        $materials = Material::query()->whereIn('id', $chapterIds)->get()->keyBy('id');
        $prefixChapter = $materials->count() > 1;

        return response()->json(
            MaterialTopic::query()
                ->whereIn('material_id', $chapterIds)
                ->orderBy('material_id')
                ->orderBy('topic_order')
                ->get(['id', 'material_id', 'title', 'title_gu', 'topic_order', 'generated'])
                ->map(function (MaterialTopic $topic) use ($materials, $prefixChapter) {
                    $name = $topic->displayName();
                    if ($prefixChapter) {
                        $chapter = $materials->get($topic->material_id);
                        $chapterName = $chapter ? $chapter->displayChapterName() : 'Chapter';
                        $name = $chapterName.' — '.$name;
                    }

                    return [
                        'id' => $topic->id,
                        'name' => $name,
                    ];
                })
                ->values()
        );
    }

    public function questionCounts(Request $request): JsonResponse
    {
        $request->validate([
            'chapter_id' => ['nullable', 'integer', 'exists:materials,id'],
            'chapter_ids' => ['nullable', 'array'],
            'chapter_ids.*' => ['integer', 'exists:materials,id'],
            'topic_id' => ['nullable', 'integer', 'exists:material_topics,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:material_topics,id'],
        ]);

        $chapterIds = $this->idList($request, 'chapter_ids', 'chapter_id');
        $topicIds = $this->idList($request, 'topic_ids', 'topic_id');

        if ($chapterIds === []) {
            return response()->json([
                'counts' => [],
                'types' => [],
                'total' => 0,
            ]);
        }

        $materials = Material::query()
            ->with(['topics' => fn ($q) => $q->orderBy('topic_order')])
            ->whereIn('id', $chapterIds)
            ->get();
        $topics = collect();

        if ($topicIds !== []) {
            $topics = MaterialTopic::query()
                ->whereIn('material_id', $materials->pluck('id'))
                ->whereIn('id', $topicIds)
                ->get();
        }

        $scopedTopics = $topics->isEmpty() ? null : $topics;
        $counts = MaterialPaperBank::availableCounts($materials, $scopedTopics);

        $types = collect(PaperTypeHelper::types())->map(fn ($label, $type) => [
            'type' => $type,
            'label' => $label,
            'available' => $counts[$type] ?? 0,
        ])->values();

        $plan = null;
        $planError = '';
        $target = (int) $request->input('target_marks');
        if (in_array($target, SelfExamGeneratorService::MARK_OPTIONS, true)) {
            try {
                $plan = app(SelfExamGeneratorService::class)->buildPlan($materials, $scopedTopics, $target);
            } catch (ValidationException $e) {
                $planError = (string) collect($e->errors())->flatten()->first();
            }
        }

        return response()->json([
            'counts' => $counts,
            'types' => $types,
            'total' => array_sum($counts),
            'plan' => $plan,
            'plan_error' => $planError,
        ]);
    }

    /**
     * @return list<int>
     */
    private function idList(Request $request, string $listKey, string $singleKey): array
    {
        return collect($request->input($listKey, []))
            ->when($request->filled($singleKey), fn ($ids) => $ids->push($request->input($singleKey)))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function mediumsForSubject(int $subjectId, string $requestedMedium): array
    {
        $requested = Material::normalizeMedium($requestedMedium);
        if ($requested && array_key_exists($requested, Standard::MEDIUMS)) {
            return [$requested];
        }

        $fromAllotment = TeacherSubject::query()
            ->where('teacher_id', auth()->id())
            ->where('subject_id', $subjectId)
            ->pluck('medium')
            ->map(fn ($m) => Material::normalizeMedium($m) ?: strtolower(trim((string) $m)))
            ->filter(fn ($m) => array_key_exists((string) $m, Standard::MEDIUMS))
            ->unique()
            ->values()
            ->all();

        return $fromAllotment !== [] ? $fromAllotment : array_keys(Standard::MEDIUMS);
    }
}
