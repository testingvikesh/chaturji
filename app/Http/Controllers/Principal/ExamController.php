<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Standard;
use App\Models\User;
use App\Support\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $principal = auth()->user();
        $standards = $this->allottedStandards($principal);
        $selected = (string) $request->query('standard', '');
        $slugs = $standards->pluck('slug')->filter()->unique()->values();

        if ($selected !== '' && ! $slugs->contains($selected)) {
            $selected = '';
        }

        $exams = collect();
        if ($slugs->isNotEmpty()) {
            $exams = Exam::query()
                ->excludeSelfExams()
                ->whereIn('standard', $selected !== '' ? [$selected] : $slugs->all())
                ->with(['teacher:id,name', 'subject:id,name'])
                ->withCount('questions')
                ->orderByDesc('id')
                ->get();
        }

        $groups = $standards
            ->when($selected !== '', fn (Collection $rows) => $rows->where('slug', $selected))
            ->map(function (Standard $standard) use ($exams) {
                return [
                    'standard' => $standard,
                    'exams' => $exams->where('standard', $standard->slug)->values(),
                ];
            })
            ->filter(fn (array $group) => $selected !== '' || $group['exams']->isNotEmpty())
            ->values();

        return view('principal.exams.index', [
            'standards' => $standards,
            'groups' => $groups,
            'selected' => $selected,
            'examCount' => $exams->count(),
        ]);
    }

    public function edit(Exam $exam): View
    {
        $this->assertAllottedExam(auth()->user(), $exam);
        $exam->load(['teacher:id,name', 'subject:id,name'])->loadCount('questions');

        return view('principal.exams.edit', [
            'exam' => $exam,
            'standardName' => $this->standardName($exam->standard),
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->assertAllottedExam(auth()->user(), $exam);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $exam->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'starts_at' => $this->schoolTime($validated['starts_at'] ?? null),
            'ends_at' => $this->schoolTime($validated['ends_at'] ?? null),
            'status' => $validated['status'],
        ]);

        ActivityLogger::log(
            'principal.exams.update',
            'Principal updated exam '.$exam->title,
            $exam
        );

        return redirect()
            ->route('principal.exams.index', ['standard' => $exam->standard])
            ->with('success', 'Exam details updated.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->assertAllottedExam(auth()->user(), $exam);
        $standard = $exam->standard;
        $title = $exam->title;

        ActivityLogger::log(
            'principal.exams.delete',
            'Principal deleted exam '.$title,
            $exam
        );

        $exam->delete();

        return redirect()
            ->route('principal.exams.index', ['standard' => $standard])
            ->with('success', 'Exam deleted.');
    }

    private function allottedStandards(?User $principal): Collection
    {
        if (! $principal) {
            return collect();
        }

        return $principal->allottedStandards()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get(['standards.id', 'standards.name', 'standards.slug', 'standards.medium']);
    }

    private function assertAllottedExam(?User $principal, Exam $exam): void
    {
        abort_if($exam->isSelfExam(), 404);

        $slugs = $this->allottedStandards($principal)->pluck('slug')->filter()->all();
        abort_unless($slugs !== [] && in_array((string) $exam->standard, $slugs, true), 404);
    }

    private function standardName(?string $slug): string
    {
        $standard = Standard::query()->where('slug', $slug)->first(['name', 'medium']);
        if (! $standard) {
            return str_replace('_', ' ', ucwords((string) $slug, '_'));
        }

        $medium = $standard->mediumLabel();

        return trim($standard->name.($medium !== '' ? ' · '.$medium : ''));
    }

    private function schoolTime(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value, Exam::SCHOOL_TIMEZONE)->utc()->toDateTimeString();
    }
}
