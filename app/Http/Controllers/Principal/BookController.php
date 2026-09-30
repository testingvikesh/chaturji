<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use App\Support\MaterialPaperBank;
use App\Support\MaterialTopicReader;
use App\Support\MaterialWorkedExamples;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $principal = auth()->user();
        $allotted = $this->allottedStandards($principal);
        $medium = $this->resolveMedium($request, $allotted);

        $standards = $allotted
            ->filter(fn (Standard $standard) => $this->standardMatchesMedium($standard, $medium))
            ->map(function (Standard $standard) use ($medium) {
                $subjects = Material::subjectsForStudent($standard, $medium);
                if ($subjects->isEmpty()) {
                    return null;
                }
                $standard->setRelation('bookSubjects', $subjects);

                return $standard;
            })
            ->filter()
            ->values();

        $mediums = collect(Standard::MEDIUMS)
            ->only(
                $allotted
                    ->map(fn (Standard $s) => Material::normalizeMedium($s->medium) ?: $s->medium)
                    ->filter(fn ($key) => array_key_exists((string) $key, Standard::MEDIUMS))
                    ->unique()
                    ->values()
                    ->all()
            )
            ->all();

        // Standards table medium may be empty; still allow english/gujarati tabs from materials.
        if ($mediums === [] && $allotted->isNotEmpty()) {
            $mediums = Standard::MEDIUMS;
        }

        return view('principal.books.index', [
            'principal' => $principal,
            'medium' => $medium,
            'mediums' => $mediums,
            'standards' => $standards,
            'hasAllotments' => $allotted->isNotEmpty(),
        ]);
    }

    public function show(Request $request, Subject $subject): View
    {
        abort_unless($subject->is_active, 404);

        $principal = auth()->user();
        $medium = $this->resolveMedium($request, $this->allottedStandards($principal));
        $this->assertAllotted($principal, $subject);

        $subject->loadMissing('standard');
        $standard = $subject->standard;
        abort_unless($standard && $standard->is_active, 404);

        $materials = Material::forStudentSubject($subject, $medium);

        return view('principal.books.show', [
            'principal' => $principal,
            'standard' => $standard,
            'subject' => $subject,
            'materials' => $materials,
            'medium' => $medium,
        ]);
    }

    public function topic(Request $request, Subject $subject, MaterialTopic $materialTopic): View
    {
        $principal = auth()->user();
        $medium = $this->resolveMedium($request, $this->allottedStandards($principal));
        $this->assertAllotted($principal, $subject);

        $materialTopic->load(['material.chapter.subject.standard']);
        $material = $materialTopic->material;
        $chapter = $material?->chapter;

        abort_unless(
            $subject->is_active
            && $material
            && $materialTopic->hasContent()
            && (
                ($chapter && (int) $chapter->subject_id === (int) $subject->id && $chapter->is_active
                    && in_array(trim((string) $material->subject), ['', $subject->name], true))
                || strcasecmp(trim((string) $material->subject), trim($subject->name)) === 0
            )
            && (
                Material::normalizeMedium($medium) === null
                || Material::normalizeMedium($material->medium) === Material::normalizeMedium($medium)
            ),
            404
        );

        $subject->loadMissing('standard');
        $standard = $subject->standard;

        $payload = MaterialTopicReader::forTopic($materialTopic);

        return view('principal.books.topic', [
            'principal' => $principal,
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
            'readerNav' => $this->readerNav($principal, $standard, $medium),
            'readerMaterialId' => $material->id,
            'medium' => $medium,
        ]);
    }

    public function material(Request $request, Subject $subject, Material $material): View
    {
        abort_unless($subject->is_active, 404);

        $principal = auth()->user();
        $medium = $this->resolveMedium($request, $this->allottedStandards($principal));
        $this->assertAllotted($principal, $subject);

        $material->load(['chapter.subject.standard', 'topics' => fn ($q) => $q->orderBy('topic_order')]);

        abort_unless(
            $material->matchesStudentSubject($subject)
            && (
                Material::normalizeMedium($medium) === null
                || Material::normalizeMedium($material->medium) === Material::normalizeMedium($medium)
            )
            && MaterialWorkedExamples::isExampleSubject($subject),
            404
        );

        $examples = MaterialWorkedExamples::fromMaterial($material);
        abort_unless($examples->isNotEmpty(), 404);

        $subject->loadMissing('standard');
        $chapter = $material->chapter;

        return view('principal.books.material', [
            'principal' => $principal,
            'standard' => $subject->standard,
            'subject' => $subject,
            'chapter' => $chapter ?: (object) [
                'id' => 0,
                'name' => $material->displayChapterName(),
            ],
            'material' => $material,
            'examples' => $examples,
            'backUrl' => route('principal.books.show', ['subject' => $subject, 'medium' => $medium]),
            'practiceMode' => false,
            'readerNav' => $this->readerNav($principal, $subject->standard, $medium),
            'medium' => $medium,
        ]);
    }

    private function allottedStandards(?User $principal): Collection
    {
        if (! $principal) {
            return collect();
        }

        return $principal->allottedStandards()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    private function assertAllotted(User $principal, Subject $subject): void
    {
        abort_unless(
            $principal->allottedStandards()->where('standards.id', $subject->standard_id)->exists(),
            404
        );
    }

    private function resolveMedium(Request $request, Collection $allotted): string
    {
        $requested = Material::normalizeMedium($request->query('medium'));
        if ($requested && array_key_exists($requested, Standard::MEDIUMS)) {
            return $requested;
        }

        $fromStandards = $allotted
            ->map(fn (Standard $s) => Material::normalizeMedium($s->medium) ?: $s->medium)
            ->filter(fn ($key) => array_key_exists((string) $key, Standard::MEDIUMS))
            ->unique()
            ->values();

        if ($fromStandards->isNotEmpty()) {
            return (string) $fromStandards->first();
        }

        return 'english';
    }

    private function standardMatchesMedium(Standard $standard, string $medium): bool
    {
        $standardMedium = Material::normalizeMedium($standard->medium) ?: $standard->medium;
        if ($standardMedium === '' || $standardMedium === null) {
            return true;
        }

        return $standardMedium === $medium;
    }

    private function readerNav(User $principal, ?Standard $standard, string $medium): array
    {
        try {
            if (! $standard || ! $principal->allottedStandards()->where('standards.id', $standard->id)->exists()) {
                return [];
            }

            return MaterialPaperBank::readerNavTree($standard, $medium, 'principal.books.topics.show', [
                'medium' => $medium,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }
}
