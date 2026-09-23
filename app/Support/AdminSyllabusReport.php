<?php

namespace App\Support;

use App\Models\Chapter;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\TeachingLog;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSyllabusReport
{
    /**
     * @param  array{
     *     medium?: string,
     *     standard?: string,
     *     subject_id?: int|string|null,
     *     teacher_id?: int|string|null,
     *     chapter_id?: int|string|null,
     *     topic_id?: int|string|null,
     *     status?: string,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     search?: string|null,
     *     sort?: string|null,
     *     dir?: string|null,
     *     view?: string|null
     * }  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $logs = $this->filteredLogs($filters);

        return [
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
            'summary' => $this->overallSummary($logs, $filters),
            'classRows' => $this->classWise($logs, $filters),
            'subjectRows' => $this->subjectWise($logs, $filters),
            'teacherRows' => $this->teacherWise($logs, $filters),
            'chapterRows' => $this->chapterWise($logs, $filters),
            'dailyRows' => $this->dailyUpdates($filters),
            'pendingRows' => $this->pendingTopics($logs, $filters),
            'alerts' => $this->alerts($logs, $filters),
            'topTeachers' => $this->topTeachers($logs, $filters),
            'attentionTeachers' => $this->attentionTeachers($logs, $filters),
            'complianceRows' => $this->dailyCompliance($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'medium' => trim((string) ($filters['medium'] ?? '')),
            'standard' => trim((string) ($filters['standard'] ?? '')),
            'subject_id' => ($filters['subject_id'] ?? null) !== null && ($filters['subject_id'] ?? '') !== '' ? (int) $filters['subject_id'] : null,
            'teacher_id' => ($filters['teacher_id'] ?? null) !== null && ($filters['teacher_id'] ?? '') !== '' ? (int) $filters['teacher_id'] : null,
            'chapter_id' => ($filters['chapter_id'] ?? null) !== null && ($filters['chapter_id'] ?? '') !== '' ? (int) $filters['chapter_id'] : null,
            'topic_id' => ($filters['topic_id'] ?? null) !== null && ($filters['topic_id'] ?? '') !== '' ? (int) $filters['topic_id'] : null,
            'status' => trim((string) ($filters['status'] ?? '')),
            'date_from' => ($filters['date_from'] ?? null) ?: null,
            'date_to' => ($filters['date_to'] ?? null) ?: null,
            'search' => trim((string) ($filters['search'] ?? '')),
            'sort' => trim((string) ($filters['sort'] ?? 'date')),
            'dir' => strtolower((string) ($filters['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
            'view' => trim((string) ($filters['view'] ?? 'overview')) ?: 'overview',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'mediums' => Standard::MEDIUMS,
            'standards' => Standard::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'medium']),
            'subjects' => Subject::query()->where('is_active', true)->with('standard:id,name,slug')->orderBy('sort_order')->get(['id', 'name', 'standard_id']),
            'teachers' => User::teachers()->where('is_approved', true)->orderBy('name')->get(['id', 'name', 'email', 'mobile']),
            'chapters' => Chapter::query()->where('is_active', true)->orderBy('sort_order')->limit(500)->get(['id', 'name', 'subject_id']),
            'statuses' => [
                TeachingLog::STATUS_COMPLETED => 'Completed',
                TeachingLog::STATUS_REMAINING => 'Pending / Remaining',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredLogs(array $filters): Collection
    {
        $query = TeachingLog::query()
            ->with([
                'teacher:id,name,email,mobile',
                'subject:id,name,standard_id',
                'chapter:id,name,subject_id',
                'topic:id,name,chapter_id',
            ]);

        if ($filters['standard'] !== '') {
            $query->where('standard', $filters['standard']);
        }

        if ($filters['medium'] !== '') {
            $standardSlugs = Standard::query()
                ->where('medium', $filters['medium'])
                ->pluck('slug')
                ->all();
            $query->whereIn('standard', $standardSlugs ?: ['__none__']);
        }

        if ($filters['subject_id']) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if ($filters['teacher_id']) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if ($filters['chapter_id']) {
            $query->where('chapter_id', $filters['chapter_id']);
        }

        if ($filters['topic_id']) {
            $query->where('topic_id', $filters['topic_id']);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_from']) {
            $query->whereDate('teaching_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('teaching_date', '<=', $filters['date_to']);
        }

        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('teacher', fn ($t) => $t->where('name', 'like', $search))
                    ->orWhereHas('subject', fn ($s) => $s->where('name', 'like', $search))
                    ->orWhereHas('chapter', fn ($c) => $c->where('name', 'like', $search))
                    ->orWhereHas('topic', fn ($t) => $t->where('name', 'like', $search))
                    ->orWhere('standard', 'like', $search)
                    ->orWhere('notes', 'like', $search);
            });
        }

        return $query->latest('teaching_date')->latest('id')->get();
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function overallSummary(Collection $logs, array $filters): array
    {
        $classKeys = $logs->pluck('standard')->unique()->filter()->values();
        $subjectKeys = $logs->pluck('subject_id')->unique()->filter()->values();
        $chapterKeys = $logs->pluck('chapter_id')->unique()->filter()->values();
        $topicKeys = $logs->pluck('topic_id')->unique()->filter()->values();
        $teacherKeys = $logs->pluck('teacher_id')->unique()->filter()->values();

        $completedLogs = $logs->where('status', TeachingLog::STATUS_COMPLETED);
        $pendingLogs = $logs->where('status', TeachingLog::STATUS_REMAINING);

        $metrics = [
            [
                'metric' => 'Classes',
                'total' => $classKeys->count(),
                'completed' => $completedLogs->pluck('standard')->unique()->count(),
                'in_progress' => $pendingLogs->pluck('standard')->unique()->intersect($completedLogs->pluck('standard'))->count(),
                'pending' => max(0, $classKeys->count() - $completedLogs->pluck('standard')->unique()->count()),
            ],
            [
                'metric' => 'Subjects',
                'total' => $subjectKeys->count(),
                'completed' => $completedLogs->pluck('subject_id')->unique()->count(),
                'in_progress' => $pendingLogs->pluck('subject_id')->unique()->intersect($completedLogs->pluck('subject_id'))->count(),
                'pending' => max(0, $subjectKeys->count() - $completedLogs->pluck('subject_id')->unique()->count()),
            ],
            [
                'metric' => 'Chapters',
                'total' => $chapterKeys->count(),
                'completed' => $completedLogs->pluck('chapter_id')->unique()->count(),
                'in_progress' => $pendingLogs->pluck('chapter_id')->unique()->intersect($completedLogs->pluck('chapter_id'))->count(),
                'pending' => max(0, $chapterKeys->count() - $completedLogs->pluck('chapter_id')->unique()->count()),
            ],
            [
                'metric' => 'Topics',
                'total' => max($topicKeys->count(), $logs->count()),
                'completed' => $completedLogs->count(),
                'in_progress' => 0,
                'pending' => $pendingLogs->count(),
            ],
            [
                'metric' => 'Teachers',
                'total' => $teacherKeys->count(),
                'completed' => $completedLogs->pluck('teacher_id')->unique()->count(),
                'in_progress' => $pendingLogs->pluck('teacher_id')->unique()->intersect($completedLogs->pluck('teacher_id'))->count(),
                'pending' => max(0, $teacherKeys->count() - $completedLogs->pluck('teacher_id')->unique()->count()),
            ],
        ];

        return array_map(function (array $row) {
            $pct = $row['total'] > 0 ? (int) round(($row['completed'] / $row['total']) * 100) : 0;

            return $row + ['percent' => $pct];
        }, $metrics);
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function classWise(Collection $logs, array $filters): Collection
    {
        $standardNames = Standard::query()->pluck('name', 'slug');

        return $logs->groupBy('standard')->map(function (Collection $group, $standard) use ($standardNames) {
            $total = $group->count();
            $completed = $group->where('status', TeachingLog::STATUS_COMPLETED)->count();
            $pending = $total - $completed;
            $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

            return [
                'standard' => $standard,
                'class' => $standardNames[$standard] ?? $standard,
                'section' => '—',
                'subjects' => $group->pluck('subject_id')->unique()->count(),
                'chapters' => $group->pluck('chapter_id')->unique()->count(),
                'completed' => $completed,
                'pending' => $pending,
                'percent' => $percent,
                'status' => $this->statusTone($percent),
            ];
        })->sortByDesc('percent')->values();
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function subjectWise(Collection $logs, array $filters): Collection
    {
        $standardNames = Standard::query()->pluck('name', 'slug');

        return $logs->groupBy(fn (TeachingLog $log) => $log->standard.'|'.$log->subject_id)->map(function (Collection $group) use ($standardNames) {
            /** @var TeachingLog $first */
            $first = $group->first();
            $total = $group->count();
            $completed = $group->where('status', TeachingLog::STATUS_COMPLETED)->count();
            $pending = $total - $completed;
            $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

            return [
                'standard' => $first->standard,
                'subject_id' => $first->subject_id,
                'subject' => $first->subject?->name ?? '—',
                'class' => $standardNames[$first->standard] ?? $first->standard,
                'teacher' => $group->pluck('teacher.name')->unique()->filter()->implode(', ') ?: '—',
                'teacher_id' => $first->teacher_id,
                'chapters' => $group->pluck('chapter_id')->unique()->count(),
                'completed' => $completed,
                'pending' => $pending,
                'percent' => $percent,
                'status' => $this->statusTone($percent),
            ];
        })->sortByDesc('percent')->values();
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function teacherWise(Collection $logs, array $filters): Collection
    {
        $standardNames = Standard::query()->pluck('name', 'slug');

        return $logs->groupBy('teacher_id')->map(function (Collection $group) use ($standardNames) {
            /** @var TeachingLog $first */
            $first = $group->first();
            $total = $group->count();
            $completed = $group->where('status', TeachingLog::STATUS_COMPLETED)->count();
            $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
            $updatedToday = $group->contains(fn (TeachingLog $log) => $log->teaching_date?->isToday());

            return [
                'teacher_id' => $first->teacher_id,
                'teacher' => $first->teacher?->name ?? '—',
                'subject' => $group->pluck('subject.name')->unique()->filter()->implode(', ') ?: '—',
                'class' => $group->pluck('standard')->unique()->map(fn ($s) => $standardNames[$s] ?? $s)->implode(', '),
                'planned' => 80,
                'actual' => $percent,
                'gap' => $percent - 80,
                'daily_update' => $updatedToday,
                'status' => $this->statusTone($percent),
                'completed' => $completed,
                'pending' => $total - $completed,
                'total' => $total,
            ];
        })->sortByDesc('actual')->values();
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function chapterWise(Collection $logs, array $filters): Collection
    {
        $standardNames = Standard::query()->pluck('name', 'slug');

        return $logs->groupBy('chapter_id')->map(function (Collection $group) use ($standardNames) {
            /** @var TeachingLog $first */
            $first = $group->first();
            $total = $group->count();
            $completed = $group->where('status', TeachingLog::STATUS_COMPLETED)->count();
            $pending = $group->where('status', TeachingLog::STATUS_REMAINING)->count();
            $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

            return [
                'chapter_id' => $first->chapter_id,
                'chapter' => $first->chapter?->name ?? '—',
                'subject' => $first->subject?->name ?? '—',
                'subject_id' => $first->subject_id,
                'class' => $standardNames[$first->standard] ?? $first->standard,
                'standard' => $first->standard,
                'total_topics' => $group->pluck('topic_id')->filter()->unique()->count() ?: $total,
                'completed' => $completed,
                'partial' => 0,
                'pending' => $pending,
                'percent' => $percent,
                'status' => $this->statusTone($percent),
            ];
        })->sortByDesc('percent')->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function dailyUpdates(array $filters): Collection
    {
        $logs = $this->filteredLogs($filters)->take(100);
        $standardNames = Standard::query()->pluck('name', 'slug');

        return $logs->map(function (TeachingLog $log) use ($standardNames) {
            return [
                'id' => $log->id,
                'date' => optional($log->teaching_date)->format('d-M-Y') ?? '—',
                'date_raw' => optional($log->teaching_date)->toDateString(),
                'teacher' => $log->teacher?->name ?? '—',
                'teacher_id' => $log->teacher_id,
                'class' => $standardNames[$log->standard] ?? $log->standard,
                'standard' => $log->standard,
                'subject' => $log->subject?->name ?? '—',
                'subject_id' => $log->subject_id,
                'chapter' => $log->chapter?->name ?? '—',
                'chapter_id' => $log->chapter_id,
                'topic' => $log->topic?->name ?? '—',
                'topic_id' => $log->topic_id,
                'period' => $log->period_label ?: '—',
                'status' => $log->status,
                'remark' => $log->notes ?: '—',
            ];
        })->values();
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function pendingTopics(Collection $logs, array $filters): Collection
    {
        $standardNames = Standard::query()->pluck('name', 'slug');

        return $logs->where('status', TeachingLog::STATUS_REMAINING)
            ->sortBy('teaching_date')
            ->values()
            ->take(100)
            ->map(function (TeachingLog $log) use ($standardNames) {
                $delay = $log->teaching_date ? max(0, $log->teaching_date->diffInDays(now())) : 0;

                return [
                    'class' => $standardNames[$log->standard] ?? $log->standard,
                    'standard' => $log->standard,
                    'subject' => $log->subject?->name ?? '—',
                    'subject_id' => $log->subject_id,
                    'teacher' => $log->teacher?->name ?? '—',
                    'teacher_id' => $log->teacher_id,
                    'chapter' => $log->chapter?->name ?? '—',
                    'chapter_id' => $log->chapter_id,
                    'topic' => $log->topic?->name ?? '—',
                    'topic_id' => $log->topic_id,
                    'expected_date' => optional($log->teaching_date)->format('d-M-Y') ?? '—',
                    'status' => 'Pending',
                    'delay' => $delay.' Days',
                    'delay_days' => $delay,
                ];
            });
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function alerts(Collection $logs, array $filters): array
    {
        $today = now()->toDateString();
        $teachersWithLogsToday = TeachingLog::query()
            ->whereDate('teaching_date', $today)
            ->distinct()
            ->pluck('teacher_id');

        $approvedTeachers = User::teachers()->where('is_approved', true)->count();
        $notUpdated = max(0, $approvedTeachers - $teachersWithLogsToday->count());

        $behind = $this->teacherWise($logs, $filters)->where('gap', '<', -10)->count();
        $pending = $logs->where('status', TeachingLog::STATUS_REMAINING)->count();
        $noRemark = $logs->filter(fn (TeachingLog $log) => blank($log->notes))->count();

        return [
            ['type' => 'Teacher Not Updated Today', 'count' => $notUpdated, 'priority' => 'high', 'view' => 'compliance'],
            ['type' => 'Syllabus Behind Schedule', 'count' => $behind, 'priority' => 'high', 'view' => 'teachers'],
            ['type' => 'Pending Topics', 'count' => $pending, 'priority' => 'medium', 'view' => 'pending'],
            ['type' => 'No Remark', 'count' => $noRemark, 'priority' => 'low', 'view' => 'daily'],
        ];
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function topTeachers(Collection $logs, array $filters): Collection
    {
        return $this->teacherWise($logs, $filters)->take(5)->values();
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function attentionTeachers(Collection $logs, array $filters): Collection
    {
        return $this->teacherWise($logs, $filters)
            ->filter(fn (array $row) => $row['gap'] < 0)
            ->sortBy('gap')
            ->take(5)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function dailyCompliance(array $filters): Collection
    {
        $date = $filters['date_to'] ?: ($filters['date_from'] ?: now()->toDateString());
        $weekday = \Illuminate\Support\Carbon::parse($date)->dayOfWeekIso;

        $teachers = User::teachers()->where('is_approved', true)->orderBy('name')->get(['id', 'name']);
        $updates = TeachingLog::query()
            ->whereDate('teaching_date', $date)
            ->select('teacher_id', DB::raw('COUNT(*) as updates_done'))
            ->groupBy('teacher_id')
            ->pluck('updates_done', 'teacher_id');

        $logoutDone = \App\Models\TeacherLogoutReport::query()
            ->whereDate('report_date', $date)
            ->select('teacher_id', DB::raw('COUNT(*) as reports_done'))
            ->groupBy('teacher_id')
            ->pluck('reports_done', 'teacher_id');

        $requiredByTeacher = collect();
        if (Schema::hasTable('teacher_timetables') && Schema::hasTable('school_periods')) {
            $requiredByTeacher = \App\Models\TeacherTimetable::query()
                ->active()
                ->where('weekday', $weekday)
                ->whereHas('period', fn ($q) => $q->where('is_active', true))
                ->select('teacher_id', DB::raw('COUNT(*) as required_slots'))
                ->groupBy('teacher_id')
                ->pluck('required_slots', 'teacher_id');
        }

        return $teachers->map(function (User $teacher) use ($updates, $logoutDone, $requiredByTeacher) {
            $required = (int) ($requiredByTeacher[$teacher->id] ?? 0);
            $doneLogs = (int) ($updates[$teacher->id] ?? 0);
            $doneLogout = (int) ($logoutDone[$teacher->id] ?? 0);
            $done = max($doneLogs, $doneLogout);

            if ($required === 0) {
                // No timetable — keep soft baseline from updates
                $required = $done > 0 ? $done : 1;
                $assignedLabel = '—';
            } else {
                $assignedLabel = (string) $required;
            }

            $pending = max(0, $required - $done);
            $percent = (int) round(($done / max(1, $required)) * 100);
            if ($done === 0) {
                $percent = 0;
            }

            return [
                'teacher_id' => $teacher->id,
                'teacher' => $teacher->name,
                'assigned_periods' => $assignedLabel,
                'required' => $required,
                'done' => $done,
                'pending' => $pending,
                'percent' => $percent,
                'status' => $this->statusTone($percent),
            ];
        })->sortBy('percent')->values();
    }

    /**
     * @return array{label: string, badge: string}
     */
    private function statusTone(int $percent): array
    {
        if ($percent >= 75) {
            return ['label' => 'On Track', 'badge' => 'admin-badge-green', 'emoji' => '🟢'];
        }

        if ($percent >= 50) {
            return ['label' => 'Behind', 'badge' => 'admin-badge-gold', 'emoji' => '🟡'];
        }

        return ['label' => 'Critical', 'badge' => 'admin-badge-slate', 'emoji' => '🔴'];
    }
}
