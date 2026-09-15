<?php

namespace App\Models;

use App\Services\S3ObjectService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Material extends Model
{
    protected $table = 'materials';

    protected $fillable = [
        'chapter_id',
        'user_id',
        'slug',
        'title',
        'medium',
        'standard',
        'subject',
        'chapter_no',
        'chapter_name',
        'status',
        'topics_total',
        'topics_done',
        'material_json_name',
        'material_json',
        'html_name',
        'material_attachment',
    ];

    protected $casts = [
        'topics_total' => 'integer',
        'topics_done' => 'integer',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(MaterialTopic::class)->orderBy('topic_order');
    }

    public function generatedTopics(): HasMany
    {
        return $this->topics()->where('generated', true)->whereNotNull('section_json');
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function scopePreferred($query)
    {
        return $query
            ->orderByRaw("FIELD(status, 'complete', 'partial', 'draft')")
            ->orderByDesc('topics_done')
            ->orderByDesc('id');
    }

    public function scopeForSubject($query, Subject $subject)
    {
        $chapterIds = $subject->relationLoaded('chapters')
            ? $subject->chapters->pluck('id')
            : $subject->chapters()->pluck('id');
        $standard = $subject->relationLoaded('standard')
            ? $subject->standard
            : $subject->standard()->first();
        $subjectName = mb_strtolower(trim($subject->name));

        return $query
            ->where(function ($q) use ($subjectName, $chapterIds) {
                $q->whereRaw('LOWER(TRIM(subject)) = ?', [$subjectName]);

                if ($chapterIds->isNotEmpty()) {
                    $q->orWhere(function ($inner) use ($chapterIds, $subjectName) {
                        $inner->whereIn('chapter_id', $chapterIds)
                            ->where(function ($subjectCol) use ($subjectName) {
                                $subjectCol->whereNull('subject')
                                    ->orWhere('subject', '')
                                    ->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectName]);
                            });
                    });
                }
            })
            ->when($standard !== null, function ($q) use ($standard) {
                self::applyStandardFilter($q, $standard);
            });
    }

    public function scopeForMedium($query, ?string $medium)
    {
        $normalized = self::normalizeMedium($medium);

        if ($normalized === null) {
            return $query;
        }

        return $query->whereRaw('LOWER(TRIM(medium)) = ?', [$normalized]);
    }

    public static function normalizeMedium(?string $medium): ?string
    {
        $medium = mb_strtolower(trim((string) $medium));

        if ($medium === '') {
            return null;
        }

        return match (true) {
            str_contains($medium, 'gujar') => 'gujarati',
            str_contains($medium, 'hind') => 'hindi',
            str_contains($medium, 'eng') => 'english',
            default => $medium,
        };
    }

    public static function forStudentSubject(Subject $subject, ?string $medium): Collection
    {
        $mediumKey = self::normalizeMedium($medium) ?? 'any';
        // v3: slim columns only (never pull material_json / attachments into the chapter index).
        $cacheKey = 'materials:for-subject:v3:'.$subject->id.':'.$mediumKey;

        try {
            $cached = Cache::get($cacheKey);
            if ($cached instanceof Collection) {
                return $cached;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if (! $subject->relationLoaded('standard')) {
            $subject->load('standard');
        }

        $materials = self::sortAsChapters(
            self::uniqueChapters(
                self::query()
                    ->forSubject($subject)
                    ->forMedium($medium)
                    ->select([
                        'id',
                        'chapter_id',
                        'slug',
                        'title',
                        'medium',
                        'standard',
                        'subject',
                        'chapter_no',
                        'chapter_name',
                        'status',
                        'topics_total',
                        'topics_done',
                        'updated_at',
                    ])
                    ->with(['topics' => function ($q) {
                        // Index only needs titles — never touch section_json here.
                        $q->orderBy('topic_order')
                            ->select([
                                'id',
                                'material_id',
                                'topic_order',
                                'topic_key',
                                'title',
                                'title_gu',
                                'generated',
                                'image_url',
                                'updated_at',
                            ])
                            ->where('generated', true);
                    }])
                    ->get()
            )
        );

        try {
            // Slim payload should fit; skip cache if serialization fails on shared hosts.
            Cache::put($cacheKey, $materials, 600);
        } catch (\Throwable $e) {
            report($e);
        }

        return $materials;
    }

    public static function forgetStudentSubjectCache(int $subjectId, ?string $medium = null): void
    {
        $mediums = $medium !== null
            ? [self::normalizeMedium($medium) ?? 'any']
            : ['any', 'english', 'hindi', 'gujarati'];

        foreach ($mediums as $mediumKey) {
            Cache::forget('materials:for-subject:'.$subjectId.':'.$mediumKey);
            Cache::forget('materials:for-subject:v3:'.$subjectId.':'.$mediumKey);
        }
    }

    public static function standardNumber(?Standard $standard): string
    {
        return preg_replace('/\D+/', '', (string) ($standard?->slug ?: $standard?->name ?: '')) ?: '';
    }

    public static function applyStandardFilter($query, ?Standard $standard)
    {
        $number = self::standardNumber($standard);

        if ($number === '') {
            return $query;
        }

        return $query->where(function ($inner) use ($number) {
            $inner->where('standard', $number)
                ->orWhereRaw('LOWER(TRIM(standard)) IN (?, ?, ?, ?)', [
                    'std '.$number,
                    'std-'.$number,
                    'standard '.$number,
                    'std. '.$number,
                ]);
        });
    }

    /**
     * @return array{english_subjects:int, gujarati_subjects:int, chapters:int, topics:int, topics_ready:int}
     */
    public static function reportCountsForStandard(Standard $standard): array
    {
        $chapterQuery = fn (?string $medium) => self::query()
            ->forMedium($medium)
            ->tap(fn ($query) => self::applyStandardFilter($query, $standard))
            ->whereNotNull('subject')
            ->whereRaw("TRIM(subject) <> ''");

        $englishSubjects = (clone $chapterQuery('english'))
            ->selectRaw('COUNT(DISTINCT LOWER(TRIM(subject))) as total')
            ->value('total');
        $gujaratiSubjects = (clone $chapterQuery('gujarati'))
            ->selectRaw('COUNT(DISTINCT LOWER(TRIM(subject))) as total')
            ->value('total');

        $materialIds = self::query()
            ->tap(fn ($query) => self::applyStandardFilter($query, $standard))
            ->pluck('id');

        $topics = MaterialTopic::query()->whereIn('material_id', $materialIds);
        $topicsReady = (clone $topics)
            ->where('generated', true)
            ->whereNotNull('section_json')
            ->whereRaw("TRIM(section_json) <> ''")
            ->count();

        return [
            'english_subjects' => (int) $englishSubjects,
            'gujarati_subjects' => (int) $gujaratiSubjects,
            'chapters' => $materialIds->count(),
            'topics' => $materialIds->isEmpty() ? 0 : (clone $topics)->count(),
            'topics_ready' => $materialIds->isEmpty() ? 0 : $topicsReady,
        ];
    }

    /**
     * Distinct subjects from the materials table for this standard + medium.
     *
     * @return Collection<int, Subject>
     */
    public static function subjectsForStudent(?Standard $standard, ?string $medium): Collection
    {
        if (! $standard) {
            return collect();
        }

        $mediumKey = self::normalizeMedium($medium) ?? 'any';
        $cacheKey = 'materials:subjects-for-student:v1:'.$standard->id.':'.$mediumKey;

        try {
            $cached = Cache::get($cacheKey);
            if ($cached instanceof Collection) {
                return $cached;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $number = self::standardNumber($standard);

        $rows = self::query()
            ->forMedium($medium)
            ->when($number !== '', function ($query) use ($number) {
                $query->where(function ($inner) use ($number) {
                    $inner->where('standard', $number)
                        ->orWhereRaw('LOWER(TRIM(standard)) = ?', ['std '.$number])
                        ->orWhereRaw('LOWER(TRIM(standard)) = ?', ['std-'.$number]);
                });
            })
            ->whereNotNull('subject')
            ->whereRaw("TRIM(subject) <> ''")
            ->selectRaw(
                'MAX(TRIM(subject)) as subject_name, LOWER(TRIM(subject)) as subject_key, '
                .'COUNT(DISTINCT IF(TRIM(IFNULL(chapter_no, \'\')) = \'\', CONCAT(\'id-\', id), TRIM(chapter_no))) as chapters_count'
            )
            ->groupByRaw('LOWER(TRIM(subject))')
            ->orderByRaw('LOWER(TRIM(subject))')
            ->get();

        $subjects = $rows->map(function ($row) use ($standard) {
            $name = trim((string) $row->subject_name);
            $subject = self::matchOrCreateSubject($standard, $name);
            $subject->setAttribute('chapters_count', (int) $row->chapters_count);

            return $subject;
        })->values();

        try {
            Cache::put($cacheKey, $subjects, 600);
        } catch (\Throwable $e) {
            report($e);
        }

        return $subjects;
    }

    public static function matchOrCreateSubject(Standard $standard, string $name): Subject
    {
        $name = trim($name);
        $key = mb_strtolower($name);

        $subject = Subject::query()
            ->where('standard_id', $standard->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$key])
            ->first();

        if ($subject) {
            if (! $subject->is_active) {
                $subject->is_active = true;
                $subject->save();
            }
            $subject->setRelation('standard', $standard);

            return $subject;
        }

        $baseSlug = Str::slug($name) ?: 'subject';
        $slug = $baseSlug;
        $suffix = 2;
        while (Subject::query()->where('standard_id', $standard->id)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        $subject = Subject::query()->create([
            'standard_id' => $standard->id,
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => ((int) Subject::query()->where('standard_id', $standard->id)->max('sort_order')) + 1,
        ]);
        $subject->setRelation('standard', $standard);

        return $subject;
    }

    public function scopeOrderedAsChapters($query)
    {
        return $query->orderBy('id');
    }

    public function displayChapterNo(int $fallback = 1): string
    {
        $no = trim((string) $this->chapter_no);

        return $no !== '' ? $no : (string) $fallback;
    }

    public function displayChapterName(): string
    {
        return $this->chapter_name
            ?: $this->title
            ?: ('Chapter '.$this->displayChapterNo());
    }

    public function matchesStudentSubject(Subject $subject): bool
    {
        if (strcasecmp(trim((string) $this->subject), trim($subject->name)) === 0) {
            return true;
        }

        $chapter = $this->relationLoaded('chapter') ? $this->chapter : $this->chapter()->first();

        return $chapter
            && (int) $chapter->subject_id === (int) $subject->id
            && $chapter->is_active
            && in_array(trim((string) $this->subject), ['', $subject->name], true);
    }

    public function readyTopicsCount(): int
    {
        if ($this->relationLoaded('topics')) {
            return $this->topics->filter(fn (MaterialTopic $topic) => $topic->hasContent())->count();
        }

        return $this->generatedTopics()->count();
    }

    public function hasTextbookPdf(): bool
    {
        return $this->textbookPdfAbsolutePath() !== null
            || $this->textbookPdfPublicUrl() !== null;
    }

    public function textbookPdfFilename(): string
    {
        $path = trim((string) $this->material_attachment);
        if ($path === '') {
            return 'textbook.pdf';
        }

        return basename(str_replace('\\', '/', $path)) ?: 'textbook.pdf';
    }

    /**
     * Public URL on the Material Generator host (e.g. https://gseschaturji.xyz/...).
     */
    public function textbookPdfPublicUrl(): ?string
    {
        $raw = trim((string) $this->material_attachment);
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $raw);

        if (preg_match('#^https?://#i', $normalized)) {
            return $normalized;
        }

        $base = rtrim((string) config('materials.public_url', ''), '/');
        if ($base === '') {
            return null;
        }

        return $base.'/'.ltrim($normalized, '/');
    }

    public function textbookPdfAbsolutePath(): ?string
    {
        $raw = trim((string) $this->material_attachment);
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $raw);
        $filename = basename($normalized);
        $relative = ltrim(preg_replace('#^public/#i', '', $normalized), '/');
        $uploadsRelative = ltrim(preg_replace('#^(public/)?uploads/#i', '', $normalized), '/');

        $uploadsPath = rtrim(str_replace('\\', '/', (string) config('materials.uploads_path', public_path('uploads'))), '/');
        $basePath = rtrim(str_replace('\\', '/', (string) (config('materials.base_path') ?: '')), '/');

        $candidates = [
            $normalized,
            $uploadsPath.'/'.$filename,
            $uploadsPath.'/'.$uploadsRelative,
            public_path($relative),
            public_path('uploads/'.$filename),
            base_path($normalized),
            base_path($relative),
            storage_path('app/'.$relative),
            storage_path('app/public/'.$relative),
        ];

        if ($basePath !== '') {
            $candidates[] = $basePath.'/'.$normalized;
            $candidates[] = $basePath.'/'.$relative;
            $candidates[] = $basePath.'/public/uploads/'.$filename;
            $candidates[] = $basePath.'/uploads/'.$filename;
        }

        foreach (array_unique(array_filter($candidates)) as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function s3ObjectKey(): ?string
    {
        $url = $this->textbookPdfPublicUrl();
        if ($url === null) {
            return null;
        }

        return S3ObjectService::fromConfig()?->parseUrl($url)['key'] ?? null;
    }

    public function s3PagesPrefix(): ?string
    {
        $key = $this->s3ObjectKey();
        if ($key === null) {
            return null;
        }

        $dir = trim(str_replace('\\', '/', dirname($key)), '/.');
        if ($dir === '') {
            return null;
        }

        return $dir.'/pages/';
    }

    /**
     * Page images stored on the same S3 chapter folder as tools (`.../pages/page-001.jpg`).
     *
     * @return Collection<int, array{page: int, label: string, key: string, file: string, topic_id: int|null}>
     */
    public function textbookPageImages(): Collection
    {
        return Cache::remember('material-textbook-pages-'.$this->id, 600, function () {
            $pages = collect();

            $topics = $this->relationLoaded('topics')
                ? $this->topics
                : $this->topics()->orderBy('topic_order')->get([
                    'id',
                    'material_id',
                    'topic_order',
                    'title',
                    'title_gu',
                    'image_url',
                ]);

            foreach ($topics as $topic) {
                $url = trim((string) ($topic->image_url ?? ''));
                if ($url === '') {
                    continue;
                }

                $parsed = S3ObjectService::fromConfig()?->parseUrl($url);
                $key = $parsed['key'] ?? null;
                if ($key === null) {
                    continue;
                }

                $pages->push([
                    'page' => (int) $topic->topic_order,
                    'label' => $topic->displayName(),
                    'key' => $key,
                    'file' => basename($key),
                    'topic_id' => (int) $topic->id,
                ]);
            }

            if ($pages->isNotEmpty()) {
                return $pages->sortBy('page')->values();
            }

            $service = S3ObjectService::fromConfig();
            $prefix = $this->s3PagesPrefix();
            if ($service === null || $prefix === null) {
                return $pages;
            }

            foreach ($service->listKeys($prefix) as $key) {
                if (! preg_match('/\.(jpe?g|png|webp|gif)$/i', $key)) {
                    continue;
                }

                $file = basename($key);
                $number = preg_match('/(\d+)/', $file, $match) ? (int) $match[1] : 0;
                $pages->push([
                    'page' => $number,
                    'label' => $number > 0 ? 'Page '.$number : $file,
                    'key' => $key,
                    'file' => $file,
                    'topic_id' => null,
                ]);
            }

            return $pages->sortBy('page')->values();
        });
    }

    public function resolveTextbookPageKey(string $page): ?string
    {
        $page = ltrim(str_replace(['..', '\\'], ['', '/'], $page), '/');
        if ($page === '') {
            return null;
        }

        $images = $this->textbookPageImages();

        if (ctype_digit($page)) {
            $match = $images->firstWhere('page', (int) $page);

            return is_array($match) ? ($match['key'] ?? null) : null;
        }

        $base = basename($page);
        $match = $images->first(function (array $image) use ($base) {
            return ($image['file'] ?? '') === $base || basename((string) ($image['key'] ?? '')) === $base;
        });
        if (is_array($match)) {
            return $match['key'] ?? null;
        }

        $prefix = $this->s3PagesPrefix();
        if ($prefix && preg_match('/^page-\d+\.(jpe?g|png|webp|gif)$/i', $base)) {
            return $prefix.$base;
        }

        return null;
    }

    public static function uniqueChapters(Collection $materials): Collection
    {
        return $materials
            ->sortByDesc(function (self $material) {
                $topics = $material->relationLoaded('topics')
                    ? $material->topics->count()
                    : (int) ($material->topics_done ?? 0);

                return sprintf('%010d|%010d', $topics, $material->id);
            })
            ->unique(function (self $material) {
                $no = trim((string) $material->chapter_no);

                if ($no !== '') {
                    return 'no:'.mb_strtolower($no);
                }

                $name = trim((string) ($material->chapter_name ?: $material->title ?: ''));

                return $name !== '' ? 'name:'.mb_strtolower($name) : 'id:'.$material->id;
            })
            ->values();
    }

    public static function sortAsChapters(Collection $materials): Collection
    {
        return $materials
            ->sortBy(function (self $material) {
                $number = (int) preg_replace('/\D+/', '', (string) $material->chapter_no);

                return sprintf(
                    '%010d|%s|%010d',
                    $number > 0 ? $number : 999999999,
                    (string) $material->chapter_no,
                    $material->id
                );
            })
            ->values();
    }
}
