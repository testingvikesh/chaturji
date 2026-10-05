<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Support\MaterialPaperBank;
use App\Support\PaperTypeHelper;
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
        $request->validate([
            'chapter_id' => ['required', 'integer', 'exists:materials,id'],
        ]);

        $material = Material::query()->findOrFail((int) $request->chapter_id);

        return response()->json(
            MaterialTopic::query()
                ->where('material_id', $material->id)
                ->orderBy('topic_order')
                ->get(['id', 'title', 'title_gu', 'topic_order', 'generated'])
                ->map(fn (MaterialTopic $topic) => [
                    'id' => $topic->id,
                    'name' => $topic->displayName(),
                ])
                ->values()
        );
    }

    public function questionCounts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chapter_id' => ['required', 'integer', 'exists:materials,id'],
            'topic_id' => ['nullable', 'integer', 'exists:material_topics,id'],
        ]);

        $material = Material::query()->with(['topics' => fn ($q) => $q->orderBy('topic_order')])->findOrFail((int) $validated['chapter_id']);
        $materials = collect([$material]);
        $topics = collect();

        if (! empty($validated['topic_id'])) {
            $topic = MaterialTopic::query()
                ->where('material_id', $material->id)
                ->whereKey((int) $validated['topic_id'])
                ->first();
            if ($topic) {
                $topics = collect([$topic]);
            }
        }

        $counts = MaterialPaperBank::availableCounts($materials, $topics->isEmpty() ? null : $topics);

        $types = collect(PaperTypeHelper::types())->map(fn ($label, $type) => [
            'type' => $type,
            'label' => $label,
            'available' => $counts[$type] ?? 0,
        ])->values();

        return response()->json([
            'counts' => $counts,
            'types' => $types,
            'total' => array_sum($counts),
        ]);
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
