<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Mail\TeacherLogoutReportMail;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Subject;
use App\Models\TeacherLogoutReport;
use App\Models\TeacherSubject;
use App\Services\AuthActivityService;
use App\Support\ActivityLogger;
use App\Support\MailConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LogoutReportController extends Controller
{
    public function create(): View
    {
        $teacher = auth()->user();
        $assignments = TeacherSubject::query()
            ->with(['standard:id,name,slug', 'subject:id,name,standard_id'])
            ->where('teacher_id', $teacher->id)
            ->orderBy('medium')
            ->orderBy('standard_id')
            ->get();

        $options = $assignments->map(function (TeacherSubject $row) {
            $medium = Material::normalizeMedium($row->medium) ?: $row->medium;
            $standard = $row->standard;
            $subject = $row->subject;
            if (! $standard || ! $subject) {
                return null;
            }

            return [
                'key' => $medium.'|'.$standard->id.'|'.$subject->id,
                'medium' => $medium,
                'medium_label' => \App\Models\Standard::MEDIUMS[$medium] ?? ucfirst((string) $medium),
                'standard_id' => $standard->id,
                'standard_slug' => $standard->slug ?: $standard->name,
                'standard_name' => $standard->name,
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
            ];
        })->filter()->values();

        $oldKey = old('assignment_key');
        if (! $oldKey && $options->isNotEmpty()) {
            $oldKey = $options->first()['key'];
        }

        return view('teacher.logout-report.create', [
            'teacher' => $teacher,
            'employeeCode' => 'EMP-'.str_pad((string) $teacher->id, 4, '0', STR_PAD_LEFT),
            'options' => $options,
            'initialKey' => $oldKey,
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * Chapters for logout report = book materials (same source as Teacher Books).
     */
    public function chapters(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_key' => ['required', 'string'],
        ]);

        [$medium, $standardId, $subjectId, $subject] = $this->resolveAssignedSubject(
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

    /**
     * Topics for a selected material chapter.
     */
    public function topics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_key' => ['required', 'string'],
            'material_id' => ['required', 'integer', 'exists:materials,id'],
        ]);

        [$medium, $standardId, $subjectId, $subject] = $this->resolveAssignedSubject(
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

        [$medium, $standardId, $subjectId, $subject] = $this->resolveAssignedSubject(
            $teacher->id,
            $validated['assignment_key']
        );

        $assigned = TeacherSubject::query()
            ->with(['standard:id,name,slug'])
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subjectId)
            ->where('standard_id', $standardId)
            ->whereRaw('LOWER(TRIM(medium)) = ?', [strtolower(trim((string) $medium))])
            ->first();

        abort_unless($assigned, 403, 'Selected medium / standard / subject is not assigned to you.');
        $standard = $assigned->standard;

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

        $mailSent = $this->sendMail($report);
        if ($mailSent) {
            $report->update(['mail_sent' => true]);
        }

        ActivityLogger::log(
            'teacher.logout_report',
            'Logout report: '.$subject->name.' / '.$chapterName.' ('.$topics->count().' topics, '.$status.')',
            $report,
            [
                'medium' => $medium,
                'standard' => $standard?->name,
                'subject' => $subject->name,
                'chapter' => $chapterName,
                'material_id' => $material->id,
                'topics' => $topics->map(fn (MaterialTopic $t) => $t->displayName())->all(),
                'status' => $status,
                'mail_sent' => $mailSent,
            ],
            $teacher
        );

        AuthActivityService::endSession($request);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'Work report submitted'.($mailSent ? ' and emailed' : '').'. You are logged out.');
    }

    /**
     * @return array{0: string, 1: int, 2: int, 3: Subject}
     */
    private function resolveAssignedSubject(int $teacherId, string $assignmentKey): array
    {
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

        return [$medium, $standardId, $subjectId, $subject];
    }

    private function sendMail(TeacherLogoutReport $report): bool
    {
        $to = MailConfig::adminEmail();
        if (! $to) {
            return false;
        }

        try {
            MailConfig::apply();
            Mail::to($to)->send(new TeacherLogoutReportMail($report->load('teacher')));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
