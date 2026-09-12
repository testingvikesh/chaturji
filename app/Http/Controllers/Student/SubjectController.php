<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Standard;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);
        $medium = $user->medium;

        $subjects = Material::subjectsForStudent($standard, $medium);

        return view('student.subjects.index', [
            'user' => $user,
            'standard' => $standard,
            'subjects' => $subjects,
            'medium' => $medium,
        ]);
    }

    public function show(Subject $subject): View
    {
        $user = auth()->user();
        $standard = $this->resolveStandard($user->standard);

        abort_unless(
            $standard && $subject->standard_id === $standard->id && $subject->is_active,
            404
        );

        $subject->loadMissing('standard');
        $materials = Material::forStudentSubject($subject, $user->medium);

        return view('student.subjects.show', [
            'user' => $user,
            'standard' => $standard,
            'subject' => $subject,
            'materials' => $materials,
            'medium' => $user->medium,
        ]);
    }

    private function resolveStandard(?string $slug): ?Standard
    {
        if (! $slug) {
            return null;
        }

        return Standard::where('slug', $slug)->where('is_active', true)->first();
    }
}
