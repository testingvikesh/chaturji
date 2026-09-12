<?php

namespace App\Support;

use App\Models\Material;
use App\Models\Standard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdminMaterialUploadReport
{
    public function build(?string $standardFilter = null, ?string $subjectFilter = null, string $sort = 'standard', string $dir = 'asc', ?string $mediumFilter = null): array
    {
        $standardNames = $this->standardNames();
        $topicStats = $this->topicStats();

        $materials = Material::query()
            ->select([
                'id',
                'medium',
                'standard',
                'subject',
                'chapter_no',
                'chapter_name',
                'title',
                'status',
                'topics_total',
                'topics_done',
                'material_attachment',
            ])
            ->get();

        $rows = $materials->map(fn (Material $material) => $this->chapterRow($material, $standardNames, $topicStats));

        $mediumFilter = Material::normalizeMedium($mediumFilter) ?: 'all';
        $standardFilter = trim((string) $standardFilter);
        $subjectFilter = trim((string) $subjectFilter);

        $filtered = $rows
            ->when($mediumFilter !== 'all', fn (Collection $set) => $set->filter(
                fn (array $row) => $row['medium_key'] === $mediumFilter
            )->values())
            ->when($standardFilter !== '' && $standardFilter !== 'all', fn (Collection $set) => $set->filter(
                fn (array $row) => (string) $row['standard_key'] === $standardFilter
            )->values())
            ->when($subjectFilter !== '' && $subjectFilter !== 'all', fn (Collection $set) => $set->filter(
                fn (array $row) => mb_strtolower((string) $row['subject']) === mb_strtolower($subjectFilter)
            )->values());

        $standardOptions = $rows
            ->map(fn (array $row) => [
                'key' => (string) $row['standard_key'],
                'name' => (string) $row['standard'],
                'sort' => (int) $row['standard_sort'],
            ])
            ->unique('key')
            ->sortBy('sort')
            ->values();

        $subjectOptions = $rows
            ->pluck('subject')
            ->filter(fn ($name) => $name !== '—')
            ->unique(fn ($name) => mb_strtolower((string) $name))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return [
            'standards' => $standardOptions,
            'standard_filter' => $standardFilter !== '' ? $standardFilter : 'all',
            'subject_filter' => $subjectFilter !== '' ? $subjectFilter : 'all',
            'medium_filter' => $mediumFilter,
            'subjects' => $subjectOptions,
            'totals' => [
                'all' => $filtered->count(),
                'english' => $filtered->where('medium_key', 'english')->count(),
                'gujarati' => $filtered->where('medium_key', 'gujarati')->count(),
                'complete' => $filtered->where('status', 'complete')->count(),
                'partial' => $filtered->where('status', 'partial')->count(),
                'planned' => $filtered->where('status', 'planned')->count(),
                'draft' => $filtered->whereIn('status', ['draft', 'planned'])->count(),
                'with_pdf' => $filtered->where('has_pdf', true)->count(),
                'topics' => (int) $filtered->sum('topics_count'),
                'topics_ready' => (int) $filtered->sum('topics_ready'),
                'subjects' => $filtered->unique(fn (array $row) => $row['medium_key'].'|'.$row['standard_key'].'|'.$row['subject_key'])->count(),
                'standards' => $filtered->unique(fn (array $row) => $row['medium_key'].'|'.$row['standard_key'])->count(),
            ],
            'tree' => $this->toTree($filtered),
            'rows' => $filtered,
            'sort' => 'standard',
            'dir' => 'asc',
            'english' => collect(),
            'gujarati' => collect(),
        ];
    }

    /**
     * @return Collection<string, string>
     */
    private function standardNames(): Collection
    {
        try {
            $standards = Standard::query()->orderBy('sort_order')->orderBy('name')->get();
        } catch (Throwable $e) {
            $standards = Standard::query()->orderBy('name')->get();
        }

        return $standards->mapWithKeys(function (Standard $standard) {
            $key = Material::standardNumber($standard);

            return $key !== '' ? [$key => $standard->name] : [];
        });
    }

    /**
     * @return array<int, array{topics_count:int, topics_ready:int}>
     */
    private function topicStats(): array
    {
        try {
            $rows = DB::table('material_topics')
                ->select('material_id')
                ->selectRaw('COUNT(*) as topics_count')
                ->selectRaw('SUM(CASE WHEN generated = 1 THEN 1 ELSE 0 END) as topics_ready')
                ->groupBy('material_id')
                ->get();
        } catch (Throwable $e) {
            return [];
        }

        $stats = [];
        foreach ($rows as $row) {
            $stats[(int) $row->material_id] = [
                'topics_count' => (int) $row->topics_count,
                'topics_ready' => (int) $row->topics_ready,
            ];
        }

        return $stats;
    }

    /**
     * @param  Collection<int, string>  $standardNames
     * @param  array<int, array{topics_count:int, topics_ready:int}>  $topicStats
     * @return array<string, mixed>
     */
    private function chapterRow(Material $material, Collection $standardNames, array $topicStats): array
    {
        $mediumKey = Material::normalizeMedium($material->medium) ?? 'other';
        $standardRaw = trim((string) $material->standard);
        $standardKey = preg_replace('/\D+/', '', $standardRaw) ?: ($standardRaw !== '' ? mb_strtolower($standardRaw) : '—');
        $chapterNo = trim((string) $material->chapter_no);
        $chapterNoSort = (int) preg_replace('/\D+/', '', $chapterNo);
        $subject = trim((string) $material->subject);
        $stats = $topicStats[(int) $material->id] ?? null;

        return [
            'id' => $material->id,
            'medium' => $mediumKey === 'other' ? ($material->medium ?: 'Other') : ucfirst($mediumKey),
            'medium_key' => $mediumKey,
            'standard' => $standardNames[$standardKey] ?? ($standardRaw !== '' ? 'Std '.$standardRaw : '—'),
            'standard_key' => (string) $standardKey,
            'standard_sort' => (int) preg_replace('/\D+/', '', (string) $standardKey) ?: 9999,
            'subject' => $subject !== '' ? $subject : '—',
            'subject_key' => $subject !== '' ? mb_strtolower($subject) : '—',
            'chapter_no' => $chapterNo !== '' ? $chapterNo : '—',
            'chapter_no_sort' => $chapterNoSort > 0 ? $chapterNoSort : PHP_INT_MAX,
            'chapter_name' => $material->displayChapterName(),
            'status' => $material->status ?: 'draft',
            'topics_count' => $stats['topics_count'] ?? (int) ($material->topics_total ?? 0),
            'topics_ready' => $stats['topics_ready'] ?? (int) ($material->topics_done ?? 0),
            'topics' => [],
            'has_pdf' => filled($material->material_attachment),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function toTree(Collection $rows): array
    {
        $mediumOrder = ['english' => 1, 'gujarati' => 2, 'hindi' => 3];

        return $rows
            ->groupBy('medium_key')
            ->sortBy(fn ($group, $key) => $mediumOrder[$key] ?? 99)
            ->map(function (Collection $mediumRows, string $mediumKey) {
                $standards = $mediumRows
                    ->groupBy('standard_key')
                    ->sortBy(fn (Collection $group) => (int) ($group->first()['standard_sort'] ?? 9999))
                    ->map(function (Collection $standardRows) {
                        $subjects = $standardRows
                            ->groupBy('subject_key')
                            ->sortBy(fn (Collection $group) => mb_strtolower((string) ($group->first()['subject'] ?? '')))
                            ->map(function (Collection $subjectRows) {
                                $chapters = $subjectRows
                                    ->sortBy(fn (array $row) => sprintf('%010d|%s', $row['chapter_no_sort'], mb_strtolower((string) $row['chapter_name'])))
                                    ->values()
                                    ->all();

                                return [
                                    'name' => $subjectRows->first()['subject'],
                                    'chapters_count' => $subjectRows->count(),
                                    'topics_count' => (int) $subjectRows->sum('topics_count'),
                                    'topics_ready' => (int) $subjectRows->sum('topics_ready'),
                                    'chapters' => $chapters,
                                ];
                            })
                            ->values()
                            ->all();

                        return [
                            'key' => $standardRows->first()['standard_key'],
                            'name' => $standardRows->first()['standard'],
                            'subjects_count' => count($subjects),
                            'chapters_count' => $standardRows->count(),
                            'topics_count' => (int) $standardRows->sum('topics_count'),
                            'topics_ready' => (int) $standardRows->sum('topics_ready'),
                            'subjects' => $subjects,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'key' => $mediumKey,
                    'name' => $mediumRows->first()['medium'],
                    'standards_count' => count($standards),
                    'subjects_count' => $mediumRows->unique(
                        fn (array $row) => $row['standard_key'].'|'.$row['subject_key']
                    )->count(),
                    'chapters_count' => $mediumRows->count(),
                    'topics_count' => (int) $mediumRows->sum('topics_count'),
                    'topics_ready' => (int) $mediumRows->sum('topics_ready'),
                    'standards' => $standards,
                ];
            })
            ->values()
            ->all();
    }
}
