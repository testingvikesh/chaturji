<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ExamLog;
use App\Models\Standard;
use App\Services\TeachingExamGeneratorService;
use App\Support\TeachingHomeworkLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TodaysExamController extends Controller
{
    private const PREVIEW_SESSION_KEY = 'teacher.todays_exam.exam_preview';

    public function __construct(
        private readonly TeachingExamGeneratorService $examGenerator,
    ) {}

    public function index(Request $request): View
    {
        $teacher = auth()->user();
        $date = $request->date('date') ?? now()->toDateString();
        $standard = $request->string('standard')->toString();

        $query = ExamLog::query()
            ->where('teacher_id', $teacher->id)
            ->whereDate('exam_date', $date)
            ->with(['subject:id,name', 'chapter:id,name', 'topic:id,name', 'exam:id,title'])
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
            'completed' => $logs->where('status', ExamLog::STATUS_COMPLETED)->count(),
            'remaining' => $logs->where('status', ExamLog::STATUS_REMAINING)->count(),
            'exam_generated' => $logs->whereNotNull('exam_id')->count(),
        ];

        return view('teacher.todays-exam.index', [
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
        return view('teacher.todays-exam.create', [
            'standards' => $this->standardOptions(),
            'date' => $request->date('date') ?? now()->toDateString(),
            'markOptions' => [20, 30, 40, 50],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_date' => ['required', 'date'],
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
            ->contains(fn (array $entry) => $entry['status'] === ExamLog::STATUS_COMPLETED);

        if ($hasCompleted && empty($validated['target_marks'])) {
            return back()->withInput()->with('error', 'Select exam marks (20 / 30 / 40 / 50) for completed topics.');
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
        $skippedWithExam = 0;
        $processedLogs = collect();

        foreach ($validated['entries'] as $entry) {
            $topic = \App\Models\Topic::query()->find((int) $entry['topic_id']);

            if (! $topic || (int) $topic->chapter_id !== $chapterId) {
                continue;
            }

            $result = $this->upsertExamLog(
                $teacherId,
                $validated['exam_date'],
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
                $skippedWithExam++;
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
            if ($skippedWithExam > 0) {
                return back()->withInput()->with('error', 'Selected topic(s) already have an exam for this date.');
            }

            return back()->withInput()->with('error', 'No exam entries could be saved. Please check your selection.');
        }

        $completedLogs = $processedLogs
            ->filter(fn (ExamLog $log) => $log->isCompleted() && ! $log->hasExam())
            ->values();

        if ($completedLogs->isNotEmpty()) {
            $completedLogs->each(fn (ExamLog $log) => $log->loadMissing(['subject', 'chapter', 'topic']));

            $remainingCount = $processedLogs
                ->filter(fn (ExamLog $log) => $log->isRemaining())
                ->count();

            return $this->redirectToExamPreview(
                $completedLogs,
                (int) $validated['target_marks'],
                $request->input('type_counts', []),
                $validated['exam_date'],
                $remainingCount
            );
        }

        $message = match (true) {
            $created > 0 && $updated > 0 => "{$created} topic(s) added and {$updated} updated in today's exam report.",
            $created > 0 => "{$created} exam topic(s) added to today's report.",
            $updated > 0 => "{$updated} exam topic(s) updated in today's report.",
            default => 'Exam topics saved in today\'s report.',
        };

        return redirect()
            ->route('teacher.todays-exam.index', ['date' => $validated['exam_date']])
            ->with('success', $message);
    }

    public function showExamPreview(): View|RedirectResponse
    {
        $preview = session(self::PREVIEW_SESSION_KEY);

        if (! $preview) {
            return redirect()
                ->route('teacher.todays-exam.index')
                ->with('error', 'Exam preview expired. Please add completed topics again.');
        }

        return view('teacher.todays-exam.exam-preview', [
            'preview' => $preview,
        ]);
    }

    public function buildExamPreview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_log_ids' => ['required', 'string'],
            'target_marks' => ['required', 'integer', 'in:20,30,40,50'],
            'type_counts' => ['required', 'array'],
            'type_counts.*' => ['nullable', 'integer', 'min:0', 'max:200'],
        ]);

        $logIds = collect(explode(',', $validated['exam_log_ids']))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $logs = ExamLog::query()
            ->where('teacher_id', auth()->id())
            ->whereIn('id', $logIds)
            ->with(['subject', 'chapter', 'topic'])
            ->get();

        if ($logs->count() !== count($logIds)) {
            return back()->with('error', 'Invalid exam entries for preview.');
        }

        $existingPreview = session(self::PREVIEW_SESSION_KEY, []);

        return $this->redirectToExamPreview(
            $logs,
            (int) $validated['target_marks'],
            $validated['type_counts'],
            $existingPreview['exam_date'] ?? $logs->first()?->exam_date?->toDateString(),
            (int) ($existingPreview['remaining_count'] ?? 0)
        );
    }

    public function confirmExam(): RedirectResponse
    {
        $preview = session()->pull(self::PREVIEW_SESSION_KEY);

        if (! $preview) {
            return redirect()
                ->route('teacher.todays-exam.index')
                ->with('error', 'Exam preview expired. Please configure and preview again.');
        }

        try {
            $exam = $this->examGenerator->generateFromPreview($preview, auth()->user());
        } catch (\Throwable $exception) {
            session([self::PREVIEW_SESSION_KEY => $preview]);

            return redirect()
                ->route('teacher.todays-exam.exam-preview')
                ->with('error', $exception->getMessage());
        }

        $remainingCount = (int) ($preview['remaining_count'] ?? 0);
        $message = $remainingCount > 0
            ? "Exam published. {$remainingCount} remaining topic(s) saved in today's report."
            : 'Exam published successfully from today\'s exam.';

        return redirect()
            ->route('teacher.exams.show', $exam)
            ->with('success', $message);
    }

    public function updateStatus(Request $request, ExamLog $examLog): RedirectResponse
    {
        $this->authorizeLog($examLog);

        $validated = $request->validate([
            'status' => ['required', 'in:remaining,completed'],
            'target_marks' => ['nullable', 'integer', 'in:20,30,40,50'],
        ]);

        if ($examLog->hasExam() && $validated['status'] === ExamLog::STATUS_REMAINING) {
            return back()->with('error', 'Cannot mark as remaining — exam already generated for this topic.');
        }

        $examLog->update(['status' => $validated['status']]);

        if ($validated['status'] === ExamLog::STATUS_COMPLETED && ! $examLog->hasExam()) {
            $targetMarks = (int) ($validated['target_marks'] ?? 20);
            $layout = TeachingHomeworkLayout::autoLayout($targetMarks);

            return $this->redirectToExamPreview(
                collect([$examLog->fresh(['subject', 'chapter', 'topic'])]),
                $targetMarks,
                $layout['type_counts'],
                $examLog->exam_date?->toDateString(),
                0
            );
        }

        return back()->with('success', 'Exam status updated.');
    }

    public function generateExam(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_log_ids' => ['required', 'array', 'min:1'],
            'exam_log_ids.*' => ['integer', 'exists:exam_logs,id'],
            'target_marks' => ['required', 'integer', 'in:20,30,40,50'],
        ]);

        $logs = ExamLog::query()
            ->where('teacher_id', auth()->id())
            ->whereIn('id', $validated['exam_log_ids'])
            ->with(['subject', 'chapter', 'topic'])
            ->get();

        if ($logs->count() !== count($validated['exam_log_ids'])) {
            return back()->with('error', 'Invalid exam entries selected.');
        }

        foreach ($logs as $log) {
            if (! $log->isCompleted()) {
                return back()->with('error', 'Only completed topics can be used for exam. Mark them completed first.');
            }
        }

        $layout = TeachingHomeworkLayout::autoLayout((int) $validated['target_marks']);

        return $this->redirectToExamPreview(
            $logs,
            (int) $validated['target_marks'],
            $layout['type_counts'],
            $logs->first()?->exam_date?->toDateString(),
            0
        );
    }

    public function destroy(ExamLog $examLog): RedirectResponse
    {
        $this->authorizeLog($examLog);

        $date = $examLog->exam_date?->toDateString();
        $hadExam = $examLog->hasExam();
        $examLog->delete();

        $message = $hadExam
            ? 'Exam entry removed from today\'s report. Exam is still available under My Exams.'
            : 'Exam entry removed.';

        return redirect()
            ->route('teacher.todays-exam.index', ['date' => $date])
            ->with('success', $message);
    }

    private function redirectToExamPreview(
        $logs,
        int $targetMarks,
        array $typeCountsInput,
        ?string $examDate,
        int $remainingCount,
    ): RedirectResponse {
        $layout = TeachingHomeworkLayout::fromInput($typeCountsInput, $targetMarks);

        if ($layout['type_counts'] === []) {
            return back()->withInput()->with('error', 'Set at least one question quantity for exam.');
        }

        try {
            $preview = $this->examGenerator->preview(
                $logs,
                $targetMarks,
                $layout['type_counts'],
                $layout['marks_per_type']
            );
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $preview['exam_log_ids'] = $logs->pluck('id')->all();
        $preview['exam_date'] = $examDate;
        $preview['remaining_count'] = $remainingCount;

        session([self::PREVIEW_SESSION_KEY => $preview]);

        return redirect()->route('teacher.todays-exam.exam-preview');
    }

    private function authorizeLog(ExamLog $log): void
    {
        abort_unless($log->teacher_id === auth()->id(), 403);
    }

    /**
     * @return array{log: ExamLog, created: bool, updated: bool, skipped: bool}|null
     */
    private function upsertExamLog(
        int $teacherId,
        string $date,
        string $standard,
        int $subjectId,
        int $chapterId,
        ?int $topicId,
        string $status = ExamLog::STATUS_REMAINING,
        ?string $notes = null,
    ): ?array {
        $log = ExamLog::query()
            ->where('teacher_id', $teacherId)
            ->whereDate('exam_date', $date)
            ->where('standard', $standard)
            ->where('subject_id', $subjectId)
            ->where('chapter_id', $chapterId)
            ->when($topicId, fn ($q) => $q->where('topic_id', $topicId), fn ($q) => $q->whereNull('topic_id'))
            ->first();

        if ($log) {
            if ($log->hasExam()) {
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

        $log = ExamLog::create([
            'teacher_id' => $teacherId,
            'exam_date' => $date,
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
