<?php

namespace App\Http\Controllers\Student;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Subject;
use App\Services\AnswerSheetProcessor;
use App\Services\SelfHomeworkGeneratorService;
use App\Support\MaterialPaperBank;
use App\Support\ObjectiveQuestionTypes;
use App\Support\PaperTypeHelper;
use App\Support\SubjectiveQuestionTypes;
use App\Support\TeachingHomeworkLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeworkController extends BaseStudentController
{
    public function index(): View
    {
        $user = auth()->user();
        $selfHomeworks = collect();
        $teacherHomeworks = collect();
        $selfHomeworkDbReady = false;

        try {
            $selfHomeworkDbReady = $this->selfHomeworkColumnsExist();
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            if ($selfHomeworkDbReady) {
                $selfHomeworks = Homework::query()
                    ->where('is_self_homework', 1)
                    ->where('student_id', $user->id)
                    ->with(['subject:id,name', 'chapter:id,name'])
                    ->withCount('questions')
                    ->latest()
                    ->get();

                $teacherHomeworks = Homework::query()
                    ->where('status', 'published')
                    ->where(function ($q) {
                        $q->where('is_self_homework', 0)->orWhereNull('is_self_homework');
                    })
                    ->when($user->standard, fn ($q) => $q->where('standard', $user->standard))
                    ->with(['subject:id,name', 'chapter:id,name', 'teacher:id,name'])
                    ->withCount('questions')
                    ->latest()
                    ->get();
            } else {
                $teacherHomeworks = Homework::query()
                    ->published()
                    ->forStandard($user->standard)
                    ->with(['subject:id,name', 'chapter:id,name', 'teacher:id,name'])
                    ->withCount('questions')
                    ->latest()
                    ->get();
            }
        } catch (\Throwable $e) {
            report($e);
            try {
                $teacherHomeworks = Homework::query()
                    ->published()
                    ->forStandard($user->standard)
                    ->with(['subject:id,name', 'chapter:id,name', 'teacher:id,name'])
                    ->withCount('questions')
                    ->latest()
                    ->get();
            } catch (\Throwable $inner) {
                report($inner);
            }
        }

        return view('student.homework.index', [
            'user' => $user,
            'standardName' => $this->standardName($user->standard),
            'selfHomeworks' => $selfHomeworks,
            'teacherHomeworks' => $teacherHomeworks,
            'selfHomeworkDbReady' => $selfHomeworkDbReady,
            'createUrl' => url('/student/homework/create'),
            'markOptions' => SelfHomeworkGeneratorService::MARK_OPTIONS,
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);
        $subjects = MaterialPaperBank::subjectsForStudent($standard, $user->medium);

        return view('student.homework.create', [
            'user' => $user,
            'standard' => $standard,
            'standardName' => $this->standardName($user->standard),
            'subjects' => $subjects,
            'alpineSubjects' => MaterialPaperBank::alpineSubjects($subjects),
            'markOptions' => SelfHomeworkGeneratorService::MARK_OPTIONS,
        ]);
    }

    public function questionCounts(Request $request): JsonResponse
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);
        $generator = app(SelfHomeworkGeneratorService::class);

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_ids' => ['required_without:chapter_id', 'array', 'min:1'],
            'chapter_ids.*' => ['integer', 'exists:materials,id'],
            'chapter_id' => ['nullable', 'integer', 'exists:materials,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:material_topics,id'],
            'target_marks' => ['nullable', 'integer', 'in:'.implode(',', SelfHomeworkGeneratorService::MARK_OPTIONS)],
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
                $plan = $generator->buildPlan($materials, $topics, $targetMarks);
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
            'target_marks' => ['required', 'integer', 'in:'.implode(',', SelfHomeworkGeneratorService::MARK_OPTIONS)],
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
        $preview['paper_title'] = 'Self Homework Paper';

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
            'target_marks' => ['required', 'integer', 'in:'.implode(',', SelfHomeworkGeneratorService::MARK_OPTIONS)],
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

        $requestedCounts = PaperTypeHelper::normalizeCounts($validated['type_counts'] ?? []);

        try {
            $chapterIds = $validated['chapter_ids'] ?? (isset($validated['chapter_id']) ? [(int) $validated['chapter_id']] : []);
            $materials = MaterialPaperBank::resolveMaterials($subject, $user->medium, $chapterIds);
            $topics = MaterialPaperBank::resolveTopics($materials, $validated['topic_ids'] ?? []);

            $homework = app(SelfHomeworkGeneratorService::class)->generate(
                $user,
                $subject,
                $materials,
                $topics,
                (int) $validated['target_marks'],
                $requestedCounts,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('student.homework.create')->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            return redirect()->route('student.homework.create')->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('student.homework.show', $homework)
            ->with('success', 'Self Homework generated! You can attempt it now.');
    }

    public function show(Homework $homework): View
    {
        $user = auth()->user();
        $this->authorizeHomeworkAccess($homework, $user);

        $homework->load(['subject', 'chapter', 'topic', 'teacher:id,name', 'questions.chapterQuestion']);
        $paper = $this->paperViewData($homework);

        $submission = HomeworkSubmission::query()
            ->where('homework_id', $homework->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return view('student.homework.show', [
            'user' => $user,
            'homework' => $homework,
            'submission' => $submission,
            ...$paper,
        ]);
    }

    public function preview(Homework $homework): View
    {
        $user = auth()->user();
        $this->authorizeHomeworkAccess($homework, $user);

        $homework->load(['subject', 'chapter', 'topic', 'teacher:id,name', 'questions.chapterQuestion']);

        return view('student.homework.preview', [
            'user' => $user,
            'homework' => $homework,
            ...$this->paperViewData($homework),
        ]);
    }

    public function printPaper(Homework $homework): View
    {
        $user = auth()->user();
        $this->authorizeHomeworkAccess($homework, $user);

        $homework->load(['subject', 'chapter:id,name,sort_order', 'topic:id,name', 'teacher:id,name', 'questions.chapterQuestion']);

        return view('student.homework.print', [
            'user' => $user,
            'homework' => $homework,
            ...$this->paperViewData($homework),
        ]);
    }

    public function submitAnswerPdf(Request $request, Homework $homework): RedirectResponse
    {
        $user = auth()->user();
        $this->authorizeHomeworkAccess($homework, $user);

        $request->validate([
            'answer_pdf' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'answer_images' => ['nullable', 'array', 'max:4'],
            'answer_images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if (! $request->file('answer_pdf') && ! $request->file('answer_images')) {
            return redirect()->route('student.homework.show', $homework)
                ->with('error', 'Please upload your answer sheet image or PDF.');
        }

        $primary = $request->file('answer_pdf') ?? $request->file('answer_images')[0];
        $extraImages = $request->file('answer_pdf')
            ? ($request->file('answer_images') ?? [])
            : array_slice($request->file('answer_images') ?? [], 1);

        try {
            $submission = app(AnswerSheetProcessor::class)
                ->startHomeworkSubmission($homework, $user, $primary, $extraImages);

            \App\Jobs\ProcessHomeworkAnswerSheet::dispatchAfterResponse($submission->id);

            try {
                \App\Models\StudentWorkAttempt::query()->create([
                    'user_id' => $user->id,
                    'work_type' => \App\Models\StudentWorkAttempt::TYPE_HOMEWORK_SHEET,
                    'paper_type' => 'homework',
                    'paper_id' => $homework->id,
                    'answered_count' => 0,
                    'correct_count' => 0,
                    'total_objective' => 0,
                    'earned_marks' => 0,
                    'max_marks' => (float) ($homework->total_marks ?? 0),
                    'status' => 'submitted',
                    'attempted_at' => now(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        } catch (\Throwable $exception) {
            return redirect()->route('student.homework.show', $homework)->with('error', $exception->getMessage());
        }

        return redirect()->route('student.homework.show', $homework)
            ->with('success', 'Answer sheet uploaded. Teacher check is running — this page will refresh automatically.');
    }

    private function authorizeHomeworkAccess(Homework $homework, $user): void
    {
        abort_unless($homework->isPublished(), 404);

        if ($this->isSelfHomework($homework)) {
            abort_unless((int) $homework->student_id === (int) $user->id, 404);

            return;
        }

        abort_unless($homework->standard === $user->standard, 404);
    }

    private function isSelfHomework(Homework $homework): bool
    {
        return (bool) ($homework->is_self_homework ?? false);
    }

    private function selfHomeworkColumnsExist(): bool
    {
        try {
            return count(DB::select("SHOW COLUMNS FROM `homeworks` LIKE 'is_self_homework'")) > 0
                && count(DB::select("SHOW COLUMNS FROM `homeworks` LIKE 'student_id'")) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function paperViewData(Homework $homework): array
    {
        $grouped = PaperTypeHelper::groupedForStudentDisplay($homework->questions);
        $typeCounts = PaperTypeHelper::groupInPaperOrder($homework->questions)->map->count()->all();
        $marksPerType = $homework->generation_config['marks_per_type'] ?? [];

        $breakdown = PaperTypeHelper::breakdown(
            $homework->generation_config['type_counts'] ?? $typeCounts,
            $marksPerType
        );
        ObjectiveQuestionTypes::applyMarks($homework->questions, $marksPerType);
        $objectiveStats = ObjectiveQuestionTypes::stats($homework->questions, $marksPerType);
        $subjectiveStats = SubjectiveQuestionTypes::stats($homework->questions, $marksPerType);

        return [
            'grouped' => $grouped,
            'breakdown' => $breakdown,
            'objectiveStats' => $objectiveStats,
            'subjectiveStats' => $subjectiveStats,
        ];
    }
}
