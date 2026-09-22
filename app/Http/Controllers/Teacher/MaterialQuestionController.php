<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Support\MaterialQuestionEditor;
use App\Support\MaterialTopicReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialQuestionController extends Controller
{
    public function edit(Request $request, Subject $subject, MaterialTopic $materialTopic, string $questionKey): View
    {
        $material = $this->authorizeTopic($request, $subject, $materialTopic);

        $raw = MaterialTopicReader::rawUniqueQuestions($materialTopic);
        $question = $raw->first(function ($item) use ($questionKey) {
            return MaterialQuestionEditor::questionKey((string) $item->question_type, (string) $item->question_text) === $questionKey;
        });

        abort_unless($question, 404);

        $edits = is_array($materialTopic->question_edits) ? $materialTopic->question_edits : [];
        if (isset($edits[$questionKey]) && is_array($edits[$questionKey])) {
            $edit = $edits[$questionKey];
            $question->question_text = $edit['question_text'] ?? $question->question_text;
            $question->answer = $edit['answer'] ?? $question->answer;
            $question->options = $edit['options'] ?? $question->options;
        }

        $options = $question->options;
        if (is_string($options)) {
            $decoded = json_decode($options, true);
            $options = is_array($decoded) ? $decoded : null;
        }

        $optionsText = '';
        if (is_array($options) && ! isset($options['column_a']) && ! isset($options['column_b'])) {
            $optionsText = implode("\n", array_map(
                fn ($o) => is_array($o) ? (string) ($o['text'] ?? $o['label'] ?? json_encode($o, JSON_UNESCAPED_UNICODE)) : (string) $o,
                $options
            ));
        }

        $medium = Material::normalizeMedium($request->query('medium', $material->medium)) ?: $material->medium;

        return view('teacher.books.question-edit', [
            'subject' => $subject,
            'material' => $material,
            'materialTopic' => $materialTopic,
            'question' => $question,
            'questionKey' => $questionKey,
            'optionsText' => $optionsText,
            'medium' => $medium,
            'backUrl' => route('teacher.books.topics.show', [
                'subject' => $subject,
                'materialTopic' => $materialTopic,
                'medium' => $medium,
            ]),
        ]);
    }

    public function update(Request $request, Subject $subject, MaterialTopic $materialTopic, string $questionKey): RedirectResponse
    {
        $material = $this->authorizeTopic($request, $subject, $materialTopic);

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'answer' => ['nullable', 'string'],
            'options_text' => ['nullable', 'string'],
        ]);

        $options = null;
        if ($request->filled('options_text')) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $validated['options_text']))));
            $options = $lines !== [] ? $lines : null;
        }

        MaterialQuestionEditor::update(auth()->user(), $materialTopic->fresh(), $questionKey, [
            'question_text' => $validated['question_text'],
            'answer' => $validated['answer'] ?? null,
            'options' => $options,
        ]);

        $medium = Material::normalizeMedium($request->input('medium', $material->medium)) ?: $material->medium;

        return redirect()
            ->route('teacher.books.topics.show', [
                'subject' => $subject,
                'materialTopic' => $materialTopic,
                'medium' => $medium,
            ])
            ->with('success', 'Question updated. Admin log saved.');
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
        $medium = Material::normalizeMedium($medium) ?: $medium;

        $medium = strtolower(trim((string) $medium));

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
