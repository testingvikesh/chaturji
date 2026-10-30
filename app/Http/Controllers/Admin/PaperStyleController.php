<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaperStyle;
use App\Models\Standard;
use App\Models\Subject;
use App\Support\PaperTypeHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaperStyleController extends Controller
{
    public function index(): View
    {
        $styles = PaperStyle::query()
            ->with(['standard', 'subject'])
            ->get()
            ->sortBy(fn (PaperStyle $style) => strtolower(($style->standard->name ?? '').' '.($style->subject->name ?? '')))
            ->values();

        return view('admin.paper-styles.index', [
            'styles' => $styles,
        ]);
    }

    public function create(): View
    {
        return view('admin.paper-styles.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'standard_ids' => ['required', 'array', 'min:1'],
            'standard_ids.*' => ['integer', 'exists:standards,id'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'type_counts' => ['required', 'array'],
            'type_counts.*' => ['nullable', 'integer', 'min:0', 'max:200'],
        ], [
            'standard_ids.required' => 'Select at least one standard.',
            'subject_ids.required' => 'Select at least one subject.',
        ]);

        $counts = $this->countsOrFail($request);
        if ($counts instanceof RedirectResponse) {
            return $counts;
        }

        $subjects = Subject::query()
            ->whereIn('id', $validated['subject_ids'])
            ->whereIn('standard_id', $validated['standard_ids'])
            ->get(['id', 'standard_id']);

        if ($subjects->isEmpty()) {
            return back()
                ->withErrors(['subject_ids' => 'Choose subjects from the selected standards.'])
                ->withInput();
        }

        foreach ($subjects as $subject) {
            PaperStyle::query()->updateOrCreate(
                [
                    'standard_id' => $subject->standard_id,
                    'subject_id' => $subject->id,
                ],
                ['type_counts' => $counts]
            );
        }

        $saved = $subjects->count();

        return redirect()
            ->route('admin.paper-styles.index')
            ->with('success', 'Paper style saved for '.$saved.' '.str('subject')->plural($saved).'.');
    }

    public function edit(PaperStyle $paperStyle): View
    {
        return view('admin.paper-styles.edit', [
            ...$this->formData($paperStyle),
            'style' => $paperStyle,
        ]);
    }

    public function update(Request $request, PaperStyle $paperStyle): RedirectResponse
    {
        $validated = $this->validateStyle($request, $paperStyle);
        $counts = $this->countsOrFail($request);
        if ($counts instanceof RedirectResponse) {
            return $counts;
        }

        $paperStyle->update([
            'standard_id' => $validated['standard_id'],
            'subject_id' => $validated['subject_id'],
            'type_counts' => $counts,
        ]);

        return redirect()
            ->route('admin.paper-styles.index')
            ->with('success', 'Paper style updated.');
    }

    public function destroy(PaperStyle $paperStyle): RedirectResponse
    {
        $paperStyle->delete();

        return redirect()
            ->route('admin.paper-styles.index')
            ->with('success', 'Paper style deleted.');
    }

    /**
     * @return array{standards: \Illuminate\Support\Collection, subjects: \Illuminate\Support\Collection, paperTypes: array<string, string>, counts: array<string, int>}
     */
    private function formData(?PaperStyle $style = null): array
    {
        $stored = is_array($style?->type_counts) ? $style->type_counts : [];

        return [
            'standards' => Standard::query()->where('is_active', true)->orderedByNumber()->get(),
            'subjects' => Subject::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'standard_id', 'name']),
            'paperTypes' => PaperTypeHelper::types(),
            'counts' => old('type_counts', $stored),
            'selectedStandard' => old('standard_id', $style?->standard_id),
            'selectedSubject' => old('subject_id', $style?->subject_id),
        ];
    }

    /**
     * @return array{standard_id: int, subject_id: int}
     */
    private function validateStyle(Request $request, ?PaperStyle $style = null): array
    {
        $validated = $request->validate([
            'standard_id' => ['required', 'integer', 'exists:standards,id'],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(fn ($query) => $query->where('standard_id', $request->input('standard_id'))),
                Rule::unique('paper_styles', 'subject_id')
                    ->where(fn ($query) => $query->where('standard_id', $request->input('standard_id')))
                    ->ignore($style?->id),
            ],
            'type_counts' => ['required', 'array'],
            'type_counts.*' => ['nullable', 'integer', 'min:0', 'max:200'],
        ], [
            'subject_id.unique' => 'This standard and subject already has a paper style.',
            'subject_id.exists' => 'Choose a subject from the selected standard.',
        ]);

        return [
            'standard_id' => (int) $validated['standard_id'],
            'subject_id' => (int) $validated['subject_id'],
        ];
    }

    /**
     * @return array<string, int>|RedirectResponse
     */
    private function countsOrFail(Request $request): array|RedirectResponse
    {
        $counts = PaperTypeHelper::normalizeCounts((array) $request->input('type_counts', []));
        if ($counts === []) {
            return back()
                ->withErrors(['type_counts' => 'Enter a quantity for at least one question type.'])
                ->withInput();
        }

        return $counts;
    }
}
