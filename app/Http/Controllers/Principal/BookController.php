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
    /** Full topic/material content opens only from this standard number upward. */
    private const FULL_MATERIAL_FROM_STANDARD = 5;

    public function index(Request $request): View
    {
        $principal = auth()->user();
        $allotted = $this->allottedStandards($principal);
        $requestedMedium = Material::normalizeMedium($request->query('medium'));
        $mediumFilter = ($requestedMedium && array_key_exists($requestedMedium, Standard::MEDIUMS))
            ? $requestedMedium
            : '';
        $search = $request->string('search')->trim()->toString();
        $standardId = $request->integer('standard_id') ?: null;
        $allottedIds = $allotted->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($standardId && ! in_array($standardId, $allottedIds, true)) {
            $standardId = null;
        }

        $source = $standardId
            ? $allotted->where('id', $standardId)->values()
            : $allotted;

        $mediumsToShow = $mediumFilter !== ''
            ? [$mediumFilter]
            : array_keys(Standard::MEDIUMS);

        // Standard-wise: show both English and Gujarati (or the selected medium filter).
        $standards = $source
            ->sortBy(fn (Standard $standard) => (int) Material::standardNumber($standard))
            ->map(function (Standard $standard) use ($mediumsToShow, $search) {
                $mediumBlocks = collect($mediumsToShow)
                    ->map(function (string $medium) use ($standard, $search) {
                        $subjects = Material::subjectsForStudent($standard, $medium);
                        if ($search !== '') {
                            $needle = mb_strtolower($search);
                            $subjects = $subjects
                                ->filter(fn ($subject) => str_contains(mb_strtolower((string) $subject->name), $needle))
                                ->values();
                        }
                        if ($subjects->isEmpty()) {
                            return null;
                        }

                        return [
                            'medium' => $medium,
                            'label' => Standard::MEDIUMS[$medium] ?? ucfirst($medium),
                            'subjects' => $subjects,
                        ];
                    })
                    ->filter()
                    ->values();

                if ($mediumBlocks->isEmpty()) {
                    return null;
                }

                $standard->setAttribute('medium_blocks', $mediumBlocks);
                $standard->setAttribute('allows_full_material', $this->allowsFullMaterial($standard));

                return $standard;
            })
            ->filter()
            ->values();

        return view('principal.books.index', [
            'principal' => $principal,
            'medium' => $mediumFilter,
            'mediums' => Standard::MEDIUMS,
            'allottedStandards' => $allotted,
            'standards' => $standards,
            'hasAllotments' => $allotted->isNotEmpty(),
            'filters' => [
                'search' => $search,
                'medium' => $mediumFilter,
                'standard_id' => $standardId ? (string) $standardId : '',
            ],
        ]);
    }

    public function show(Request $request, Subject $subject): View
    {
        abort_unless($subject->is_active, 404);

        $principal = auth()->user();
        $this->assertAllotted($principal, $subject);

        $subject->loadMissing('standard');
        $standard = $subject->standard;
        abort_unless($standard && $standard->is_active, 404);

        $medium = $this->resolveMediumForSubject($request, $subject, $this->allottedStandards($principal));
        $materials = Material::forStudentSubject($subject, $medium);
        // If the selected medium has no chapters, fall back so primary/secondary books still open.
        if ($materials->isEmpty()) {
            foreach (array_keys(Standard::MEDIUMS) as $fallbackMedium) {
                if ($fallbackMedium === $medium) {
                    continue;
                }
                $materials = Material::forStudentSubject($subject, $fallbackMedium);
                if ($materials->isNotEmpty()) {
                    $medium = $fallbackMedium;
                    break;
                }
            }
        }

        $allowsFullMaterial = $this->allowsFullMaterial($standard);

        return view('principal.books.show', [
            'principal' => $principal,
            'standard' => $standard,
            'subject' => $subject,
            'materials' => $materials,
            'medium' => $medium,
            'allowsFullMaterial' => $allowsFullMaterial,
        ]);
    }

    public function topic(Request $request, Subject $subject, MaterialTopic $materialTopic): View|\Illuminate\Http\RedirectResponse
    {
        $principal = auth()->user();
        $this->assertAllotted($principal, $subject);
        abort_unless($subject->is_active, 404);

        $subject->loadMissing('standard');
        $standard = $subject->standard;
        $medium = $this->resolveMediumForSubject($request, $subject, $this->allottedStandards($principal));

        // Std 1–4: index only (subject / chapter / topic). Full material from Std 5.
        if (! $this->allowsFullMaterial($standard)) {
            return redirect()
                ->route('principal.books.show', ['subject' => $subject, 'medium' => $medium])
                ->with('error', 'Full material opens from Standard 5. For Standard 1–4 only subject, chapter and topic list is shown.');
        }

        $materialTopic->load(['material.chapter.subject.standard']);
        $material = $materialTopic->material;
        $chapter = $material?->chapter;
        abort_unless($material && $material->matchesStudentSubject($subject), 404);

        $medium = $this->resolveMediumForSubject(
            $request,
            $subject,
            $this->allottedStandards($principal),
            $material
        );

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

    public function material(Request $request, Subject $subject, Material $material): View|\Illuminate\Http\RedirectResponse
    {
        abort_unless($subject->is_active, 404);

        $principal = auth()->user();
        $this->assertAllotted($principal, $subject);

        $subject->loadMissing('standard');
        $medium = $this->resolveMediumForSubject($request, $subject, $this->allottedStandards($principal), $material);

        if (! $this->allowsFullMaterial($subject->standard)) {
            return redirect()
                ->route('principal.books.show', ['subject' => $subject, 'medium' => $medium])
                ->with('error', 'Full material opens from Standard 5. For Standard 1–4 only subject, chapter and topic list is shown.');
        }

        $material->load(['chapter.subject.standard', 'topics' => fn ($q) => $q->orderBy('topic_order')]);

        abort_unless(
            $material->matchesStudentSubject($subject)
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
            ->orderedByNumber()
            ->get();
    }

    private function assertAllotted(User $principal, Subject $subject): void
    {
        abort_unless(
            $principal->allottedStandards()->where('standards.id', $subject->standard_id)->exists(),
            404
        );
    }

    private function allowsFullMaterial(?Standard $standard): bool
    {
        $number = (int) Material::standardNumber($standard);

        return $number >= self::FULL_MATERIAL_FROM_STANDARD;
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

    /**
     * Prefer URL medium, then the material's medium, then the standard's medium.
     */
    private function resolveMediumForSubject(
        Request $request,
        Subject $subject,
        Collection $allotted,
        ?Material $material = null
    ): string {
        $requested = Material::normalizeMedium($request->query('medium'));
        if ($requested && array_key_exists($requested, Standard::MEDIUMS)) {
            return $requested;
        }

        $fromMaterial = Material::normalizeMedium($material?->medium);
        if ($fromMaterial && array_key_exists($fromMaterial, Standard::MEDIUMS)) {
            return $fromMaterial;
        }

        $subject->loadMissing('standard');
        $fromStandard = Material::normalizeMedium($subject->standard?->medium);
        if ($fromStandard && array_key_exists($fromStandard, Standard::MEDIUMS)) {
            return $fromStandard;
        }

        return $this->resolveMedium($request, $allotted);
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
