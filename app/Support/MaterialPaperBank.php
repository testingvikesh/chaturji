<?php

namespace App\Support;

use App\Models\ChapterQuestion;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Standard;
use App\Models\Subject;
use App\Support\MaterialWorkedExamples;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Self Exam / Self Homework options & question bank from materials + material_topics.
 */
class MaterialPaperBank
{
    /**
     * Subjects with material chapters (and material topics) for the create form.
     *
     * @return Collection<int, Subject>
     */
    public static function subjectsForStudent(?Standard $standard, ?string $medium): Collection
    {
        if (! $standard) {
            return collect();
        }

        return Material::subjectsForStudent($standard, $medium)
            ->map(function (Subject $subject) use ($medium) {
                $materials = Material::forStudentSubject($subject, $medium);

                $subject->setRelation('paperMaterials', $materials);
                $subject->setAttribute('chapters_count', $materials->count());

                return $subject;
            })
            ->filter(fn (Subject $subject) => $subject->paperMaterials->isNotEmpty())
            ->values();
    }

    /**
     * Payload for Alpine selfPaperBuilder (materials as chapters).
     *
     * @param  Collection<int, Subject>  $subjects
     * @return list<array{id:int,name:string,chapters:list<array{id:int,name:string,topics:list<array{id:int,name:string}>}>}>
     */
    public static function alpineSubjects(Collection $subjects): array
    {
        return $subjects->map(function (Subject $subject) {
            $materials = $subject->relationLoaded('paperMaterials')
                ? $subject->paperMaterials
                : collect();

            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'chapters' => $materials->map(function (Material $material, int $index) {
                    $no = $material->displayChapterNo($index + 1);

                    return [
                        'id' => $material->id,
                        'name' => trim($no.'. '.$material->displayChapterName()),
                        'topics' => ($material->topics ?? collect())
                            ->map(fn (MaterialTopic $topic) => [
                                'id' => $topic->id,
                                'name' => $topic->displayName(),
                                'material_id' => $material->id,
                            ])
                            ->values()
                            ->all(),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    private static function chapterExamplesRoute(string $topicRoute): ?string
    {
        return match ($topicRoute) {
            'student.material-topics.show' => 'student.materials.show',
            'teacher.books.topics.show' => 'teacher.books.materials.show',
            'student.self-practice.material-topics.show' => 'student.self-practice.materials.show',
            default => null,
        };
    }

    /**
     * Subject → material chapter → ready topic tree for the material reader change bar.
     *
     * @return list<array{id:int,name:string,url:string,chapters:list<array{id:int,name:string,url:string,topics:list<array{id:int,name:string,url:string}>}>}>
     */
    public static function readerNavTree(?Standard $standard, ?string $medium, string $topicRoute = 'student.material-topics.show', array $topicRouteExtra = []): array
    {
        if (! $standard) {
            return [];
        }

        return Material::subjectsForStudent($standard, $medium)
            ->map(function (Subject $subject) use ($medium, $topicRoute, $topicRouteExtra) {
                $chapters = Material::forStudentSubject($subject, $medium)
                    ->map(function (Material $material, int $index) use ($subject, $topicRoute, $topicRouteExtra) {
                        $topics = ($material->topics ?? collect())
                            ->filter(fn (MaterialTopic $topic) => $topic->hasContent())
                            ->values()
                            ->map(fn (MaterialTopic $topic) => [
                                'id' => $topic->id,
                                'name' => $topic->displayName(),
                                'url' => route($topicRoute, array_merge([
                                    'subject' => $subject,
                                    'materialTopic' => $topic,
                                ], $topicRouteExtra)),
                            ])
                            ->all();

                        $examples = MaterialWorkedExamples::isExampleSubject($subject)
                            ? MaterialWorkedExamples::fromMaterial($material)
                            : collect();
                        $chapterRoute = self::chapterExamplesRoute($topicRoute);

                        if ($topics === [] && $examples->isEmpty()) {
                            return null;
                        }

                        $no = $material->displayChapterNo($index + 1);
                        $chapterUrl = ($examples->isNotEmpty() && $chapterRoute)
                            ? route($chapterRoute, array_merge([
                                'subject' => $subject,
                                'material' => $material,
                            ], $topicRouteExtra))
                            : ($topics[0]['url'] ?? null);

                        if (! $chapterUrl) {
                            return null;
                        }

                        return [
                            'id' => $material->id,
                            'name' => trim($no.'. '.$material->displayChapterName()),
                            'url' => $chapterUrl,
                            'topics' => $topics,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                if ($chapters === []) {
                    return null;
                }

                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'url' => $chapters[0]['url'],
                    'chapters' => $chapters,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<int|string>|null  $materialIds
     * @return Collection<int, Material>
     */
    public static function resolveMaterials(Subject $subject, ?string $medium, ?array $materialIds): Collection
    {
        $ids = collect($materialIds ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'chapter_ids' => 'Select at least one chapter.',
            ]);
        }

        $allowed = Material::forStudentSubject($subject, $medium)
            ->keyBy('id');

        $materials = collect();
        foreach ($ids as $id) {
            $material = $allowed->get($id);
            if (! $material) {
                throw ValidationException::withMessages([
                    'chapter_ids' => 'One or more selected chapters are invalid for this subject.',
                ]);
            }
            if (! $material->relationLoaded('topics')) {
                $material->load(['topics' => fn ($q) => $q->orderBy('topic_order')]);
            }
            $materials->push($material);
        }

        return $materials->values();
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  list<int|string>|null  $materialTopicIds  Empty = all topics in selected materials
     * @return Collection<int, MaterialTopic>
     */
    public static function resolveTopics(Collection $materials, ?array $materialTopicIds): Collection
    {
        $materialIds = $materials->pluck('id')->map(fn ($id) => (int) $id)->all();
        $ids = collect($materialTopicIds ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $topics = MaterialTopic::query()
            ->whereIn('material_id', $materialIds)
            ->whereIn('id', $ids->all())
            ->orderBy('topic_order')
            ->get();

        if ($topics->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'topic_ids' => 'One or more selected topics are invalid for the selected chapters.',
            ]);
        }

        return $topics;
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, MaterialTopic>|null  $topics  Empty = all topics in materials
     * @return Collection<int, ChapterQuestion>
     */
    public static function questionPool(Collection $materials, ?Collection $topics = null): Collection
    {
        $topics = $topics ?? collect();
        $pool = collect();

        if ($topics->isNotEmpty()) {
            $scopeByMaterial = $topics->groupBy('material_id');
            foreach ($materials as $material) {
                $scoped = $scopeByMaterial->get($material->id, collect());
                $pool = $pool->concat(self::topicQuestionPool($scoped));
            }

            return $pool->values();
        }

        foreach ($materials as $material) {
            $allTopics = $material->relationLoaded('topics')
                ? $material->topics
                : $material->topics()->orderBy('topic_order')->get();
            $pool = $pool->concat(self::topicQuestionPool($allTopics));
        }

        return $pool->values();
    }

    /**
     * @param  Collection<int, MaterialTopic>  $topics
     * @return Collection<int, ChapterQuestion>
     */
    private static function topicQuestionPool(Collection $topics): Collection
    {
        $pool = collect();

        foreach ($topics as $materialTopic) {
            if (! $materialTopic instanceof MaterialTopic || ! $materialTopic->hasContent()) {
                continue;
            }

            try {
                $payload = MaterialTopicReader::forTopic($materialTopic);
            } catch (\Throwable) {
                continue;
            }

            foreach ($payload['questions'] as $question) {
                if (! $question instanceof ChapterQuestion) {
                    continue;
                }
                if (! array_key_exists((string) $question->question_type, PaperTypeHelper::types())) {
                    continue;
                }
                $pool->push($question);
            }
        }

        return $pool;
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, MaterialTopic>|null  $topics
     * @return array<string, int>
     */
    public static function availableCounts(Collection $materials, ?Collection $topics = null): array
    {
        $counts = array_fill_keys(array_keys(PaperTypeHelper::types()), 0);

        foreach (self::questionPool($materials, $topics)->groupBy('question_type') as $type => $group) {
            if (array_key_exists($type, $counts)) {
                $counts[$type] = $group->count();
            }
        }

        return $counts;
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, MaterialTopic>|null  $topics
     * @param  array<string, int>  $typeCounts
     * @param  array<string, int>  $marksPerType
     * @return array{
     *     questions: Collection<int, ChapterQuestion>,
     *     grouped: Collection<string, Collection<int, ChapterQuestion>>,
     *     breakdown: array,
     *     total_marks: int,
     *     marks_per_type: array<string, int>
     * }
     */
    public static function generate(
        Collection $materials,
        ?Collection $topics,
        array $typeCounts,
        array $marksPerType = []
    ): array {
        if ($materials->isEmpty()) {
            throw ValidationException::withMessages([
                'chapter_ids' => 'Select at least one chapter.',
            ]);
        }

        if ($typeCounts === []) {
            throw ValidationException::withMessages([
                'type_counts' => 'Enter at least one question quantity.',
            ]);
        }

        $pool = self::questionPool($materials, $topics);
        $available = self::availableCounts($materials, $topics);
        $selected = collect();
        $usedKeys = [];

        foreach ($typeCounts as $type => $count) {
            if (! array_key_exists($type, PaperTypeHelper::types()) || $count <= 0) {
                continue;
            }

            if ($count > ($available[$type] ?? 0)) {
                throw ValidationException::withMessages([
                    "type_counts.{$type}" => PaperTypeHelper::label($type).' has only '.($available[$type] ?? 0).' question(s) available, but you requested '.$count.'.',
                ]);
            }

            $picked = $pool
                ->where('question_type', $type)
                ->filter(function (ChapterQuestion $question) use ($usedKeys) {
                    $key = $question->question_type.'|'.md5((string) $question->question_text);

                    return ! isset($usedKeys[$key]);
                })
                ->shuffle()
                ->take($count)
                ->values();

            foreach ($picked as $question) {
                $usedKeys[$question->question_type.'|'.md5((string) $question->question_text)] = true;
            }

            $selected = $selected->concat($picked);
        }

        $marksPerType = PaperTypeHelper::normalizeMarksPerType($marksPerType, $typeCounts);

        return [
            'questions' => $selected->values(),
            'grouped' => PaperTypeHelper::groupInPaperOrder($selected),
            'breakdown' => PaperTypeHelper::breakdown($typeCounts, $marksPerType),
            'total_marks' => PaperTypeHelper::totalMarks($typeCounts, $marksPerType),
            'marks_per_type' => $marksPerType,
        ];
    }

    public static function chapterLabel(Collection $materials): string
    {
        if ($materials->isEmpty()) {
            return 'No chapter';
        }

        if ($materials->count() === 1) {
            return $materials->first()->displayChapterName();
        }

        return $materials->count().' chapters';
    }

    public static function topicLabel(Collection $topics): string
    {
        if ($topics->isEmpty()) {
            return 'All topics';
        }

        if ($topics->count() === 1) {
            return $topics->first()->displayName();
        }

        return $topics->count().' topics';
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, MaterialTopic>|null  $topics
     * @param  array<string, int|string|null>  $typeCounts
     * @return array{
     *     ok: bool,
     *     error: ?string,
     *     available: array<string, int>,
     *     type_counts: array<string, int>,
     *     marks_per_type: array<string, int>,
     *     breakdown: array,
     *     total_marks: int,
     *     total_questions: int,
     *     questions: list<array{type:string,label:string,text:string,marks:int}>
     * }
     */
    public static function previewPaper(
        Collection $materials,
        ?Collection $topics,
        array $typeCounts,
        int $targetMarks
    ): array {
        $topics = $topics ?? collect();
        $available = self::availableCounts($materials, $topics);
        $normalized = PaperTypeHelper::normalizeCounts($typeCounts);
        $marksPerType = PaperTypeHelper::normalizeMarksPerType(
            TeachingHomeworkLayout::marksPerType(),
            $normalized
        );

        if ($materials->isEmpty()) {
            return [
                'ok' => false,
                'error' => 'Select at least one chapter before preview.',
                'available' => $available,
                'type_counts' => [],
                'marks_per_type' => $marksPerType,
                'breakdown' => [],
                'sections' => [],
                'total_marks' => 0,
                'total_questions' => 0,
                'questions' => [],
            ];
        }

        if ($normalized === []) {
            return [
                'ok' => false,
                'error' => 'Set at least one question in "In this paper" before preview.',
                'available' => $available,
                'type_counts' => [],
                'marks_per_type' => $marksPerType,
                'breakdown' => [],
                'sections' => [],
                'total_marks' => 0,
                'total_questions' => 0,
                'questions' => [],
            ];
        }

        $errors = [];
        foreach ($normalized as $type => $count) {
            $have = (int) ($available[$type] ?? 0);
            if ($count > $have) {
                $errors[] = PaperTypeHelper::label($type).": need {$count}, available {$have}.";
            }
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'error' => 'Not enough questions available for this paper plan. '.implode(' ', $errors),
                'available' => $available,
                'type_counts' => $normalized,
                'marks_per_type' => $marksPerType,
                'breakdown' => PaperTypeHelper::breakdown($normalized, $marksPerType),
                'sections' => [],
                'total_marks' => PaperTypeHelper::totalMarks($normalized, $marksPerType),
                'total_questions' => PaperTypeHelper::totalQuestions($normalized),
                'questions' => [],
            ];
        }

        try {
            $generated = self::generate($materials, $topics, $normalized, $marksPerType);
        } catch (ValidationException $e) {
            return [
                'ok' => false,
                'error' => collect($e->errors())->flatten()->first() ?: 'Could not build paper preview.',
                'available' => $available,
                'type_counts' => $normalized,
                'marks_per_type' => $marksPerType,
                'breakdown' => [],
                'sections' => [],
                'total_marks' => 0,
                'total_questions' => 0,
                'questions' => [],
            ];
        }

        $marks = $generated['marks_per_type'];
        $questionNo = 0;
        $sections = [];
        $flatQuestions = [];

        foreach ($generated['grouped'] as $type => $typeQuestions) {
            $items = [];
            foreach ($typeQuestions as $question) {
                $questionNo++;
                $payload = self::serializePreviewQuestion($question, $questionNo, $marks);
                $items[] = $payload;
                $flatQuestions[] = $payload;
            }

            $sections[] = [
                'type' => (string) $type,
                'label' => PaperTypeHelper::sectionLabel((string) $type),
                'icon' => PaperTypeHelper::sectionIcon((string) $type),
                'count' => count($items),
                'marks' => (int) ($marks[$type] ?? 1),
                'subtotal' => count($items) * (int) ($marks[$type] ?? 1),
                'questions' => $items,
            ];
        }

        $breakdown = collect($generated['breakdown'])->map(function (array $row) {
            return [
                'type' => $row['type'],
                'label' => $row['label'],
                'icon' => PaperTypeHelper::icon((string) $row['type']),
                'count' => (int) $row['count'],
                'marks' => (int) ($row['marks'] ?? 1),
                'subtotal' => (int) ($row['subtotal'] ?? 0),
            ];
        })->values()->all();

        return [
            'ok' => true,
            'error' => null,
            'available' => $available,
            'type_counts' => $normalized,
            'marks_per_type' => $marks,
            'breakdown' => $breakdown,
            'sections' => $sections,
            'total_marks' => $generated['total_marks'],
            'total_questions' => count($flatQuestions),
            'target_marks' => $targetMarks,
            'chapter_name' => self::chapterLabel($materials),
            'topic_label' => self::topicLabel($topics),
            'questions' => $flatQuestions,
        ];
    }

    /**
     * @param  array<string, int>  $marksPerType
     * @return array{
     *     no:int,
     *     type:string,
     *     label:string,
     *     text:string,
     *     marks:int,
     *     options:list<array{key:string,text:string}>,
     *     match: ?array{column_a:list<string>,column_b:list<string>}
     * }
     */
    private static function serializePreviewQuestion(ChapterQuestion $question, int $number, array $marksPerType): array
    {
        $type = (string) $question->question_type;
        $rawOptions = $question->options;
        if (is_string($rawOptions)) {
            $rawOptions = json_decode($rawOptions, true);
        }

        $match = null;
        $options = [];

        if (is_array($rawOptions) && (isset($rawOptions['column_a']) || isset($rawOptions['column_b']))) {
            $match = [
                'column_a' => array_values(array_map('strval', $rawOptions['column_a'] ?? [])),
                'column_b' => array_values(array_map('strval', $rawOptions['column_b'] ?? [])),
            ];
        } elseif (is_array($rawOptions)) {
            $options = ObjectiveOptionHelper::entries($rawOptions, $type);
        }

        return [
            'no' => $number,
            'type' => $type,
            'label' => PaperTypeHelper::label($type),
            'text' => (string) $question->question_text,
            'marks' => (int) ($marksPerType[$type] ?? 1),
            'options' => $options,
            'match' => $match,
        ];
    }
}
