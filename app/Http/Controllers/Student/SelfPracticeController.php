<?php

namespace App\Http\Controllers\Student;

use App\Models\Chapter;
use App\Models\ChapterQuestion;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Subject;
use App\Support\ChapterMaterialHelper;
use App\Support\MaterialPaperBank;
use App\Support\MaterialTopicReader;
use App\Support\MaterialWorkedExamples;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SelfPracticeController extends BaseStudentController
{
    public function index(): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);
        $medium = $user->medium;

        $subjects = Material::subjectsForStudent($standard, $medium);

        return view('student.self-practice.index', [
            'user' => $user,
            'standard' => $standard,
            'standardName' => $this->standardName($user->standard),
            'subjects' => $subjects,
            'medium' => $medium,
        ]);
    }

    public function subject(Subject $subject): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);

        abort_unless(
            $standard && $subject->standard_id === $standard->id && $subject->is_active,
            404
        );

        $subject->loadMissing('standard');
        $materials = Material::forStudentSubject($subject, $user->medium);

        return view('student.self-practice.subject', [
            'user' => $user,
            'standard' => $standard,
            'subject' => $subject,
            'materials' => $materials,
            'medium' => $user->medium,
        ]);
    }

    public function materialTopic(Subject $subject, MaterialTopic $materialTopic): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);

        $materialTopic->load(['material.chapter.subject']);
        $material = $materialTopic->material;
        $chapter = $material?->chapter;

        abort_unless(
            $standard
            && $material
            && $subject->standard_id === $standard->id
            && $subject->is_active
            && $materialTopic->hasContent()
            && $this->materialBelongsToSubject($material, $subject)
            && $this->mediumMatches($material->medium, $user->medium),
            404
        );

        $payload = MaterialTopicReader::forTopic($materialTopic);

        return view('student.self-practice.material-topic', [
            'user' => $user,
            'standard' => $standard,
            'subject' => $subject,
            'chapter' => $chapter ?: (object) [
                'id' => 0,
                'name' => $material->displayChapterName(),
            ],
            'material' => $material,
            'materialTopic' => $materialTopic,
            'topic' => (object) [
                'id' => $materialTopic->id,
                'name' => $materialTopic->displayName(),
            ],
            'content' => $payload['content'],
            'sections' => $payload['sections'],
            'questionGroups' => $payload['questionGroups'],
            'questionGroupLabels' => $payload['questionGroupLabels'],
            'textbookPoints' => MaterialTopicReader::textbookPoints($payload['sections']),
            'workedExamples' => $payload['workedExamples'] ?? collect(),
            'readerNav' => MaterialPaperBank::readerNavTree(
                $standard,
                $user->medium,
                'student.self-practice.material-topics.show'
            ),
            'readerMaterialId' => $material->id,
        ]);
    }

    public function material(Subject $subject, Material $material): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);

        $material->load(['chapter.subject', 'topics' => fn ($q) => $q->orderBy('topic_order')]);

        abort_unless(
            $standard
            && $subject->standard_id === $standard->id
            && $subject->is_active
            && $this->materialBelongsToSubject($material, $subject)
            && $this->mediumMatches($material->medium, $user->medium)
            && MaterialWorkedExamples::isExampleSubject($subject),
            404
        );

        $examples = MaterialWorkedExamples::fromMaterial($material);
        abort_unless($examples->isNotEmpty(), 404);

        $chapter = $material->chapter;

        return view('student.self-practice.material', [
            'user' => $user,
            'standard' => $standard,
            'subject' => $subject,
            'chapter' => $chapter ?: (object) [
                'id' => 0,
                'name' => $material->displayChapterName(),
            ],
            'material' => $material,
            'examples' => $examples,
            'backUrl' => route('student.self-practice.subject', $subject),
            'practiceMode' => true,
            'readerNav' => MaterialPaperBank::readerNavTree(
                $standard,
                $user->medium,
                'student.self-practice.material-topics.show'
            ),
        ]);
    }

    public function chapter(Subject $subject, Chapter $chapter): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);

        abort_unless(
            $standard
            && $subject->standard_id === $standard->id
            && $chapter->subject_id === $subject->id
            && $subject->is_active
            && $chapter->is_active,
            404
        );

        $chapter->load([
            'content.sections' => fn ($q) => $q->visibleToStudent()->orderBy('sort_order'),
            'content.questions' => fn ($q) => $q->visibleToStudent()->orderBy('sort_order'),
        ]);

        $content = $chapter->content;
        $questions = $content?->questions ?? collect();
        $questionGroups = ChapterMaterialHelper::groupQuestions($questions);

        $questionGroupLabels = collect($questionGroups)->mapWithKeys(fn ($items, $type) => [
            $type => ChapterQuestion::labelForType($type),
        ]);

        return view('student.self-practice.chapter', [
            'user' => $user,
            'standard' => $standard,
            'subject' => $subject,
            'chapter' => $chapter,
            'content' => $content,
            'sections' => $content?->sections ?? collect(),
            'questions' => $questions,
            'questionGroups' => $questionGroups,
            'questionGroupLabels' => $questionGroupLabels,
        ]);
    }

    private function materialBelongsToSubject(Material $material, Subject $subject): bool
    {
        if (strcasecmp(trim((string) $material->subject), trim($subject->name)) === 0) {
            return true;
        }

        return $subject->chapters()->where('id', $material->chapter_id)->exists()
            && in_array(trim((string) $material->subject), ['', $subject->name], true);
    }

    private function mediumMatches(?string $materialMedium, ?string $studentMedium): bool
    {
        $materialNorm = Material::normalizeMedium($materialMedium);
        $studentNorm = Material::normalizeMedium($studentMedium);

        if ($studentNorm === null) {
            return true;
        }

        return $materialNorm === $studentNorm;
    }
}
