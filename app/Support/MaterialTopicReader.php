<?php

namespace App\Support;

use App\Models\ChapterContentSection;
use App\Models\ChapterQuestion;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Services\MaterialJsonImporter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class MaterialTopicReader
{
    /**
     * Build reader-ready payload from a materials.material_topics row.
     *
     * @return array{
     *     content: object,
     *     sections: Collection,
     *     questions: Collection,
     *     questionGroups: array<string, Collection>,
     *     questionGroupLabels: Collection,
     *     workedExamples: Collection
     * }
     */
    public static function forTopic(MaterialTopic $materialTopic): array
    {
        $section = $materialTopic->sectionData();

        if ($section === null) {
            throw new \InvalidArgumentException('This material topic has no content yet.');
        }

        $material = $materialTopic->relationLoaded('material')
            ? $materialTopic->material
            : $materialTopic->material()->first();

        $language = strtolower((string) ($material?->medium ?: 'english'));
        if (! in_array($language, ['english', 'hindi', 'gujarati'], true)) {
            $language = 'english';
        }

        try {
            $cached = self::rememberParsed($materialTopic, $material, $language, $section);

            return self::hydrate($materialTopic, $material, $cached);
        } catch (Throwable $e) {
            report($e);

            // Never blank the whole page if parse/cache fails — show a recoverable empty reader.
            return self::hydrate($materialTopic, $material, [
                'language' => $language,
                'title' => $materialTopic->displayName(),
                'total_questions' => 0,
                'sections' => [],
                'questions' => [],
                'workedExamples' => [],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array{language:string,title:string,total_questions:int,sections:list<array>,questions:list<array>,workedExamples:list<array>}
     */
    private static function rememberParsed(
        MaterialTopic $materialTopic,
        ?Material $material,
        string $language,
        array $section
    ): array {
        $version = optional($materialTopic->updated_at)?->getTimestamp() ?? 0;
        $cacheKey = 'material-topic-reader:v2:'.$materialTopic->id.':'.$version;

        try {
            $cached = Cache::get($cacheKey);
            if (self::isValidCache($cached)) {
                return $cached;
            }
        } catch (Throwable $e) {
            report($e);
        }

        $built = self::buildParsed($materialTopic, $language, $section);

        try {
            // Skip caching oversized payloads (can fail / corrupt on shared hosts).
            $approx = strlen(json_encode($built, JSON_UNESCAPED_UNICODE) ?: '');
            if ($approx > 0 && $approx < 1500000) {
                Cache::put($cacheKey, $built, 900);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $built;
    }

    /**
     * @param  mixed  $cached
     */
    private static function isValidCache($cached): bool
    {
        return is_array($cached)
            && isset($cached['sections'], $cached['questions'], $cached['language'])
            && is_array($cached['sections'])
            && is_array($cached['questions']);
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array{language:string,title:string,total_questions:int,sections:list<array>,questions:list<array>,workedExamples:list<array>}
     */
    private static function buildParsed(MaterialTopic $materialTopic, string $language, array $section): array
    {
        $parsed = app(MaterialJsonImporter::class)->parse(
            [
                'meta' => [
                    'title' => $materialTopic->displayName(),
                    'language' => $language,
                ],
                'sections' => [$section],
            ],
            $materialTopic->displayName(),
            $language
        );

        return [
            'language' => $language,
            'title' => $materialTopic->displayName(),
            'total_questions' => (int) ($parsed['total_questions'] ?? count($parsed['questions'] ?? [])),
            'sections' => array_values($parsed['sections'] ?? []),
            'questions' => array_values($parsed['questions'] ?? []),
            'workedExamples' => MaterialWorkedExamples::fromTopic($materialTopic)->values()->all(),
        ];
    }

    /**
     * @param  array{language:string,title?:string,total_questions:int,sections:list<array>,questions:list<array>,workedExamples?:list<array>}  $cached
     * @return array{
     *     content: object,
     *     sections: Collection,
     *     questions: Collection,
     *     questionGroups: array<string, Collection>,
     *     questionGroupLabels: Collection,
     *     workedExamples: Collection
     * }
     */
    private static function hydrate(MaterialTopic $materialTopic, ?Material $material, array $cached): array
    {
        $sections = collect($cached['sections'] ?? [])->values()->map(function (array $row, int $index) {
            $section = new ChapterContentSection([
                'section_type' => $row['section_type'] ?? 'paragraph',
                'title' => $row['title'] ?? null,
                'content' => $row['content'] ?? '',
                'sort_order' => $row['sort_order'] ?? ($index + 1),
            ]);
            $section->id = $index + 1;

            return $section;
        });

        $questions = collect($cached['questions'] ?? [])->map(function (array $row) {
            return new ChapterQuestion([
                'question_type' => $row['question_type'] ?? 'short_answer',
                'question_text' => $row['question_text'] ?? '',
                'options' => $row['options'] ?? null,
                'answer' => is_array($row['answer'] ?? null)
                    ? json_encode($row['answer'], JSON_UNESCAPED_UNICODE)
                    : ($row['answer'] ?? null),
                'metadata' => $row['metadata'] ?? null,
                'sort_order' => $row['sort_order'] ?? 0,
            ]);
        });

        $questionGroups = ChapterMaterialHelper::groupQuestions($questions);
        $questionGroupLabels = collect($questionGroups)->mapWithKeys(
            fn ($items, $type) => [$type => ChapterQuestion::labelForType((string) $type)]
        );

        return [
            'content' => self::contentProxy(
                $materialTopic,
                $material,
                (string) ($cached['language'] ?? 'english'),
                (int) ($cached['total_questions'] ?? $questions->count())
            ),
            'sections' => $sections,
            'questions' => $questions,
            'questionGroups' => $questionGroups,
            'questionGroupLabels' => $questionGroupLabels,
            'workedExamples' => collect($cached['workedExamples'] ?? []),
        ];
    }

    /**
     * Flatten section content into textbook / summary modal points.
     *
     * @param  \Illuminate\Support\Collection<int, mixed>  $sections
     * @return \Illuminate\Support\Collection<int, array{section: string, text: string, anchor: string, section_id: int|string|null}>
     */
    public static function textbookPoints(Collection $sections): Collection
    {
        return $sections
            ->flatMap(function ($section) {
                if (! is_object($section) || ! method_exists($section, 'contentPoints')) {
                    return [];
                }

                $points = $section->contentPoints();
                if ($points === [] && filled($section->content ?? null)) {
                    $points = [trim((string) $section->content)];
                }

                $sectionId = $section->id ?? null;
                $sectionTitle = method_exists($section, 'displayTitle')
                    ? $section->displayTitle()
                    : ($section->title ?? 'Point');

                return collect($points)->values()->map(function ($point, $index) use ($sectionId, $sectionTitle) {
                    $n = $index + 1;

                    return [
                        'section' => $sectionTitle,
                        'text' => $point,
                        'anchor' => $sectionId ? 'point-'.$sectionId.'-'.$n : 'section-gks',
                        'section_id' => $sectionId,
                    ];
                });
            })
            ->values();
    }

    private static function contentProxy(MaterialTopic $topic, ?Material $material, string $language, int $totalQuestions): object
    {
        return new class($topic, $material, $language, $totalQuestions)
        {
            public function __construct(
                public MaterialTopic $topic,
                public ?Material $material,
                public string $language,
                public int $total_questions,
            ) {
                $this->title = $topic->displayName();
                $this->id = $topic->id;
                $this->original_pdf_path = null;
                $this->original_pdf_filename = null;
            }

            public string $title;

            public int $id;

            public ?string $original_pdf_path;

            public ?string $original_pdf_filename;

            public function languageLabel(): string
            {
                return \App\Models\ChapterContent::LANGUAGES[$this->language]
                    ?? ucfirst($this->language);
            }

            public function hasOriginalPdf(): bool
            {
                return false;
            }
        };
    }
}
