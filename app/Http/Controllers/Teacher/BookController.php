<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\TeacherSubject;
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
        $teacher = auth()->user();
        $assignments = $this->teacherAssignments($teacher);
        $medium = $this->resolveMedium($request, $assignments);
        $assignedForMedium = $assignments->filter(
            fn (TeacherSubject $row) => Material::normalizeMedium($row->medium) === $medium
        );

        $standards = $assignedForMedium
            ->groupBy('standard_id')
            ->map(function (Collection $rows) use ($medium) {
                $standard = $rows->first()?->standard;
                if (! $standard || ! $standard->is_active) {
                    return null;
                }

                $materialSubjects = Material::subjectsForStudent($standard, $medium)->keyBy('id');
                $subjects = $rows
                    ->map(function (TeacherSubject $row) use ($materialSubjects, $medium) {
                        $subject = $row->subject;
                        if (! $subject || ! $subject->is_active) {
                            return null;
                        }

                        $fromMaterials = $materialSubjects->get($subject->id);
                        $subject->setAttribute(
                            'chapters_count',
                            $fromMaterials
                                ? (int) $fromMaterials->chapters_count
                                : Material::forStudentSubject($subject, $medium)->count()
                        );

                        return $subject;
                    })
                    ->filter()
                    ->unique('id')
                    ->values();

                $standard->setRelation('bookSubjects', $subjects);

                return $standard;
            })
            ->filter(fn ($standard) => $standard && $standard->bookSubjects->isNotEmpty())
            ->sortBy(fn (Standard $standard) => (int) preg_replace('/\D+/', '', (string) ($standard->slug ?: $standard->name)))
            ->values();

        $mediums = collect(Standard::MEDIUMS)
            ->only(
                $assignments
                    ->map(fn (TeacherSubject $row) => Material::normalizeMedium($row->medium))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all()
            )
            ->all();

        return view('teacher.books.index', [
            'teacher' => $teacher,
            'medium' => $medium,
            'mediums' => $mediums,
            'standards' => $standards,
            'hasAssignments' => $assignments->isNotEmpty(),
        ]);
    }

    public function show(Request $request, Subject $subject): View
    {
        abort_unless($subject->is_active, 404);

        $teacher = auth()->user();
        $medium = $this->resolveMedium($request, $this->teacherAssignments($teacher));
        $this->assertAssigned($teacher, $subject, $medium);

        $subject->loadMissing('standard');
        $standard = $subject->standard;
        abort_unless($standard && $standard->is_active, 404);

        $materials = Material::forStudentSubject($subject, $medium);

        return view('teacher.books.show', [
            'teacher' => auth()->user(),
            'standard' => $standard,
            'subject' => $subject,
            'materials' => $materials,
            'medium' => $medium,
        ]);
    }

    public function topic(Request $request, Subject $subject, MaterialTopic $materialTopic): View
    {
        $teacher = auth()->user();
        $medium = $this->resolveMedium($request, $this->teacherAssignments($teacher));
        $this->assertAssigned($teacher, $subject, $medium);

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
        $textbookPoints = MaterialTopicReader::textbookPoints($payload['sections']);

        return view('teacher.books.topic', [
            'teacher' => auth()->user(),
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
            'readerNav' => $this->assignedReaderNav($teacher, $standard, $medium),
            'readerMaterialId' => $material->id,
            'medium' => $medium,
        ]);
    }

    public function material(Request $request, Subject $subject, Material $material): View
    {
        abort_unless($subject->is_active, 404);

        $teacher = auth()->user();
        $medium = $this->resolveMedium($request, $this->teacherAssignments($teacher));
        $this->assertAssigned($teacher, $subject, $medium);

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

        return view('teacher.books.material', [
            'teacher' => $teacher,
            'standard' => $subject->standard,
            'subject' => $subject,
            'chapter' => $chapter ?: (object) [
                'id' => 0,
                'name' => $material->displayChapterName(),
            ],
            'material' => $material,
            'examples' => $examples,
            'backUrl' => route('teacher.books.show', ['subject' => $subject, 'medium' => $medium]),
            'practiceMode' => false,
            'readerNav' => $this->assignedReaderNav($teacher, $subject->standard, $medium),
            'medium' => $medium,
        ]);
    }

    private function resolveMedium(Request $request, ?Collection $assignments = null): string
    {
        $assignments ??= $this->teacherAssignments(auth()->user());
        $assignedMediums = $assignments
            ->map(fn (TeacherSubject $row) => Material::normalizeMedium($row->medium))
            ->filter()
            ->unique()
            ->values();

        $requested = Material::normalizeMedium($request->query('medium'));
        if ($requested && ($assignedMediums->isEmpty() || $assignedMediums->contains($requested))) {
            return $requested;
        }

        $teacherMedium = Material::normalizeMedium(auth()->user()?->medium);
        if ($teacherMedium && $assignedMediums->contains($teacherMedium)) {
            return $teacherMedium;
        }

        if ($assignedMediums->isNotEmpty()) {
            return (string) $assignedMediums->first();
        }

        return array_key_exists((string) $teacherMedium, Standard::MEDIUMS)
            ? (string) $teacherMedium
            : 'english';
    }

    private function teacherAssignments(?User $teacher): Collection
    {
        if (! $teacher) {
            return collect();
        }

        return TeacherSubject::query()
            ->with(['standard', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();
    }

    private function assertAssigned(User $teacher, Subject $subject, string $medium): void
    {
        abort_unless(
            TeacherSubject::query()
                ->where('teacher_id', $teacher->id)
                ->where('subject_id', $subject->id)
                ->where('standard_id', $subject->standard_id)
                ->whereRaw('LOWER(TRIM(medium)) = ?', [$medium])
                ->exists(),
            404
        );
    }

    private function assignedReaderNav(User $teacher, ?Standard $standard, string $medium): array
    {
        $allowedIds = TeacherSubject::query()
            ->where('teacher_id', $teacher->id)
            ->whereRaw('LOWER(TRIM(medium)) = ?', [$medium])
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id);

        return collect(MaterialPaperBank::readerNavTree($standard, $medium, 'teacher.books.topics.show', [
            'medium' => $medium,
        ]))
            ->filter(fn (array $item) => $allowedIds->contains((int) ($item['id'] ?? 0)))
            ->values()
            ->all();
    }
}
