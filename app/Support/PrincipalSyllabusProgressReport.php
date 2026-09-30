<?php

namespace App\Support;

use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Subject;
use App\Models\TeacherLogoutReport;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Support\Collection;

class PrincipalSyllabusProgressReport
{
    /**
     * Teacher → subjects → topics complete / remain from logout report selections.
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
        $standardKeys = $allotted->flatMap(fn ($s) => array_filter([(string) $s->name, (string) $s->slug]))->unique()->values()->all();

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

        // Book material topics per subject + medium (logout uses material_topics).
        $materialTopicTotals = $this->materialTopicTotalsBySubjectMedium($subjectIds);

        // Logout reports: selected topics marked complete / remain.
        $logoutReports = TeacherLogoutReport::query()
            ->whereIn('teacher_id', $teacherIds)
            ->whereIn('subject_id', $subjectIds)
            ->when($standardKeys !== [], fn ($q) => $q->where(function ($inner) use ($standardKeys) {
                $inner->whereIn('standard', $standardKeys)->orWhereNull('standard');
            }))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get(['id', 'teacher_id', 'subject_id', 'medium', 'standard', 'topic_ids', 'chk_complete', 'chk_remain', 'submitted_at']);

        // Latest status per teacher|subject|medium|topic_id from logout selections.
        $topicStatus = [];
        foreach ($logoutReports as $report) {
            $reportMedium = Material::normalizeMedium($report->medium) ?: strtolower(trim((string) $report->medium));
            $ids = collect($report->topic_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->all();

            // If no topic selected but complete/remain checked, count as 1 chapter-level mark.
            if ($ids === []) {
                continue;
            }

            $status = $report->chk_complete ? 'complete' : ($report->chk_remain ? 'remain' : null);
            if ($status === null) {
                continue;
            }

            foreach ($ids as $topicId) {
                $key = $report->teacher_id.'|'.$report->subject_id.'|'.$reportMedium.'|'.$topicId;
                if (isset($topicStatus[$key])) {
                    continue; // already have newer status (reports ordered desc)
                }
                $topicStatus[$key] = $status;
            }
        }

        $rows = $assignments->map(function (TeacherSubject $row) use (
            $materialTopicTotals,
            $topicStatus,
            $slugById,
            $nameById
        ) {
            $rowMedium = Material::normalizeMedium($row->medium) ?: strtolower(trim((string) $row->medium));
            $stdName = (string) ($nameById[$row->standard_id] ?? $row->standard?->name ?? '');
            $stdSlug = (string) ($slugById[$row->standard_id] ?? $row->standard?->slug ?? '');

            $total = (int) ($materialTopicTotals[$row->subject_id.'|'.$rowMedium] ?? 0);
            // Fallback: any medium total for subject if medium-specific empty.
            if ($total === 0) {
                $total = (int) ($materialTopicTotals[$row->subject_id.'|*'] ?? 0);
            }

            $prefix = $row->teacher_id.'|'.$row->subject_id.'|'.$rowMedium.'|';
            $completeIds = [];
            $remainIds = [];
            foreach ($topicStatus as $key => $status) {
                if (! str_starts_with($key, $prefix)) {
                    continue;
                }
                $topicId = (int) substr($key, strlen($prefix));
                if ($status === 'complete') {
                    $completeIds[$topicId] = true;
                } else {
                    $remainIds[$topicId] = true;
                }
            }

            // Also match keys without medium normalization edge cases (empty medium).
            if ($rowMedium === '') {
                foreach ($topicStatus as $key => $status) {
                    if (! preg_match('/^'.preg_quote((string) $row->teacher_id, '/').'\|'.preg_quote((string) $row->subject_id, '/').'\|[^|]*\|(\d+)$/', $key, $m)) {
                        continue;
                    }
                    $topicId = (int) $m[1];
                    if ($status === 'complete') {
                        $completeIds[$topicId] = true;
                    } else {
                        $remainIds[$topicId] = true;
                    }
                }
            }

            $complete = count($completeIds);
            $remainFromLogout = count(array_diff_key($remainIds, $completeIds));

            // If book has topic list: remain = not yet completed. Else use logout remain marks.
            if ($total > 0) {
                if ($complete > $total) {
                    $complete = $total;
                }
                $remain = max(0, $total - $complete);
            } else {
                $total = $complete + $remainFromLogout;
                $remain = $remainFromLogout;
            }

            $pctComplete = $total > 0 ? (int) round(($complete / $total) * 100) : 0;
            $pctRemain = $total > 0 ? max(0, 100 - $pctComplete) : 0;

            return [
                'teacher_id' => (int) $row->teacher_id,
                'teacher' => $row->teacher?->name ?? '—',
                'mobile' => $row->teacher?->mobile ?? '',
                'subject_id' => (int) $row->subject_id,
                'subject' => $row->subject?->name ?? '—',
                'standard_id' => (int) $row->standard_id,
                'standard' => $stdName !== '' ? $stdName : '—',
                'standard_slug' => $stdSlug,
                'medium' => $rowMedium !== '' ? $rowMedium : '—',
                'topics_total' => $total,
                'topics_complete' => $complete,
                'topics_remain' => $remain,
                'percent_complete' => $pctComplete,
                'percent_remain' => $pctRemain,
                'status' => $this->statusTone($pctComplete, $total),
            ];
        })->values();

        // Standard-wise ascending (1, 2, 3…), then teacher, then subject.
        $standardOrder = $allotted->pluck('id')->values()->flip();
        $rows = $rows
            ->sortBy([
                fn (array $r) => $standardOrder[$r['standard_id']] ?? 999,
                fn (array $r) => mb_strtolower((string) $r['teacher']),
                fn (array $r) => mb_strtolower((string) $r['subject']),
            ])
            ->values();

        $teachers = $rows->groupBy('teacher_id')->map(function (Collection $group) use ($standardOrder) {
            $first = $group->first();
            $topicsTotal = (int) $group->sum('topics_total');
            $topicsComplete = (int) $group->sum('topics_complete');
            $topicsRemain = (int) $group->sum('topics_remain');
            $pctComplete = $topicsTotal > 0 ? (int) round(($topicsComplete / $topicsTotal) * 100) : 0;
            $sortedRows = $group
                ->sortBy([
                    fn (array $r) => $standardOrder[$r['standard_id']] ?? 999,
                    fn (array $r) => mb_strtolower((string) $r['subject']),
                ])
                ->values();

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
                'rows' => $sortedRows,
            ];
        })->sortBy([
            fn (array $t) => $standardOrder[$t['rows']->first()['standard_id'] ?? 0] ?? 999,
            fn (array $t) => mb_strtolower((string) $t['teacher']),
        ])->values();

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

    /**
     * Count material topics available in the book for each subject|medium.
     *
     * @param  array<int, int>  $subjectIds
     * @return array<string, int>
     */
    private function materialTopicTotalsBySubjectMedium(array $subjectIds): array
    {
        $subjects = Subject::query()
            ->with('standard:id,name,slug,medium')
            ->whereIn('id', $subjectIds)
            ->get(['id', 'name', 'standard_id']);

        $totals = [];
        $anyMedium = [];

        foreach ($subjects as $subject) {
            foreach (array_keys(\App\Models\Standard::MEDIUMS) as $med) {
                $materials = Material::forStudentSubject($subject, $med);
                if ($materials->isEmpty()) {
                    continue;
                }
                $materialIds = $materials->pluck('id')->all();
                $count = MaterialTopic::query()->whereIn('material_id', $materialIds)->count();
                if ($count > 0) {
                    $totals[$subject->id.'|'.$med] = $count;
                    $anyMedium[$subject->id] = ($anyMedium[$subject->id] ?? 0) + $count;
                }
            }
            if (isset($anyMedium[$subject->id])) {
                $totals[$subject->id.'|*'] = $anyMedium[$subject->id];
            }
        }

        return $totals;
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
