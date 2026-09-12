<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Standard;
use App\Models\TeachingLog;
use App\Services\TeachingHomeworkGeneratorService;
use App\Support\TeachingHomeworkLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TodaysTeachingController extends Controller
{
    private const PREVIEW_SESSION_KEY = 'teacher.todays_teaching.homework_preview';

    public function __construct(
        private readonly TeachingHomeworkGeneratorService $homeworkGenerator,
    ) {}

    public function index(Request $request): View
    {
        $teacher = auth()->user();
        $date = $request->date('date') ?? now()->toDateString();
        $standard = $request->string('standard')->toString();

        $query = TeachingLog::query()
            ->where('teacher_id', $teacher->id)
            ->whereDate('teaching_date', $date)
            ->with(['subject:id,name', 'chapter:id,name', 'topic:id,name', 'homework:id,title'])
            ->orderBy('standard')
            ->orderBy('subject_id')
            ->orderBy('chapter_id')
            ->orderBy('topic_id');

        if ($standard !== '') {
            $query->where('standard', $standard);
        }

        $logs = $query->get();

        $stats = [
            'total' => $logs->count(),
            'completed' => $logs->where('status', TeachingLog::STATUS_COMPLETED)->count(),
            'remaining' => $logs->where('status', TeachingLog::STATUS_REMAINING)->count(),
            'homework_generated' => $logs->whereNotNull('homework_id')->count(),
        ];

        return view('teacher.todays-teaching.index', [
            'teacher' => $teacher,
            'logs' => $logs,
            'stats' => $stats,
            'date' => $date,
            'standard' => $standard,
            'standards' => $this->standardOptions(),
            'markOptions' => [20, 30, 40, 50],
        ]);
    }

    public function create(Request $request): View
    {
        return view('teacher.todays-teaching.create', [
            'standards' => $this->standardOptions(),
            'date' => $request->date('date') ?? now()->toDateString(),
            'markOptions' => [20, 30, 40, 50],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teaching_date' => ['required', 'date'],
            'standard' => ['required', 'string', 'max:50'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.topic_id' => ['required', 'integer', 'exists:topics,id'],
            'entries.*.status' => ['required', 'in:remaining,completed'],
            'target_marks' => ['nullable', 'integer', 'in:20,30,40,50'],
            'type_counts' => ['nullable', 'array'],
            'type_counts.*' => ['nullable', 'integer', 'min:0', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $hasCompleted = collect($validated['entries'])
            ->contains(fn (array $entry) => $entry['status'] === TeachingLog::STATUS_COMPLETED);

        if ($hasCompleted && empty($validated['target_marks'])) {
            return back()->withInput()->with('error', 'Select homework marks (20 / 30 / 40 / 50) for completed topics.');
        }

        $teacherId = auth()->id();
        $subjectId = (int) $validated['subject_id'];
        $chapterId = (int) $validated['chapter_id'];
        $chapter = \App\Models\Chapter::query()->find($chapterId);

        if (! $chapter || (int) $chapter->subject_id !== $subjectId) {
            return back()->withInput()->with('error', 'Selected chapter does not belong to this subject.');
        }

        $created = 0;
        $updated = 0;
        $skippedWithHomework = 0;
        $processedLogs = collect();

        foreach ($validated['entries'] as $entry) {
            $topic = \App\Models\Topic::query()->find((int) $entry['topic_id']);

            if (! $topic || (int) $topic->chapter_id !== $chapterId) {
                continue;
            }

            $result = $this->upsertTeachingLog(
                $teacherId,
                $validated['teaching_date'],
                $validated['standard'],
                $subjectId,
                $chapterId,
                (int) $topic->id,
                $entry['status'],
                $validated['notes'] ?? null
            );

            if ($result === null) {
                continue;
            }

            if ($result['skipped']) {
                $skippedWithHomework++;
                continue;
            }

            if ($result['created']) {
                $created++;
            } elseif ($result['updated']) {
                $updated++;
            }

            $processedLogs->push($result['log']);
        }

        if ($processedLogs->isEmpty()) {
            if ($skippedWithHomework > 0) {
                return back()->withInput()->with('error', 'Selected topic(s) already have homework for this date.');
            }

            return back()->withInput()->with('error', 'No teaching entries could be saved. Please check your selection.');
        }

        $completedLogs = $processedLogs
            ->filter(fn (TeachingLog $log) => $log->isCompleted() && ! $log->hasHomework())
            ->values();

        if ($completedLogs->isNotEmpty()) {
            $completedLogs->each(fn (TeachingLog $log) => $log->loadMissing(['subject', 'chapter', 'topic']));

            $remainingCount = $processedLogs
                ->filter(fn (TeachingLog $log) => $log->isRemaining())
                ->count();

            return $this->redirectToHomeworkPreview(
                $completedLogs,
                (int) $validated['target_marks'],
                $request->input('type_counts', []),
                $validated['teaching_date'],
                $remainingCount
            );
        }

        $message = match (true) {
            $created > 0 && $updated > 0 => "{$created} topic(s) added and {$updated} updated in today's report.",
            $created > 0 => "{$created} teaching topic(s) added to today's report.",
            $updated > 0 => "{$updated} teaching topic(s) updated in today's report.",
            default => 'Teaching topics saved in today\'s report.',
        };

        return redirect()
            ->route('teacher.todays-teaching.index', ['date' => $validated['teaching_date']])
            ->with('success', $message);
    }

    public function showHomeworkPreview(): View|RedirectResponse
    {
        $preview = session(self::PREVIEW_SESSION_KEY);

        if (! $preview) {
            return redirect()
                ->route('teacher.todays-teaching.index')
                ->with('error', 'Homework preview expired. Please add completed topics again.');
        }

        return view('teacher.todays-teaching.homework-preview', [
            'preview' => $preview,
        ]);
    }

    public function buildHomeworkPreview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teaching_log_ids' => ['required', 'string'],
            'target_marks' => ['required', 'integer', 'in:20,30,40,50'],
            'type_counts' => ['required', 'array'],
            'type_counts.*' => ['nullable', 'integer', 'min:0', 'max:200'],
        ]);

        $logIds = collect(explode(',', $validated['teaching_log_ids']))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $logs = TeachingLog::query()
            ->where('teacher_id', auth()->id())
            ->whereIn('id', $logIds)
            ->with(['subject', 'chapter', 'topic'])
            ->get();

        if ($logs->count() !== count($logIds)) {
            return back()->with('error', 'Invalid teaching entries for homework preview.');
        }

        $existingPreview = session(self::PREVIEW_SESSION_KEY, []);

        return $this->redirectToHomeworkPreview(
            $logs,
            (int) $validated['target_marks'],
            $validated['type_counts'],
            $existingPreview['teaching_date'] ?? $logs->first()?->teaching_date?->toDateString(),
            (int) ($existingPreview['remaining_count'] ?? 0)
        );
    }

    public function confirmHomework(): RedirectResponse
    {
        $preview = session()->pull(self::PREVIEW_SESSION_KEY);

        if (! $preview) {
            return redirect()
                ->route('teacher.todays-teaching.index')
                ->with('error', 'Homework preview expired. Please configure and preview again.');
        }

        try {
            $homework = $this->homeworkGenerator->generateFromPreview($preview, auth()->user());
        } catch (\Throwable $exception) {
            session([self::PREVIEW_SESSION_KEY => $preview]);

            return redirect()
                ->route('teacher.todays-teaching.homework-preview')
                ->with('error', $exception->getMessage());
        }

        $remainingCount = (int) ($preview['remaining_count'] ?? 0);
        $message = $remainingCount > 0
            ? "Homework published. {$remainingCount} remaining topic(s) saved in today's report."
            : 'Homework published successfully from today\'s teaching.';

        return redirect()
            ->route('teacher.homework.show', $homework)
            ->with('success', $message);
    }

    public function updateStatus(Request $request, TeachingLog $teachingLog): RedirectResponse
    {
        $this->authorizeLog($teachingLog);

        $validated = $request->validate([
            'status' => ['required', 'in:remaining,completed'],
            'target_marks' => ['nullable', 'integer', 'in:20,30,40,50'],
        ]);

        if ($teachingLog->hasHomework() && $validated['status'] === TeachingLog::STATUS_REMAINING) {
            return back()->with('error', 'Cannot mark as remaining — homework already generated for this topic.');
        }

        $teachingLog->update(['status' => $validated['status']]);

        if ($validated['status'] === TeachingLog::STATUS_COMPLETED && ! $teachingLog->hasHomework()) {
            $targetMarks = (int) ($validated['target_marks'] ?? 20);
            $layout = TeachingHomeworkLayout::autoLayout($targetMarks);

            return $this->redirectToHomeworkPreview(
                collect([$teachingLog->fresh(['subject', 'chapter', 'topic'])]),
                $targetMarks,
                $layout['type_counts'],
                $teachingLog->teaching_date?->toDateString(),
                0
            );
        }

        return back()->with('success', 'Teaching status updated.');
    }

    public function generateHomework(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teaching_log_ids' => ['required', 'array', 'min:1'],
            'teaching_log_ids.*' => ['integer', 'exists:teaching_logs,id'],
            'target_marks' => ['required', 'integer', 'in:20,30,40,50'],
        ]);

        $logs = TeachingLog::query()
            ->where('teacher_id', auth()->id())
            ->whereIn('id', $validated['teaching_log_ids'])
            ->with(['subject', 'chapter', 'topic'])
            ->get();

        if ($logs->count() !== count($validated['teaching_log_ids'])) {
            return back()->with('error', 'Invalid teaching entries selected.');
        }

        foreach ($logs as $log) {
            if (! $log->isCompleted()) {
                return back()->with('error', 'Only completed topics can be used for homework. Mark them completed first.');
            }
        }

        $layout = TeachingHomeworkLayout::autoLayout((int) $validated['target_marks']);

        return $this->redirectToHomeworkPreview(
            $logs,
            (int) $validated['target_marks'],
            $layout['type_counts'],
            $logs->first()?->teaching_date?->toDateString(),
            0
        );
    }

    public function destroy(TeachingLog $teachingLog): RedirectResponse
    {
        $this->authorizeLog($teachingLog);

        $date = $teachingLog->teaching_date?->toDateString();
        $hadHomework = $teachingLog->hasHomework();
        $teachingLog->delete();

        $message = $hadHomework
            ? 'Teaching entry removed from today\'s report. Homework is still available under My Homework.'
            : 'Teaching entry removed.';

        return redirect()
            ->route('teacher.todays-teaching.index', ['date' => $date])
            ->with('success', $message);
    }

    private function redirectToHomeworkPreview(
        $logs,
        int $targetMarks,
        array $typeCountsInput,
        ?string $teachingDate,
        int $remainingCount,
    ): RedirectResponse {
        $layout = TeachingHomeworkLayout::fromInput($typeCountsInput, $targetMarks);

        if ($layout['type_counts'] === []) {
            return back()->withInput()->with('error', 'Set at least one question quantity for homework.');
        }

        try {
            $preview = $this->homeworkGenerator->preview(
                $logs,
                $targetMarks,
                $layout['type_counts'],
                $layout['marks_per_type']
            );
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $preview['teaching_log_ids'] = $logs->pluck('id')->all();
        $preview['teaching_date'] = $teachingDate;
        $preview['remaining_count'] = $remainingCount;

        session([self::PREVIEW_SESSION_KEY => $preview]);

        return redirect()->route('teacher.todays-teaching.homework-preview');
    }

    private function authorizeLog(TeachingLog $log): void
    {
        abort_unless($log->teacher_id === auth()->id(), 403);
    }

    /**
     * @return array{log: TeachingLog, created: bool, updated: bool, skipped: bool}|null
     */
    private function upsertTeachingLog(
        int $teacherId,
        string $date,
        string $standard,
        int $subjectId,
        int $chapterId,
        ?int $topicId,
        string $status = TeachingLog::STATUS_REMAINING,
        ?string $notes = null,
    ): ?array {
        $log = TeachingLog::query()
            ->where('teacher_id', $teacherId)
            ->whereDate('teaching_date', $date)
            ->where('standard', $standard)
            ->where('subject_id', $subjectId)
            ->where('chapter_id', $chapterId)
            ->when($topicId, fn ($q) => $q->where('topic_id', $topicId), fn ($q) => $q->whereNull('topic_id'))
            ->first();

        if ($log) {
            if ($log->hasHomework()) {
                return ['log' => $log, 'created' => false, 'updated' => false, 'skipped' => true];
            }

            $updated = false;

            if ($log->status !== $status) {
                $log->status = $status;
                $updated = true;
            }

            if (filled($notes) && $log->notes !== $notes) {
                $log->notes = $notes;
                $updated = true;
            }

            if ($updated) {
                $log->save();
            }

            return ['log' => $log->fresh(), 'created' => false, 'updated' => $updated, 'skipped' => false];
        }

        $log = TeachingLog::create([
            'teacher_id' => $teacherId,
            'teaching_date' => $date,
            'standard' => $standard,
            'subject_id' => $subjectId,
            'chapter_id' => $chapterId,
            'topic_id' => $topicId,
            'status' => $status,
            'notes' => $notes,
        ]);

        return ['log' => $log, 'created' => true, 'updated' => false, 'skipped' => false];
    }

    private function standardOptions(): array
    {
        $standards = Standard::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'slug')
            ->all();

        return $standards !== [] ? $standards : collect(range(1, 12))
            ->mapWithKeys(fn ($i) => ["standard_{$i}" => "Standard {$i}"])
            ->all();
    }
}
