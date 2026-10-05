<?php

namespace App\Support;

use App\Models\ChapterContentSection;
use App\Models\MaterialTopic;
use App\Models\Subject;
use App\Models\TeacherSectionClick;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherSectionClickRecorder
{
    public static function record(
        User $teacher,
        Subject $subject,
        ?object $chapter,
        MaterialTopic $materialTopic,
        string $sectionKey
    ): TeacherSectionClick {
        $payload = MaterialTopicReader::forTopic($materialTopic);
        $described = self::describe($payload, $sectionKey);
        $chapterId = (int) ($chapter->id ?? 0);
        $chapterName = trim((string) ($chapter->name ?? ''));
        if ($chapterName === '' && $materialTopic->relationLoaded('material')) {
            $chapterName = (string) ($materialTopic->material?->displayChapterName() ?? '');
        }

        return DB::transaction(function () use ($teacher, $subject, $materialTopic, $sectionKey, $described, $chapterId, $chapterName, $payload) {
            $keys = [
                'teacher_id' => $teacher->id,
                'click_date' => now()->toDateString(),
                'material_topic_id' => $materialTopic->id,
                'section_key' => $sectionKey,
            ];

            $row = TeacherSectionClick::query()->where($keys)->lockForUpdate()->first();
            $attributes = [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'chapter_id' => $chapterId > 0 ? $chapterId : null,
                'chapter_name' => $chapterName !== '' ? $chapterName : null,
                'topic_name' => $materialTopic->displayName(),
                'section_label' => $described['label'],
                'points' => $described['points'],
                'topic_points' => self::topicPoints($payload),
            ];

            if (! $row) {
                return TeacherSectionClick::query()->create($keys + $attributes + ['clicks' => 1]);
            }

            $row->fill($attributes);
            $row->clicks = 1;
            $row->save();

            return $row;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{points: int, label: string}
     */
    public static function describe(array $payload, string $sectionKey): array
    {
        $sections = collect($payload['sections'] ?? []);
        $groups = $payload['questionGroups'] ?? [];
        $labels = collect($payload['questionGroupLabels'] ?? []);

        if ($sectionKey === 'section-gks') {
            $points = $sections
                ->filter(fn ($section) => $section instanceof ChapterContentSection && $section->isGksSection())
                ->sum(fn ($section) => self::sectionPoints($section));

            return ['points' => (int) $points, 'label' => ChapterMaterialHelper::GKS_PILL_LABEL];
        }

        if ($sectionKey === 'worked-examples') {
            $examples = collect($payload['workedExamples'] ?? []);

            return ['points' => $examples->count(), 'label' => 'Examples ('.$examples->count().')'];
        }

        if (str_starts_with($sectionKey, 'questions-')) {
            $type = substr($sectionKey, strlen('questions-'));
            $items = $groups[$type] ?? collect();
            $count = $items instanceof Collection ? $items->count() : (is_countable($items) ? count($items) : 0);
            $label = (string) ($labels[$type] ?? $type);

            return ['points' => $count, 'label' => $label.' ('.$count.')'];
        }

        if (preg_match('/^section-(\d+)$/', $sectionKey, $match)) {
            $section = $sections->first(fn ($row) => (int) ($row->id ?? 0) === (int) $match[1]);
            if ($section instanceof ChapterContentSection) {
                $title = method_exists($section, 'displayTitle')
                    ? $section->displayTitle()
                    : ($section->title ?: 'Section');

                return ['points' => self::sectionPoints($section), 'label' => (string) $title];
            }
        }

        return ['points' => 0, 'label' => 'Section'];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function topicPoints(array $payload): int
    {
        $sections = collect($payload['sections'] ?? []);
        $sectionPoints = $sections->sum(fn ($section) => $section instanceof ChapterContentSection ? self::sectionPoints($section) : 0);
        $groups = collect($payload['questionGroups'] ?? []);
        $questionPoints = $groups->sum(fn ($items) => $items instanceof Collection ? $items->count() : (is_countable($items) ? count($items) : 0));
        $examples = collect($payload['workedExamples'] ?? [])->count();

        return (int) $sectionPoints + (int) $questionPoints + (int) $examples;
    }

    private static function sectionPoints(ChapterContentSection $section): int
    {
        $points = count($section->contentPoints());
        if ($points === 0 && trim((string) $section->content) !== '') {
            return 1;
        }

        return $points;
    }
}
