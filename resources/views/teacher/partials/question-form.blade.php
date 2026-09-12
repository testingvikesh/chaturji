@php
    $existingQuestions = $existingQuestions ?? collect();
    $showMarks = $showMarks ?? false;
    $initialQuestions = old('questions', $existingQuestions->map(fn ($q) => [
        'question_type' => $q->question_type,
        'question_text' => $q->question_text,
        'answer' => $q->answer,
        'options' => \App\Support\QuestionFormHelper::optionsToText($q->options),
        'marks' => $q->marks ?? 1,
    ])->values()->all());

    if (empty($initialQuestions)) {
        $initialQuestions = [['question_type' => 'mcq', 'question_text' => '', 'answer' => '', 'options' => '', 'marks' => 1]];
    }
@endphp

<div x-data="{
    questions: {{ json_encode($initialQuestions) }},
    types: {{ json_encode($questionTypes) }},
    addQuestion() {
        this.questions.push({ question_type: 'mcq', question_text: '', answer: '', options: '', marks: 1 });
    },
    removeQuestion(index) {
        if (this.questions.length > 1) this.questions.splice(index, 1);
    }
}" class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-900">Questions</h3>
        <button type="button" @click="addQuestion()" class="rounded-lg bg-brand-green text-white px-3 py-1.5 text-sm font-semibold hover:bg-brand-green-dark transition">+ Add Question</button>
    </div>

    <template x-for="(q, index) in questions" :key="index">
        <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-700" x-text="'Question ' + (index + 1)"></span>
                <button type="button" @click="removeQuestion(index)" class="text-xs text-red-500 hover:text-red-700 font-medium">Remove</button>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
                    <select :name="'questions[' + index + '][question_type]'" x-model="q.question_type" class="w-full rounded-xl border-slate-200 text-sm">
                        <template x-for="(label, value) in types" :key="value">
                            <option :value="value" x-text="label" :selected="q.question_type === value"></option>
                        </template>
                    </select>
                </div>
                @if ($showMarks)
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Marks</label>
                        <input type="number" :name="'questions[' + index + '][marks]'" x-model="q.marks" min="1" max="100" class="w-full rounded-xl border-slate-200 text-sm">
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Question Text</label>
                <textarea :name="'questions[' + index + '][question_text]'" x-model="q.question_text" rows="2" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Enter question..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Options (one per line, A. B. C. for MCQ)</label>
                <textarea :name="'questions[' + index + '][options]'" x-model="q.options" rows="3" class="w-full rounded-xl border-slate-200 text-sm font-mono text-xs" placeholder="A. Option 1&#10;B. Option 2"></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Answer</label>
                <input type="text" :name="'questions[' + index + '][answer]'" x-model="q.answer" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Correct answer">
            </div>
        </div>
    </template>
</div>
