<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Models\Topic;
use App\Support\PaperTypeHelper;
use App\Services\QuestionPaperGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    public function __construct(
        private readonly QuestionPaperGeneratorService $generator
    ) {}

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

    public function chapters(Request $request): JsonResponse
    {
        $request->validate(['subject_id' => ['required', 'integer']]);

        return response()->json(
            Chapter::query()
                ->where('subject_id', $request->subject_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name'])
        );
    }

    public function topics(Request $request): JsonResponse
    {
        $request->validate(['chapter_id' => ['required', 'integer']]);

        return response()->json(
            Topic::query()
                ->where('chapter_id', $request->chapter_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name'])
        );
    }

    public function questionCounts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
        ]);

        $counts = $this->generator->availableCounts(
            (int) $validated['chapter_id'],
            isset($validated['topic_id']) ? (int) $validated['topic_id'] : null
        );

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
}
