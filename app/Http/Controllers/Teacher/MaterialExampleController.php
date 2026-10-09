<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Support\MaterialExampleEditor;
use App\Support\MaterialWorkedExamples;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialExampleController extends Controller
{
    public function edit(Request $request, Subject $subject, MaterialTopic $materialTopic, string $exampleKey): View
    {
        $material = $this->authorizeTopic($request, $subject, $materialTopic);

        $examples = MaterialWorkedExamples::fromTopic($materialTopic);
        $row = $examples->first(fn (array $example) => ($example['edit_key'] ?? '') === $exampleKey);
        if (! $row) {
            $row = MaterialWorkedExamples::rawFromTopic($materialTopic)
                ->first(fn (array $example) => ($example['edit_key'] ?? '') === $exampleKey);
        }
        abort_unless($row, 404);

        $item = is_array($row['item'] ?? null) ? $row['item'] : [];
        $medium = Material::normalizeMedium($request->query('medium', $material->medium)) ?: $material->medium;
        $page = MaterialWorkedExamples::normalizePage($row['page'] ?? '') ?: '1';

        return view('teacher.books.example-edit', [
            'subject' => $subject,
            'material' => $material,
            'materialTopic' => $materialTopic,
            'example' => $row,
            'item' => $item,
            'exampleKey' => $exampleKey,
            'solutionText' => MaterialExampleEditor::solutionToText(is_array($item['solution'] ?? null) ? $item['solution'] : []),
            'medium' => $medium,
            'backUrl' => route('teacher.books.materials.show', [
                'subject' => $subject,
                'material' => $material,
                'medium' => $medium,
                'page' => $page,
            ]).'#'.($row['uid'] ?? ''),
        ]);
    }

    public function update(Request $request, Subject $subject, MaterialTopic $materialTopic, string $exampleKey): RedirectResponse
    {
        $material = $this->authorizeTopic($request, $subject, $materialTopic);

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'find' => ['nullable', 'string'],
            'given' => ['nullable', 'string'],
            'final_answer' => ['nullable', 'string'],
            'solution_overview' => ['nullable', 'string'],
            'solution_text' => ['nullable', 'string'],
        ]);

        MaterialExampleEditor::update(auth()->user(), $materialTopic->fresh(), $exampleKey, $validated);

        $medium = Material::normalizeMedium($request->input('medium', $material->medium)) ?: $material->medium;
        $examples = MaterialWorkedExamples::fromTopic($materialTopic->fresh());
        $row = $examples->first(fn (array $example) => ($example['edit_key'] ?? '') === $exampleKey);
        $page = MaterialWorkedExamples::normalizePage($row['page'] ?? '') ?: '1';

        return redirect()
            ->route('teacher.books.materials.show', [
                'subject' => $subject,
                'material' => $material,
                'medium' => $medium,
                'page' => $page,
            ])
            ->with('success', 'Example updated. Admin log saved.');
    }

    private function authorizeTopic(Request $request, Subject $subject, MaterialTopic $materialTopic): Material
    {
        abort_unless($subject->is_active, 404);

        $materialTopic->loadMissing('material');
        $material = $materialTopic->material;
        abort_unless($material, 404);

        $teacher = auth()->user();
        $medium = Material::normalizeMedium($request->input('medium', $request->query('medium', $material->medium)))
            ?: $material->medium;
        $medium = strtolower(trim((string) (Material::normalizeMedium($medium) ?: $medium)));

        $assigned = TeacherSubject::query()
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subject->id)
            ->where('standard_id', $subject->standard_id)
            ->whereRaw('LOWER(TRIM(medium)) = ?', [$medium])
            ->exists();

        abort_unless($assigned, 403);
        abort_unless($material->matchesStudentSubject($subject), 404);

        return $material;
    }
}
