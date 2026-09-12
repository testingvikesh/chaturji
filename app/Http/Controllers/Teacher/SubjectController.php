<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\Subject;
use App\Support\TeacherSubjectStats;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function show(Subject $subject): View
    {
        abort_unless($subject->is_active, 404);

        $teacher = auth()->user();
        $subject->load('standard');
        $subject->loadCount(['chapters' => fn ($q) => $q->where('is_active', true)]);

        TeacherSubjectStats::attachStatsToSubject($subject, $teacher->id);

        $chapters = $subject->chapters()
            ->where('is_active', true)
            ->with([
                'topics' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'content:id,chapter_id,title',
            ])
            ->orderBy('sort_order')
            ->get();

        $exams = Exam::query()
            ->where('teacher_id', $teacher->id)
            ->excludeSelfExams()
            ->where('subject_id', $subject->id)
            ->with(['chapter:id,name'])
            ->withCount('questions')
            ->latest()
            ->get();

        $homeworks = Homework::query()
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subject->id)
            ->with(['chapter:id,name'])
            ->withCount('questions')
            ->latest()
            ->get();

        return view('teacher.subjects.show', [
            'teacher' => $teacher,
            'subject' => $subject,
            'standard' => $subject->standard,
            'chapters' => $chapters,
            'exams' => $exams,
            'homeworks' => $homeworks,
        ]);
    }
}
