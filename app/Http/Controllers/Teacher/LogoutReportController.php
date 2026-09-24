<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Subject;
use App\Models\TeacherLogoutReport;
use App\Models\TeacherSubject;
use App\Models\TeacherTimetable;
use App\Services\AuthActivityService;
use App\Support\ActivityLogger;
use App\Support\TeacherTimetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LogoutReportController extends Controller
{
    public function __construct(
        private readonly TeacherTimetableService $timetables
    ) {}

    public function create(): View
    {
        $teacher = auth()->user();
        $pack = $this->timetables->logoutReportOptions($teacher);
        $options = $pack['options'];

        $oldKey = old('assignment_key');
        if (! $oldKey && $options->isNotEmpty()) {
            $oldKey = $options->first()['key'];
        }

        return view('teacher.logout-report.create', [
            'teacher' => $teacher,
            'employeeCode' => 'EMP-'.str_pad((string) $teacher->id, 4, '0', STR_PAD_LEFT),
            'options' => $options,
            'optionsSource' => $pack['source'],
            'initialKey' => $oldKey,
            'today' => now()->toDateString(),
            'todaySlots' => $this->timetables->forTeacherOnDate($teacher->id),
        ]);
    }

    public function chapters(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_key' => ['required', 'string'],
        ]);

        [$medium, $standardId, $subjectId, $subject] = $this->resolveAssignment(
            auth()->user()->id,
            $validated['assignment_key']
        );

        $materials = Material::forStudentSubject($subject, $medium);

        return response()->json(
            $materials->values()->map(fn (Material $m) => [
                'id' => $m->id,
                'name' => $m->displayChapterName(),
            ])->all()
        );
    }

    public function topics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_key' => ['required', 'string'],
            'material_id' => ['required', 'integer', 'exists:materials,id'],
        ]);

        [$medium, $standardId, $subjectId, $subject] = $this->resolveAssignment(
            auth()->user()->id,
            $validated['assignment_key']
        );

        $material = Material::query()->findOrFail((int) $validated['material_id']);
        abort_unless($material->matchesStudentSubject($subject), 422);
        $normalized = Material::normalizeMedium($medium);
        abort_unless(
            $normalized === null
            || Material::normalizeMedium($material->medium) === $normalized
            || Material::normalizeMedium($material->medium) === null,
            422
        );

        $topics = MaterialTopic::query()
            ->where('material_id', $material->id)
            ->where('generated', true)
            ->orderBy('topic_order')
            ->get(['id', 'title', 'title_gu', 'topic_order']);

        return response()->json(
            $topics->map(fn (MaterialTopic $t) => [
                'id' => $t->id,
                'name' => $t->displayName(),
            ])->all()
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = auth()->user();

        $validated = $request->validate([
            'assignment_key' => ['required', 'string'],
            'material_id' => ['required', 'integer', 'exists:materials,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:material_topics,id'],
            'chk_complete' => ['nullable', 'boolean'],
            'chk_remain' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $chkComplete = $request->boolean('chk_complete');
        $chkRemain = $request->boolean('chk_remain');
        if (! $chkComplete && ! $chkRemain) {
            throw ValidationException::withMessages([
                'chk_complete' => 'Select Complete or Remain before logout.',
            ]);
        }

        [$medium, $standardId, $subjectId, $subject, $slot] = $this->resolveAssignment(
            $teacher->id,
            $validated['assignment_key'],
            true
        );

        $standard = $subject->standard;
        if (! $standard) {
            $assigned = TeacherSubject::query()
                ->with(['standard:id,name,slug'])
                ->where('teacher_id', $teacher->id)
                ->where('subject_id', $subjectId)
                ->where('standard_id', $standardId)
                ->whereRaw('LOWER(TRIM(medium)) = ?', [strtolower(trim((string) $medium))])
                ->first();
            $standard = $assigned?->standard;
        }

        $material = Material::query()->findOrFail((int) $validated['material_id']);
        abort_unless($material->matchesStudentSubject($subject), 422, 'Chapter does not belong to this subject.');

        $topicIds = collect($validated['topic_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $topics = MaterialTopic::query()
            ->where('material_id', $material->id)
            ->whereIn('id', $topicIds)
            ->orderBy('topic_order')
            ->get();

        if ($topicIds->isNotEmpty() && $topics->count() !== $topicIds->count()) {
            throw ValidationException::withMessages([
                'topic_ids' => 'Select valid topics for this chapter.',
            ]);
        }

        $status = $chkComplete ? 'complete' : 'remain';
        $firstTopic = $topics->first();
        $chapterName = $material->displayChapterName();

        $report = TeacherLogoutReport::query()->create([
            'teacher_id' => $teacher->id,
            'report_date' => now()->toDateString(),
            'employee_code' => 'EMP-'.str_pad((string) $teacher->id, 4, '0', STR_PAD_LEFT),
            'medium' => $medium,
            'standard' => $standard?->name ?: $standard?->slug,
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'timetable_id' => $slot?->id,
            'period_id' => $slot?->period_id,
            'period_label' => $slot?->period?->displayLabel(),
            'section' => $slot?->section,
            'chapter_id' => $material->chapter_id,
            'chapter_name' => $chapterName,
            'topic_id' => null,
            'topic_name' => $firstTopic?->displayName(),
            'topic_ids' => $topics->pluck('id')->all(),
            'topic_names' => $topics->map(fn (MaterialTopic $t) => $t->displayName())->implode(', '),
            'chk_medium' => true,
            'chk_standard' => true,
            'chk_subject' => true,
            'chk_chapter' => true,
            'chk_topic' => $topics->isNotEmpty(),
            'chk_complete' => $chkComplete,
            'chk_remain' => $chkRemain && ! $chkComplete,
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'mail_sent' => false,
            'submitted_at' => now(),
        ]);

        ActivityLogger::log(
            'teacher.logout_report',
            'Logout report: '.($slot?->optionLabel() ?: $subject->name).' / '.$chapterName.' ('.$topics->count().' topics, '.$status.')',
            $report,
            [
                'medium' => $medium,
                'standard' => $standard?->name,
                'subject' => $subject->name,
                'chapter' => $chapterName,
                'material_id' => $material->id,
                'timetable_id' => $slot?->id,
                'period' => $slot?->period?->displayLabel(),
                'topics' => $topics->map(fn (MaterialTopic $t) => $t->displayName())->all(),
                'status' => $status,
            ],
            $teacher
        );

        AuthActivityService::endSession($request);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'Work report submitted. You are logged out.');
    }

    /**
     * @return array{0: string, 1: int, 2: int, 3: Subject, 4: ?TeacherTimetable}
     */
    private function resolveAssignment(int $teacherId, string $assignmentKey, bool $withSlot = false): array
    {
        $slot = null;

        if (str_starts_with($assignmentKey, 'tt|')) {
            $slotId = (int) substr($assignmentKey, 3);
            $slot = TeacherTimetable::query()
                ->with(['period', 'standard:id,name,slug', 'subject:id,name,standard_id'])
                ->active()
                ->where('teacher_id', $teacherId)
                ->where('id', $slotId)
                ->first();

            abort_unless($slot, 403, 'Selected timetable period is not valid for you.');

            // Prefer today's weekday; still allow if admin changed day mid-session
            $medium = Material::normalizeMedium($slot->medium) ?: $slot->medium;
            $subject = $slot->subject ?: Subject::query()->findOrFail($slot->subject_id);
            $subject->setRelation('standard', $slot->standard);
            $subject->loadMissing('standard');

            $this->timetables->syncTeacherSubject($slot);

            return [$medium, (int) $slot->standard_id, (int) $slot->subject_id, $subject, $withSlot ? $slot : null];
        }

        [$medium, $standardId, $subjectId] = array_pad(explode('|', $assignmentKey), 3, null);
        $medium = Material::normalizeMedium($medium) ?: $medium;
        $standardId = (int) $standardId;
        $subjectId = (int) $subjectId;

        $assigned = TeacherSubject::query()
            ->where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->where('standard_id', $standardId)
            ->whereRaw('LOWER(TRIM(medium)) = ?', [strtolower(trim((string) $medium))])
            ->exists();

        abort_unless($assigned, 403, 'Selected medium / standard / subject is not assigned to you.');

        $subject = Subject::query()->findOrFail($subjectId);
        $subject->loadMissing('standard');

        return [$medium, $standardId, $subjectId, $subject, null];
    }
}
