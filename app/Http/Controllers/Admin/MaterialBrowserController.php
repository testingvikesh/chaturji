<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Standard;
use App\Models\Subject;
use App\Support\MaterialTopicReader;
use App\Support\MaterialWorkedExamples;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialBrowserController extends Controller
{
    public function index(): View
    {
        $mediums = collect(Standard::MEDIUMS)->map(function (string $label, string $key) {
            $count = Material::query()->forMedium($key)->count();
            $standards = Standard::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (Standard $s) => Material::subjectsForStudent($s, $key)->isNotEmpty())
                ->count();

            return [
                'key' => $key,
                'label' => $label,
                'materials_count' => $count,
                'standards_count' => $standards,
            ];
        })->filter(fn (array $row) => $row['materials_count'] > 0)->values();

        return view('admin.materials.index', [
            'mediums' => $mediums,
            'totalMaterials' => Material::query()->count(),
        ]);
    }

    public function medium(string $medium): View
    {
        $medium = $this->normalizeMediumOrFail($medium);

        $standards = Standard::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Standard $standard) use ($medium) {
                $subjects = Material::subjectsForStudent($standard, $medium);
                if ($subjects->isEmpty()) {
                    return null;
                }
                $standard->setAttribute('subjects_count', $subjects->count());
                $standard->setAttribute('chapters_count', (int) $subjects->sum('chapters_count'));

                return $standard;
            })
            ->filter()
            ->values();

        return view('admin.materials.medium', [
            'medium' => $medium,
            'mediumLabel' => Standard::MEDIUMS[$medium] ?? ucfirst($medium),
            'standards' => $standards,
        ]);
    }

    public function standard(string $medium, Standard $standard): View
    {
        $medium = $this->normalizeMediumOrFail($medium);
        abort_unless($standard->is_active, 404);

        $subjects = Material::subjectsForStudent($standard, $medium);
        abort_unless($subjects->isNotEmpty(), 404);

        return view('admin.materials.standard', [
            'medium' => $medium,
            'mediumLabel' => Standard::MEDIUMS[$medium] ?? ucfirst($medium),
            'standard' => $standard,
            'subjects' => $subjects,
        ]);
    }

    public function subject(string $medium, Subject $subject): View
    {
        $medium = $this->normalizeMediumOrFail($medium);
        abort_unless($subject->is_active, 404);

        $subject->loadMissing('standard');
        $standard = $subject->standard;
        abort_unless($standard && $standard->is_active, 404);

        $materials = Material::forStudentSubject($subject, $medium);
        abort_unless($materials->isNotEmpty(), 404);

        return view('admin.materials.subject', [
            'medium' => $medium,
            'mediumLabel' => Standard::MEDIUMS[$medium] ?? ucfirst($medium),
            'standard' => $standard,
            'subject' => $subject,
            'materials' => $materials,
        ]);
    }

    public function topic(string $medium, Subject $subject, MaterialTopic $materialTopic): View
    {
        $medium = $this->normalizeMediumOrFail($medium);
        abort_unless($subject->is_active, 404);

        $materialTopic->load(['material.chapter.subject.standard']);
        $material = $materialTopic->material;
        $chapter = $material?->chapter;

        abort_unless(
            $material
            && $materialTopic->hasContent()
            && (
                ($chapter && (int) $chapter->subject_id === (int) $subject->id && $chapter->is_active
                    && in_array(trim((string) $material->subject), ['', $subject->name], true))
                || strcasecmp(trim((string) $material->subject), trim($subject->name)) === 0
            )
            && Material::normalizeMedium($material->medium) === $medium,
            404
        );

        $subject->loadMissing('standard');
        $payload = MaterialTopicReader::forTopic($materialTopic);
        $textbookPoints = MaterialTopicReader::textbookPoints($payload['sections']);

        return view('admin.materials.topic', [
            'medium' => $medium,
            'mediumLabel' => Standard::MEDIUMS[$medium] ?? ucfirst($medium),
            'standard' => $subject->standard,
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
            'readerNav' => $this->readerNav($subject->standard, $medium),
            'readerMaterialId' => $material->id,
        ]);
    }

    public function material(string $medium, Subject $subject, Material $material): View
    {
        $medium = $this->normalizeMediumOrFail($medium);
        abort_unless($subject->is_active, 404);

        $material->load(['chapter.subject.standard', 'topics' => fn ($q) => $q->orderBy('topic_order')]);

        abort_unless(
            $material->matchesStudentSubject($subject)
            && Material::normalizeMedium($material->medium) === $medium
            && MaterialWorkedExamples::isExampleSubject($subject),
            404
        );

        $examples = MaterialWorkedExamples::fromMaterial($material);
        abort_unless($examples->isNotEmpty(), 404);

        $subject->loadMissing('standard');
        $chapter = $material->chapter;

        return view('admin.materials.material', [
            'medium' => $medium,
            'mediumLabel' => Standard::MEDIUMS[$medium] ?? ucfirst($medium),
            'standard' => $subject->standard,
            'subject' => $subject,
            'chapter' => $chapter ?: (object) [
                'id' => 0,
                'name' => $material->displayChapterName(),
            ],
            'material' => $material,
            'examples' => $examples,
            'readerNav' => $this->readerNav($subject->standard, $medium),
        ]);
    }

    private function normalizeMediumOrFail(string $medium): string
    {
        $normalized = Material::normalizeMedium($medium);
        abort_unless($normalized && isset(Standard::MEDIUMS[$normalized]), 404);

        return $normalized;
    }

    /**
     * @return array<int, array{label: string, url: string, active?: bool}>
     */
    private function readerNav(?Standard $standard, string $medium): array
    {
        if (! $standard) {
            return [];
        }

        $subjects = Material::subjectsForStudent($standard, $medium);

        return $subjects->map(fn (Subject $s) => [
            'label' => $s->name,
            'url' => route('admin.materials.subject', ['medium' => $medium, 'subject' => $s]),
            'active' => false,
        ])->values()->all();
    }
}
