<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Mail\TeacherLogoutReportMail;
use App\Models\Chapter;
use App\Models\Material;
use App\Models\Subject;
use App\Models\TeacherLogoutReport;
use App\Models\TeacherSubject;
use App\Models\Topic;
use App\Services\AuthActivityService;
use App\Support\ActivityLogger;
use App\Support\MailConfig;
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

    public function store(Request $request): RedirectResponse
    {
        $teacher = auth()->user();

        $validated = $request->validate([
            'assignment_key' => ['required', 'string'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:topics,id'],
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

        [$medium, $standardId, $subjectId] = array_pad(explode('|', $validated['assignment_key']), 3, null);
        $medium = Material::normalizeMedium($medium) ?: $medium;
        $standardId = (int) $standardId;
        $subjectId = (int) $subjectId;

        $assigned = TeacherSubject::query()
            ->with(['standard:id,name,slug', 'subject:id,name'])
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subjectId)
            ->where('standard_id', $standardId)
            ->whereRaw('LOWER(TRIM(medium)) = ?', [strtolower(trim((string) $medium))])
            ->first();

        abort_unless($assigned, 403, 'Selected medium / standard / subject is not assigned to you.');

        $subject = $assigned->subject;
        $standard = $assigned->standard;
        $chapter = Chapter::query()->findOrFail((int) $validated['chapter_id']);
        abort_unless((int) $chapter->subject_id === (int) $subject->id, 422);

        $topicIds = collect($validated['topic_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $topics = Topic::query()
            ->where('chapter_id', $chapter->id)
            ->whereIn('id', $topicIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        if ($topicIds->isNotEmpty() && $topics->isEmpty()) {
            throw ValidationException::withMessages([
                'topic_ids' => 'Select valid topics for this chapter.',
            ]);
        }

        $status = $chkComplete ? 'complete' : 'remain';
        $firstTopic = $topics->first();

        $report = TeacherLogoutReport::query()->create([
            'teacher_id' => $teacher->id,
            'report_date' => now()->toDateString(),
            'employee_code' => 'EMP-'.str_pad((string) $teacher->id, 4, '0', STR_PAD_LEFT),
            'medium' => $medium,
            'standard' => $standard?->name ?: $standard?->slug,
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'chapter_id' => $chapter->id,
            'chapter_name' => $chapter->name,
            'topic_id' => $firstTopic?->id,
            'topic_name' => $firstTopic?->name,
            'topic_ids' => $topics->pluck('id')->all(),
            'topic_names' => $topics->pluck('name')->implode(', '),
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
            'Logout report: '.$subject->name.' / '.$chapter->name.' ('.$topics->count().' topics, '.$status.')',
            $report,
            [
                'medium' => $medium,
                'standard' => $standard?->name,
                'subject' => $subject->name,
                'chapter' => $chapter->name,
                'topics' => $topics->pluck('name')->all(),
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
