<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\Material;
use App\Models\Standard;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $standard = Standard::where('slug', $user->standard)->where('is_active', true)->first();
        $standardName = $standard?->name ?? $user->standardLabel();

        $subjects = Material::subjectsForStudent($standard, $user->medium);

        $examCount = Exam::query()
            ->published()
            ->where(function ($q) use ($user) {
                if (Exam::hasSelfExamColumns()) {
                    $q->where(function ($inner) use ($user) {
                        $inner->where('is_self_exam', true)->where('student_id', $user->id);
                    })->orWhere(function ($inner) use ($user) {
                        $inner->where('is_self_exam', false)->where('standard', $user->standard);
                    });
                } else {
                    $q->where('standard', $user->standard);
                }
            })
            ->count();
        $homeworkCount = Homework::published()->forStandard($user->standard)->count();
        $unreadNotifications = $user->unreadNotifications()
            ->where(function ($q) {
                $q->where('data->type', 'exam')->orWhere('data->type', 'homework');
            })
            ->count();

        return view('student.dashboard', [
            'user' => $user,
            'standard' => $standard,
            'standardName' => $standardName,
            'subjects' => $subjects,
            'examCount' => $examCount,
            'homeworkCount' => $homeworkCount,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }
}
