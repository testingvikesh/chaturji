<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Standard;
use App\Models\Subject;
use App\Support\MaterialPaperBank;
use App\Support\MaterialTopicReader;
use App\Support\MaterialWorkedExamples;
use Illuminate\View\View;

class MaterialTopicController extends Controller
{
    public function show(Subject $subject, MaterialTopic $materialTopic): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);

        $materialTopic->load(['material.chapter.subject.standard']);
        $material = $materialTopic->material;
        $chapter = $material?->chapter;

        abort_unless(
            $standard
            && $material
            && $subject->standard_id === $standard->id
            && $subject->is_active
            && $materialTopic->hasContent()
            && (
                ($chapter && $chapter->subject_id === $subject->id && $chapter->is_active
                    && in_array(trim((string) $material->subject), ['', $subject->name], true))
                || strcasecmp(trim((string) $material->subject), trim($subject->name)) === 0
            )
            && (
                Material::normalizeMedium($user->medium) === null
                || Material::normalizeMedium($material->medium) === Material::normalizeMedium($user->medium)
            ),
            404
        );

        $payload = MaterialTopicReader::forTopic($materialTopic);
        $textbookPoints = MaterialTopicReader::textbookPoints($payload['sections']);

        try {
            $readerNav = MaterialPaperBank::readerNavTree($standard, $user->medium);
        } catch (\Throwable $e) {
            report($e);
            $readerNav = [];
        }

        return view('student.material-topics.show', [
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
            'textbookPoints' => $textbookPoints,
            'workedExamples' => $payload['workedExamples'] ?? collect(),
            'readerNav' => $readerNav,
            'readerMaterialId' => $material->id,
        ]);
    }

    public function material(Subject $subject, Material $material): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);

        $material->load(['chapter.subject.standard', 'topics' => fn ($q) => $q->orderBy('topic_order')]);

        abort_unless(
            $standard
            && $subject->standard_id === $standard->id
            && $subject->is_active
            && $material->matchesStudentSubject($subject)
            && (
                Material::normalizeMedium($user->medium) === null
                || Material::normalizeMedium($material->medium) === Material::normalizeMedium($user->medium)
            )
            && MaterialWorkedExamples::isExampleSubject($subject),
            404
        );

        $examples = MaterialWorkedExamples::fromMaterial($material);
        abort_unless($examples->isNotEmpty(), 404);

        $chapter = $material->chapter;

        return view('student.materials.show', [
            'user' => $user,
            'standard' => $standard,
            'subject' => $subject,
            'chapter' => $chapter ?: (object) [
                'id' => 0,
                'name' => $material->displayChapterName(),
            ],
            'material' => $material,
            'examples' => $examples,
            'backUrl' => route('student.subjects.show', $subject),
            'practiceMode' => false,
            'readerNav' => MaterialPaperBank::readerNavTree($standard, $user->medium),
        ]);
    }

    private function resolveStandard(?string $slug): ?Standard
    {
        if (! $slug) {
            return null;
        }

        return Standard::where('slug', $slug)->where('is_active', true)->first();
    }
}
