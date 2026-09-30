<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MentorStudent;
use App\Models\Standard;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MentorController extends Controller
{
    public function index(Request $request): View
    {
        $principal = auth()->user();
        $allotted = $this->allottedStandards($principal);
        $allottedIds = $allotted->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allottedSlugs = $allotted->pluck('slug')->filter()->unique()->values()->all();

        $search = $request->string('search')->trim()->toString();
        $medium = Material::normalizeMedium($request->string('medium')->trim()->toString()) ?: '';
        $standardId = $request->integer('standard_id') ?: null;
        if ($standardId && ! in_array($standardId, $allottedIds, true)) {
            $standardId = null;
        }

        if ($allottedIds === []) {
            return view('principal.mentors.index', [
                'teachers' => User::teachers()->whereRaw('1 = 0')->paginate(1),
                'standards' => $allotted,
                'filters' => [
                    'search' => $search,
                    'medium' => $medium,
                    'standard_id' => '',
                ],
                'summary' => [
                    'teachers' => 0,
                    'mentors' => 0,
                    'students' => 0,
                ],
                'hasAllotments' => false,
            ]);
        }

        $query = User::teachers()
            ->whereHas('teacherSubjects', function ($q) use ($allottedIds, $medium, $standardId) {
                $q->whereIn('teacher_subjects.standard_id', $allottedIds);
                if ($medium !== '') {
                    $q->whereRaw('LOWER(TRIM(teacher_subjects.medium)) = ?', [$medium]);
                }
                if ($standardId) {
                    $q->where('teacher_subjects.standard_id', $standardId);
                }
            })
            ->withCount([
                'mentorAssignments as mentee_count' => function ($q) use ($allottedSlugs) {
                    $q->whereIn('standard', $allottedSlugs);
                },
            ])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $teachers = $query->paginate(100)->withQueryString();

        $mentorStudentQuery = MentorStudent::query()
            ->whereIn('standard', $allottedSlugs)
            ->whereIn('teacher_id', TeacherSubject::query()
                ->whereIn('standard_id', $allottedIds)
                ->select('teacher_id'));

        return view('principal.mentors.index', [
            'teachers' => $teachers,
            'standards' => $allotted,
            'filters' => [
                'search' => $search,
                'medium' => $medium,
                'standard_id' => $standardId ? (string) $standardId : '',
            ],
            'summary' => [
                'teachers' => $teachers->total(),
                'mentors' => (int) (clone $mentorStudentQuery)->selectRaw('COUNT(DISTINCT teacher_id) as aggregate')->value('aggregate'),
                'students' => (clone $mentorStudentQuery)->count(),
            ],
            'hasAllotments' => true,
        ]);
    }

    public function show(Request $request, User $teacher): View
    {
        $principal = auth()->user();
        $this->assertAllottedTeacher($principal, $teacher);

        $allotted = $this->allottedStandards($principal);
        $allottedSlugs = $allotted->pluck('slug')->filter()->unique()->values()->all();

        $medium = Material::normalizeMedium($request->string('medium')->trim()->toString()) ?: '';
        $standardSlug = $request->string('standard')->trim()->toString();
        if ($standardSlug !== '' && ! in_array($standardSlug, $allottedSlugs, true)) {
            $standardSlug = '';
        }
        $search = $request->string('search')->trim()->toString();
        $onlyMine = $request->boolean('only_mine');

        $studentsQuery = User::students()
            ->approved()
            ->whereIn('standard', $allottedSlugs ?: ['__none__'])
            ->orderBy('name');

        if ($standardSlug !== '') {
            $studentsQuery->where('standard', $standardSlug);
        }
        if ($medium !== '') {
            $studentsQuery->whereRaw('LOWER(TRIM(medium)) = ?', [$medium]);
        }
        if ($search !== '') {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery->get(['id', 'name', 'mobile', 'email', 'medium', 'standard']);

        $assignedIds = MentorStudent::query()
            ->where('teacher_id', $teacher->id)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $otherMentors = MentorStudent::query()
            ->with('teacher:id,name')
            ->whereIn('student_id', $students->pluck('id')->all() ?: [0])
            ->where('teacher_id', '!=', $teacher->id)
            ->get()
            ->keyBy('student_id');

        if ($onlyMine) {
            $students = $students->filter(fn (User $s) => in_array((int) $s->id, $assignedIds, true))->values();
        }

        $standardNames = $allotted->pluck('name', 'slug');

        return view('principal.mentors.show', [
            'teacher' => $teacher,
            'students' => $students,
            'standards' => $allotted,
            'standardNames' => $standardNames,
            'assignedIds' => $assignedIds,
            'otherMentors' => $otherMentors,
            'filters' => [
                'search' => $search,
                'medium' => $medium,
                'standard' => $standardSlug,
                'only_mine' => $onlyMine,
            ],
            'menteeCount' => count($assignedIds),
        ]);
    }

    public function update(Request $request, User $teacher): RedirectResponse
    {
        $principal = auth()->user();
        $this->assertAllottedTeacher($principal, $teacher);

        $allotted = $this->allottedStandards($principal);
        $allottedSlugs = $allotted->pluck('slug')->filter()->unique()->values()->all();
        abort_if($allottedSlugs === [], 403, 'No standard allotted.');

        $medium = Material::normalizeMedium($request->string('medium')->trim()->toString()) ?: '';
        $standardSlug = $request->string('standard')->trim()->toString();
        if ($standardSlug !== '' && ! in_array($standardSlug, $allottedSlugs, true)) {
            $standardSlug = '';
        }

        $validated = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $selectedIds = collect($validated['student_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Eligible students in current filter (standard / medium) within allotted standards.
        $eligibleQuery = User::students()
            ->approved()
            ->whereIn('standard', $allottedSlugs);
        if ($standardSlug !== '') {
            $eligibleQuery->where('standard', $standardSlug);
        }
        if ($medium !== '') {
            $eligibleQuery->whereRaw('LOWER(TRIM(medium)) = ?', [$medium]);
        }
        $eligibleIds = $eligibleQuery->pluck('id')->map(fn ($id) => (int) $id)->all();

        $selectedInScope = $selectedIds->intersect($eligibleIds)->values()->all();

        // Remove this teacher's mentees who are in the filtered list but unchecked.
        MentorStudent::query()
            ->where('teacher_id', $teacher->id)
            ->whereIn('student_id', $eligibleIds)
            ->whereNotIn('student_id', $selectedInScope ?: [0])
            ->delete();

        $added = 0;
        $moved = 0;

        foreach ($selectedInScope as $studentId) {
            $student = User::students()->whereKey($studentId)->first();
            if (! $student) {
                continue;
            }

            $existing = MentorStudent::query()->where('student_id', $studentId)->first();
            if ($existing && (int) $existing->teacher_id === (int) $teacher->id) {
                $existing->fill([
                    'principal_id' => $principal->id,
                    'medium' => $student->medium,
                    'standard' => $student->standard,
                ])->save();
                continue;
            }

            if ($existing) {
                $existing->fill([
                    'teacher_id' => $teacher->id,
                    'principal_id' => $principal->id,
                    'medium' => $student->medium,
                    'standard' => $student->standard,
                ])->save();
                $moved++;
                continue;
            }

            MentorStudent::query()->create([
                'teacher_id' => $teacher->id,
                'student_id' => $studentId,
                'principal_id' => $principal->id,
                'medium' => $student->medium,
                'standard' => $student->standard,
            ]);
            $added++;
        }

        $total = MentorStudent::query()->where('teacher_id', $teacher->id)->count();

        ActivityLogger::log(
            'principal.mentors.update',
            "Principal set mentor {$teacher->name}: {$total} student(s)",
            $principal,
            [
                'teacher_id' => $teacher->id,
                'added' => $added,
                'moved' => $moved,
                'total' => $total,
                'standard' => $standardSlug,
                'medium' => $medium,
            ]
        );

        $msg = "Mentor saved for {$teacher->name}. {$total} student(s) under this teacher.";
        if ($moved > 0) {
            $msg .= " {$moved} moved from another mentor.";
        }

        return redirect()
            ->route('principal.mentors.show', [
                'teacher' => $teacher,
                'medium' => $medium,
                'standard' => $standardSlug,
            ])
            ->with('success', $msg);
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

    private function assertAllottedTeacher(User $principal, User $teacher): void
    {
        abort_unless($teacher->role === 'teacher', 404);

        $allottedIds = $this->allottedStandards($principal)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        abort_unless($allottedIds !== [], 404);

        $ok = TeacherSubject::query()
            ->where('teacher_id', $teacher->id)
            ->whereIn('standard_id', $allottedIds)
            ->exists();

        abort_unless($ok, 404);
    }
}
