<?php

namespace App\Http\Controllers\Student;

use App\Models\Exam;
use App\Models\ExamSubmission;
use App\Models\Subject;
use App\Services\ExamAnswerSheetProcessor;
use App\Services\SelfExamGeneratorService;
use App\Support\MaterialPaperBank;
use App\Support\ObjectiveQuestionTypes;
use App\Support\PaperTypeHelper;
use App\Support\SubjectiveQuestionTypes;
use App\Support\TeachingHomeworkLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends BaseStudentController
{
    /**
     * Fallback list page (also used if routes still point here).
     */
    public function index(): View
    {
        return app(SelfExamPageController::class)->index();
    }

    public function create(): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);
        $subjects = MaterialPaperBank::subjectsForStudent($standard, $user->medium);

        return view('student.exams.create', [
            'user' => $user,
            'standard' => $standard,
            'standardName' => $this->standardName($user->standard),
            'subjects' => $subjects,
            'alpineSubjects' => MaterialPaperBank::alpineSubjects($subjects),
            'markOptions' => SelfExamGeneratorService::MARK_OPTIONS,
        ]);
    }

    public function questionCounts(Request $request): JsonResponse
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);
        $selfExamGenerator = app(SelfExamGeneratorService::class);

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_ids' => ['required_without:chapter_id', 'array', 'min:1'],
            'chapter_ids.*' => ['integer', 'exists:materials,id'],
            'chapter_id' => ['nullable', 'integer', 'exists:materials,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:material_topics,id'],
            'target_marks' => ['nullable', 'integer', 'in:'.implode(',', SelfExamGeneratorService::MARK_OPTIONS)],
        ]);

        $subject = Subject::query()->findOrFail($validated['subject_id']);

        abort_unless(
            $standard
            && $subject->standard_id === $standard->id
            && $subject->is_active,
            404
        );

        $chapterIds = $validated['chapter_ids'] ?? (isset($validated['chapter_id']) ? [(int) $validated['chapter_id']] : []);
        $materials = MaterialPaperBank::resolveMaterials($subject, $user->medium, $chapterIds);
        $topics = MaterialPaperBank::resolveTopics($materials, $validated['topic_ids'] ?? []);

        $available = MaterialPaperBank::availableCounts($materials, $topics);
        $marksPerType = TeachingHomeworkLayout::marksPerType();
        $types = collect(PaperTypeHelper::types())->map(fn ($label, $type) => [
            'type' => $type,
            'label' => $label,
            'available' => $available[$type] ?? 0,
            'marks' => $marksPerType[$type] ?? 1,
        ])->values();

        $plan = null;
        $targetMarks = isset($validated['target_marks']) ? (int) $validated['target_marks'] : null;

        if ($targetMarks) {
            try {
                $plan = $selfExamGenerator->buildPlan($materials, $topics, $targetMarks);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'counts' => $available,
                    'types' => $types,
                    'total' => array_sum($available),
                    'plan' => null,
                    'error' => collect($e->errors())->flatten()->first(),
                ]);
            }
        }

        return response()->json([
            'counts' => $available,
            'types' => $types,
            'total' => array_sum($available),
            'plan' => $plan,
        ]);
    }

    public function paperPreview(Request $request): JsonResponse
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_ids' => ['required_without:chapter_id', 'array', 'min:1'],
            'chapter_ids.*' => ['integer', 'exists:materials,id'],
            'chapter_id' => ['nullable', 'integer', 'exists:materials,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:material_topics,id'],
            'target_marks' => ['required', 'integer', 'in:'.implode(',', SelfExamGeneratorService::MARK_OPTIONS)],
            'type_counts' => ['nullable', 'array'],
            'type_counts.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $subject = Subject::query()->findOrFail($validated['subject_id']);

        abort_unless(
            $standard
            && $subject->standard_id === $standard->id
            && $subject->is_active,
            404
        );

        try {
            $chapterIds = $validated['chapter_ids'] ?? (isset($validated['chapter_id']) ? [(int) $validated['chapter_id']] : []);
            $materials = MaterialPaperBank::resolveMaterials($subject, $user->medium, $chapterIds);
            $topics = MaterialPaperBank::resolveTopics($materials, $validated['topic_ids'] ?? []);
            $preview = MaterialPaperBank::previewPaper(
                $materials,
                $topics,
                $validated['type_counts'] ?? [],
                (int) $validated['target_marks']
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'error' => collect($e->errors())->flatten()->first(),
                'sections' => [],
                'questions' => [],
            ], 422);
        }

        $settings = \App\Models\Setting::allCached();
        $preview['subject_name'] = $subject->name;
        $preview['class_name'] = $standard?->name ?? ($user->standard ?? '—');
        $preview['school_name'] = $settings['site_name'] ?? config('app.name', 'Gses Chaturji');
        $preview['logo_letter'] = $settings['site_logo_letter'] ?? 'G';
        $preview['logo_url'] = asset('images/brand/logo.png');
        $preview['paper_title'] = 'Self Exam Paper';

        return response()->json($preview, $preview['ok'] ? 200 : 422);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_ids' => ['required_without:chapter_id', 'array', 'min:1'],
            'chapter_ids.*' => ['integer', 'exists:materials,id'],
            'chapter_id' => ['nullable', 'integer', 'exists:materials,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:material_topics,id'],
            'target_marks' => ['required', 'integer', 'in:'.implode(',', SelfExamGeneratorService::MARK_OPTIONS)],
            'type_counts' => ['nullable', 'array'],
            'type_counts.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $subject = Subject::query()->findOrFail($validated['subject_id']);

        abort_unless(
            $standard
            && $subject->standard_id === $standard->id
            && $subject->is_active,
            404
        );

        try {
            $chapterIds = $validated['chapter_ids'] ?? (isset($validated['chapter_id']) ? [(int) $validated['chapter_id']] : []);
            $materials = MaterialPaperBank::resolveMaterials($subject, $user->medium, $chapterIds);
            $topics = MaterialPaperBank::resolveTopics($materials, $validated['topic_ids'] ?? []);
            $requestedCounts = PaperTypeHelper::normalizeCounts($validated['type_counts'] ?? []);

            $exam = app(SelfExamGeneratorService::class)->generate(
                $user,
                $subject,
                $materials,
                $topics,
                (int) $validated['target_marks'],
                $requestedCounts,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route('student.exams.create')
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            return redirect()
                ->route('student.exams.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('student.exams.show', $exam)
            ->with('success', 'Self Exam generated! You can attempt it now.');
    }

    public function show(Exam $exam): View
    {
        $user = auth()->user();
        $this->authorizeExamAccess($exam, $user);

        $exam->load(['subject', 'teacher:id,name', 'questions.chapterQuestion']);
        $paper = $this->paperViewData($exam);

        $submission = ExamSubmission::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return view('student.exams.show', [
            'user' => $user,
            'exam' => $exam,
            'submission' => $submission,
            ...$paper,
        ]);
    }

    public function preview(Exam $exam): View
    {
        $user = auth()->user();
        $this->authorizeExamAccess($exam, $user);

        $exam->load(['subject', 'teacher:id,name', 'questions.chapterQuestion']);
        $paper = $this->paperViewData($exam);

        return view('student.exams.preview', [
            'user' => $user,
            'exam' => $exam,
            ...$paper,
        ]);
    }

    public function printPaper(Exam $exam): View
    {
        $user = auth()->user();
        $this->authorizeExamAccess($exam, $user);

        $exam->load(['subject', 'chapter:id,name,sort_order', 'topic:id,name', 'teacher:id,name', 'questions.chapterQuestion']);
        $paper = $this->paperViewData($exam);

        return view('student.exams.print', [
            'user' => $user,
            'exam' => $exam,
            ...$paper,
        ]);
    }

    public function submitAnswerPdf(Request $request, Exam $exam): RedirectResponse
    {
        $user = auth()->user();
        $this->authorizeExamAccess($exam, $user);

        $request->validate([
            'answer_pdf' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'answer_images' => ['nullable', 'array', 'max:4'],
            'answer_images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if (! $request->file('answer_pdf') && ! $request->file('answer_images')) {
            return redirect()
                ->route('student.exams.show', $exam)
                ->with('error', 'Please upload your answer sheet image or PDF.');
        }

        $primary = $request->file('answer_pdf') ?? $request->file('answer_images')[0];
        $extraImages = $request->file('answer_pdf')
            ? ($request->file('answer_images') ?? [])
            : array_slice($request->file('answer_images') ?? [], 1);

        try {
            $submission = app(ExamAnswerSheetProcessor::class)
                ->startExamSubmission($exam, $user, $primary, $extraImages);

            \App\Jobs\ProcessExamAnswerSheet::dispatchAfterResponse($submission->id);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('student.exams.show', $exam)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('student.exams.show', $exam)
            ->with('success', 'Answer sheet uploaded. Teacher check is running — this page will refresh automatically.');
    }

    private function authorizeExamAccess(Exam $exam, $user): void
    {
        abort_unless($exam->isPublished(), 404);

        if ($exam->isSelfExam()) {
            abort_unless((int) $exam->student_id === (int) $user->id, 404);

            return;
        }

        abort_unless($exam->standard === $user->standard, 404);
    }

    private function paperViewData(Exam $exam): array
    {
        $grouped = PaperTypeHelper::groupedForStudentDisplay($exam->questions);
        $typeCounts = PaperTypeHelper::groupInPaperOrder($exam->questions)->map->count()->all();

        $marksPerType = $exam->generation_config['marks_per_type'] ?? [];
        if ($marksPerType === []) {
            foreach ($grouped as $type => $typeQuestions) {
                $marksPerType[$type] = (int) ($typeQuestions->first()->marks ?? 1);
            }
        }

        $breakdown = PaperTypeHelper::breakdown(
            $exam->generation_config['type_counts'] ?? $typeCounts,
            $marksPerType
        );
        ObjectiveQuestionTypes::applyMarks($exam->questions, $marksPerType);
        $objectiveStats = ObjectiveQuestionTypes::stats($exam->questions, $marksPerType);
        $subjectiveStats = SubjectiveQuestionTypes::stats($exam->questions, $marksPerType);

        return [
            'grouped' => $grouped,
            'breakdown' => $breakdown,
            'objectiveStats' => $objectiveStats,
            'subjectiveStats' => $subjectiveStats,
        ];
    }
}
