<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
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
    /** Every standard: up to 2 teachers may share the same subject. */
    private const SHARED_MAX_TEACHERS = 2;

    public function edit(): View
    {
        $teacher = auth()->user();
        $assignedGroups = $this->assignedGroups($teacher->id);
        $firstGroup = $assignedGroups->first();
        $first = $firstGroup?->first();

        $initialMedium = old('medium', $first?->medium ?: ($teacher->medium ?: ''));
        $initialMedium = Material::normalizeMedium($initialMedium) ?: $initialMedium;
        $initialStandardId = (string) old('standard_id', $first?->standard_id ?: '');
        $initialSubjectIds = old('subject_ids');
        if (! is_array($initialSubjectIds)) {
            $initialSubjectIds = $firstGroup
                ? $firstGroup->pluck('subject_id')->map(fn ($id) => (string) $id)->values()->all()
                : [];
        } else {
            $initialSubjectIds = array_map('strval', $initialSubjectIds);
        }

        return view('teacher.settings', [
            'teacher' => $teacher,
            'todayOtp' => TeacherOtpService::ensureForTeacher($teacher),
            'mediums' => Standard::MEDIUMS,
            'assignedGroups' => $assignedGroups,
            'formData' => $this->formData($teacher->id),
            'initialMedium' => $initialMedium,
            'initialStandardId' => $initialStandardId,
            'initialSubjectIds' => $initialSubjectIds,
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

        $grade = self::gradeNumber($standard);
        $maxTeachers = self::maxTeachersForGrade($grade);

        $allowedIds = Material::subjectsForStudent($standard, $medium)->pluck('id')->map(fn ($id) => (int) $id);
        $validIds = $subjectIds->intersect($allowedIds)->values();

        if ($subjectIds->diff($validIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'subject_ids' => 'Select subjects that belong to the chosen medium and standard.',
            ]);
        }

        DB::transaction(function () use ($teacher, $standard, $medium, $validIds, $maxTeachers, $grade) {
            $conflicts = [];

            foreach ($validIds as $subjectId) {
                $others = TeacherSubject::query()
                    ->with(['teacher:id,name', 'subject:id,name'])
                    ->where('medium', $medium)
                    ->where('subject_id', $subjectId)
                    ->where('teacher_id', '!=', $teacher->id)
                    ->lockForUpdate()
                    ->get();

                if ($others->count() >= $maxTeachers) {
                    $names = $others->map(fn (TeacherSubject $row) => $row->teacher?->name ?: 'Teacher')->implode(', ');
                    $subjectName = $others->first()?->subject?->name ?: 'Subject';
                    $conflicts[] = $subjectName.' (already '.$others->count().'/'.$maxTeachers.': '.$names.')';
                }
            }

            if ($conflicts !== []) {
                throw ValidationException::withMessages([
                    'subject_ids' => 'Each subject allows up to '.self::SHARED_MAX_TEACHERS.' teachers. '.implode(' · ', $conflicts),
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

        ActivityLogger::log(
            'teacher.settings.update',
            'Saved subjects for '.$medium.' / '.$standard->name.' ('.$validIds->count().' selected)',
            $standard,
            [
                'medium' => $medium,
                'standard_id' => $standard->id,
                'subject_ids' => $validIds->all(),
            ],
            $teacher
        );

        return redirect()
            ->route('teacher.settings.edit')
            ->with('success', 'Your medium, standard and subjects were saved.');
    }

    public function destroySubject(TeacherSubject $teacherSubject): RedirectResponse
    {
        abort_unless((int) $teacherSubject->teacher_id === (int) auth()->id(), 403);

        $name = $teacherSubject->subject?->name ?: 'Subject';
        $medium = $teacherSubject->medium;
        $standardId = $teacherSubject->standard_id;
        $subjectId = $teacherSubject->subject_id;
        $teacherSubject->delete();

        ActivityLogger::log(
            'teacher.settings.remove_subject',
            'Removed subject '.$name,
            null,
            [
                'medium' => $medium,
                'standard_id' => $standardId,
                'subject_id' => $subjectId,
                'subject_name' => $name,
            ]
        );

        return redirect()
            ->route('teacher.settings.edit')
            ->with('success', $name.' was removed from your assignment.');
    }

    public function destroyGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard_id' => ['required', 'integer', 'exists:standards,id'],
        ]);

        $teacher = auth()->user();
        $medium = Material::normalizeMedium($validated['medium']) ?: $validated['medium'];

        $deleted = TeacherSubject::query()
            ->where('teacher_id', $teacher->id)
            ->where('medium', $medium)
            ->where('standard_id', $validated['standard_id'])
            ->delete();

        ActivityLogger::log(
            'teacher.settings.clear_group',
            'Cleared subject group ('.$deleted.' removed)',
            null,
            [
                'medium' => $medium,
                'standard_id' => (int) $validated['standard_id'],
                'deleted' => $deleted,
            ],
            $teacher
        );

        return redirect()
            ->route('teacher.settings.edit')
            ->with('success', $deleted > 0
                ? 'Assignment group cleared.'
                : 'Nothing to remove for that group.');
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
     * @return array{mediums: array<string, string>, standards: list<array{id:int,name:string,grade:int}>, subjects: array<string, list<array{id:int,name:string,mine:bool,taken_by:?string,teacher_names:list<string>,slots_used:int,slots_max:int,locked:bool}>>}
     */
    private function formData(int $teacherId): array
    {
        $taken = TeacherSubject::query()
            ->with(['teacher:id,name', 'standard:id,name,slug'])
            ->get();

        $standards = Standard::query()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get();

        $subjects = [];
        foreach (array_keys(Standard::MEDIUMS) as $medium) {
            foreach ($standards as $standard) {
                $grade = self::gradeNumber($standard);
                $maxTeachers = self::maxTeachersForGrade($grade);
                $key = $medium.'-'.$standard->id;

                $subjects[$key] = Material::subjectsForStudent($standard, $medium)
                    ->map(function (Subject $subject) use ($taken, $teacherId, $medium, $maxTeachers) {
                        $rows = $taken->filter(function (TeacherSubject $item) use ($subject, $medium) {
                            return (int) $item->subject_id === (int) $subject->id
                                && Material::normalizeMedium($item->medium) === $medium;
                        })->values();

                        $mine = $rows->contains(fn (TeacherSubject $row) => (int) $row->teacher_id === $teacherId);
                        $others = $rows->filter(fn (TeacherSubject $row) => (int) $row->teacher_id !== $teacherId)->values();
                        $otherNames = $others->map(fn (TeacherSubject $row) => $row->teacher?->name ?: 'Teacher')->values()->all();
                        $slotsUsed = $rows->count();
                        $locked = ! $mine && $others->count() >= $maxTeachers;

                        return [
                            'id' => $subject->id,
                            'name' => $subject->name,
                            'mine' => (bool) $mine,
                            'taken_by' => $locked ? implode(', ', $otherNames) : null,
                            'teacher_names' => $otherNames,
                            'slots_used' => $slotsUsed,
                            'slots_max' => $maxTeachers,
                            'locked' => $locked,
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
                'grade' => self::gradeNumber($standard),
            ])->values()->all(),
            'subjects' => $subjects,
        ];
    }

    private static function gradeNumber(Standard $standard): int
    {
        return (int) preg_replace('/\D+/', '', (string) ($standard->slug ?: $standard->name));
    }

    private static function maxTeachersForGrade(int $grade): int
    {
        return self::SHARED_MAX_TEACHERS;
    }
}
