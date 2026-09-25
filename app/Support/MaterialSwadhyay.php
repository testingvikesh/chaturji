<?php

namespace App\Support;

use App\Models\ChapterQuestion;
use App\Models\Material;
use App\Models\MaterialTopic;
use Illuminate\Support\Collection;

class MaterialSwadhyay
{
    /** Lesson drills stay on the topic page. સ્વાધ્યાય is the chapter exercise. */
    private const LESSON_TYPES = [
        'knowledge_ladder',
        'line_to_line',
        'preview_que',
    ];

    /**
     * @return array{questions: Collection, groups: array<string, Collection>, labels: Collection}
     */
    public static function forMaterial(Material $material): array
    {
        $topics = $material->relationLoaded('topics')
            ? $material->topics
            : $material->topics()->orderBy('topic_order')->get();

        $questions = collect();

        foreach ($topics as $topic) {
            if (! $topic instanceof MaterialTopic || ! $topic->generated) {
                continue;
            }

            if (! $topic->sectionData()) {
                continue;
            }

            try {
                $payload = MaterialTopicReader::forTopic($topic);
            } catch (\Throwable $e) {
                report($e);
                continue;
            }

            foreach ($payload['questions'] ?? [] as $question) {
                $questions->push($question);
            }
        }

        $exercise = $questions->reject(
            fn ($question) => in_array((string) $question->question_type, self::LESSON_TYPES, true)
        )->values();

        if ($exercise->isEmpty()) {
            $exercise = $questions->values();
        }

        $groups = ChapterMaterialHelper::groupQuestions($exercise);
        $labels = collect($groups)->mapWithKeys(
            fn ($items, $type) => [$type => ChapterQuestion::labelForType((string) $type)]
        );

        return [
            'questions' => $exercise,
            'groups' => $groups,
            'labels' => $labels,
        ];
    }
}
