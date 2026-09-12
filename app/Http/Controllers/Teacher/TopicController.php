<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\ChapterQuestion;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\ChapterMaterialHelper;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function show(Subject $subject, Topic $topic): View
    {
        abort_unless($subject->is_active, 404);

        $topic->load('chapter.subject.standard');
        abort_unless($topic->chapter->subject_id === $subject->id, 404);

        return $this->renderMaterial($subject, $topic->chapter, $topic);
    }

    public function chapter(Subject $subject, Chapter $chapter): View
    {
        abort_unless($subject->is_active && $chapter->is_active, 404);
        abort_unless($chapter->subject_id === $subject->id, 404);

        return $this->renderMaterial($subject, $chapter, null);
    }

    private function renderMaterial(Subject $subject, Chapter $chapter, ?Topic $topic): View
    {
        if ($topic) {
            abort_unless($topic->chapter_id === $chapter->id && $topic->is_active, 404);
        }

        $subject->load('standard');

        $chapter->load([
            'content.sections' => fn ($q) => $q->visibleToStudent()->orderBy('sort_order'),
            'content.questions' => fn ($q) => $q->visibleToStudent()->orderBy('sort_order'),
            'topics' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
        ]);

        $content = $chapter->content;
        abort_unless($content, 404);

        $questionGroups = ChapterMaterialHelper::groupQuestions($content->questions);

        $questionGroupLabels = collect($questionGroups)->mapWithKeys(fn ($questions, $type) => [
            $type => ChapterQuestion::labelForType($type),
        ]);

        return view('teacher.topics.show', [
            'teacher' => auth()->user(),
            'standard' => $subject->standard,
            'subject' => $subject,
            'chapter' => $chapter,
            'topic' => $topic,
            'content' => $content,
            'sections' => $content->sections,
            'questionGroups' => $questionGroups,
            'questionGroupLabels' => $questionGroupLabels,
        ]);
    }
}
