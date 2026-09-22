<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\TeachingLog;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DailySyllabusController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = auth()->user();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = TeachingLog::query()
            ->where('teacher_id', $teacher->id)
            ->with(['subject:id,name', 'chapter:id,name', 'topic:id,name'])
            ->latest('teaching_date')
            ->latest('id');

        if ($dateFrom) {
            $query->whereDate('teaching_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('teaching_date', '<=', $dateTo);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $logs = $query->paginate(500)->withQueryString();

        return view('teacher.daily-syllabus.index', [
            'teacher' => $teacher,
            'logs' => $logs,
            'filters' => $request->only(['date_from', 'date_to', 'status']),
        ]);
    }

    public function create(Request $request): View
    {
        $teacher = auth()->user();
        $today = Carbon::today();
        $standards = $this->standardOptions();

        $prefill = [
            'standard' => old('standard', $request->string('standard')->toString()),
            'subject_id' => old('subject_id', $request->input('subject_id')),
            'chapter_id' => old('chapter_id', $request->input('chapter_id')),
            'topic_id' => old('topic_id', $request->input('topic_id')),
        ];

        return view('teacher.daily-syllabus.create', [
            'teacher' => $teacher,
            'employeeId' => 'EMP-'.str_pad((string) $teacher->id, 4, '0', STR_PAD_LEFT),
            'academicYear' => $this->academicYear($today),
            'today' => $today,
            'dayName' => $today->format('l'),
            'standards' => $standards,
            'prefill' => $prefill,
            'progress' => $this->emptyProgress(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teaching_date' => ['required', 'date'],
            'standard' => ['required', 'string', 'max:50'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'status' => ['required', 'in:completed,partial,remaining'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'concept_covered' => ['nullable', 'string', 'max:500'],
            'homework_given' => ['nullable', 'boolean'],
            'material_shared' => ['nullable', 'boolean'],
            'self_test_given' => ['nullable', 'boolean'],
            'period_label' => ['nullable', 'string', 'max:100'],
            'section' => ['nullable', 'string', 'max:50'],
            'action' => ['nullable', 'in:save,save_next,mark_completed'],
        ]);

        if (($validated['action'] ?? '') === 'mark_completed') {
            $validated['status'] = TeachingLog::STATUS_COMPLETED;
        }

        $chapter = Chapter::query()->find((int) $validated['chapter_id']);
        $topic = Topic::query()->find((int) $validated['topic_id']);

        if (! $chapter || (int) $chapter->subject_id !== (int) $validated['subject_id']) {
            return back()->withInput()->with('error', 'Selected chapter does not belong to this subject.');
        }

        if (! $topic || (int) $topic->chapter_id !== (int) $validated['chapter_id']) {
            return back()->withInput()->with('error', 'Selected topic does not belong to this chapter.');
        }

        $teacherId = auth()->id();
        $existing = TeachingLog::query()
            ->where('teacher_id', $teacherId)
            ->whereDate('teaching_date', $validated['teaching_date'])
            ->where('standard', $validated['standard'])
            ->where('subject_id', $validated['subject_id'])
            ->where('chapter_id', $validated['chapter_id'])
            ->where('topic_id', $validated['topic_id'])
            ->first();

        $payload = [
            'teacher_id' => $teacherId,
            'teaching_date' => $validated['teaching_date'],
            'standard' => $validated['standard'],
            'subject_id' => (int) $validated['subject_id'],
            'chapter_id' => (int) $validated['chapter_id'],
            'topic_id' => (int) $validated['topic_id'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'concept_covered' => $validated['concept_covered'] ?? null,
            'homework_given' => (bool) ($validated['homework_given'] ?? false),
            'material_shared' => (bool) ($validated['material_shared'] ?? false),
            'self_test_given' => (bool) ($validated['self_test_given'] ?? false),
            'period_label' => $validated['period_label'] ?? null,
            'section' => $validated['section'] ?? null,
        ];

        if ($existing) {
            $existing->update($payload);
            $message = 'Daily syllabus update saved.';
        } else {
            TeachingLog::query()->create($payload);
            $message = 'Daily syllabus update submitted successfully.';
        }

        if (($validated['action'] ?? '') === 'save_next') {
            return redirect()
                ->route('teacher.daily-syllabus.create', [
                    'standard' => $validated['standard'],
                    'subject_id' => $validated['subject_id'],
                    'chapter_id' => $validated['chapter_id'],
                ])
                ->with('success', $message.' Add the next topic.');
        }

        return redirect()
            ->route('teacher.daily-syllabus.index')
            ->with('success', $message);
    }

    public function progress(Request $request)
    {
        $teacherId = auth()->id();
        $standard = $request->string('standard')->toString();
        $subjectId = (int) $request->input('subject_id');
        $chapterId = (int) $request->input('chapter_id');

        return response()->json($this->computeProgress($teacherId, $standard, $subjectId, $chapterId));
    }

    public function pending(Request $request): View
    {
        $teacher = auth()->user();
        $logs = TeachingLog::query()
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', [TeachingLog::STATUS_REMAINING, TeachingLog::STATUS_PARTIAL])
            ->with(['subject:id,name', 'chapter:id,name', 'topic:id,name'])
            ->latest('teaching_date')
            ->paginate(500);

        return view('teacher.daily-syllabus.pending', [
            'teacher' => $teacher,
            'logs' => $logs,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function standardOptions(): array
    {
        return Standard::query()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get()
            ->mapWithKeys(fn (Standard $s) => [$s->slug => $s->name.' ('.$s->mediumLabel().')'])
            ->all();
    }

    private function academicYear(Carbon $date): string
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        if ($month >= 6) {
            return $year.'-'.substr((string) ($year + 1), -2);
        }

        return ($year - 1).'-'.substr((string) $year, -2);
    }

    /**
     * @return array<string, int|string>
     */
    private function emptyProgress(): array
    {
        return [
            'chapter_percent' => 0,
            'subject_percent' => 0,
            'class_percent' => 0,
            'total_chapters' => 0,
            'completed_chapters' => 0,
            'pending_chapters' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function computeProgress(int $teacherId, string $standard, int $subjectId, int $chapterId): array
    {
        $chapterTopicIds = Topic::query()
            ->where('chapter_id', $chapterId)
            ->where('is_active', true)
            ->pluck('id');

        $subjectChapterIds = Chapter::query()
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->pluck('id');

        $completedChapterTopics = TeachingLog::query()
            ->where('teacher_id', $teacherId)
            ->where('status', TeachingLog::STATUS_COMPLETED)
            ->when($standard !== '', fn ($q) => $q->where('standard', $standard))
            ->where('chapter_id', $chapterId)
            ->whereIn('topic_id', $chapterTopicIds)
            ->distinct()
            ->count('topic_id');

        $completedSubjectChapters = TeachingLog::query()
            ->where('teacher_id', $teacherId)
            ->where('status', TeachingLog::STATUS_COMPLETED)
            ->when($standard !== '', fn ($q) => $q->where('standard', $standard))
            ->where('subject_id', $subjectId)
            ->whereIn('chapter_id', $subjectChapterIds)
            ->distinct()
            ->pluck('chapter_id');

        $classSubjectIds = Subject::query()
            ->whereHas('standard', fn ($q) => $q->where('slug', $standard))
            ->where('is_active', true)
            ->pluck('id');

        $classChapterIds = Chapter::query()
            ->whereIn('subject_id', $classSubjectIds)
            ->where('is_active', true)
            ->pluck('id');

        $completedClassChapters = TeachingLog::query()
            ->where('teacher_id', $teacherId)
            ->where('status', TeachingLog::STATUS_COMPLETED)
            ->where('standard', $standard)
            ->whereIn('chapter_id', $classChapterIds)
            ->distinct()
            ->count('chapter_id');

        $totalChapterTopics = max(1, $chapterTopicIds->count());
        $totalSubjectChapters = max(1, $subjectChapterIds->count());
        $totalClassChapters = max(1, $classChapterIds->count());

        return [
            'chapter_percent' => (int) round(($completedChapterTopics / $totalChapterTopics) * 100),
            'subject_percent' => (int) round(($completedSubjectChapters->count() / $totalSubjectChapters) * 100),
            'class_percent' => (int) round(($completedClassChapters / $totalClassChapters) * 100),
            'total_chapters' => $subjectChapterIds->count(),
            'completed_chapters' => $completedSubjectChapters->count(),
            'pending_chapters' => max(0, $subjectChapterIds->count() - $completedSubjectChapters->count()),
        ];
    }
}
