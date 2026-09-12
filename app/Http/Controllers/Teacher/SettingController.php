<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Support\TeacherOtpService;
use App\Models\Material;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\TeacherSubject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $teacher = auth()->user();

        return view('teacher.settings', [
            'teacher' => $teacher,
            'todayOtp' => TeacherOtpService::ensureForTeacher($teacher),
            'mediums' => Standard::MEDIUMS,
            'assignedGroups' => $this->assignedGroups($teacher->id),
            'formData' => $this->formData($teacher->id),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $teacher = auth()->user();

        $validated = $request->validate([
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard_id' => ['required', 'integer', 'exists:standards,id'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        $medium = Material::normalizeMedium($validated['medium']) ?: $validated['medium'];

        $standard = Standard::query()
            ->where('id', $validated['standard_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $subjectIds = collect($validated['subject_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allowedIds = Material::subjectsForStudent($standard, $medium)->pluck('id')->map(fn ($id) => (int) $id);
        $validIds = $subjectIds->intersect($allowedIds)->values();

        if ($subjectIds->diff($validIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'subject_ids' => 'Select subjects that belong to the chosen medium and standard.',
            ]);
        }

        DB::transaction(function () use ($teacher, $standard, $medium, $validIds) {
            $taken = TeacherSubject::query()
                ->with(['teacher:id,name', 'subject:id,name'])
                ->where('medium', $medium)
                ->whereIn('subject_id', $validIds)
                ->where('teacher_id', '!=', $teacher->id)
                ->lockForUpdate()
                ->get();

            if ($taken->isNotEmpty()) {
                $names = $taken->map(function (TeacherSubject $row) {
                    return ($row->subject?->name ?: 'Subject').' — '.$row->teacher?->name;
                })->implode(', ');

                throw ValidationException::withMessages([
                    'subject_ids' => 'Already assigned to another teacher: '.$names,
                ]);
            }

            TeacherSubject::query()
                ->where('teacher_id', $teacher->id)
                ->where('medium', $medium)
                ->where('standard_id', $standard->id)
                ->delete();

            foreach ($validIds as $subjectId) {
                TeacherSubject::query()->create([
                    'teacher_id' => $teacher->id,
                    'medium' => $medium,
                    'standard_id' => $standard->id,
                    'subject_id' => $subjectId,
                ]);
            }

            $teacher->update(['medium' => $medium]);
        });

        return redirect()
            ->route('teacher.settings.edit')
            ->with('success', 'Your medium, standard and subjects were saved.');
    }

    private function assignedGroups(int $teacherId)
    {
        return TeacherSubject::query()
            ->with(['standard:id,name,slug', 'subject:id,name'])
            ->where('teacher_id', $teacherId)
            ->get()
            ->groupBy(fn (TeacherSubject $row) => $row->medium.'|'.$row->standard_id)
            ->sortBy(function ($rows) {
                $first = $rows->first();

                return ($first?->medium ?? '').'|'.($first?->standard?->name ?? '');
            });
    }

    /**
     * @return array{mediums: array<string, string>, standards: list<array{id:int,name:string}>, subjects: array<string, list<array{id:int,name:string,mine:bool,taken_by:?string}>>}
     */
    private function formData(int $teacherId): array
    {
        $taken = TeacherSubject::query()
            ->with('teacher:id,name')
            ->get();

        $standards = Standard::query()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get();

        $subjects = [];
        foreach (array_keys(Standard::MEDIUMS) as $medium) {
            foreach ($standards as $standard) {
                $key = $medium.'-'.$standard->id;
                $subjects[$key] = Material::subjectsForStudent($standard, $medium)
                    ->map(function (Subject $subject) use ($taken, $teacherId, $medium) {
                        $row = $taken->first(function (TeacherSubject $item) use ($subject, $medium) {
                            return (int) $item->subject_id === (int) $subject->id
                                && Material::normalizeMedium($item->medium) === $medium;
                        });
                        $mine = $row && (int) $row->teacher_id === $teacherId;

                        return [
                            'id' => $subject->id,
                            'name' => $subject->name,
                            'mine' => (bool) $mine,
                            'taken_by' => ($row && ! $mine) ? ($row->teacher?->name ?: 'Another teacher') : null,
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        return [
            'mediums' => Standard::MEDIUMS,
            'standards' => $standards->map(fn (Standard $standard) => [
                'id' => $standard->id,
                'name' => $standard->name,
            ])->values()->all(),
            'subjects' => $subjects,
        ];
    }
}
