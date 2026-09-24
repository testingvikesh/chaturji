<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Standard;
use App\Models\Subject;
use App\Support\SlugHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function manage(): RedirectResponse
    {
        $standard = Standard::query()
            ->orderedByNumber()
            ->orderBy('medium')
            ->orderBy('name')
            ->first();

        if (! $standard) {
            return redirect()
                ->route('admin.standards.index')
                ->with('success', 'Add a standard first, then manage its subjects.');
        }

        return redirect()->route('admin.standards.subjects.index', $standard);
    }

    public function index(Standard $standard): View
    {
        Material::subjectsForStudent($standard, $standard->medium);

        return view('admin.subjects.index', [
            'standard' => $standard,
            'standards' => $this->standards(),
            'subjects' => $standard->subjects()->withCount('chapters')->get(),
        ]);
    }

    public function create(Standard $standard): View
    {
        return view('admin.subjects.create', compact('standard'));
    }

    public function store(Request $request, Standard $standard): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($validated['name']);
        $this->assertUniqueName($standard, $name);

        $standard->subjects()->create([
            'name' => $name,
            'slug' => SlugHelper::unique($name, fn ($slug) => Subject::where('standard_id', $standard->id)->where('slug', $slug)->exists()),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->forgetSubjectCaches($standard);

        return redirect()->route('admin.standards.subjects.index', $standard)->with('success', 'Subject created successfully.');
    }

    public function edit(Subject $subject): View
    {
        $subject->load('standard');

        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $standard = $subject->standard;
        $name = trim($validated['name']);
        $this->assertUniqueName($standard, $name, $subject->id);

        $this->applySubjectChanges($subject, $standard, $name, (int) ($validated['sort_order'] ?? $subject->sort_order), $request->boolean('is_active'));
        $this->forgetSubjectCaches($standard, [$subject->id]);

        return redirect()->route('admin.standards.subjects.index', $standard)->with('success', 'Subject updated successfully.');
    }

    public function bulkUpdate(Request $request, Standard $standard): RedirectResponse
    {
        $validated = $request->validate([
            'subjects' => ['required', 'array'],
            'subjects.*.name' => ['required', 'string', 'max:255'],
            'subjects.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'subjects.*.is_active' => ['nullable'],
        ]);

        $existing = $standard->subjects()->get()->keyBy('id');
        $rows = collect($validated['subjects']);

        $unknown = $rows->keys()->map(fn ($id) => (int) $id)->diff($existing->keys());
        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'subjects' => 'One or more subjects do not belong to this standard.',
            ]);
        }

        $proposed = $existing->mapWithKeys(fn (Subject $subject) => [$subject->id => mb_strtolower(trim($subject->name))]);
        foreach ($rows as $id => $row) {
            $proposed[(int) $id] = mb_strtolower(trim($row['name']));
        }

        if ($proposed->unique()->count() !== $proposed->count()) {
            throw ValidationException::withMessages([
                'subjects' => 'Each subject name must be unique for this standard.',
            ]);
        }

        $touched = [];
        foreach ($rows as $id => $row) {
            $subject = $existing->get((int) $id);
            $this->applySubjectChanges(
                $subject,
                $standard,
                trim($row['name']),
                (int) ($row['sort_order'] ?? $subject->sort_order),
                filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN)
            );
            $touched[] = $subject->id;
        }

        $this->forgetSubjectCaches($standard, $touched);

        return redirect()
            ->route('admin.standards.subjects.index', $standard)
            ->with('success', 'Subjects updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $standard = $subject->standard;
        $subjectId = $subject->id;
        $subject->delete();

        $this->forgetSubjectCaches($standard, [$subjectId]);

        return redirect()->route('admin.standards.subjects.index', $standard)->with('success', 'Subject deleted successfully.');
    }

    private function standards()
    {
        return Standard::query()
            ->orderedByNumber()
            ->orderBy('medium')
            ->orderBy('name')
            ->get();
    }

    private function assertUniqueName(Standard $standard, string $name, ?int $ignoreId = null): void
    {
        $exists = Subject::query()
            ->where('standard_id', $standard->id)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'This subject already exists for '.$standard->name.'.',
            ]);
        }
    }

    private function applySubjectChanges(Subject $subject, Standard $standard, string $name, int $sortOrder, bool $isActive): void
    {
        $oldKey = mb_strtolower(trim($subject->name));
        $newKey = mb_strtolower($name);

        $updates = [
            'name' => $name,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ];

        if ($oldKey !== $newKey) {
            $updates['slug'] = SlugHelper::unique(
                $name,
                fn ($slug) => Subject::query()
                    ->where('standard_id', $standard->id)
                    ->where('id', '!=', $subject->id)
                    ->where('slug', $slug)
                    ->exists()
            );

            Material::query()
                ->forMedium($standard->medium)
                ->tap(fn ($query) => Material::applyStandardFilter($query, $standard))
                ->whereRaw('LOWER(TRIM(subject)) = ?', [$oldKey])
                ->update(['subject' => $name]);
        }

        $subject->update($updates);
    }

    /**
     * @param  list<int>  $subjectIds
     */
    private function forgetSubjectCaches(Standard $standard, array $subjectIds = []): void
    {
        Material::forgetStandardSubjectListCache($standard);

        foreach ($subjectIds as $subjectId) {
            Material::forgetStudentSubjectCache((int) $subjectId);
        }
    }
}
