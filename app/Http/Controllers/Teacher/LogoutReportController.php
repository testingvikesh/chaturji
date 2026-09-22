<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Mail\TeacherLogoutReportMail;
use App\Models\Chapter;
use App\Models\Material;
use App\Models\Standard;
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

        $mediums = $assignments
            ->pluck('medium')
            ->map(fn ($m) => Material::normalizeMedium($m) ?: $m)
            ->filter()
            ->unique()
            ->values();

        if ($mediums->isEmpty() && $teacher->medium) {
            $mediums = collect([Material::normalizeMedium($teacher->medium) ?: $teacher->medium]);
        }

        return view('teacher.logout-report.create', [
            'teacher' => $teacher,
            'employeeCode' => 'EMP-'.str_pad((string) $teacher->id, 4, '0', STR_PAD_LEFT),
            'mediums' => $mediums,
            'assignments' => $assignments,
            'standards' => Standard::query()->where('is_active', true)->orderedByNumber()->get(['id', 'name', 'slug']),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = auth()->user();

        $validated = $request->validate([
            'medium' => ['required', 'string', 'max:32'],
            'standard' => ['required', 'string', 'max:50'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'chk_medium' => ['nullable', 'boolean'],
            'chk_standard' => ['nullable', 'boolean'],
            'chk_subject' => ['nullable', 'boolean'],
            'chk_chapter' => ['nullable', 'boolean'],
            'chk_topic' => ['nullable', 'boolean'],
            'chk_complete' => ['nullable', 'boolean'],
            'chk_remain' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $chkComplete = $request->boolean('chk_complete');
        $chkRemain = $request->boolean('chk_remain');

        if (! $chkComplete && ! $chkRemain) {
            return back()
                ->withInput()
                ->withErrors(['chk_complete' => 'Select Complete or Remain before logout.']);
        }

        $subject = Subject::query()->findOrFail((int) $validated['subject_id']);
        $chapter = Chapter::query()->findOrFail((int) $validated['chapter_id']);
        abort_unless((int) $chapter->subject_id === (int) $subject->id, 422);

        $topic = null;
        if (! empty($validated['topic_id'])) {
            $topic = Topic::query()->find((int) $validated['topic_id']);
            if ($topic && (int) $topic->chapter_id !== (int) $chapter->id) {
                $topic = null;
            }
        }

        $medium = Material::normalizeMedium($validated['medium']) ?: $validated['medium'];
        $status = $chkComplete ? 'complete' : 'remain';

        $report = TeacherLogoutReport::query()->create([
            'teacher_id' => $teacher->id,
            'report_date' => now()->toDateString(),
            'employee_code' => 'EMP-'.str_pad((string) $teacher->id, 4, '0', STR_PAD_LEFT),
            'medium' => $medium,
            'standard' => $validated['standard'],
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'chapter_id' => $chapter->id,
            'chapter_name' => $chapter->name,
            'topic_id' => $topic?->id,
            'topic_name' => $topic?->name,
            'chk_medium' => $request->boolean('chk_medium'),
            'chk_standard' => $request->boolean('chk_standard'),
            'chk_subject' => $request->boolean('chk_subject'),
            'chk_chapter' => $request->boolean('chk_chapter'),
            'chk_topic' => $request->boolean('chk_topic'),
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
            'Logout report: '.$subject->name.' / '.$chapter->name.' ('.$status.')',
            $report,
            [
                'medium' => $medium,
                'standard' => $validated['standard'],
                'subject' => $subject->name,
                'chapter' => $chapter->name,
                'topic' => $topic?->name,
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
