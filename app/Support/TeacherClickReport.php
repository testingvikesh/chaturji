<?php

namespace App\Support;

use App\Models\TeacherSectionClick;
use App\Models\User;
use Illuminate\Http\Request;
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

        $grouped = (clone $query)
            ->selectRaw('teacher_section_clicks.teacher_id')
            ->selectRaw('users.name as teacher_name')
            ->selectRaw('teacher_section_clicks.click_date')
            ->selectRaw('teacher_section_clicks.subject_name')
            ->selectRaw('teacher_section_clicks.chapter_name')
            ->selectRaw('teacher_section_clicks.topic_name')
            ->selectRaw('teacher_section_clicks.section_label')
            ->selectRaw('MAX(teacher_section_clicks.points) as total_points')
            ->selectRaw('SUM(teacher_section_clicks.clicks) as total_clicks')
            ->groupBy(
                'teacher_section_clicks.teacher_id',
                'users.name',
                'teacher_section_clicks.click_date',
                'teacher_section_clicks.subject_id',
                'teacher_section_clicks.subject_name',
                'teacher_section_clicks.chapter_name',
                'teacher_section_clicks.material_topic_id',
                'teacher_section_clicks.topic_name',
                'teacher_section_clicks.section_key',
                'teacher_section_clicks.section_label'
            )
            ->orderBy('users.name')
            ->orderByDesc('teacher_section_clicks.click_date')
            ->orderBy('teacher_section_clicks.subject_name')
            ->orderBy('teacher_section_clicks.topic_name')
            ->orderBy('teacher_section_clicks.section_label');

        $rows = $grouped->paginate(40)->withQueryString();

        $summaryBase = (clone $query);
        $summary = [
            'clicks' => (int) (clone $summaryBase)->sum('teacher_section_clicks.clicks'),
            'points' => (int) (clone $summaryBase)->sum('teacher_section_clicks.points'),
            'teachers' => (int) (clone $summaryBase)->distinct()->count('teacher_section_clicks.teacher_id'),
            'topics' => (int) (clone $summaryBase)->distinct()->count('teacher_section_clicks.material_topic_id'),
        ];

        $byTeacher = (clone $query)
            ->selectRaw('users.name as teacher_name')
            ->selectRaw('SUM(teacher_section_clicks.points) as total_points')
            ->selectRaw('SUM(teacher_section_clicks.clicks) as total_clicks')
            ->groupBy('teacher_section_clicks.teacher_id', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->map(fn ($row) => [
                'teacher' => $row->teacher_name,
                'total_points' => (int) $row->total_points,
                'total_clicks' => (int) $row->total_clicks,
            ]);

        $byDate = (clone $query)
            ->selectRaw('teacher_section_clicks.click_date')
            ->selectRaw('SUM(teacher_section_clicks.points) as total_points')
            ->selectRaw('SUM(teacher_section_clicks.clicks) as total_clicks')
            ->groupBy('teacher_section_clicks.click_date')
            ->orderByDesc('teacher_section_clicks.click_date')
            ->get()
            ->map(fn ($row) => [
                'date' => Carbon::parse($row->click_date)->toDateString(),
                'total_points' => (int) $row->total_points,
                'total_clicks' => (int) $row->total_clicks,
            ]);

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
}
