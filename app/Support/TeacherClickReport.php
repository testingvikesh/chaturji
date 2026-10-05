<?php

namespace App\Support;

use App\Models\MaterialTopic;
use App\Models\TeacherSectionClick;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class TeacherClickReport
{
    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<int, int>|null  $allowedTeacherIds  null = every teacher (admin)
     */
    public static function build(Request $request, ?int $forceTeacherId = null, ?array $allowedTeacherIds = null): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->startOfDay() : now()->startOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $teacherId = $forceTeacherId ?: (int) $request->input('teacher_id');
        $search = trim((string) $request->input('search', ''));

        $query = TeacherSectionClick::query()
            ->join('users', 'users.id', '=', 'teacher_section_clicks.teacher_id')
            ->whereDate('teacher_section_clicks.click_date', '>=', $from->toDateString())
            ->whereDate('teacher_section_clicks.click_date', '<=', $to->toDateString());

        if (is_array($allowedTeacherIds)) {
            $allowedTeacherIds = array_values(array_unique(array_map('intval', $allowedTeacherIds)));
            if ($allowedTeacherIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('teacher_section_clicks.teacher_id', $allowedTeacherIds);
            }
            if ($teacherId > 0 && ! in_array($teacherId, $allowedTeacherIds, true)) {
                $query->whereRaw('1 = 0');
            }
        }

        if ($teacherId > 0) {
            $query->where('teacher_section_clicks.teacher_id', $teacherId);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('teacher_section_clicks.subject_name', 'like', "%{$search}%")
                    ->orWhere('teacher_section_clicks.chapter_name', 'like', "%{$search}%")
                    ->orWhere('teacher_section_clicks.topic_name', 'like', "%{$search}%")
                    ->orWhere('teacher_section_clicks.section_label', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        $topicRows = (clone $query)
            ->selectRaw('teacher_section_clicks.teacher_id')
            ->selectRaw('users.name as teacher_name')
            ->selectRaw('teacher_section_clicks.click_date')
            ->selectRaw('teacher_section_clicks.subject_name')
            ->selectRaw('teacher_section_clicks.chapter_name')
            ->selectRaw('teacher_section_clicks.topic_name')
            ->selectRaw('teacher_section_clicks.material_topic_id')
            ->selectRaw('COUNT(*) as clicks')
            ->groupBy(
                'teacher_section_clicks.teacher_id',
                'users.name',
                'teacher_section_clicks.click_date',
                'teacher_section_clicks.subject_id',
                'teacher_section_clicks.subject_name',
                'teacher_section_clicks.chapter_name',
                'teacher_section_clicks.material_topic_id',
                'teacher_section_clicks.topic_name'
            )
            ->orderBy('users.name')
            ->orderByDesc('teacher_section_clicks.click_date')
            ->orderBy('teacher_section_clicks.subject_name')
            ->orderBy('teacher_section_clicks.topic_name')
            ->get()
            ->map(function ($row) {
                $totalSections = self::sectionsForTopic((int) $row->material_topic_id);
                $clicks = (int) $row->clicks;
                if ($totalSections > 0) {
                    $clicks = min($clicks, $totalSections);
                }
                $percent = $totalSections > 0
                    ? (int) round(($clicks / $totalSections) * 100)
                    : 0;

                return (object) [
                    'teacher_name' => $row->teacher_name,
                    'teacher_id' => (int) $row->teacher_id,
                    'click_date' => $row->click_date,
                    'subject_name' => $row->subject_name,
                    'chapter_name' => $row->chapter_name,
                    'topic_name' => $row->topic_name,
                    'clicks' => $clicks,
                    'total_sections' => $totalSections,
                    'work_percent' => $percent,
                ];
            })
            ->values();

        $page = max(1, (int) $request->input('page', 1));
        $rows = new LengthAwarePaginator(
            $topicRows->forPage($page, 40)->values(),
            $topicRows->count(),
            40,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $summary = [
            'clicks' => (int) $topicRows->sum('clicks'),
            'sections' => (int) $topicRows->sum('total_sections'),
            'teachers' => (int) $topicRows->pluck('teacher_id')->unique()->count(),
            'topics' => $topicRows->count(),
            'complete' => (int) $topicRows->where('work_percent', 100)->count(),
        ];

        $byTeacher = $topicRows
            ->groupBy('teacher_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'teacher' => $first->teacher_name,
                    'topics' => $items->count(),
                    'clicks' => (int) $items->sum('clicks'),
                    'sections' => (int) $items->sum('total_sections'),
                    'complete' => (int) $items->where('work_percent', 100)->count(),
                ];
            })
            ->sortBy('teacher')
            ->values();

        $byDate = $topicRows
            ->groupBy(fn ($row) => Carbon::parse($row->click_date)->toDateString())
            ->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'topics' => $items->count(),
                    'clicks' => (int) $items->sum('clicks'),
                    'sections' => (int) $items->sum('total_sections'),
                    'complete' => (int) $items->where('work_percent', 100)->count(),
                ];
            })
            ->sortByDesc('date')
            ->values();

        return [
            'rows' => $rows,
            'summary' => $summary,
            'byTeacher' => $byTeacher,
            'byDate' => $byDate,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'teacher_id' => $teacherId > 0 ? $teacherId : '',
                'search' => $search,
            ],
            'teachers' => $forceTeacherId
                ? collect()
                : User::query()
                    ->where('role', 'teacher')
                    ->when(is_array($allowedTeacherIds), fn ($q) => $q->whereIn('id', $allowedTeacherIds ?: [0]))
                    ->orderBy('name')
                    ->get(['id', 'name']),
        ];
    }

    private static function sectionsForTopic(int $topicId): int
    {
        static $cache = [];

        if ($topicId <= 0) {
            return 0;
        }
        if (! array_key_exists($topicId, $cache)) {
            $topic = MaterialTopic::query()->find($topicId);
            $cache[$topicId] = $topic ? TeacherSectionClickRecorder::sectionTotal($topic) : 0;
        }

        return $cache[$topicId];
    }
}
