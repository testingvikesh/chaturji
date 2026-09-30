<?php

namespace App\Support;

use App\Models\Material;
use App\Models\TeacherSubject;
use App\Models\TeachingLog;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Collection;

class PrincipalSyllabusProgressReport
{
    /**
     * Teacher → subjects → topics complete / remain with percentages (allotted standards).
     *
     * @param  array{medium?: string, standard_id?: int|null, teacher_id?: int|null, search?: string}  $filters
     * @return array{rows: Collection, teachers: Collection, summary: array<string, int|float>, filters: array<string, mixed>}
     */
    public function build(User $principal, array $filters = []): array
    {
        $allotted = $principal->allottedStandards()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get(['standards.id', 'standards.name', 'standards.slug', 'standards.medium']);

        $allottedIds = $allotted->pluck('id')->map(fn ($id) => (int) $id)->all();
        $slugById = $allotted->pluck('slug', 'id');
        $nameById = $allotted->pluck('name', 'id');

        $medium = Material::normalizeMedium($filters['medium'] ?? '') ?: '';
        $standardId = ! empty($filters['standard_id']) ? (int) $filters['standard_id'] : null;
        if ($standardId && ! in_array($standardId, $allottedIds, true)) {
            $standardId = null;
        }
        $teacherId = ! empty($filters['teacher_id']) ? (int) $filters['teacher_id'] : null;
        $search = trim((string) ($filters['search'] ?? ''));

        $empty = [
            'rows' => collect(),
            'teachers' => collect(),
            'summary' => [
                'teachers' => 0,
                'subjects' => 0,
                'topics_total' => 0,
                'topics_complete' => 0,
                'topics_remain' => 0,
                'percent_complete' => 0,
                'percent_remain' => 0,
            ],
            'filters' => [
                'medium' => $medium,
                'standard_id' => $standardId ? (string) $standardId : '',
                'teacher_id' => $teacherId ? (string) $teacherId : '',
                'search' => $search,
            ],
            'standards' => $allotted,
            'teacherOptions' => collect(),
            'hasAllotments' => $allottedIds !== [],
        ];

        if ($allottedIds === []) {
            return $empty;
        }

        $assignmentQuery = TeacherSubject::query()
            ->with([
                'teacher:id,name,mobile,email',
                'subject:id,name,standard_id',
                'standard:id,name,slug,medium',
            ])
            ->whereIn('standard_id', $allottedIds)
            ->whereHas('teacher', fn ($q) => $q->where('role', 'teacher'));

        if ($medium !== '') {
            $assignmentQuery->whereRaw('LOWER(TRIM(medium)) = ?', [$medium]);
        }
        if ($standardId) {
            $assignmentQuery->where('standard_id', $standardId);
        }
        if ($teacherId) {
            $assignmentQuery->where('teacher_id', $teacherId);
        }
        if ($search !== '') {
            $assignmentQuery->whereHas('teacher', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $assignments = $assignmentQuery
            ->orderBy('teacher_id')
            ->orderBy('standard_id')
            ->orderBy('subject_id')
            ->get();

        $teacherOptions = User::teachers()
            ->whereIn('id', TeacherSubject::query()->whereIn('standard_id', $allottedIds)->select('teacher_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($assignments->isEmpty()) {
            $empty['teacherOptions'] = $teacherOptions;

            return $empty;
        }

        $subjectIds = $assignments->pluck('subject_id')->unique()->map(fn ($id) => (int) $id)->values()->all();
        $teacherIds = $assignments->pluck('teacher_id')->unique()->map(fn ($id) => (int) $id)->values()->all();

        $topicTotals = Topic::query()
            ->where('topics.is_active', true)
            ->join('chapters', 'chapters.id', '=', 'topics.chapter_id')
            ->where('chapters.is_active', true)
            ->whereIn('chapters.subject_id', $subjectIds)
            ->groupBy('chapters.subject_id')
            ->selectRaw('chapters.subject_id, COUNT(topics.id) as total')
            ->pluck('total', 'subject_id');

        $completedByTeacherSubject = TeachingLog::query()
            ->where('status', TeachingLog::STATUS_COMPLETED)
            ->whereIn('teacher_id', $teacherIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereNotNull('topic_id')
            ->groupBy('teacher_id', 'subject_id')
            ->selectRaw('teacher_id, subject_id, COUNT(DISTINCT topic_id) as completed')
            ->get()
            ->keyBy(fn ($row) => $row->teacher_id.'|'.$row->subject_id);

        // Also count remain/partial marked topics for display (optional insight).
        $remainLoggedByTeacherSubject = TeachingLog::query()
            ->whereIn('status', [TeachingLog::STATUS_REMAINING, TeachingLog::STATUS_PARTIAL])
            ->whereIn('teacher_id', $teacherIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereNotNull('topic_id')
            ->groupBy('teacher_id', 'subject_id')
            ->selectRaw('teacher_id, subject_id, COUNT(DISTINCT topic_id) as remain_logged')
            ->get()
            ->keyBy(fn ($row) => $row->teacher_id.'|'.$row->subject_id);

        $rows = $assignments->map(function (TeacherSubject $row) use (
            $topicTotals,
            $completedByTeacherSubject,
            $remainLoggedByTeacherSubject,
            $slugById,
            $nameById
        ) {
            $key = $row->teacher_id.'|'.$row->subject_id;
            $total = (int) ($topicTotals[$row->subject_id] ?? 0);
            $complete = (int) ($completedByTeacherSubject[$key]->completed ?? 0);
            if ($complete > $total && $total > 0) {
                $complete = $total;
            }
            $remain = max(0, $total - $complete);
            $pctComplete = $total > 0 ? (int) round(($complete / $total) * 100) : 0;
            $pctRemain = $total > 0 ? max(0, 100 - $pctComplete) : 0;
            $remainLogged = (int) ($remainLoggedByTeacherSubject[$key]->remain_logged ?? 0);

            return [
                'teacher_id' => (int) $row->teacher_id,
                'teacher' => $row->teacher?->name ?? '—',
                'mobile' => $row->teacher?->mobile ?? '',
                'subject_id' => (int) $row->subject_id,
                'subject' => $row->subject?->name ?? '—',
                'standard_id' => (int) $row->standard_id,
                'standard' => $nameById[$row->standard_id] ?? ($row->standard?->name ?? '—'),
                'standard_slug' => $slugById[$row->standard_id] ?? ($row->standard?->slug ?? ''),
                'medium' => Material::normalizeMedium($row->medium) ?: ($row->medium ?: '—'),
                'topics_total' => $total,
                'topics_complete' => $complete,
                'topics_remain' => $remain,
                'remain_logged' => $remainLogged,
                'percent_complete' => $pctComplete,
                'percent_remain' => $pctRemain,
                'status' => $this->statusTone($pctComplete, $total),
            ];
        })->values();

        $teachers = $rows->groupBy('teacher_id')->map(function (Collection $group) {
            $first = $group->first();
            $topicsTotal = (int) $group->sum('topics_total');
            $topicsComplete = (int) $group->sum('topics_complete');
            $topicsRemain = (int) $group->sum('topics_remain');
            $pctComplete = $topicsTotal > 0 ? (int) round(($topicsComplete / $topicsTotal) * 100) : 0;

            return [
                'teacher_id' => $first['teacher_id'],
                'teacher' => $first['teacher'],
                'mobile' => $first['mobile'],
                'subjects' => $group->count(),
                'topics_total' => $topicsTotal,
                'topics_complete' => $topicsComplete,
                'topics_remain' => $topicsRemain,
                'percent_complete' => $pctComplete,
                'percent_remain' => $topicsTotal > 0 ? max(0, 100 - $pctComplete) : 0,
                'status' => $this->statusTone($pctComplete, $topicsTotal),
                'rows' => $group->values(),
            ];
        })->sortBy('teacher')->values();

        $topicsTotal = (int) $rows->sum('topics_total');
        $topicsComplete = (int) $rows->sum('topics_complete');
        $topicsRemain = (int) $rows->sum('topics_remain');
        $pctComplete = $topicsTotal > 0 ? (int) round(($topicsComplete / $topicsTotal) * 100) : 0;

        return [
            'rows' => $rows,
            'teachers' => $teachers,
            'summary' => [
                'teachers' => $teachers->count(),
                'subjects' => $rows->count(),
                'topics_total' => $topicsTotal,
                'topics_complete' => $topicsComplete,
                'topics_remain' => $topicsRemain,
                'percent_complete' => $pctComplete,
                'percent_remain' => $topicsTotal > 0 ? max(0, 100 - $pctComplete) : 0,
            ],
            'filters' => [
                'medium' => $medium,
                'standard_id' => $standardId ? (string) $standardId : '',
                'teacher_id' => $teacherId ? (string) $teacherId : '',
                'search' => $search,
            ],
            'standards' => $allotted,
            'teacherOptions' => $teacherOptions,
            'hasAllotments' => true,
        ];
    }

    private function statusTone(int $percent, int $total): string
    {
        if ($total === 0) {
            return 'empty';
        }
        if ($percent >= 80) {
            return 'good';
        }
        if ($percent >= 40) {
            return 'warn';
        }

        return 'low';
    }
}
