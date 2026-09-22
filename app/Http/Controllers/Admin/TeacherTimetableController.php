<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\SchoolPeriod;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\TeacherTimetable;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\TeacherTimetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherTimetableController extends Controller
{
    public function __construct(
        private readonly TeacherTimetableService $timetables
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $teachers = User::teachers()
            ->where('is_approved', true)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount(['teacherTimetables as timetable_slots' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->paginate(500)
            ->withQueryString();

        return view('admin.timetable.index', [
            'teachers' => $teachers,
            'filters' => ['search' => $search],
            'periodCount' => SchoolPeriod::query()->active()->count(),
        ]);
    }

    public function edit(User $teacher): View
    {
        abort_unless($teacher->role === 'teacher', 404);

        $slots = $this->timetables->weekForTeacher($teacher->id);
        $grid = [];
        foreach ($slots as $slot) {
            $grid[$slot->weekday][$slot->period_id] = $slot;
        }

        return view('admin.timetable.edit', [
            'teacher' => $teacher,
            'slots' => $slots,
            'grid' => $grid,
            'periods' => $this->timetables->activePeriods(),
            'weekdays' => TeacherTimetable::WEEKDAYS,
            'mediums' => Standard::MEDIUMS,
            'standards' => Standard::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'medium']),
            'subjects' => Subject::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'standard_id']),
        ]);
    }

    public function store(Request $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);

        $validated = $this->validateSlot($request);
        $medium = Material::normalizeMedium($validated['medium']) ?: $validated['medium'];

        $this->assertSubjectMatchesStandard((int) $validated['subject_id'], (int) $validated['standard_id']);
        $this->assertStandardMedium((int) $validated['standard_id'], $medium);

        $slot = TeacherTimetable::query()->updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'weekday' => (int) $validated['weekday'],
                'period_id' => (int) $validated['period_id'],
            ],
            [
                'medium' => $medium,
                'standard_id' => (int) $validated['standard_id'],
                'subject_id' => (int) $validated['subject_id'],
                'section' => filled($validated['section'] ?? null) ? trim((string) $validated['section']) : null,
                'is_active' => true,
            ]
        );

        $this->timetables->syncTeacherSubject($slot);

        ActivityLogger::log(
            'admin.timetable.save',
            'Timetable slot: '.$teacher->name.' · '.$slot->optionLabel(),
            $slot,
            ['teacher_id' => $teacher->id]
        );

        return back()->with('success', 'Timetable slot saved.');
    }

    public function destroy(User $teacher, TeacherTimetable $timetable): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher' && (int) $timetable->teacher_id === (int) $teacher->id, 404);

        $label = $timetable->optionLabel();
        $timetable->delete();

        ActivityLogger::log(
            'admin.timetable.delete',
            'Removed timetable slot: '.$teacher->name.' · '.$label,
            null,
            ['teacher_id' => $teacher->id]
        );

        return back()->with('success', 'Timetable slot removed.');
    }

    public function subjects(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'standard_id' => ['required', 'integer', 'exists:standards,id'],
        ]);

        $subjects = Subject::query()
            ->where('standard_id', (int) $validated['standard_id'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return response()->json($subjects);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSlot(Request $request): array
    {
        return $request->validate([
            'weekday' => ['required', 'integer', Rule::in(array_keys(TeacherTimetable::WEEKDAYS))],
            'period_id' => ['required', 'integer', 'exists:school_periods,id'],
            'medium' => ['required', 'string', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard_id' => ['required', 'integer', 'exists:standards,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'section' => ['nullable', 'string', 'max:32'],
        ]);
    }

    private function assertSubjectMatchesStandard(int $subjectId, int $standardId): void
    {
        $ok = Subject::query()
            ->where('id', $subjectId)
            ->where('standard_id', $standardId)
            ->exists();

        abort_unless($ok, 422, 'Subject does not belong to selected standard.');
    }

    private function assertStandardMedium(int $standardId, string $medium): void
    {
        $standard = Standard::query()->findOrFail($standardId);
        $stdMedium = Material::normalizeMedium($standard->medium) ?: $standard->medium;
        abort_unless($stdMedium === $medium, 422, 'Standard medium does not match selected medium.');
    }
}
