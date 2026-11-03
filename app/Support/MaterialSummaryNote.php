<?php

namespace App\Support;

use App\Models\Material;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MaterialSummaryNote
{
    /**
     * Chapter summary note generated on the material host.
     *
     * @return array{
     *     meta: array<string, string>,
     *     labels: array<string, string>,
     *     pages: list<array<string, mixed>>,
     *     fills: list<array<string, mixed>>,
     *     close: array<string, mixed>
     * }
     */
    public static function forMaterial(?Material $material): array
    {
        $empty = self::empty();
        if (! $material) {
            return $empty;
        }

        $filename = self::filename($material);
        if ($filename === null) {
            return $empty;
        }

        $cacheKey = 'material-summary-note:v1:'.$material->id.':'.$filename;

        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && isset($cached['pages'], $cached['fills'], $cached['close']['revision_chain'], $cached['close']['quick_revision'])) {
                return $cached;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $note = self::load($filename) ?? $empty;

        try {
            Cache::put($cacheKey, $note, ($note['pages'] !== [] || $note['fills'] !== []) ? 600 : 60);
        } catch (\Throwable $e) {
            report($e);
        }

        return $note;
    }

    public static function filename(Material $material): ?string
    {
        $name = trim((string) $material->material_json_name);
        if ($name !== '') {
            $slug = preg_replace('/-material-ai\.json$/i', '', $name) ?? $name;
            $slug = preg_replace('/\.json$/i', '', $slug) ?? $slug;
            $slug = trim($slug);

            return $slug !== '' ? $slug.'-summary-note.json' : null;
        }

        $medium = Material::normalizeMedium($material->medium);
        $standard = preg_replace('/\D+/', '', (string) $material->standard) ?: '';
        $subject = strtolower(trim((string) $material->subject));
        $subject = preg_replace('/[^a-z0-9]+/', '', $subject) ?? '';
        $chapter = preg_replace('/\D+/', '', (string) $material->chapter_no) ?: '';

        if ($medium === null || $standard === '' || $subject === '' || $chapter === '') {
            return null;
        }

        return "{$medium}-std{$standard}-{$subject}-ch{$chapter}-summary-note.json";
    }

    public static function rich(string $value): string
    {
        $safe = e($value);

        return preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $safe) ?? $safe;
    }

    /**
     * @return array{meta: array<string, string>, labels: array<string, string>, pages: list<array<string, mixed>>, fills: list<array<string, mixed>>, close: array<string, mixed>}
     */
    private static function empty(): array
    {
        return [
            'meta' => [],
            'labels' => self::labels('gu'),
            'pages' => [],
            'fills' => [],
            'close' => [
                'revision_chain' => [],
                'quick_revision' => [],
                'memory_trick' => '',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function labels(string $language): array
    {
        $language = strtolower($language);
        if (str_starts_with($language, 'en')) {
            return [
                'main' => 'Main points',
                'remember' => 'Remember',
                'exam' => 'Exam-important words',
                'glance' => 'Whole page in one look',
                'trick' => 'Child Memory Trick',
                'fill' => 'FILL IN THE BLANKS REVISION',
                'seeAnswer' => 'See answer',
                'chain' => 'SUPER REVISION CHAIN',
            ];
        }
        if (str_starts_with($language, 'hi')) {
            return [
                'main' => 'मुख्य बात',
                'remember' => 'याद रखो',
                'exam' => 'EXAM के लिए बहुत महत्वपूर्ण शब्द',
                'glance' => 'एक नजर में पूरा पेज',
                'trick' => 'Child Memory Trick',
                'fill' => 'FILL IN THE BLANKS REVISION',
                'seeAnswer' => 'उत्तर देखें',
                'chain' => 'SUPER REVISION CHAIN',
            ];
        }

        return [
            'main' => 'મુખ્ય વાત',
            'remember' => 'યાદ રાખો',
            'exam' => 'EXAM માટે ખૂબ જ મહત્વના શબ્દો',
            'glance' => 'એક નજરમાં આખું પાનું',
            'trick' => 'Child Memory Trick',
            'fill' => 'FILL IN THE BLANKS REVISION',
            'seeAnswer' => 'જવાબ જુઓ',
            'chain' => 'SUPER REVISION CHAIN',
        ];
    }

    /**
     * @return array{meta: array<string, string>, labels: array<string, string>, pages: list<array<string, mixed>>, fills: list<array<string, mixed>>, close: array<string, mixed>}|null
     */
    private static function load(string $filename): ?array
    {
        $base = rtrim((string) config('materials.public_url', ''), '/');
        if ($base === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)->acceptJson()->get($base.'/output/'.$filename);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? self::parse($json) : null;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array{meta: array<string, string>, labels: array<string, string>, pages: list<array<string, mixed>>, fills: list<array<string, mixed>>, close: array<string, mixed>}
     */
    public static function parse(array $json): array
    {
        $meta = is_array($json['meta'] ?? null) ? $json['meta'] : [];
        $language = (string) ($meta['language'] ?? 'gu');
        $labels = self::labels($language);
        $pages = [];
        $fills = [];

        foreach ($json['pages'] ?? [] as $page) {
            if (! is_array($page)) {
                continue;
            }

            $topics = [];
            foreach ($page['topics'] ?? [] as $topic) {
                if (! is_array($topic)) {
                    continue;
                }
                $points = self::lines($topic['points'] ?? []);
                $name = trim((string) ($topic['name'] ?? ''));
                if ($name === '' || $points === []) {
                    continue;
                }
                $topics[] = [
                    'emoji' => trim((string) ($topic['emoji'] ?? '📚')) ?: '📚',
                    'name' => $name,
                    'points' => $points,
                    'remember' => trim((string) ($topic['remember'] ?? '')),
                ];
            }

            $fillTopics = [];
            $fill = is_array($page['fill_blanks'] ?? null) ? $page['fill_blanks'] : [];
            foreach ($fill['topics'] ?? [] as $topic) {
                if (! is_array($topic)) {
                    continue;
                }
                $questions = [];
                foreach ($topic['questions'] ?? [] as $question) {
                    if (! is_array($question)) {
                        continue;
                    }
                    $blank = trim((string) ($question['blank'] ?? $question['question'] ?? ''));
                    $answer = trim((string) ($question['answer'] ?? ''));
                    if ($blank === '') {
                        continue;
                    }
                    $questions[] = [
                        'n' => (int) ($question['n'] ?? (count($questions) + 1)),
                        'blank' => $blank,
                        'answer' => $answer,
                    ];
                }
                if ($questions === []) {
                    continue;
                }
                $fillTopics[] = [
                    'emoji' => trim((string) ($topic['emoji'] ?? '📌')) ?: '📌',
                    'name' => trim((string) ($topic['name'] ?? '')),
                    'questions' => $questions,
                ];
            }

            $pageNo = trim((string) ($page['page'] ?? ''));
            if ($topics !== [] || trim((string) ($page['unclear'] ?? '')) !== '') {
                $pages[] = [
                    'page' => $pageNo,
                    'topics' => $topics,
                    'exam_words' => self::lines($page['exam_words'] ?? []),
                    'glance' => self::lines($page['glance'] ?? []),
                    'memory_trick' => trim((string) ($page['memory_trick'] ?? '')),
                    'trick_note' => trim((string) ($page['trick_note'] ?? '')),
                    'unclear' => trim((string) ($page['unclear'] ?? '')),
                ];
            }
            if ($fillTopics !== []) {
                $fills[] = [
                    'page' => $pageNo,
                    'topics' => $fillTopics,
                    'revision_chain' => self::lines($fill['revision_chain'] ?? []),
                ];
            }
        }

        $closeRaw = is_array($json['fill_close'] ?? null) ? $json['fill_close'] : [];

        return [
            'meta' => [
                'standard' => trim((string) ($meta['standard'] ?? '')),
                'subject' => trim((string) ($meta['subject'] ?? '')),
                'chapter_no' => trim((string) ($meta['chapter_no'] ?? '')),
                'chapter_name' => trim((string) ($meta['chapter_name'] ?? $meta['title'] ?? '')),
                'language' => $language,
            ],
            'labels' => $labels,
            'pages' => $pages,
            'fills' => $fills,
            'close' => [
                'revision_chain' => self::lines($closeRaw['revision_chain'] ?? []),
                'quick_revision' => self::lines($closeRaw['quick_revision'] ?? []),
                'memory_trick' => trim((string) ($closeRaw['memory_trick'] ?? '')),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private static function lines(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $lines = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $lines[] = $item;
            }
        }

        return $lines;
    }
}
